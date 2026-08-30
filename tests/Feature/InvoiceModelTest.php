<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected DeliveryOrder $deliveryOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'customer_code' => 'CUST01',
            'customer_name' => 'Test Customer',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'warehouse_code' => 'WH01',
            'warehouse_name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->deliveryOrder = DeliveryOrder::create([
            'do_number' => 'DO-20260804-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'sales_id' => $this->user->id,
            'do_date' => '2026-08-04',
            'total_amount' => 500000.00,
            'status' => 'shipped',
        ]);
    }

    public function test_auto_generates_invoice_number_and_calculates_due_date(): void
    {
        $invoice = Invoice::create([
            'delivery_order_id' => $this->deliveryOrder->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-08-14',
            'payment_term_days' => 14,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'receivable_amount' => 100000,
            'status' => 'unpaid',
            'created_by' => $this->user->id,
        ]);

        // Assert auto-generated invoice number format: 1/SI/TTP/VIII/2026
        $this->assertEquals('1/SI/TTP/VIII/2026', $invoice->invoice_number);

        // Assert due date calculation: 2026-08-14 + 14 days = 2026-08-28
        $this->assertEquals('2026-08-28', $invoice->due_date->format('Y-m-d'));
    }
}
