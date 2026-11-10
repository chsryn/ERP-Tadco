<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, array $attributes = []): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    // ---------- login ----------

    public function test_active_user_can_login(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_is_rejected_with_generic_message(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password-salah',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Email atau password tidak sesuai.']);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_even_with_correct_password(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Email atau password tidak sesuai.']);

        $this->assertGuest();
    }

    public function test_user_without_role_does_not_get_permissions_automatically(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertTrue($user->getAllPermissions()->isEmpty());
        $this->assertFalse($user->hasRole('admin'));

        $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
        $this->actingAs($user)->get(route('purchase_orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_active_user_with_role_keeps_working(): void
    {
        $admin = $this->userWithRole('admin');
        $sales = $this->userWithRole('sales');

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($sales)->get(route('dashboard.index'))->assertOk();
        $this->actingAs($sales)->get(route('users.index'))->assertForbidden();
    }

    // ---------- registration ----------

    public function test_login_page_renders_without_registration_link(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('Register');
    }

    public function test_guest_cannot_open_registration_form(): void
    {
        $this->assertFalse(Route::has('register'));

        $this->get('/register')->assertNotFound();
    }

    public function test_guest_cannot_register_through_public_endpoint(): void
    {
        $this->post('/register', [
            'name' => 'Akun Publik',
            'email' => 'akun-publik@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'akun-publik@example.com']);
        $this->assertGuest();
    }

    // ---------- session security ----------

    public function test_active_user_can_access_protected_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
    }

    public function test_user_deactivated_after_login_loses_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_access_scm_endpoint(): void
    {
        Permission::firstOrCreate(['name' => 'purchase-orders.view']);
        $user = User::factory()->create(['is_active' => false]);
        $user->givePermissionTo('purchase-orders.view');

        $this->actingAs($user)->get(route('purchase_orders.index'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
