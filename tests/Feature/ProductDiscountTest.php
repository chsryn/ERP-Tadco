<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $code = 'PROD-DISC'): Product
    {
        return Product::create([
            'product_code' => $code,
            'product_name' => 'Produk Diskon',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
        ]);
    }

    private function addDiscount(Product $product, string $tier, int $min, ?int $max, float $percentage): ProductDiscount
    {
        return ProductDiscount::create([
            'product_id' => $product->id,
            'strata_level' => $tier,
            'min_qty' => $min,
            'max_qty' => $max,
            'discount_percentage' => $percentage,
        ]);
    }

    public function test_discount_for_qty_matches_min_max_and_unbounded_ranges(): void
    {
        $product = $this->makeProduct();
        $this->addDiscount($product, 'S1', 1, 5, 0);
        $this->addDiscount($product, 'S2', 6, 14, 2);
        $this->addDiscount($product, 'S3', 15, null, 3);

        $product->load('discounts');

        $this->assertSame('S1', $product->discountForQty(1)?->strata_level);
        $this->assertSame('S1', $product->discountForQty(5)?->strata_level);
        $this->assertSame('S2', $product->discountForQty(6)?->strata_level);
        $this->assertSame('S2', $product->discountForQty(14)?->strata_level);
        $this->assertSame('S3', $product->discountForQty(15)?->strata_level);
        $this->assertSame('S3', $product->discountForQty(100)?->strata_level);
    }

    public function test_discount_for_qty_returns_null_outside_all_ranges(): void
    {
        $product = $this->makeProduct();
        $this->addDiscount($product, 'S1', 5, 10, 1);

        $product->load('discounts');

        $this->assertNull($product->discountForQty(4));
        $this->assertNull($product->discountForQty(11));
    }

    public function test_discount_for_qty_returns_null_when_product_has_no_discounts(): void
    {
        $product = $this->makeProduct();

        $this->assertNull($product->discountForQty(10));
    }

    public function test_discount_for_qty_rejects_overlapping_ranges(): void
    {
        $product = $this->makeProduct();
        $this->addDiscount($product, 'S1', 1, 10, 0);
        $this->addDiscount($product, 'S2', 5, 15, 2);

        $product->load('discounts');

        $this->expectException(ValidationException::class);

        $product->discountForQty(7);
    }

    public function test_discount_for_qty_rejects_duplicate_strata_rows(): void
    {
        $product = $this->makeProduct();
        $this->addDiscount($product, 'S1', 1, 5, 0);
        $this->addDiscount($product, 'S1', 1, 5, 1);

        $product->load('discounts');

        $this->expectException(ValidationException::class);

        $product->discountForQty(3);
    }

    public function test_discount_for_qty_is_deterministic_regardless_of_insert_order(): void
    {
        $product = $this->makeProduct();
        $this->addDiscount($product, 'S3', 15, null, 3);
        $this->addDiscount($product, 'S1', 1, 5, 0);
        $this->addDiscount($product, 'S2', 6, 14, 2);

        $product->load('discounts');

        $this->assertSame('S1', $product->discountForQty(3)?->strata_level);
        $this->assertSame('S2', $product->discountForQty(10)?->strata_level);
        $this->assertSame('S3', $product->discountForQty(20)?->strata_level);
    }
}
