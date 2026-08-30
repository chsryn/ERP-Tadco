<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class StockImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    public function startRow(): int
    {
        return 7;
    }

    public function collection(Collection $rows)
    {
        // Tambahkan query() agar Intelephense mengerti
        $warehouse = Warehouse::query()->first();

        // Jika Gudang belum di-setting, hentikan proses import stok untuk menghindari error
        if (!$warehouse) {
            return;
        }

        foreach ($rows as $row) {
            $productCode = trim($row[1] ?? '');

            // Kolom STOK AWAL berada di index ke-2
            $stokAwal = $this->toDecimal($row[2] ?? 0);

            if ($productCode === '') {
                continue;
            }

            // Cari Product berdasarkan Product Code
            $product = Product::query()->where('product_code', $productCode)->first();

            // Hanya update stok jika produk tersebut terdaftar di database
            if ($product) {
                // Tambahkan query() agar Intelephense mengerti
                StockBalance::query()->updateOrCreate(
                    [
                        'product_id'   => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'qty_available' => $stokAwal,
                        // qty_reserved tidak dimasukkan ke sini agar tidak mereset jumlah barang yang sedang di-booking.
                    ]
                );
            }
        }
    }

    private function toDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}
