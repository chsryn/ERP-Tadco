<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Alur penuh SCM-01 → SCM-07 (supplier → produk → Auto PO → confirm → receipt → stock IN).
 */
class ScmEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        return $user;
    }

    private function supplier(): Supplier
    {
        return Supplier::create([
            'code' => 'SUP-EPFM',
            'name' => 'EPFM',
            'term_of_payment_days' => 30,
            'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'product_code' => 'TG-E2E',
            'product_name' => 'Terigu E2E',
            'base_price' => 261000,
            'uom' => 'SACK',
            'net_weight' => '50KG',
            'segment' => 'Terigu',
            'is_active' => true,
            'dynamics_code' => 'FG-E2E-0001',
            'purchase_price' => 100000,
            'weight_kg' => 50,
            'target_stock' => 100,
        ]);
    }

    private function receive(PurchaseOrder $purchaseOrder, float $qty): PurchaseReceipt
    {
        $this->post(route('purchase_receipts.store', $purchaseOrder), [
            'receipt_date' => '2026-09-10',
            'items' => [$purchaseOrder->items()->firstOrFail()->id => $qty],
        ])->assertSessionHasNoErrors();

        return PurchaseReceipt::orderByDesc('id')->firstOrFail();
    }

    public function test_full_flow_from_reorder_until_stock_in(): void
    {
        $this->actingAs($this->admin());

        $supplier = $this->supplier();
        $product = $this->product();
        $balance = StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => 20,
            'qty_reserved' => 0,
        ]);

        // SCM-04: target 100 - available 20 - on order 0 = 80
        $suggested = app(ReorderService::class)->recommendations()
            ->firstWhere('id', $product->id)->suggested_qty;
        $this->assertEqualsWithDelta(80.0, (float) $suggested, 0.001);

        $this->post(route('purchase_orders.reorder.generate'), [
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'ppn_rate' => 11,
            'items' => [['product_id' => $product->id, 'qty_plan' => 80]],
        ])->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::firstOrFail();
        $item = $purchaseOrder->items()->firstOrFail();

        $this->assertSame('draft', $purchaseOrder->status);
        $this->assertSame('001/09/2026', $purchaseOrder->po_number);
        $this->assertEqualsWithDelta(80.0, (float) $item->qty_plan, 0.001);
        $this->assertEqualsWithDelta(100000.0, (float) $item->price, 0.001);
        $this->assertEqualsWithDelta(4.0, (float) $item->qty_ton, 0.001);

        // confirm tidak boleh mengubah stok
        $this->post(route('purchase_orders.confirm', $purchaseOrder))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $purchaseOrder->fresh()->status);
        $this->assertEqualsWithDelta(20.0, (float) $balance->fresh()->qty_available, 0.001);
        $this->assertDatabaseCount('stock_movements', 0);

        // receipt partial: qty 30 dari 80, stok masih 20
        $firstReceipt = $this->receive($purchaseOrder, 30);
        $this->assertSame('partial', $purchaseOrder->fresh()->status);
        $this->assertEqualsWithDelta(20.0, (float) $balance->fresh()->qty_available, 0.001);
        $this->assertDatabaseCount('stock_movements', 0);

        // Stock IN pertama: 20 + 30 = 50
        $this->post(route('purchase_receipts.stock_in', $firstReceipt))->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(50.0, (float) $balance->fresh()->qty_available, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $balance->fresh()->qty_reserved, 0.001);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'in',
            'source_type' => 'po_receipt',
            'source_id' => $firstReceipt->id,
            'qty' => 30,
        ]);

        // Stock IN kedua untuk receipt yang sama ditolak
        $this->post(route('purchase_receipts.stock_in', $firstReceipt))
            ->assertSessionHasErrors('stock_in');
        $this->assertEqualsWithDelta(50.0, (float) $balance->fresh()->qty_available, 0.001);
        $this->assertDatabaseCount('stock_movements', 1);

        // receipt kedua melunasi sisa 50 → PO received
        $secondReceipt = $this->receive($purchaseOrder, 50);
        $this->assertSame('received', $purchaseOrder->fresh()->status);
        $this->assertSame('received', PurchaseOrder::firstOrFail()->status);

        $this->post(route('purchase_receipts.stock_in', $secondReceipt))->assertSessionHasNoErrors();

        $balance->refresh();
        $this->assertEqualsWithDelta(100.0, (float) $balance->qty_available, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $balance->qty_reserved, 0.001);
        $this->assertSame(2, StockMovement::query()->where('source_type', 'po_receipt')->count());
    }

    public function test_po_and_receipt_number_sequences_do_not_interfere(): void
    {
        $this->actingAs($this->admin());

        $supplier = $this->supplier();
        $product = $this->product();

        $this->post(route('purchase_orders.store'), [
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'ppn_rate' => 11,
            'items' => [['product_id' => $product->id, 'qty_plan' => 10]],
        ])->assertSessionHasNoErrors();

        $firstOrder = PurchaseOrder::firstOrFail();
        $this->post(route('purchase_orders.confirm', $firstOrder));

        $this->receive($firstOrder, 10);
        $this->assertSame('001/09/2026', PurchaseReceipt::firstOrFail()->receipt_number);

        // receipt memakai sequence sendiri → PO berikutnya tetap 002
        $this->post(route('purchase_orders.store'), [
            'po_date' => '2026-09-06',
            'period' => '2026-09',
            'supplier_id' => $supplier->id,
            'ppn_rate' => 11,
            'items' => [['product_id' => $product->id, 'qty_plan' => 10]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['001/09/2026', '002/09/2026'],
            PurchaseOrder::orderBy('id')->pluck('po_number')->all()
        );
    }
}
