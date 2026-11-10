<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderPrintTest extends TestCase
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
            'code' => 'SUP-EPFM',
            'name' => 'EPFM',
            'region' => 'Jawa Timur',
            'port_depo' => 'Tanjung Perak',
            'delivery_mode' => 'TRUCK',
            'term_of_payment_days' => 30,
            'pic_name' => 'Budi',
            'is_active' => true,
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_code' => 'TG-' . fake()->unique()->numerify('####'),
            'product_name' => 'Terigu Print',
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

    /**
     * Buat PO lewat alur normal (service SCM-03) lalu konfirmasi bila diminta.
     */
    private function makePurchaseOrder(array $overrides = [], bool $confirm = true): PurchaseOrder
    {
        $this->post(route('purchase_orders.store'), $this->payload(
            $this->supplier(),
            [$this->itemPayload($this->product())],
            $overrides
        ))->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::firstOrFail();

        if ($confirm) {
            $this->post(route('purchase_orders.confirm', $purchaseOrder))->assertSessionHasNoErrors();
            $purchaseOrder->refresh();
        }

        return $purchaseOrder;
    }

    // ---------- akses print ----------

    public function test_admin_can_open_print_view_of_confirmed_order(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->makePurchaseOrder();

        $this->get(route('purchase_orders.print', $purchaseOrder))
            ->assertOk()
            ->assertSee('CONFIRM ORDER')
            ->assertSee($purchaseOrder->po_number)
            ->assertSee('EPFM')
            ->assertSee('SUP-EPFM')
            ->assertSee('Terigu Print')
            ->assertSee('window.print()', false);
    }

    public function test_print_view_shows_supplier_fields_and_weekly_allocation(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->makePurchaseOrder();

        $this->get(route('purchase_orders.print', $purchaseOrder))
            ->assertOk()
            ->assertSee('Jawa Timur')
            ->assertSee('Tanjung Perak')
            ->assertSee('TRUCK')
            ->assertSee('Budi')
            ->assertSee('30 hari')
            ->assertSee('Alokasi Mingguan')
            ->assertSee('W1')
            ->assertSee('W4');
    }

    public function test_print_view_shows_stored_totals_and_notes(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->makePurchaseOrder(['notes' => 'Dibuat dari rekomendasi Auto PO']);

        $this->get(route('purchase_orders.print', $purchaseOrder))
            ->assertOk()
            ->assertSee('Subtotal')
            ->assertSee('Special Discount')
            ->assertSee('PPN (11%)')
            ->assertSee('Grand Total')
            // qty 10 × (100.000 − 5.000) = 950.000 ; PPN 11% = 104.500 ; grand total 1.054.500
            ->assertSee('950.000')
            ->assertSee('104.500')
            ->assertSee('1.054.500')
            ->assertSee('Dibuat dari rekomendasi Auto PO');
    }

    public function test_print_view_uses_item_snapshot_not_current_product_data(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product([
            'dynamics_code' => 'FG-SNAP-OLD',
            'weight_kg' => 25,
            'purchase_price' => 100000,
        ]);

        $this->post(route('purchase_orders.store'), $this->payload($this->supplier(), [
            $this->itemPayload($product),
        ]))->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::firstOrFail();
        $this->post(route('purchase_orders.confirm', $purchaseOrder));

        $product->update([
            'dynamics_code' => 'FG-SNAP-NEW',
            'weight_kg' => 40,
            'purchase_price' => 200000,
        ]);

        $this->get(route('purchase_orders.print', $purchaseOrder))
            ->assertOk()
            ->assertSee('FG-SNAP-OLD')
            ->assertDontSee('FG-SNAP-NEW')
            ->assertSee('25,00')
            ->assertDontSee('40,00');
    }

    public function test_draft_order_cannot_be_printed(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->makePurchaseOrder([], confirm: false);

        $this->get(route('purchase_orders.print', $purchaseOrder))->assertForbidden();

        $this->post(route('purchase_orders.cancel', $purchaseOrder));
        $purchaseOrder->refresh();

        $this->get(route('purchase_orders.print', $purchaseOrder))->assertForbidden();
    }

    public function test_guest_and_non_admin_cannot_open_print_view(): void
    {
        $this->actingAs($this->userWithRole());
        $purchaseOrder = $this->makePurchaseOrder();

        auth()->logout();

        $this->get(route('purchase_orders.print', $purchaseOrder))->assertRedirect('/login');

        $this->actingAs($this->userWithRole('sales'))
            ->get(route('purchase_orders.print', $purchaseOrder))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('finance'))
            ->get(route('purchase_orders.print', $purchaseOrder))
            ->assertForbidden();
    }

    public function test_print_route_does_not_change_status_or_stock(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product();
        StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => 40,
            'qty_reserved' => 10,
        ]);

        $this->post(route('purchase_orders.store'), $this->payload($this->supplier(), [
            $this->itemPayload($product),
        ]))->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::firstOrFail();
        $this->post(route('purchase_orders.confirm', $purchaseOrder));

        $this->get(route('purchase_orders.print', $purchaseOrder))->assertOk();

        $this->assertSame('confirmed', $purchaseOrder->fresh()->status);
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'qty_available' => 40,
            'qty_reserved' => 10,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    // ---------- konfirmasi ----------

    public function test_confirmation_does_not_touch_stock(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product();
        $balance = StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => 40,
            'qty_reserved' => 10,
        ]);

        $this->makePurchaseOrder();

        $balance->refresh();

        $this->assertEqualsWithDelta(40.0, (float) $balance->qty_available, 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $balance->qty_reserved, 0.001);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_confirmation_recalculates_totals_from_stored_items(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->makePurchaseOrder([], confirm: false);

        // nilai total dirusak manual → confirm wajib menghitung ulang dari item
        $purchaseOrder->update(['subtotal' => 1, 'ppn_amount' => 1, 'grand_total' => 1]);

        $this->post(route('purchase_orders.confirm', $purchaseOrder))->assertSessionHasNoErrors();

        $purchaseOrder->refresh();

        $this->assertSame('confirmed', $purchaseOrder->status);
        $this->assertEqualsWithDelta(950000.0, (float) $purchaseOrder->subtotal, 0.001);
        $this->assertEqualsWithDelta(104500.0, (float) $purchaseOrder->ppn_amount, 0.001);
        $this->assertEqualsWithDelta(1054500.0, (float) $purchaseOrder->grand_total, 0.001);
    }

    public function test_guest_and_non_admin_cannot_confirm(): void
    {
        $this->actingAs($this->userWithRole());
        $purchaseOrder = $this->makePurchaseOrder([], confirm: false);

        auth()->logout();

        $this->post(route('purchase_orders.confirm', $purchaseOrder))->assertRedirect('/login');
        $this->assertSame('draft', $purchaseOrder->fresh()->status);

        $this->actingAs($this->userWithRole('sales'))
            ->post(route('purchase_orders.confirm', $purchaseOrder))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('finance'))
            ->post(route('purchase_orders.confirm', $purchaseOrder))
            ->assertForbidden();

        $this->assertSame('draft', $purchaseOrder->fresh()->status);
    }

    public function test_show_page_switches_actions_between_draft_and_confirmed(): void
    {
        $this->actingAs($this->userWithRole());

        $draft = $this->makePurchaseOrder([], confirm: false);

        $this->get(route('purchase_orders.show', $draft))
            ->assertOk()
            ->assertSee('Edit Draft')
            ->assertSee(route('purchase_orders.confirm', $draft), false)
            ->assertDontSee('Print Confirm Order');

        $this->post(route('purchase_orders.confirm', $draft));
        $draft->refresh();

        $this->get(route('purchase_orders.show', $draft))
            ->assertOk()
            ->assertSee('Print Confirm Order')
            ->assertSee(route('purchase_orders.print', $draft), false)
            ->assertDontSee('Edit Draft')
            ->assertDontSee(route('purchase_orders.confirm', $draft), false);
    }
}
