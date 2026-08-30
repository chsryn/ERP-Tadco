<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'product_name' => 'Terigu Gerbang Mas @25kg',
                'product_code' => 'TG-GM-25',
                'uom' => 'SACK',
                'net_weight' => '25KG',
                'base_price' => 261000,
                'segment' => 'Terigu',
                'discounts' => [
                    ['strata_level' => 'S1', 'min_qty' => 1, 'max_qty' => 5, 'discount_percentage' => 0.00],
                    ['strata_level' => 'S2', 'min_qty' => 6, 'max_qty' => 14, 'discount_percentage' => 0.50],
                    ['strata_level' => 'S3', 'min_qty' => 15, 'max_qty' => null, 'discount_percentage' => 1.50],
                ],
            ],
            [
                'product_name' => 'Terigu Gerbang @25kg',
                'product_code' => 'TG-G-25',
                'uom' => 'SACK',
                'net_weight' => '25KG',
                'base_price' => 240000,
                'segment' => 'Terigu',
                'discounts' => [
                    ['strata_level' => 'S1', 'min_qty' => 1, 'max_qty' => 5, 'discount_percentage' => 0.00],
                    ['strata_level' => 'S2', 'min_qty' => 6, 'max_qty' => 14, 'discount_percentage' => 0.50],
                    ['strata_level' => 'S3', 'min_qty' => 15, 'max_qty' => null, 'discount_percentage' => 1.55],
                ],
            ],
            [
                'product_name' => 'Terigu Gerbang @10kg/box (B)',
                'product_code' => 'TG-G-10B',
                'uom' => 'BOX',
                'net_weight' => '10KG',
                'base_price' => 111000,
                'segment' => 'Terigu',
                'discounts' => [
                    ['strata_level' => 'S1', 'min_qty' => 1, 'max_qty' => 10, 'discount_percentage' => 0.00],
                    ['strata_level' => 'S2', 'min_qty' => 11, 'max_qty' => 49, 'discount_percentage' => 1.08],
                    ['strata_level' => 'S3', 'min_qty' => 50, 'max_qty' => 99, 'discount_percentage' => 2.16],
                    ['strata_level' => 'S4', 'min_qty' => 100, 'max_qty' => 499, 'discount_percentage' => 3.24],
                    ['strata_level' => 'S5', 'min_qty' => 500, 'max_qty' => null, 'discount_percentage' => 4.32],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::updateOrCreate(
                ['product_code' => $productData['product_code']],
                [
                    'product_name' => $productData['product_name'],
                    'base_price' => $productData['base_price'],
                    'uom' => strtoupper($productData['uom']),
                    'net_weight' => $productData['net_weight'] ?? null,
                    'segment' => $productData['segment'] ?? 'Terigu',
                    'is_active' => true,
                ]
            );

            $product->discounts()->delete();

            foreach ($productData['discounts'] as $discountData) {
                ProductDiscount::create([
                    'product_id' => $product->id,
                    'strata_level' => $discountData['strata_level'],
                    'min_qty' => $discountData['min_qty'],
                    'max_qty' => $discountData['max_qty'],
                    'discount_percentage' => $discountData['discount_percentage'],
                ]);
            }
        }
    }
}
