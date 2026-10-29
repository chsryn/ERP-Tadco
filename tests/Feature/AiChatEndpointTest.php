<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function fakeProvider(): void
    {
        config([
            'ai.enabled' => true,
            'ai.api_key' => 'test-key',
        ]);

        Http::fake([
            '*/v1/responses' => Http::response([
                'output' => [
                    ['content' => [['type' => 'output_text', 'text' => 'Ringkasan uji.']]],
                ],
            ]),
        ]);
    }

    public function test_user_without_allowed_role_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->postJson('/ai/chat', ['prompt' => 'Ringkas stok hari ini'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->postJson('/ai/chat', ['prompt' => 'Ringkas stok hari ini'])
            ->assertUnauthorized();
    }

    public function test_empty_prompt_is_rejected_without_calling_provider(): void
    {
        $this->fakeProvider();

        $this->actingAs($this->userWithRole('admin'))
            ->postJson('/ai/chat', ['prompt' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('prompt');

        Http::assertNothingSent();
    }

    public function test_sales_scope_excludes_finance_data(): void
    {
        $this->fakeProvider();

        $sales = $this->userWithRole('sales');
        $customer = Customer::create([
            'customer_code' => 'C1',
            'customer_name' => 'Toko Uji',
            'is_active' => true,
        ]);
        $order = DeliveryOrder::create([
            'do_number' => 'DO-AI-1',
            'customer_id' => $customer->id,
            'sales_id' => $sales->id,
            'do_date' => now()->toDateString(),
            'total_amount' => 500000,
            'status' => 'validated',
        ]);
        Invoice::create([
            'delivery_order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 500000,
            'receivable_amount' => 500000,
            'paid_total' => 0,
            'status' => 'unpaid',
        ]);
        Payment::create([
            'payment_number' => 'PAY-AI-1',
            'invoice_id' => Invoice::first()->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount' => 100000,
        ]);

        $this->actingAs($sales)
            ->postJson('/ai/chat', ['prompt' => 'Berapa DO saya bulan ini?'])
            ->assertOk()
            ->assertJson(['available' => true, 'role' => 'sales', 'answer' => 'Ringkasan uji.']);

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return str_contains($input, 'my_delivery_orders')
                && str_contains($input, 'DO-AI-1') === false
                && ! str_contains($input, 'receivable_total')
                && ! str_contains($input, 'revenue_this_month')
                && ! str_contains($input, 'this_month_by_method')
                && str_contains($request['instructions'], 'Role aktif: sales');
        });
    }

    public function test_finance_scope_excludes_sales_pipeline(): void
    {
        $this->fakeProvider();

        $this->actingAs($this->userWithRole('finance'))
            ->postJson('/ai/chat', ['prompt' => 'Berapa piutang jatuh tempo?'])
            ->assertOk()
            ->assertJson(['role' => 'finance']);

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return str_contains($input, 'receivable_total')
                && str_contains($input, 'overdue_count')
                && ! str_contains($input, 'my_delivery_orders')
                && ! str_contains($input, 'my_top_products');
        });
    }

    public function test_admin_scope_has_full_aggregates(): void
    {
        $this->fakeProvider();

        $this->actingAs($this->userWithRole('admin'))
            ->postJson('/ai/chat', ['prompt' => 'Ringkas kondisi operasional'])
            ->assertOk()
            ->assertJson(['role' => 'admin']);

        Http::assertSent(function ($request) {
            $input = $request['input'];

            return str_contains($input, 'delivery_orders')
                && str_contains($input, 'invoices')
                && str_contains($input, 'payments')
                && str_contains($input, 'low_stock');
        });
    }

    public function test_disabled_feature_returns_safe_message_without_calling_provider(): void
    {
        config(['ai.enabled' => false, 'ai.api_key' => null]);
        Http::fake();

        $this->actingAs($this->userWithRole('admin'))
            ->postJson('/ai/chat', ['prompt' => 'Ringkas stok'])
            ->assertOk()
            ->assertJson([
                'available' => false,
                'role' => 'admin',
            ])
            ->assertJsonPath('answer', fn ($value) => str_contains($value, 'belum diaktifkan'));

        Http::assertNothingSent();
    }

    public function test_provider_failure_returns_safe_message(): void
    {
        config(['ai.enabled' => true, 'ai.api_key' => 'test-key']);
        Http::fake(['*/v1/responses' => Http::response(['error' => 'boom'], 500)]);

        $response = $this->actingAs($this->userWithRole('admin'))
            ->postJson('/ai/chat', ['prompt' => 'Ringkas stok'])
            ->assertOk();

        $this->assertFalse($response->json('available'));
        $this->assertStringNotContainsString('test-key', $response->json('answer'));
    }
}
