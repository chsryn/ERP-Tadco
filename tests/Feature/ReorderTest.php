<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReorderTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role = 'admin'): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function supplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'code' => 'SUP-' . fake()->unique()->numerify('###'),
            'name' => 'EPFM',
            'term_of_payment_days' => 30,
            'is_active' => true,
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_code' => 'TG-' . fake()->unique()->numerify('####'),
            'product_name' => 'Terigu Uji',
            'base_price' => 261000,
            'uom' => 'SACK',
            'net_weight' => '25KG',
            'segment' => 'Terigu',
            'is_active' => true,
            'dynamics_code' => 'FG-' . fake()->unique()->numerify('####'),
            'purchase_price' => 100000,
            'weight_kg' => 25,
            'target_stock' => 100,
        ], $overrides));
    }

    private function stock(Product $product, float $available, float $reserved = 0): StockBalance
    {
        return StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => $available,
            'qty_reserved' => $reserved,
        ]);
    }

    private function purchaseOrder(Supplier $supplier, string $status, array $items = []): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'po_number' => 'TST/' . str_pad((string) (PurchaseOrder::query()->count() + 1), 3, '0', STR_PAD_LEFT) . '/2026',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'po_date' => '2026-09-01',
            'status' => $status,
            'ppn_rate' => 11,
        ]);

        foreach ($items as $item) {
            $po->items()->create(array_merge([
                'weight_kg' => 25,
                'qty_ton' => 0,
                'price' => 100000,
                'special_discount' => 0,
                'price_nett' => 100000,
                'total_price' => 0,
                'qty_received' => 0,
            ], $item));
        }

        return $po;
    }

    private function recommendationFor(Product $product)
    {
        return app(ReorderService::class)
            ->recommendations()
            ->firstWhere('id', $product->id);
    }

    private function payload(Supplier $supplier, array $items, array $overrides = []): array
    {
        return array_merge([
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'items' => $items,
        ], $overrides);
    }

    // ---------- authorization ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('purchase_orders.reorder'))->assertRedirect('/login');

        $this->post(route('purchase_orders.reorder.generate'), [])->assertRedirect('/login');
    }

    public function test_sales_cannot_open_reorder_page(): void
    {
        $this->actingAs($this->userWithRole('sales'))
            ->get(route('purchase_orders.reorder'))
            ->assertForbidden();
    }

    public function test_finance_cannot_open_reorder_page(): void
    {
        $this->actingAs($this->userWithRole('finance'))
            ->get(route('purchase_orders.reorder'))
            ->assertForbidden();
    }

    public function test_admin_can_open_reorder_page(): void
    {
        $product = $this->product(['product_name' => 'Terigu Rekomendasi']);
        $this->stock($product, 10);

        $this->actingAs($this->userWithRole())
            ->get(route('purchase_orders.reorder'))
            ->assertOk()
            ->assertSee('Terigu Rekomendasi')
            ->assertSee('Generate Draft PO');
    }

    public function test_reorder_page_shows_empty_state_when_no_product_is_eligible(): void
    {
        $this->product(['target_stock' => null]);

        $this->actingAs($this->userWithRole())
            ->get(route('purchase_orders.reorder'))
            ->assertOk()
            ->assertSee('Tidak ada produk yang membutuhkan pengadaan saat ini.');
    }

    // ---------- eligibility ----------

    public function test_product_with_target_stock_is_recommended(): void
    {
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 40);

        $this->assertNotNull($this->recommendationFor($product));
    }

    public function test_product_without_target_stock_is_not_recommended(): void
    {
        $product = $this->product(['target_stock' => null]);
        $this->stock($product, 10);

        $this->assertNull($this->recommendationFor($product));
    }

    public function test_inactive_product_is_not_recommended(): void
    {
        $product = $this->product(['is_active' => false]);
        $this->stock($product, 10);

        $this->assertNull($this->recommendationFor($product));
    }

    public function test_product_without_purchase_price_is_not_recommended(): void
    {
        $product = $this->product(['purchase_price' => null]);
        $this->stock($product, 10);

        $this->assertNull($this->recommendationFor($product));
    }

    public function test_product_without_weight_is_not_recommended(): void
    {
        $product = $this->product(['weight_kg' => null]);
        $this->stock($product, 10);

        $this->assertNull($this->recommendationFor($product));
    }

    // ---------- perhitungan ----------

    public function test_available_for_reorder_subtracts_reserved_stock(): void
    {
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 100, 40);

        $row = $this->recommendationFor($product);

        $this->assertEqualsWithDelta(60.0, (float) $row->available_for_reorder, 0.001);
        $this->assertEqualsWithDelta(40.0, (float) $row->suggested_qty, 0.001);
    }

    public function test_suggested_qty_follows_formula(): void
    {
        $product = $this->product(['target_stock' => 150]);
        $this->stock($product, 100, 20);

        $this->purchaseOrder($this->supplier(), 'confirmed', [
            ['product_id' => $product->id, 'qty_plan' => 10, 'qty_received' => 0],
        ]);

        $row = $this->recommendationFor($product);

        $this->assertEqualsWithDelta(80.0, (float) $row->available_for_reorder, 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $row->qty_on_order, 0.001);
        $this->assertEqualsWithDelta(60.0, (float) $row->suggested_qty, 0.001);
    }

    public function test_suggested_qty_is_never_negative(): void
    {
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 40);

        $this->purchaseOrder($this->supplier(), 'confirmed', [
            ['product_id' => $product->id, 'qty_plan' => 80, 'qty_received' => 0],
        ]);

        $row = $this->recommendationFor($product);

        $this->assertEqualsWithDelta(0.0, (float) $row->suggested_qty, 0.001);
    }

    public function test_qty_on_order_counts_confirmed_purchase_order(): void
    {
        $product = $this->product();
        $this->stock($product, 0);

        $this->purchaseOrder($this->supplier(), 'confirmed', [
            ['product_id' => $product->id, 'qty_plan' => 100, 'qty_received' => 0],
        ]);

        $this->assertEqualsWithDelta(100.0, app(ReorderService::class)->getQtyOnOrder($product->id), 0.001);
    }

    public function test_qty_on_order_counts_only_outstanding_quantity_of_partial_order(): void
    {
        $product = $this->product();
        $this->stock($product, 0);

        $this->purchaseOrder($this->supplier(), 'partial', [
            ['product_id' => $product->id, 'qty_plan' => 80, 'qty_received' => 30],
        ]);

        $this->assertEqualsWithDelta(50.0, app(ReorderService::class)->getQtyOnOrder($product->id), 0.001);
    }

    public function test_draft_purchase_order_is_not_counted(): void
    {
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 40);

        $this->purchaseOrder($this->supplier(), 'draft', [
            ['product_id' => $product->id, 'qty_plan' => 60],
        ]);

        $this->assertEqualsWithDelta(0.0, app(ReorderService::class)->getQtyOnOrder($product->id), 0.001);
        $this->assertEqualsWithDelta(60.0, (float) $this->recommendationFor($product)->suggested_qty, 0.001);
    }

    public function test_cancelled_purchase_order_is_not_counted(): void
    {
        $product = $this->product();
        $this->stock($product, 0);

        $this->purchaseOrder($this->supplier(), 'cancelled', [
            ['product_id' => $product->id, 'qty_plan' => 70],
        ]);

        $this->assertEqualsWithDelta(0.0, app(ReorderService::class)->getQtyOnOrder($product->id), 0.001);
    }

    public function test_received_purchase_order_is_not_counted(): void
    {
        $product = $this->product();
        $this->stock($product, 0);

        $this->purchaseOrder($this->supplier(), 'received', [
            ['product_id' => $product->id, 'qty_plan' => 70, 'qty_received' => 70],
        ]);

        $this->assertEqualsWithDelta(0.0, app(ReorderService::class)->getQtyOnOrder($product->id), 0.001);
    }

    public function test_recommendations_put_suggested_products_first(): void
    {
        $enough = $this->product(['product_name' => 'Aaa Stok Cukup', 'target_stock' => 100]);
        $this->stock($enough, 500);

        $urgent = $this->product(['product_name' => 'Zzz Perlu Beli', 'target_stock' => 100]);
        $this->stock($urgent, 10);

        $rows = app(ReorderService::class)->recommendations();

        $this->assertSame($urgent->id, $rows->first()->id);
        $this->assertSame(0.0, (float) $rows->last()->suggested_qty, 0.001);
    }

    // ---------- generate draft ----------

    public function test_generate_creates_draft_purchase_order(): void
    {
        $supplier = $this->supplier();
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 40);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                ['product_id' => $product->id, 'qty_plan' => 60],
            ]))
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::firstOrFail();

        $this->assertSame('draft', $po->status);
        $this->assertSame('001/09/2026', $po->po_number);
        $this->assertCount(1, $po->items);
        $this->assertEqualsWithDelta(60.0, (float) $po->items->first()->qty_plan, 0.001);
    }

    public function test_generate_accepts_form_payload_keyed_by_product_id(): void
    {
        $supplier = $this->supplier();
        $product = $this->product();
        $this->stock($product, 10);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                $product->id => ['product_id' => $product->id, 'qty_plan' => 45],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(45.0, (float) PurchaseOrder::firstOrFail()->items->first()->qty_plan, 0.001);
    }

    public function test_generate_uses_user_override_quantity(): void
    {
        $supplier = $this->supplier();
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 40);

        $suggested = $this->recommendationFor($product)->suggested_qty;
        $this->assertEqualsWithDelta(60.0, (float) $suggested, 0.001);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                ['product_id' => $product->id, 'qty_plan' => 75],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(75.0, (float) PurchaseOrder::firstOrFail()->items->first()->qty_plan, 0.001);
    }

    public function test_generate_uses_purchase_price_weight_and_weekly_allocation_from_scm03(): void
    {
        $supplier = $this->supplier();
        $product = $this->product([
            'purchase_price' => 120000,
            'weight_kg' => 25,
            'base_price' => 999000,
        ]);
        $this->stock($product, 10);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                ['product_id' => $product->id, 'qty_plan' => 60],
            ]))
            ->assertSessionHasNoErrors();

        $item = PurchaseOrder::firstOrFail()->items->first();

        $this->assertEqualsWithDelta(120000.0, (float) $item->price, 0.001);
        $this->assertEqualsWithDelta(25.0, (float) $item->weight_kg, 0.001);
        $this->assertEqualsWithDelta(1.5, (float) $item->qty_ton, 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $item->week_1, 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $item->week_2, 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $item->week_3, 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $item->week_4, 0.001);
        $this->assertEqualsWithDelta(7200000.0, (float) $item->total_price, 0.001);
    }

    public function test_generate_does_not_touch_stock(): void
    {
        $supplier = $this->supplier();
        $product = $this->product();
        $balance = $this->stock($product, 40, 10);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                ['product_id' => $product->id, 'qty_plan' => 60],
            ]))
            ->assertSessionHasNoErrors();

        $balance->refresh();

        $this->assertEqualsWithDelta(40.0, (float) $balance->qty_available, 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $balance->qty_reserved, 0.001);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_generated_draft_does_not_increase_qty_on_order(): void
    {
        $supplier = $this->supplier();
        $product = $this->product(['target_stock' => 100]);
        $this->stock($product, 40);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                ['product_id' => $product->id, 'qty_plan' => 60],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(0.0, app(ReorderService::class)->getQtyOnOrder($product->id), 0.001);
        $this->assertEqualsWithDelta(60.0, (float) $this->recommendationFor($product)->suggested_qty, 0.001);
    }

    public function test_generate_rejects_inactive_supplier(): void
    {
        $supplier = $this->supplier(['is_active' => false]);
        $product = $this->product();
        $this->stock($product, 10);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                ['product_id' => $product->id, 'qty_plan' => 10],
            ]))
            ->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_generate_rejects_quantity_zero_or_negative(): void
    {
        $supplier = $this->supplier();
        $product = $this->product();
        $this->stock($product, 10);

        foreach ([0, -5] as $qty) {
            $this->actingAs($this->userWithRole())
                ->post(route('purchase_orders.reorder.generate'), $this->payload($supplier, [
                    ['product_id' => $product->id, 'qty_plan' => $qty],
                ]))
                ->assertSessionHasErrors();

            $this->assertDatabaseCount('purchase_orders', 0);
        }
    }

    public function test_generate_requires_at_least_one_selected_product(): void
    {
        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.reorder.generate'), [
                'po_date' => '2026-09-05',
                'period' => '2026-09',
                'supplier_id' => $this->supplier()->id,
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_orders', 0);
    }
}
