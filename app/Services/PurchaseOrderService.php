<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public const SEQUENCE_TYPE = 'purchase_order';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const PPN_RATE_DEFAULT = 11.00;

    /**
     * Buat PO draft beserta item dan snapshot produknya dalam satu transaksi.
     */
    public function generateDraft(array $data, ?int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $supplier = $this->activeSupplier((int) $data['supplier_id']);
            $period = $data['period'];

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->nextNumber($period),
                'period' => $period,
                'supplier_id' => $supplier->id,
                'po_date' => $data['po_date'],
                'status' => self::STATUS_DRAFT,
                'ppn_rate' => $data['ppn_rate'] ?? self::PPN_RATE_DEFAULT,
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->replaceItems($purchaseOrder, $data['items']);

            return $this->recalcTotals($purchaseOrder);
        });
    }

    /**
     * Ubah PO draft. Item lama diganti seluruhnya agar snapshot selalu konsisten.
     */
    public function updateDraft(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $data) {
            $locked = $this->lockOrder($purchaseOrder);

            if ($locked->status !== self::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya Purchase Order berstatus draft yang dapat diubah.',
                ]);
            }

            $supplier = $this->activeSupplier((int) $data['supplier_id']);

            $attributes = [
                'supplier_id' => $supplier->id,
                'po_date' => $data['po_date'],
                'ppn_rate' => $data['ppn_rate'] ?? $locked->ppn_rate,
                'notes' => $data['notes'] ?? null,
            ];

            // short: nomor PO mengikuti period; nomor lama dibiarkan kosong (gap) bila period diubah
            if ($data['period'] !== $locked->period) {
                $attributes['po_number'] = $this->nextNumber($data['period']);
                $attributes['period'] = $data['period'];
            }

            $locked->update($attributes);

            $this->replaceItems($locked, $data['items']);

            return $this->recalcTotals($locked);
        });
    }

    public function confirm(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder) {
            $locked = $this->lockOrder($purchaseOrder);

            if ($locked->status !== self::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya Purchase Order berstatus draft yang dapat dikonfirmasi.',
                ]);
            }

            if (! Supplier::query()->whereKey($locked->supplier_id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier Purchase Order sudah nonaktif.',
                ]);
            }

            $locked->load('items');

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Purchase Order minimal memiliki satu item.',
                ]);
            }

            foreach ($locked->items as $index => $item) {
                if ((float) $item->qty_plan <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.qty_plan" => 'Qty plan harus lebih dari 0.',
                    ]);
                }

                if (abs($this->weekTotal($item) - (float) $item->qty_plan) > 0.005) {
                    throw ValidationException::withMessages([
                        'items' => 'Total alokasi mingguan (W1-W4) harus sama dengan Qty Plan.',
                    ]);
                }

                if ((float) $item->price <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.price" => 'Harga satuan harus lebih dari 0.',
                    ]);
                }

                if ((float) $item->special_discount > (float) $item->price) {
                    throw ValidationException::withMessages([
                        "items.{$index}.special_discount" => 'Special discount tidak boleh melebihi harga satuan.',
                    ]);
                }
            }

            $this->recalcTotals($locked);

            $locked->update(['status' => self::STATUS_CONFIRMED]);

            return $locked->refresh();
        });
    }

    /**
     * draft & confirmed boleh dibatalkan; partial/received disiapkan untuk fase receipt.
     */
    public function cancel(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder) {
            $locked = $this->lockOrder($purchaseOrder);

            if (! in_array($locked->status, [self::STATUS_DRAFT, self::STATUS_CONFIRMED], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Purchase Order dengan status ' . $locked->status . ' tidak dapat dibatalkan.',
                ]);
            }

            $locked->update(['status' => self::STATUS_CANCELLED]);

            return $locked->refresh();
        });
    }

    /**
     * Hitung ulang subtotal, total discount, PPN, dan grand total dari item.
     * Uang dihitung dalam satuan sen (integer) agar tidak ada error pembulatan float.
     */
    public function recalcTotals(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $subtotalCents = 0;
        $discountCents = 0;

        foreach ($purchaseOrder->items()->get() as $item) {
            $subtotalCents += $this->cents($item->total_price);
            $discountCents += (int) round((float) $item->qty_plan * $this->cents($item->special_discount));
        }

        $ppnCents = (int) round($subtotalCents * (float) $purchaseOrder->ppn_rate / 100);

        $purchaseOrder->update([
            'subtotal' => $subtotalCents / 100,
            'special_discount_total' => $discountCents / 100,
            'ppn_amount' => $ppnCents / 100,
            'grand_total' => ($subtotalCents + $ppnCents) / 100,
        ]);

        return $purchaseOrder->refresh();
    }

    /**
     * Nomor dokumen concurrency-safe: insertOrIgnore menjamin baris sequence ada,
     * lockForUpdate men-serialisasi increment, unique(document_type, period) jadi pengaman terakhir.
     * Wajib dipanggil di dalam DB transaction.
     */
    public function nextNumber(string $period): string
    {
        $now = now();

        DB::table('document_sequences')->insertOrIgnore([
            'document_type' => self::SEQUENCE_TYPE,
            'period' => $period,
            'last_number' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sequence = DB::table('document_sequences')
            ->where('document_type', self::SEQUENCE_TYPE)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        $number = (int) $sequence->last_number + 1;

        DB::table('document_sequences')
            ->where('id', $sequence->id)
            ->update([
                'last_number' => $number,
                'updated_at' => $now,
            ]);

        [$year, $month] = explode('-', $period);

        return sprintf('%03d/%s/%s', $number, $month, $year);
    }

    private function replaceItems(PurchaseOrder $purchaseOrder, array $rows): void
    {
        $prepared = $this->prepareItems($rows);

        $purchaseOrder->items()->delete();

        foreach ($prepared as $item) {
            $purchaseOrder->items()->create($item);
        }
    }

    /**
     * Validasi item + bangun snapshot produk (dynamics_code, weight_kg, price).
     */
    private function prepareItems(array $rows): array
    {
        $prepared = [];

        foreach (array_values($rows) as $index => $row) {
            $product = Product::query()
                ->whereKey($row['product_id'] ?? null)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Produk tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            if ($product->purchase_price === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => "Produk {$product->product_name} belum memiliki harga pembelian. Lengkapi Data Purchasing terlebih dahulu.",
                ]);
            }

            if ($product->weight_kg === null || (float) $product->weight_kg <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => "Produk {$product->product_name} belum memiliki berat (KG). Lengkapi Data Purchasing terlebih dahulu.",
                ]);
            }

            $qty = round((float) ($row['qty_plan'] ?? 0), 2);

            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.qty_plan" => 'Qty plan harus lebih dari 0.',
                ]);
            }

            $price = ($row['price'] ?? null) === null || $row['price'] === ''
                ? (float) $product->purchase_price
                : round((float) $row['price'], 2);

            $discount = round((float) ($row['special_discount'] ?? 0), 2);

            if ($discount > $price) {
                throw ValidationException::withMessages([
                    "items.{$index}.special_discount" => 'Special discount tidak boleh melebihi harga satuan.',
                ]);
            }

            $priceNett = ($this->cents($price) - $this->cents($discount)) / 100;
            $weight = round((float) $product->weight_kg, 2);
            $weeks = $this->weeklyAllocation($qty, $row);

            $prepared[] = [
                'product_id' => $product->id,
                'dynamics_code' => $product->dynamics_code,
                'weight_kg' => $weight,
                'qty_plan' => $qty,
                'qty_ton' => round($qty * $weight / 1000, 4),
                'week_1' => $weeks[0],
                'week_2' => $weeks[1],
                'week_3' => $weeks[2],
                'week_4' => $weeks[3],
                'price' => $price,
                'special_discount' => $discount,
                'price_nett' => $priceNett,
                'total_price' => round($qty * $this->cents($priceNett) / 100, 2),
                'qty_received' => 0,
            ];
        }

        if ($prepared === []) {
            throw ValidationException::withMessages([
                'items' => 'Purchase Order minimal memiliki satu item.',
            ]);
        }

        return $prepared;
    }

    /**
     * Alokasi mingguan: default floor(qty/4) untuk W1-W3 dan sisanya di W4.
     * Bila user mengisi sebagian/semua, totalnya wajib sama dengan qty_plan.
     */
    private function weeklyAllocation(float $qty, array $row): array
    {
        $weeks = [];
        $hasInput = false;

        foreach (['week_1', 'week_2', 'week_3', 'week_4'] as $key) {
            $value = $row[$key] ?? null;

            if ($value === null || $value === '') {
                $weeks[] = 0.0;
                continue;
            }

            $hasInput = true;
            $weeks[] = round((float) $value, 2);
        }

        if (! $hasInput) {
            $quarter = floor($qty / 4);

            return [$quarter, $quarter, $quarter, round($qty - ($quarter * 3), 2)];
        }

        if (abs(array_sum($weeks) - $qty) > 0.005) {
            throw ValidationException::withMessages([
                'items' => 'Total alokasi mingguan (W1-W4) harus sama dengan Qty Plan.',
            ]);
        }

        return $weeks;
    }

    private function weekTotal($item): float
    {
        return (float) $item->week_1
            + (float) $item->week_2
            + (float) $item->week_3
            + (float) $item->week_4;
    }

    private function activeSupplier(int $supplierId): Supplier
    {
        $supplier = Supplier::query()
            ->whereKey($supplierId)
            ->where('is_active', true)
            ->first();

        if (! $supplier) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier tidak tersedia atau sedang nonaktif.',
            ]);
        }

        return $supplier;
    }

    private function lockOrder(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->whereKey($purchaseOrder->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function cents(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
