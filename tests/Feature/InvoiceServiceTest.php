<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $service;
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
            'sales_id' => $this->user->id,
            'do_date' => '2026-08-04',
            'total_amount' => 500000.00,
            'status' => 'shipped',
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id' => $this->deliveryOrder->id,
            'product_id' => $this->product->id,
            'qty' => 5,
            'base_price' => 100000,
            'discount_percentage' => 0.0,
            'final_price' => 100000,
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

    public function test_create_invoice_rejects_do_item_with_zero_price(): void
    {
        $this->deliveryOrder->items()->update([
            'base_price' => 0,
            'final_price' => 0,
            'line_total' => 0,
        ]);

        try {
            $this->service->createInvoice([
                'delivery_order_id' => $this->deliveryOrder->id,
                'invoice_date' => '2026-08-04',
            ], $this->user->id);
            $this->fail('Invoice seharusnya ditolak saat item DO memiliki harga nol.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('harga nol/tidak valid', $e->errors()['delivery_order_id'][0]);
            $this->assertStringContainsString('PROD01', $e->errors()['delivery_order_id'][0]);
        }

        $this->assertSame(0, Invoice::count());
        $this->assertDatabaseCount('invoice_items', 0);
    }

    public function test_create_invoice_rejects_zero_qty_item(): void
    {
        $this->deliveryOrder->items()->update(['qty' => 0]);

        try {
            $this->service->createInvoice([
                'delivery_order_id' => $this->deliveryOrder->id,
                'invoice_date' => '2026-08-04',
            ], $this->user->id);
            $this->fail('Invoice seharusnya ditolak saat qty item DO nol.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Qty item', $e->errors()['delivery_order_id'][0]);
        }

        $this->assertSame(0, Invoice::count());
    }

    public function test_create_invoice_rejects_inconsistent_line_total(): void
    {
        $this->deliveryOrder->items()->update(['line_total' => 400000]);

        try {
            $this->service->createInvoice([
                'delivery_order_id' => $this->deliveryOrder->id,
                'invoice_date' => '2026-08-04',
            ], $this->user->id);
            $this->fail('Invoice seharusnya ditolak saat line_total tidak konsisten.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tidak konsisten', $e->errors()['delivery_order_id'][0]);
        }

        $this->assertSame(0, Invoice::count());
    }

    public function test_create_invoice_with_discount_copies_final_price_and_totals(): void
    {
        $this->deliveryOrder->items()->update([
            'qty' => 10,
            'base_price' => 1000,
            'discount_percentage' => 2,
            'final_price' => 980,
            'line_total' => 9800,
        ]);

        $invoice = $this->service->createInvoice([
            'delivery_order_id' => $this->deliveryOrder->id,
            'invoice_date' => '2026-08-04',
        ], $this->user->id);

        $this->assertSame(9800.0, (float) $invoice->grand_total);
        $this->assertSame(9800.0, (float) $invoice->receivable_amount);

        $invoiceItem = $invoice->items()->first();
        $this->assertSame(980.0, (float) $invoiceItem->final_price);
        $this->assertSame(9800.0, (float) $invoiceItem->line_total);
        $this->assertSame(
            (float) $invoiceItem->line_total,
            (int) $invoiceItem->qty * (float) $invoiceItem->final_price
        );
    }
}
