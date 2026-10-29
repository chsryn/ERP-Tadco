<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
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
            'name' => 'Supplier Uji',
            'term_of_payment_days' => 30,
            'is_active' => true,
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_code' => 'TG-' . fake()->unique()->numerify('####'),
            'product_name' => 'Terigu Uji PO',
            'base_price' => 261000,
            'uom' => 'SACK',
            'net_weight' => '25KG',
            'segment' => 'Terigu',
            'is_active' => true,
            'dynamics_code' => 'FG-' . fake()->unique()->numerify('####'),
            'purchase_price' => 100000,
            'weight_kg' => 25,
            'target_stock' => 500,
        ], $overrides));
    }

    private function payload(Supplier $supplier, array $items, array $overrides = []): array
    {
        return array_merge([
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'ppn_rate' => 11,
            'items' => $items,
        ], $overrides);
    }

    private function itemPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'product_id' => $product->id,
            'qty_plan' => 10,
            'price' => 100000,
            'special_discount' => 5000,
        ], $overrides);
    }

    public function test_purchase_order_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('document_sequences'));
        $this->assertTrue(Schema::hasTable('purchase_orders'));
        $this->assertTrue(Schema::hasTable('purchase_order_items'));

        // penjaga terakhir concurrency: satu baris sequence per (document_type, period)
        $this->assertTrue(Schema::hasIndex('document_sequences', ['document_type', 'period'], 'unique'));
    }

    public function test_admin_can_create_draft_purchase_order(): void
    {
        $supplier = $this->supplier();
        $product = $this->product();

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]))
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::firstOrFail();

        $this->assertSame('draft', $po->status);
        $this->assertSame($supplier->id, $po->supplier_id);
        $this->assertCount(1, $po->items);

        $item = $po->items->first();
        $this->assertSame($product->dynamics_code, $item->dynamics_code);
        $this->assertSame(25.0, (float) $item->weight_kg);
        $this->assertSame(100000.0, (float) $item->price);
        $this->assertSame(0.25, (float) $item->qty_ton);
    }

    public function test_inactive_supplier_cannot_be_used(): void
    {
        $supplier = $this->supplier(['is_active' => false]);
        $product = $this->product();

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]))
            ->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_inactive_product_cannot_be_used(): void
    {
        $supplier = $this->supplier();
        $product = $this->product(['is_active' => false]);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]))
            ->assertSessionHasErrors('items.0.product_id');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_product_without_purchase_price_is_rejected(): void
    {
        $supplier = $this->supplier();
        $product = $this->product(['purchase_price' => null]);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($supplier, [
                $this->itemPayload($product, ['price' => null]),
            ]))
            ->assertSessionHasErrors('items.0.product_id');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_product_without_weight_is_rejected(): void
    {
        $supplier = $this->supplier();
        $product = $this->product(['weight_kg' => null]);

        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]))
            ->assertSessionHasErrors('items.0.product_id');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_po_number_format_is_sequence_month_year(): void
    {
        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($this->supplier(), [$this->itemPayload($this->product())]));

        $this->assertSame('001/09/2026', PurchaseOrder::firstOrFail()->po_number);
    }

    public function test_po_number_increments(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product();

        foreach ([1, 2, 3] as $ignored) {
            $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));
        }

        $this->assertSame(
            ['001/09/2026', '002/09/2026', '003/09/2026'],
            PurchaseOrder::orderBy('id')->pluck('po_number')->all()
        );
    }

    public function test_po_number_resets_for_different_period(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product();

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));
        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)], [
            'po_date' => '2026-10-03',
            'period' => '2026-10',
        ]));

        $this->assertSame(
            ['001/09/2026', '001/10/2026'],
            PurchaseOrder::orderBy('id')->pluck('po_number')->all()
        );
    }

    public function test_weekly_allocation_defaults_to_floor_with_remainder_on_week_4(): void
    {
        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($this->supplier(), [
                $this->itemPayload($this->product(), ['qty_plan' => 101]),
            ]))
            ->assertSessionHasNoErrors();

        $item = PurchaseOrder::firstOrFail()->items->first();

        $this->assertSame(25.0, (float) $item->week_1);
        $this->assertSame(25.0, (float) $item->week_2);
        $this->assertSame(25.0, (float) $item->week_3);
        $this->assertSame(26.0, (float) $item->week_4);
    }

    public function test_weekly_allocation_mismatch_is_rejected(): void
    {
        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($this->supplier(), [
                $this->itemPayload($this->product(), [
                    'qty_plan' => 101,
                    'week_1' => 25,
                    'week_2' => 25,
                    'week_3' => 25,
                    'week_4' => 25,
                ]),
            ]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_prices_discount_ppn_and_totals_are_calculated(): void
    {
        $this->actingAs($this->userWithRole())
            ->post(route('purchase_orders.store'), $this->payload($this->supplier(), [
                $this->itemPayload($this->product(), [
                    'qty_plan' => 10,
                    'price' => 100000,
                    'special_discount' => 5000,
                ]),
            ]))
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::firstOrFail();
        $item = $po->items->first();

        $this->assertSame(95000.0, (float) $item->price_nett);
        $this->assertSame(950000.0, (float) $item->total_price);

        $this->assertSame(950000.0, (float) $po->subtotal);
        $this->assertSame(50000.0, (float) $po->special_discount_total);
        $this->assertSame(11.0, (float) $po->ppn_rate);
        $this->assertSame(104500.0, (float) $po->ppn_amount);
        $this->assertSame(1054500.0, (float) $po->grand_total);
    }

    public function test_admin_can_update_draft_items(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product();

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));

        $po = PurchaseOrder::firstOrFail();

        $this->put(route('purchase_orders.update', $po), $this->payload($supplier, [
            $this->itemPayload($product, ['qty_plan' => 20, 'special_discount' => 0]),
        ], ['notes' => 'Revisi qty']))->assertSessionHasNoErrors();

        $po->refresh();

        $this->assertSame('Revisi qty', $po->notes);
        $this->assertCount(1, $po->items);
        $this->assertSame(20.0, (float) $po->items->first()->qty_plan);
        $this->assertSame(2000000.0, (float) $po->subtotal);
    }

    public function test_draft_can_be_confirmed(): void
    {
        $this->actingAs($this->userWithRole());
        $this->post(route('purchase_orders.store'), $this->payload($this->supplier(), [$this->itemPayload($this->product())]));

        $po = PurchaseOrder::firstOrFail();

        $this->post(route('purchase_orders.confirm', $po))->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $po->fresh()->status);
    }

    public function test_confirm_non_draft_is_rejected(): void
    {
        $this->actingAs($this->userWithRole());
        $this->post(route('purchase_orders.store'), $this->payload($this->supplier(), [$this->itemPayload($this->product())]));

        $po = PurchaseOrder::firstOrFail();
        $this->post(route('purchase_orders.confirm', $po));

        $this->post(route('purchase_orders.confirm', $po))->assertSessionHasErrors('status');

        $this->assertSame('confirmed', $po->fresh()->status);
    }

    public function test_draft_and_confirmed_can_be_cancelled_and_cancelled_cannot_be_confirmed(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product();

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));
        $draft = PurchaseOrder::orderBy('id')->firstOrFail();

        $this->post(route('purchase_orders.cancel', $draft))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $draft->fresh()->status);

        $this->post(route('purchase_orders.confirm', $draft))->assertSessionHasErrors('status');

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));
        $confirmed = PurchaseOrder::orderByDesc('id')->firstOrFail();
        $this->post(route('purchase_orders.confirm', $confirmed));

        $this->post(route('purchase_orders.cancel', $confirmed))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $confirmed->fresh()->status);
    }

    public function test_confirmed_purchase_order_cannot_be_edited(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product();

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));
        $po = PurchaseOrder::firstOrFail();
        $this->post(route('purchase_orders.confirm', $po));

        $this->get(route('purchase_orders.edit', $po))->assertForbidden();

        $this->put(route('purchase_orders.update', $po), $this->payload($supplier, [$this->itemPayload($product)]))
            ->assertSessionHasErrors('status');

        $this->assertSame('confirmed', $po->fresh()->status);
    }

    public function test_supplier_with_purchase_order_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product();

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));

        $this->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHasErrors('supplier');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_purchase_order_item_keeps_product_snapshot_after_product_changes(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier();
        $product = $this->product([
            'dynamics_code' => 'FG-SNAP-OLD',
            'weight_kg' => 25,
            'purchase_price' => 100000,
        ]);

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));

        $item = PurchaseOrder::firstOrFail()->items->first();

        $product->update([
            'dynamics_code' => 'FG-SNAP-NEW',
            'weight_kg' => 30,
            'purchase_price' => 120000,
        ]);

        $item->refresh();

        $this->assertSame('FG-SNAP-OLD', $item->dynamics_code);
        $this->assertSame(25.0, (float) $item->weight_kg);
        $this->assertSame(100000.0, (float) $item->price);

        $this->get(route('purchase_orders.show', $item->purchase_order_id))
            ->assertOk()
            ->assertSee('FG-SNAP-OLD');
    }

    public function test_authorization_rules(): void
    {
        $this->get(route('purchase_orders.index'))->assertRedirect('/login');

        $this->actingAs($this->userWithRole('sales'))
            ->get(route('purchase_orders.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('finance'))
            ->get(route('purchase_orders.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole())
            ->get(route('purchase_orders.index'))
            ->assertOk();
    }

    public function test_admin_can_open_create_and_show_pages(): void
    {
        $this->actingAs($this->userWithRole());
        $supplier = $this->supplier(['name' => 'EPFM']);
        $product = $this->product(['product_name' => 'Terigu Tampil']);

        $this->get(route('purchase_orders.create'))->assertOk()->assertSee('EPFM');

        $this->post(route('purchase_orders.store'), $this->payload($supplier, [$this->itemPayload($product)]));
        $po = PurchaseOrder::firstOrFail();

        $this->get(route('purchase_orders.show', $po))
            ->assertOk()
            ->assertSee($po->po_number)
            ->assertSee('Terigu Tampil');

        $this->get(route('purchase_orders.edit', $po))
            ->assertOk()
            ->assertSee($po->po_number);
    }
}
