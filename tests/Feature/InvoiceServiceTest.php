<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $service;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Product $product;
    protected User $user;
    protected DeliveryOrder $deliveryOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InvoiceService();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'warehouse_code' => 'WH01',
            'warehouse_name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST01',
            'customer_name' => 'Test Customer',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'product_code' => 'PROD01',
            'product_name' => 'Test Product',
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

        DeliveryOrderItem::create([
            'delivery_order_id' => $this->deliveryOrder->id,
            'product_id' => $this->product->id,
            'tier_code' => 'S1',
            'qty' => 5,
            'unit_price' => 100000,
            'discount_rate' => 0.0,
            'line_total' => 500000.00,
        ]);
    }

    public function test_create_invoice_successfully(): void
    {
        $data = [
            'delivery_order_id' => $this->deliveryOrder->id,
            'invoice_date' => '2026-08-04',
            'payment_term_days' => 30,
            'notes' => 'Invoice Test',
        ];

        $invoice = $this->service->createInvoice($data, $this->user->id);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(500000.00, (float) $invoice->grand_total);
        $this->assertEquals(500000.00, (float) $invoice->receivable_amount);
        $this->assertEquals(0.00, (float) $invoice->paid_total);
    }

    public function test_create_invoice_fails_if_not_shipped(): void
    {
        $this->expectException(ValidationException::class);

        $this->deliveryOrder->update(['status' => 'validated']);

        $data = [
            'delivery_order_id' => $this->deliveryOrder->id,
            'invoice_date' => '2026-08-04',
        ];

        $this->service->createInvoice($data, $this->user->id);
    }
}
