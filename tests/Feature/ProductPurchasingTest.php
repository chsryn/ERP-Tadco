<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductPurchasingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_code' => 'TG-PUCH-01',
            'product_name' => 'Terigu Uji Purchasing',
            'base_price' => 261000,
            'uom' => 'SACK',
            'net_weight' => '25KG',
            'segment' => 'Terigu',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_purchasing_columns_exist_on_products_table(): void
    {
        $this->assertTrue(Schema::hasColumns('products', [
            'dynamics_code',
            'purchase_price',
            'weight_kg',
            'target_stock',
        ]));
    }

    public function test_product_can_be_created_with_purchasing_data(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload([
                'dynamics_code' => 'FG-GBBP-0003',
                'purchase_price' => 125000,
                'weight_kg' => 25,
                'target_stock' => 500,
            ]))
            ->assertRedirect(route('products.index'));

        $product = Product::where('product_code', 'TG-PUCH-01')->firstOrFail();

        $this->assertSame('FG-GBBP-0003', $product->dynamics_code);
        $this->assertSame(125000.0, (float) $product->purchase_price);
        $this->assertSame(25.0, (float) $product->weight_kg);
        $this->assertSame(500.0, (float) $product->target_stock);

        // base_price tetap harga jual dan tidak tersentuh purchasing
        $this->assertSame(261000.0, (float) $product->base_price);
        $this->assertSame('SACK', $product->uom);
        $this->assertSame('25KG', $product->net_weight);
    }

    public function test_duplicate_dynamics_code_is_rejected(): void
    {
        Product::create($this->payload([
            'product_code' => 'TG-DUP-01',
            'dynamics_code' => 'FG-GBBP-0003',
        ]));

        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload([
                'product_code' => 'TG-DUP-02',
                'dynamics_code' => 'FG-GBBP-0003',
            ]))
            ->assertSessionHasErrors('dynamics_code');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_purchasing_data_can_be_updated(): void
    {
        $product = Product::create($this->payload([
            'dynamics_code' => 'FG-GBJP-0008',
            'purchase_price' => 100000,
            'weight_kg' => 10,
            'target_stock' => 100,
        ]));

        $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), $this->payload([
                'dynamics_code' => 'FG-GBJP-0008',
                'purchase_price' => 145000,
                'weight_kg' => 25,
                'target_stock' => 750,
            ]))
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertSame(145000.0, (float) $product->purchase_price);
        $this->assertSame(25.0, (float) $product->weight_kg);
        $this->assertSame(750.0, (float) $product->target_stock);
        $this->assertSame('FG-GBJP-0008', $product->dynamics_code);
    }

    public function test_product_can_keep_its_own_dynamics_code_on_update(): void
    {
        $product = Product::create($this->payload(['dynamics_code' => 'FG-KWBP-0002']));

        $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), $this->payload(['dynamics_code' => 'FG-KWBP-0002']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertSame('FG-KWBP-0002', $product->fresh()->dynamics_code);
    }

    public function test_negative_purchase_price_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload(['purchase_price' => -1]))
            ->assertSessionHasErrors('purchase_price');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_zero_and_negative_weight_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload(['weight_kg' => 0]))
            ->assertSessionHasErrors('weight_kg');

        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload(['weight_kg' => -5]))
            ->assertSessionHasErrors('weight_kg');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_negative_target_stock_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload(['target_stock' => -100]))
            ->assertSessionHasErrors('target_stock');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_purchasing_data_is_rendered_on_product_pages(): void
    {
        $product = Product::create($this->payload([
            'dynamics_code' => 'FG-GBBP-0003',
            'purchase_price' => 125000,
            'weight_kg' => 25,
            'target_stock' => 500,
        ]));

        $this->actingAs(User::factory()->create());

        $this->get(route('products.index'))->assertOk()->assertSee('FG-GBBP-0003');
        $this->get(route('products.create'))->assertOk()->assertSee('Data Purchasing');
        $this->get(route('products.edit', $product))->assertOk()->assertSee('FG-GBBP-0003');
        $this->get(route('products.show', $product))->assertOk()->assertSee('FG-GBBP-0003');
    }

    public function test_purchasing_fields_are_nullable(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::where('product_code', 'TG-PUCH-01')->firstOrFail();

        $this->assertNull($product->dynamics_code);
        $this->assertNull($product->purchase_price);
        $this->assertNull($product->weight_kg);
        $this->assertNull($product->target_stock);
    }
}
