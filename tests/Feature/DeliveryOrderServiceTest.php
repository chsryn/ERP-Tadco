<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeliveryOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeliveryOrderService $service;
    protected Customer $customer;
    protected Product $product;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DeliveryOrderService();

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
            'base_price' => 100000,
            'uom' => 'BOX',
            'is_active' => true,
        ]);

        ProductDiscount::create([
            'product_id' => $this->product->id,
            'strata_level' => 'S1',
            'discount_percentage' => 10.00,
        ]);

        StockBalance::create([
            'product_id' => $this->product->id,
            'qty_available' => 100,
            'qty_reserved' => 0,
        ]);
    }

    private function doData(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'do_date' => '2026-08-04',
        ];
    }

    public function test_create_delivery_order_stores_net_price_without_double_discount(): void
    {
        // Harga dasar Rp100.000, diskon 2%, qty 2 -> harga bersih Rp98.000,
        // line total Rp196.000. Diskon tidak boleh diterapkan dua kali.
        $this->product->update(['base_price' => 100000]);
        $this->product->discounts()->update(['discount_percentage' => 2]);

        $deliveryOrder = $this->service->createDeliveryOrder(
            $this->doData(),
            [['product_id' => $this->product->id, 'qty' => 2]],
            $this->user->id
        );

        $item = $deliveryOrder->items()->firstOrFail();

        $this->assertSame(2, $item->qty);
        $this->assertSame(100000.0, (float) $item->base_price);
        $this->assertSame(2.0, (float) $item->discount_percentage);
        $this->assertSame(98000.0, (float) $item->final_price);
        $this->assertSame(196000.0, (float) $item->line_total);
        $this->assertSame(
            (float) $item->line_total,
            (int) $item->qty * (float) $item->final_price
        );
        $this->assertSame(196000.0, (float) $deliveryOrder->total_amount);
        $this->assertSame(
            2.0,
            (float) StockBalance::where('product_id', $this->product->id)->value('qty_reserved')
        );
    }

    public function test_create_delivery_order_rejects_product_without_valid_price(): void
    {
        $this->product->update(['base_price' => 0]);

        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => $this->product->id, 'qty' => 10]],
                $this->user->id
            );
            $this->fail('DO seharusnya ditolak saat base_price produk tidak valid.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum memiliki harga dasar', $e->errors()['price'][0]);
            $this->assertStringContainsString('PROD01', $e->errors()['price'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
        $this->assertSame(
            0.0,
            (float) StockBalance::where('product_id', $this->product->id)->value('qty_reserved')
        );
    }

    public function test_create_delivery_order_rejects_inactive_product(): void
    {
        $this->product->update(['is_active' => false]);

        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => $this->product->id, 'qty' => 10]],
                $this->user->id
            );
            $this->fail('DO seharusnya ditolak untuk produk inactive.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tidak aktif', $e->errors()['product_id'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
    }

    public function test_create_delivery_order_rejects_missing_product(): void
    {
        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => 999999, 'qty' => 1]],
                $this->user->id
            );
            $this->fail('DO seharusnya ditolak saat produk tidak ditemukan.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tidak ditemukan', $e->errors()['product_id'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
    }

    public function test_create_delivery_order_rejects_fractional_qty(): void
    {
        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => $this->product->id, 'qty' => 1.5]],
                $this->user->id
            );
            $this->fail('DO seharusnya menolak qty desimal.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('bilangan bulat', $e->errors()['qty'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
        $this->assertSame(
            0.0,
            (float) StockBalance::where('product_id', $this->product->id)->value('qty_reserved')
        );
    }

    public function test_create_delivery_order_rejects_zero_qty(): void
    {
        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => $this->product->id, 'qty' => 0]],
                $this->user->id
            );
            $this->fail('DO seharusnya menolak qty nol.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('bilangan bulat lebih dari 0', $e->errors()['qty'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
    }

    public function test_create_delivery_order_rejects_overlapping_discount_ranges(): void
    {
        // S1 (min 0, max null) dan S2 (min 0, max null) sama-sama cocok.
        ProductDiscount::create([
            'product_id' => $this->product->id,
            'strata_level' => 'S2',
            'min_qty' => 0,
            'max_qty' => null,
            'discount_percentage' => 5,
        ]);

        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => $this->product->id, 'qty' => 10]],
                $this->user->id
            );
            $this->fail('DO seharusnya ditolak saat rentang strata tumpang tindih.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('lebih dari satu strata', $e->errors()['discount'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
    }

    public function test_create_delivery_order_rejects_zero_final_price(): void
    {
        $this->product->discounts()->update(['discount_percentage' => 100]);

        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [['product_id' => $this->product->id, 'qty' => 10]],
                $this->user->id
            );
            $this->fail('DO seharusnya ditolak saat harga bersih menjadi nol.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Harga bersih', $e->errors()['price'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
    }

    public function test_create_delivery_order_is_fully_rolled_back_when_one_item_is_invalid(): void
    {
        $secondProduct = Product::create([
            'product_code' => 'PROD02',
            'product_name' => 'Produk Tanpa Harga',
            'base_price' => 0,
            'uom' => 'BOX',
            'is_active' => true,
        ]);

        StockBalance::create([
            'product_id' => $secondProduct->id,
            'qty_available' => 100,
            'qty_reserved' => 0,
        ]);

        try {
            $this->service->createDeliveryOrder(
                $this->doData(),
                [
                    ['product_id' => $this->product->id, 'qty' => 10],
                    ['product_id' => $secondProduct->id, 'qty' => 5],
                ],
                $this->user->id
            );
            $this->fail('DO seharusnya ditolak seluruhnya jika salah satu item tidak valid.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum memiliki harga dasar', $e->errors()['price'][0]);
        }

        $this->assertSame(0, DeliveryOrder::count());
        $this->assertSame(0, DeliveryOrderItem::count());
        $this->assertSame(
            0.0,
            (float) StockBalance::where('product_id', $this->product->id)->value('qty_reserved')
        );
        $this->assertSame(
            0.0,
            (float) StockBalance::where('product_id', $secondProduct->id)->value('qty_reserved')
        );
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
            ],
        ];

        $do = $this->service->createDeliveryOrder($data, $items, $this->user->id);

        $this->assertInstanceOf(DeliveryOrder::class, $do);
        $this->assertEquals('validated', $do->status);
        $this->assertEquals(900000.0, (float) $do->total_amount);

        $stockBalance = StockBalance::where('product_id', $this->product->id)
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
            ],
        ];

        $do = $this->service->createDeliveryOrder($data, $items, $this->user->id);

        $cancelledDo = $this->service->cancelDeliveryOrder($do);

        $this->assertEquals('cancelled', $cancelledDo->status);

        $stockBalance = StockBalance::where('product_id', $this->product->id)
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
            ],
        ];

        $do = $this->service->createDeliveryOrder($data, $items, $this->user->id);

        $deliveredDo = $this->service->markAsDelivered($do, '2026-08-10', $this->user->id);

        $this->assertEquals('delivered', $deliveredDo->status);
        $this->assertEquals('2026-08-10', $deliveredDo->received_date->format('Y-m-d'));

        $stockBalance = StockBalance::where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(0.0, (float) $stockBalance->qty_reserved);
        $this->assertEquals(85.0, (float) $stockBalance->qty_available);
    }
}
