<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductsImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    /**
     * UOM yang valid, mengikuti enum kolom products.uom.
     */
    private const VALID_UOM = ['BOX', 'SACK'];

    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $uom = strtoupper(trim((string) ($row[0] ?? '')));
                $segment = trim((string) ($row[1] ?? ''));
                $productName = trim((string) ($row[2] ?? ''));
                $productCode = trim((string) ($row[3] ?? ''));

                if ($productCode === '' || $productName === '') {
                    continue;
                }

                if (!in_array($uom, self::VALID_UOM, true)) {
                    $excelRow = $this->startRow() + $index;

                    throw ValidationException::withMessages([
                        'file_master' => "UOM tidak valid pada baris {$excelRow} (produk {$productCode}): "
                            . "'{$uom}'. Nilai yang diizinkan hanya BOX atau SACK.",
                    ]);
                }

                // Kolom harga per strata pada LIST ITEM tidak dapat dipetakan ke
                // product_discounts karena workbook tidak memuat min_qty/max_qty.
                // Import ini hanya menyelaraskan data identitas produk.
                //
                // Fail-safe: produk baru tidak diberi harga tebakan dan dibuat
                // inactive agar tidak bisa dipakai transaksi sampai harganya
                // dikonfigurasi. Produk lama tidak diubah base_price/is_active-nya.
                $product = Product::query()->firstOrNew([
                    'product_code' => $productCode,
                ]);

                if (!$product->exists) {
                    $product->is_active = false;
                }

                $product->product_name = $productName;
                $product->uom = $uom;
                $product->segment = $segment;
                $product->save();
            }
        });
    }
}
