<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SUP-TEST',
            'name' => 'Supplier Uji',
            'region' => 'Gorontalo',
            'port_depo' => 'Pelabuhan Gorontalo',
            'delivery_mode' => 'DOOR TO PORT',
            'term_of_payment_days' => 30,
            'pic_name' => 'PIC Uji',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_access_suppliers(): void
    {
        $this->get('/suppliers')->assertRedirect('/login');
    }

    public function test_non_admin_role_is_forbidden(): void
    {
        $this->actingAs($this->userWithRole('sales'))
            ->get('/suppliers')
            ->assertForbidden();

        $this->actingAs($this->userWithRole('finance'))
            ->post('/suppliers', $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_admin_can_open_supplier_index(): void
    {
        Supplier::create($this->payload(['code' => 'SUP-LIST', 'name' => 'Principal List']));

        $this->actingAs($this->userWithRole('admin'))
            ->get('/suppliers')
            ->assertOk()
            ->assertSee('Principal List');
    }

    public function test_admin_can_create_supplier(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->post('/suppliers', $this->payload())
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUP-TEST',
            'name' => 'Supplier Uji',
            'term_of_payment_days' => 30,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        Supplier::create($this->payload(['code' => 'SUP-DUP']));

        $this->actingAs($this->userWithRole('admin'))
            ->post('/suppliers', $this->payload(['code' => 'SUP-DUP']))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('suppliers', 1);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->post('/suppliers', $this->payload([
                'name' => '',
                'term_of_payment_days' => -1,
            ]))
            ->assertSessionHasErrors(['name', 'term_of_payment_days']);

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::create($this->payload(['code' => 'SUP-UPD']));

        $this->actingAs($this->userWithRole('admin'))
            ->put(route('suppliers.update', $supplier), $this->payload([
                'code' => 'SUP-UPD',
                'name' => 'Nama Baru',
                'term_of_payment_days' => 14,
            ]))
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Nama Baru',
            'term_of_payment_days' => 14,
        ]);
    }

    public function test_admin_can_deactivate_supplier(): void
    {
        $supplier = Supplier::create($this->payload(['is_active' => '1']));

        $this->actingAs($this->userWithRole('admin'))
            ->put(route('suppliers.update', $supplier), $this->payload([
                'is_active' => null,
            ]));

        $this->assertFalse($supplier->fresh()->is_active);
    }

    public function test_admin_can_view_supplier_detail(): void
    {
        $supplier = Supplier::create($this->payload(['code' => 'SUP-SHOW', 'name' => 'Principal Detail']));

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Principal Detail')
            ->assertSee('SUP-SHOW');
    }

    public function test_admin_can_delete_supplier_without_purchase_order(): void
    {
        $supplier = Supplier::create($this->payload(['code' => 'SUP-DEL']));

        $this->actingAs($this->userWithRole('admin'))
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
