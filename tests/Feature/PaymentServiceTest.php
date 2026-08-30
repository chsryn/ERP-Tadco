<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $service;
    protected Customer $customer;
    protected User $user;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PaymentService();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $warehouse = Warehouse::create([
            'warehouse_code' => 'WH01',
            'warehouse_name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST01',
            'customer_name' => 'Test Customer',
            'is_active' => true,
        ]);

        $do = DeliveryOrder::create([
            'do_number' => 'DO-20260804-002',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouse->id,
            'sales_id' => $this->user->id,
            'do_date' => '2026-08-04',
            'total_amount' => 1000000.00,
            'status' => 'shipped',
        ]);

        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-20260804-001',
            'delivery_order_id' => $do->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-08-04',
            'subtotal' => 1000000.00,
            'discount_total' => 0.00,
            'grand_total' => 1000000.00,
            'paid_total' => 0.00,
            'receivable_amount' => 1000000.00,
            'status' => 'unpaid',
        ]);
    }

    public function test_partial_payment_updates_invoice_status_to_partial_paid(): void
    {
        $data = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => '2026-08-04',
            'payment_method' => 'transfer',
            'amount' => 400000.00,
            'bank_name' => 'BCA',
            'reference_no' => 'REF12345',
        ];

        $payment = $this->service->createPayment($data, $this->user->id);

        $this->assertInstanceOf(Payment::class, $payment);

        $this->invoice->refresh();
        $this->assertEquals('unpaid', $this->invoice->status);
        $this->assertEquals(400000.00, (float) $this->invoice->paid_total);
        $this->assertEquals(600000.00, (float) $this->invoice->receivable_amount);
    }

    public function test_full_payment_updates_invoice_status_to_paid(): void
    {
        $data = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => '2026-08-04',
            'payment_method' => 'cash',
            'amount' => 1000000.00,
        ];

        $this->service->createPayment($data, $this->user->id);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals(1000000.00, (float) $this->invoice->paid_total);
        $this->assertEquals(0.00, (float) $this->invoice->receivable_amount);
    }

    public function test_payment_exceeding_receivable_amount_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => '2026-08-04',
            'payment_method' => 'transfer',
            'amount' => 1500000.00, // Exceeds 1,000,000
        ];

        $this->service->createPayment($data, $this->user->id);
    }

    public function test_delete_payment_reverts_invoice_status_and_receivable(): void
    {
        $data = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => '2026-08-04',
            'payment_method' => 'cash',
            'amount' => 1000000.00,
        ];

        $payment = $this->service->createPayment($data, $this->user->id);
        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);

        $this->service->deletePayment($payment);

        $this->invoice->refresh();
        $this->assertEquals('unpaid', $this->invoice->status);
        $this->assertEquals(0.00, (float) $this->invoice->paid_total);
        $this->assertEquals(1000000.00, (float) $this->invoice->receivable_amount);
    }
}
