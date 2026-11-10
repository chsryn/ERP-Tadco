<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchasingAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function supplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'code' => 'SUP-'.fake()->unique()->numerify('####'),
            'name' => 'Supplier ACL',
            'term_of_payment_days' => 30,
            'is_active' => true,
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_code' => 'TG-'.fake()->unique()->numerify('####'),
            'product_name' => 'Terigu ACL',
            'base_price' => 261000,
            'uom' => 'SACK',
            'net_weight' => '25KG',
            'segment' => 'Terigu',
            'is_active' => true,
            'dynamics_code' => 'FG-'.fake()->unique()->numerify('####'),
            'purchase_price' => 100000,
            'weight_kg' => 25,
            'target_stock' => 500,
        ], $overrides));
    }

    private function purchaseOrder(string $status = 'draft'): PurchaseOrder
    {
        return PurchaseOrder::create([
            'po_number' => 'TST/ACL/'.fake()->unique()->numerify('####').'/2026',
            'period' => '2026-09',
            'supplier_id' => $this->supplier()->id,
            'po_date' => '2026-09-01',
            'status' => $status,
            'ppn_rate' => 11,
        ]);
    }

    private function receipt(PurchaseOrder $purchaseOrder): PurchaseReceipt
    {
        return PurchaseReceipt::create([
            'receipt_number' => 'RCP/ACL/2026',
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_date' => '2026-09-10',
            'status' => PurchaseReceipt::STATUS_POSTED,
        ]);
    }

    private function supplierPayload(): array
    {
        return [
            'code' => 'SUP-NEW',
            'name' => 'Supplier Baru',
            'term_of_payment_days' => 30,
            'is_active' => '1',
        ];
    }

    private function poPayload(Supplier $supplier, Product $product): array
    {
        return [
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'ppn_rate' => 11,
            'items' => [[
                'product_id' => $product->id,
                'qty_plan' => 10,
                'price' => 100000,
                'special_discount' => 0,
            ]],
        ];
    }

    // ---------- guest ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('suppliers.index'))->assertRedirect('/login');
        $this->get(route('purchase_orders.index'))->assertRedirect('/login');
        $this->get(route('purchase_orders.reorder'))->assertRedirect('/login');
        $this->get(route('purchase_receipts.index'))->assertRedirect('/login');
    }

    // ---------- admin ----------

    public function test_admin_without_explicit_permissions_can_access_purchasing(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($admin)->get(route('purchase_orders.index'))->assertOk();
        $this->actingAs($admin)->get(route('purchase_orders.reorder'))->assertOk();
        $this->actingAs($admin)->get(route('purchase_receipts.index'))->assertOk();
    }

    // ---------- tanpa permission ----------

    public function test_sales_and_finance_are_forbidden(): void
    {
        foreach (['sales', 'finance'] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('suppliers.index'))->assertForbidden();
            $this->actingAs($user)->get(route('purchase_orders.index'))->assertForbidden();
            $this->actingAs($user)->get(route('purchase_orders.reorder'))->assertForbidden();
            $this->actingAs($user)->get(route('purchase_receipts.index'))->assertForbidden();
            $this->actingAs($user)->post(route('suppliers.store'), $this->supplierPayload())->assertForbidden();
        }

        $this->assertDatabaseCount('suppliers', 0);
    }

    // ---------- sebagian permission ----------

    public function test_suppliers_view_only_can_read_but_not_mutate(): void
    {
        $supplier = $this->supplier();
        $user = $this->userWithPermissions(['suppliers.view']);

        $this->actingAs($user)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($user)->get(route('suppliers.show', $supplier))->assertOk();

        $this->actingAs($user)->get(route('suppliers.create'))->assertForbidden();
        $this->actingAs($user)->post(route('suppliers.store'), $this->supplierPayload())->assertForbidden();
        $this->actingAs($user)->get(route('suppliers.edit', $supplier))->assertForbidden();
        $this->actingAs($user)->put(route('suppliers.update', $supplier), $this->supplierPayload())->assertForbidden();
        $this->actingAs($user)->delete(route('suppliers.destroy', $supplier))->assertForbidden();

        $this->actingAs($user)->get(route('purchase_orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('purchase_receipts.index'))->assertForbidden();
    }

    public function test_purchase_orders_view_only_cannot_create_confirm_or_reorder(): void
    {
        $order = $this->purchaseOrder();
        $user = $this->userWithPermissions(['purchase-orders.view']);

        $this->actingAs($user)->get(route('purchase_orders.index'))->assertOk();
        $this->actingAs($user)->get(route('purchase_orders.show', $order))->assertOk();

        $this->actingAs($user)->get(route('purchase_orders.create'))->assertForbidden();
        $this->actingAs($user)->post(route('purchase_orders.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('purchase_orders.edit', $order))->assertForbidden();
        $this->actingAs($user)->post(route('purchase_orders.confirm', $order))->assertForbidden();
        $this->actingAs($user)->post(route('purchase_orders.cancel', $order))->assertForbidden();
        $this->actingAs($user)->get(route('purchase_orders.print', $order))->assertForbidden();
        $this->actingAs($user)->get(route('purchase_orders.reorder'))->assertForbidden();
        $this->actingAs($user)->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('purchase_receipts.index'))->assertForbidden();
    }

    public function test_auto_po_view_only_cannot_generate(): void
    {
        $user = $this->userWithPermissions(['auto-po.view']);

        $this->actingAs($user)->get(route('purchase_orders.reorder'))->assertOk();
        $this->actingAs($user)->post(route('purchase_orders.reorder.generate'), [])->assertForbidden();
    }

    public function test_receipts_view_only_cannot_create_or_process_stock_in(): void
    {
        $receipt = $this->receipt($this->purchaseOrder('confirmed'));
        $user = $this->userWithPermissions(['purchase-receipts.view']);

        $this->actingAs($user)->get(route('purchase_receipts.index'))->assertOk();
        $this->actingAs($user)->get(route('purchase_receipts.show', $receipt))->assertOk();

        $this->actingAs($user)->get(route('purchase_receipts.create', $receipt->purchaseOrder))->assertForbidden();
        $this->actingAs($user)->post(route('purchase_receipts.stock_in', $receipt))->assertForbidden();
        $this->actingAs($user)->get(route('purchase_orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('suppliers.index'))->assertForbidden();
    }

    public function test_confirm_permission_can_confirm_a_draft(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->post(
            route('purchase_orders.store'),
            $this->poPayload($this->supplier(), $this->product())
        )->assertSessionHasNoErrors();

        $order = PurchaseOrder::orderByDesc('id')->firstOrFail();
        $confirmer = $this->userWithPermissions(['purchase-orders.confirm']);

        $this->actingAs($confirmer)
            ->post(route('purchase_orders.confirm', $order))
            ->assertRedirect(route('purchase_orders.show', $order));

        $this->assertSame('confirmed', $order->refresh()->status);
    }

    // ---------- UI ----------

    public function test_view_only_permissions_hide_action_buttons(): void
    {
        $supplier = $this->supplier();
        $order = $this->purchaseOrder();
        $receipt = $this->receipt($this->purchaseOrder('confirmed'));

        $supplierReader = $this->userWithPermissions(['suppliers.view']);
        $this->actingAs($supplierReader)
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertDontSee('Tambah Supplier')
            ->assertDontSee(route('suppliers.create'), false);

        $orderReader = $this->userWithPermissions(['purchase-orders.view']);
        $this->actingAs($orderReader)
            ->get(route('purchase_orders.index'))
            ->assertOk()
            ->assertDontSee('Buat PO')
            ->assertDontSee(route('purchase_orders.create'), false)
            ->assertDontSee(route('purchase_orders.reorder'), false)
            ->assertDontSee(route('purchase_receipts.index'), false);

        $receiptReader = $this->userWithPermissions(['purchase-receipts.view']);
        $this->actingAs($receiptReader)
            ->get(route('purchase_receipts.show', $receipt))
            ->assertOk()
            ->assertDontSee(route('purchase_receipts.stock_in', $receipt), false);

        $reorderReader = $this->userWithPermissions(['auto-po.view']);
        $this->actingAs($reorderReader)
            ->get(route('purchase_orders.reorder'))
            ->assertOk()
            ->assertDontSee('Generate Draft PO');
    }

    public function test_sidebar_shows_only_permitted_purchasing_menu(): void
    {
        $user = $this->userWithPermissions(['suppliers.view']);

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Purchasing')
            ->assertSee('Suppliers')
            ->assertDontSee('Purchase Orders')
            ->assertDontSee('Auto PO / Reorder')
            ->assertDontSee('Purchase Receipts');
    }

    // ---------- seeder ----------

    public function test_seeder_creates_purchasing_permissions_and_assigns_them_to_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(15, Permission::query()->count());

        foreach (RolePermissionSeeder::PURCHASING_PERMISSIONS as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::findByName('admin');
        $this->assertTrue($admin->hasAllPermissions(RolePermissionSeeder::PURCHASING_PERMISSIONS));

        $this->assertFalse(Role::findByName('sales')->hasAnyPermission(RolePermissionSeeder::PURCHASING_PERMISSIONS));
        $this->assertFalse(Role::findByName('finance')->hasAnyPermission(RolePermissionSeeder::PURCHASING_PERMISSIONS));
    }
}
