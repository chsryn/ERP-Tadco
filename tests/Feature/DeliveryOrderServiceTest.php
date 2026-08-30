<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DeliveryOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeliveryOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeliveryOrderService $service;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Product $product;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DeliveryOrderService();

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
            'base_price' => 100000,
            'uom' => 'BOX',
            'is_active' => true,
        ]);

        \App\Models\ProductDiscount::create([
            'product_id' => $this->product->id,
            'strata_level' => 'S1',
            'discount_percentage' => 10.00,
        ]);

        StockBalance::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'qty_available' => 100,
            'qty_reserved' => 0,
        ]);
    }

    public function test_create_delivery_order_successfully_reserves_stock(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'do_date' => '2026-08-04',
            'planned_delivery_date' => '2026-08-05',
            'notes' => 'Testing DO creation',
        ];

        $items = [
            [
                'product_id' => $this->product->id,
                'qty' => 10,
                'tier_code' => 'S1',
            ],
        ];

        $do = $this->service->createDeliveryOrder($data, $items, $this->user->id);

        $this->assertInstanceOf(DeliveryOrder::class, $do);
        $this->assertEquals('validated', $do->status);
        $this->assertEquals(900000.0, (float) $do->total_amount);

        $stockBalance = StockBalance::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(10.0, (float) $stockBalance->qty_reserved);
    }

    public function test_create_delivery_order_fails_when_insufficient_stock(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customer_id' => $this->customer->id,
            'do_date' => '2026-08-04',
        ];

        $items = [
            [
                'product_id' => $this->product->id,
                'qty' => 200, // Exceeds 100 available
                'tier_code' => 'S1',
            ],
        ];

        $this->service->createDeliveryOrder($data, $items, $this->user->id);
    }

    public function test_cancel_delivery_order_releases_reserved_stock(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'do_date' => '2026-08-04',
        ];

        $items = [
            [
                'product_id' => $this->product->id,
                'qty' => 15,
                'tier_code' => 'S1',
            ],
        ];

        $do = $this->service->createDeliveryOrder($data, $items, $this->user->id);

        $cancelledDo = $this->service->cancelDeliveryOrder($do);

        $this->assertEquals('cancelled', $cancelledDo->status);

        $stockBalance = StockBalance::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(0.0, (float) $stockBalance->qty_reserved);
    }

    public function test_mark_as_delivered_updates_status_received_date_and_deducts_stock(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'do_date' => '2026-08-04',
        ];

        $items = [
            [
                'product_id' => $this->product->id,
                'qty' => 15,
                'tier_code' => 'S1',
            ],
        ];

        $do = $this->service->createDeliveryOrder($data, $items, $this->user->id);

        $deliveredDo = $this->service->markAsDelivered($do, '2026-08-10', $this->user->id);

        $this->assertEquals('delivered', $deliveredDo->status);
        $this->assertEquals('2026-08-10', $deliveredDo->received_date->format('Y-m-d'));

        $stockBalance = StockBalance::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(0.0, (float) $stockBalance->qty_reserved);
        $this->assertEquals(85.0, (float) $stockBalance->qty_available);
    }
}
