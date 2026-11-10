<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase Receipt = pencatatan penerimaan barang + tracking qty_received.
 * TIDAK menyentuh stock_balances / stock_movements (Stock IN = SCM-07).
 */
class PurchaseReceiptService
{
    public const SEQUENCE_TYPE = 'purchase_receipt';

    public function __construct(
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Buat receipt final beserta item dan update qty_received dalam satu transaksi.
     *
     * $data['items'] ber-key purchase_order_item_id => qty diterima sekarang.
     * Qty kosong/0 diabaikan; minimal satu item harus > 0.
     */
    public function createReceipt(PurchaseOrder $purchaseOrder, array $data, ?int $userId): PurchaseReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $data, $userId) {
            $locked = PurchaseOrder::query()
                ->whereKey($purchaseOrder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, [PurchaseOrderService::STATUS_CONFIRMED, PurchaseOrderService::STATUS_PARTIAL], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Purchase Receipt hanya dapat dibuat untuk PO berstatus confirmed atau partial.',
                ]);
            }

            $poItems = $locked->items()->lockForUpdate()->get()->keyBy('id');

            $rows = $this->validateReceipt($poItems->all(), $data['items'] ?? []);

            $receipt = PurchaseReceipt::create([
                'receipt_number' => $this->generateReceiptNumber($data['receipt_date']),
                'purchase_order_id' => $locked->id,
                'receipt_date' => $data['receipt_date'],
                'status' => PurchaseReceipt::STATUS_POSTED,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($rows as $row) {
                /** @var \App\Models\PurchaseOrderItem $poItem */
                $poItem = $row['item'];

                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'qty_received' => $row['qty'],
                    // snapshot berat mengikuti PO item, bukan berat produk terkini
                    'weight_kg' => $poItem->weight_kg,
                    'qty_ton' => round($row['qty'] * (float) $poItem->weight_kg / 1000, 4),
                ]);

                $poItem->update([
                    'qty_received' => round((float) $poItem->qty_received + $row['qty'], 2),
                ]);
            }

            $this->updatePurchaseOrderStatus($locked);

            return $receipt->load(['purchaseOrder.supplier', 'items.product', 'creator']);
        });
    }

    /**
     * Nomor receipt memakai sequence yang sama dengan PO (document_sequences),
     * periode diambil dari receipt_date: 001/11/2026. Wajib di dalam DB transaction.
     */
    public function generateReceiptNumber(string $receiptDate): string
    {
        return $this->purchaseOrderService->nextNumber(
            substr($receiptDate, 0, 7),
            self::SEQUENCE_TYPE
        );
    }

    /**
     * Validasi qty per item PO: 0 < qty <= (qty_plan - qty_received).
     *
     * @param  array<int, \App\Models\PurchaseOrderItem>  $poItems
     * @return array<int, array{item: \App\Models\PurchaseOrderItem, qty: float}>
     */
    private function validateReceipt(array $poItems, array $input): array
    {
        $rows = [];

        foreach ($input as $poItemId => $qty) {
            $qty = $qty === null || $qty === '' ? 0.0 : round((float) $qty, 2);

            if ($qty <= 0) {
                continue;
            }

            $item = $poItems[(int) $poItemId] ?? null;

            if (! $item) {
                throw ValidationException::withMessages([
                    "items.{$poItemId}" => 'Item PO tidak ditemukan pada Purchase Order ini.',
                ]);
            }

            $remaining = round((float) $item->qty_plan - (float) $item->qty_received, 2);

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    "items.{$poItemId}" => 'Qty diterima melebihi sisa PO (' . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') . ').',
                ]);
            }

            $rows[] = ['item' => $item, 'qty' => $qty];
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item harus memiliki qty diterima lebih dari 0.',
            ]);
        }

        return $rows;
    }

    /**
     * Semua item terpenuhi → received; sisanya → partial.
     */
    private function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $masihKurang = $purchaseOrder->items()
            ->whereColumn('qty_received', '<', 'qty_plan')
            ->exists();

        $purchaseOrder->update([
            'status' => $masihKurang
                ? PurchaseOrderService::STATUS_PARTIAL
                : PurchaseOrderService::STATUS_RECEIVED,
        ]);
    }
}
