<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_code' => 'P-CTRL-01',
            'product_name' => 'Produk Controller',
            'base_price' => 1000,
            'uom' => 'SACK',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_index_search_does_not_leak_soft_deleted_products(): void
    {
        $user = $this->user();

        Product::create([
            'product_code' => 'P-ACTIVE',
            'product_name' => 'Produk Aktif',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
        ]);

        $deleted = Product::create([
            'product_code' => 'P-DELETED',
            'product_name' => 'Produk Dihapus',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
        ]);
        $deleted->delete();

        $this->actingAs($user)
            ->get(route('products.index', ['search' => 'Produk Dihapus']))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->total() === 0);
    }

    public function test_index_search_returns_matching_active_products(): void
    {
        $user = $this->user();

        Product::create([
            'product_code' => 'P-ACTIVE',
            'product_name' => 'Produk Aktif',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('products.index', ['search' => 'Produk Aktif']))
            ->assertOk()
            ->assertViewHas('products', function ($products) {
                return $products->total() === 1
                    && $products->first()->product_code === 'P-ACTIVE';
            });
    }

    public function test_store_rejects_overlapping_discount_ranges(): void
    {
        $this->actingAs($this->user())
            ->post(route('products.store'), $this->payload([
                'discounts' => [
                    'S1' => ['min_qty' => 1, 'max_qty' => 10, 'discount_percentage' => 0],
                    'S2' => ['min_qty' => 5, 'max_qty' => 15, 'discount_percentage' => 2],
                ],
            ]))
            ->assertSessionHasErrors('discounts');

        $this->assertDatabaseMissing('products', ['product_code' => 'P-CTRL-01']);
    }

    public function test_store_rejects_max_qty_smaller_than_min_qty(): void
    {
        $this->actingAs($this->user())
            ->post(route('products.store'), $this->payload([
                'discounts' => [
                    'S1' => ['min_qty' => 10, 'max_qty' => 5, 'discount_percentage' => 0],
                ],
            ]))
            ->assertSessionHasErrors('discounts');

        $this->assertDatabaseMissing('products', ['product_code' => 'P-CTRL-01']);
    }

    public function test_store_rejects_fractional_discount_qty(): void
    {
        $this->actingAs($this->user())
            ->post(route('products.store'), $this->payload([
                'discounts' => [
                    'S1' => ['min_qty' => 1.5, 'max_qty' => 5, 'discount_percentage' => 0],
                ],
            ]))
            ->assertSessionHasErrors('discounts.S1.min_qty');

        $this->assertDatabaseMissing('products', ['product_code' => 'P-CTRL-01']);
    }

    public function test_store_only_persists_configured_discount_tiers(): void
    {
        $this->actingAs($this->user())
            ->post(route('products.store'), $this->payload([
                'discounts' => [
                    'S1' => ['min_qty' => 1, 'max_qty' => 5, 'discount_percentage' => 0],
                    'S2' => ['min_qty' => 6, 'max_qty' => 14, 'discount_percentage' => 2],
                    'S3' => ['min_qty' => '', 'max_qty' => '', 'discount_percentage' => 0],
                ],
            ]))
            ->assertRedirect(route('products.index'));

        $product = Product::where('product_code', 'P-CTRL-01')->firstOrFail();
        $this->assertSame(2, $product->discounts()->count());
        $this->assertSame(['S1', 'S2'], $product->discounts()->orderBy('strata_level')->pluck('strata_level')->all());
    }

    public function test_update_without_discounts_preserves_existing_discounts(): void
    {
        $product = Product::create([
            'product_code' => 'P-CTRL-02',
            'product_name' => 'Produk Lama',
            'base_price' => 1000,
            'uom' => 'SACK',
            'is_active' => true,
        ]);

        ProductDiscount::create([
            'product_id' => $product->id,
            'strata_level' => 'S1',
            'min_qty' => 1,
            'max_qty' => 5,
            'discount_percentage' => 0,
        ]);
        ProductDiscount::create([
            'product_id' => $product->id,
            'strata_level' => 'S2',
            'min_qty' => 6,
            'max_qty' => 14,
            'discount_percentage' => 2,
        ]);

        $this->actingAs($this->user())
            ->put(route('products.update', $product), $this->payload([
                'product_code' => 'P-CTRL-02',
                'product_name' => 'Produk Lama Revisi',
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertSame(2, $product->fresh()->discounts()->count());
        $this->assertDatabaseHas('product_discounts', [
            'product_id' => $product->id,
            'strata_level' => 'S2',
            'min_qty' => 6,
            'max_qty' => 14,
            'discount_percentage' => 2,
        ]);
    }

    public function test_update_rejects_duplicate_existing_strata_rows(): void
    {
        $product = Product::create([
            'product_code' => 'P-CTRL-03',
            'product_name' => 'Produk Duplikat',
            'base_price' => 1000,
            'uom' => 'SACK',
            'is_active' => true,
        ]);

        ProductDiscount::create([
            'product_id' => $product->id,
            'strata_level' => 'S1',
            'min_qty' => 1,
            'max_qty' => 5,
            'discount_percentage' => 0,
        ]);
        ProductDiscount::create([
            'product_id' => $product->id,
            'strata_level' => 'S1',
            'min_qty' => 1,
            'max_qty' => 5,
            'discount_percentage' => 1,
        ]);

        $this->actingAs($this->user())
            ->put(route('products.update', $product), $this->payload([
                'product_code' => 'P-CTRL-03',
                'product_name' => 'Produk Duplikat Revisi',
                'discounts' => [
                    'S1' => ['min_qty' => 1, 'max_qty' => 5, 'discount_percentage' => 0],
                ],
            ]))
            ->assertSessionHasErrors('discounts');

        // Transaksi harus rollback: nama produk tidak berubah.
        $this->assertSame('Produk Duplikat', $product->fresh()->product_name);
        $this->assertSame(2, $product->fresh()->discounts()->where('strata_level', 'S1')->count());
    }
}
