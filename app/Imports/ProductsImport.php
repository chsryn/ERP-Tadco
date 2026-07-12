<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductsImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $uom = trim($row[0] ?? '');
            $segment = trim($row[1] ?? '');
            $productName = trim($row[2] ?? '');
            $productCode = trim($row[3] ?? '');
            $uomSecondary = trim($row[4] ?? '');

            if ($productCode === '' || $productName === '') {
                continue;
            }

            $product = Product::updateOrCreate(
                [
                    'product_code' => $productCode,
                ],
                [
                    'product_name'  => $productName,
                    'uom'           => $uom,
                    'uom_secondary' => $uomSecondary,
                    'segment'       => $segment,
                    'is_active'     => true,
                ]
            );

            $prices = [
                [
                    'tier_code' => 'S1',
                    'price' => $this->toDecimal($row[5] ?? 0),
                    'discount_rate' => $this->toDecimal($row[6] ?? 0),
                ],
                [
                    'tier_code' => 'S2',
                    'price' => $this->toDecimal($row[7] ?? 0),
                    'discount_rate' => $this->toDecimal($row[8] ?? 0),
                ],
                [
                    'tier_code' => 'S3',
                    'price' => $this->toDecimal($row[9] ?? 0),
                    'discount_rate' => $this->toDecimal($row[10] ?? 0),
                ],
                [
                    'tier_code' => 'S4',
                    'price' => $this->toDecimal($row[11] ?? 0),
                    'discount_rate' => $this->toDecimal($row[12] ?? 0),
                ],
            ];

            foreach ($prices as $priceData) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'tier_code' => $priceData['tier_code'],
                    ],
                    [
                        'price' => $priceData['price'],
                        'discount_rate' => $priceData['discount_rate'],
                        'valid_from' => null,
                        'valid_until' => null,
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
