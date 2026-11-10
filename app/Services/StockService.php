<?php

namespace App\Services;

use App\Models\PurchaseReceipt;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public const SOURCE_PO_RECEIPT = 'po_receipt';

    public function processShipmentDeduction($deliveryOrder, $shipment, $shipmentDate, $userId)
    {
        foreach ($deliveryOrder->items as $item) {
            $stockBalance = StockBalance::query()
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (!$stockBalance) {
                throw ValidationException::withMessages(['delivery_order_id' => 'Stok produk ' . ($item->product->product_name ?? '-') . ' tidak ditemukan.']);
            }

            $qty = (float) $item->qty;
            if ($stockBalance->qty_reserved < $qty || $stockBalance->qty_available < $qty) {
                throw ValidationException::withMessages(['delivery_order_id' => 'Stok/Reserved tidak mencukupi untuk dikirim.']);
            }

            $stockBalance->qty_reserved -= $qty;
            $stockBalance->qty_available -= $qty;
            $stockBalance->save();

            StockMovement::query()->create([
                'product_id' => $item->product_id,
                'movement_date' => $shipmentDate,
                'movement_type' => 'out',
                'qty' => $qty,
                'source_type' => 'shipment',
                'source_id' => $shipment->id,
                'notes' => 'Pengiriman dari DO ' . $deliveryOrder->do_number,
                'created_by' => $userId,
            ]);
        }
    }

    public function reverseShipmentDeduction($deliveryOrder, $shipment, $userId)
    {
        foreach ($deliveryOrder->items as $item) {
            $stockBalance = StockBalance::query()
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($stockBalance) {
                $qty = (float) $item->qty;
                // Kembalikan stok fisik dan kembalikan status reserve (booking)
                $stockBalance->qty_reserved += $qty;
                $stockBalance->qty_available += $qty;
                $stockBalance->save();

                // Catat pergerakan pembalik (In) untuk audit
                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'movement_date' => now(),
                    'movement_type' => 'in',
                    'qty' => $qty,
                    'source_type' => 'shipment',
                    'source_id' => $shipment->id,
                    'notes' => 'Pembatalan Pengiriman DO ' . $deliveryOrder->do_number,
                    'created_by' => $userId,
                ]);
            }
        }
    }

    /**
     * Stock IN dari Purchase Receipt: qty_received masuk ke qty_available.
     *
     * Idempotency diambil dari stock_movements (source_type = po_receipt,
     * source_id = receipt.id), jadi tidak perlu kolom status tambahan.
     * Lock receipt men-serialisasi dua request yang memproses receipt sama.
     */
    public function processPurchaseReceipt(PurchaseReceipt $receipt, ?int $userId = null): void
    {
        DB::transaction(function () use ($receipt, $userId) {
            $locked = PurchaseReceipt::query()
                ->whereKey($receipt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== PurchaseReceipt::STATUS_POSTED) {
                throw ValidationException::withMessages([
                    'stock_in' => 'Stock IN hanya dapat diproses untuk receipt berstatus posted.',
                ]);
            }

            if ($this->receiptProcessed($locked)) {
                throw ValidationException::withMessages([
                    'stock_in' => 'Stock IN untuk receipt ini sudah diproses.',
                ]);
            }

            $items = $locked->items()->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'stock_in' => 'Receipt tidak memiliki item untuk diproses.',
                ]);
            }

            foreach ($items as $item) {
                $balance = StockBalance::query()
                    ->lockForUpdate()
                    ->firstOrCreate(
                        ['product_id' => $item->product_id],
                        ['qty_available' => 0, 'qty_reserved' => 0]
                    );

                $qty = (float) $item->qty_received;

                // hanya qty_available yang naik; qty_reserved tidak disentuh
                $balance->qty_available = round((float) $balance->qty_available + $qty, 2);
                $balance->save();

                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'movement_date' => $locked->receipt_date,
                    'movement_type' => 'in',
                    'qty' => $qty,
                    'source_type' => self::SOURCE_PO_RECEIPT,
                    'source_id' => $locked->id,
                    'notes' => 'Penerimaan barang PO ' . ($locked->purchaseOrder->po_number ?? '-')
                        . ' (Receipt ' . $locked->receipt_number . ')',
                    'created_by' => $userId,
                ]);
            }
        });
    }

    /**
     * Receipt sudah diproses bila movement Stock IN-nya ada.
     */
    public function receiptProcessed(PurchaseReceipt $receipt): bool
    {
        return StockMovement::query()
            ->where('source_type', self::SOURCE_PO_RECEIPT)
            ->where('source_id', $receipt->getKey())
            ->exists();
    }
}
