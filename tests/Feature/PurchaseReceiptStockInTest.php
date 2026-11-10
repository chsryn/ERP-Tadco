<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseReceiptStockInTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role = 'admin'): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

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

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_code' => 'TG-' . fake()->unique()->numerify('####'),
            'product_name' => 'Terigu Stock In',
            'base_price' => 261000,
            'uom' => 'SACK',
            'net_weight' => '25KG',
            'segment' => 'Terigu',
            'is_active' => true,
            'dynamics_code' => 'FG-' . fake()->unique()->numerify('####'),
            'purchase_price' => 100000,
            'weight_kg' => 25,
            'target_stock' => 500,
        ], $overrides));
    }

    private function itemPayload(Product $product, float $qty = 100): array
    {
        return [
            'product_id' => $product->id,
            'qty_plan' => $qty,
            'price' => 100000,
            'special_discount' => 0,
        ];
    }

    /**
     * PO confirmed lewat alur SCM-03. Aktor harus sudah login sebelum memanggil ini.
     */
    private function confirmedPurchaseOrder(array $items): PurchaseOrder
    {
        $this->post(route('purchase_orders.store'), [
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $this->supplier()->id,
            'ppn_rate' => 11,
            'items' => $items,
        ])->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::orderByDesc('id')->firstOrFail();

        $this->post(route('purchase_orders.confirm', $purchaseOrder))->assertSessionHasNoErrors();

        return $purchaseOrder->refresh();
    }

    private function receive(PurchaseOrder $purchaseOrder, array $items): PurchaseReceipt
    {
        $this->post(route('purchase_receipts.store', $purchaseOrder), [
            'receipt_date' => '2026-09-10',
            'items' => $items,
        ])->assertSessionHasNoErrors();

        return PurchaseReceipt::orderByDesc('id')->firstOrFail();
    }

    private function process(PurchaseReceipt $receipt)
    {
        return $this->post(route('purchase_receipts.stock_in', $receipt));
    }

    // ---------- akses ----------

    public function test_guest_cannot_process_stock_in(): void
    {
        $this->actingAs($this->userWithRole());
        $product = $this->product();
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 40)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        auth()->logout();

        $this->process($receipt)->assertRedirect('/login');

        $this->assertSame(0, StockMovement::count());
        $this->assertDatabaseMissing('stock_balances', ['product_id' => $product->id]);
    }

    public function test_sales_and_finance_cannot_process_stock_in(): void
    {
        $this->actingAs($this->userWithRole());
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 40)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        foreach (['sales', 'finance'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->post(route('purchase_receipts.stock_in', $receipt))
                ->assertForbidden();
        }

        $this->assertSame(0, StockMovement::count());
    }

    // ---------- stock in dasar ----------

    public function test_admin_can_process_stock_in_and_balance_increases(): void
    {
        $admin = $this->userWithRole();
        $this->actingAs($admin);

        $product = $this->product();
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => 100,
            'qty_reserved' => 20,
        ]);

        $this->process($receipt)
            ->assertRedirect(route('purchase_receipts.show', $receipt))
            ->assertSessionHasNoErrors();

        $balance = StockBalance::where('product_id', $product->id)->firstOrFail();

        $this->assertEqualsWithDelta(140.0, (float) $balance->qty_available, 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $balance->qty_reserved, 0.001);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_stock_in_uses_receipt_quantity_not_po_plan(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product();
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(40.0, (float) StockBalance::firstOrFail()->qty_available, 0.001);
        $this->assertEqualsWithDelta(40.0, (float) StockMovement::firstOrFail()->qty, 0.001);
    }

    public function test_multi_item_receipt_creates_one_movement_per_item(): void
    {
        $this->actingAs($this->userWithRole());

        $first = $this->product(['product_name' => 'Terigu A']);
        $second = $this->product(['product_name' => 'Terigu B']);
        $third = $this->product(['product_name' => 'Terigu C']);

        $purchaseOrder = $this->confirmedPurchaseOrder([
            $this->itemPayload($first, 100),
            $this->itemPayload($second, 80),
            $this->itemPayload($third, 50),
        ]);

        [$itemA, $itemB, $itemC] = $purchaseOrder->items;

        $receipt = $this->receive($purchaseOrder, [
            $itemA->id => 100,
            $itemB->id => 50,
            $itemC->id => 25,
        ]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $this->assertSame(3, StockMovement::count());
        $this->assertEqualsWithDelta(100.0, (float) StockBalance::where('product_id', $first->id)->value('qty_available'), 0.001);
        $this->assertEqualsWithDelta(50.0, (float) StockBalance::where('product_id', $second->id)->value('qty_available'), 0.001);
        $this->assertEqualsWithDelta(25.0, (float) StockBalance::where('product_id', $third->id)->value('qty_available'), 0.001);
    }

    public function test_stock_in_creates_missing_stock_balance_row(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product();
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 30)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 30]);

        $this->assertDatabaseMissing('stock_balances', ['product_id' => $product->id]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $product->id,
            'qty_available' => 30,
            'qty_reserved' => 0,
        ]);
    }

    public function test_movement_is_traceable_to_receipt_and_user(): void
    {
        $admin = $this->userWithRole();
        $this->actingAs($admin);

        $product = $this->product();
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 60]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $movement = StockMovement::firstOrFail();

        $this->assertSame('in', $movement->movement_type);
        $this->assertSame(StockService::SOURCE_PO_RECEIPT, $movement->source_type);
        $this->assertSame($receipt->id, $movement->source_id);
        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame($admin->id, $movement->created_by);
        $this->assertEqualsWithDelta(60.0, (float) $movement->qty, 0.001);
        $this->assertSame('2026-09-10', $movement->movement_date->toDateString());
    }

    public function test_stock_in_does_not_change_receipt_quantity_or_po_status(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $this->assertSame(1, PurchaseReceipt::count());
        $this->assertSame(1, $receipt->items()->count());
        $this->assertEqualsWithDelta(40.0, (float) $receipt->items()->first()->qty_received, 0.001);
        $this->assertEqualsWithDelta(40.0, (float) $purchaseOrder->items()->first()->qty_received, 0.001);
        $this->assertSame('partial', $purchaseOrder->fresh()->status);
        $this->assertSame('posted', $receipt->fresh()->status);
    }

    // ---------- idempotency ----------

    public function test_second_process_is_rejected_and_stock_not_increased(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $this->process($receipt)->assertSessionHasNoErrors();
        $this->process($receipt)->assertSessionHasErrors('stock_in');

        $this->assertEqualsWithDelta(40.0, (float) StockBalance::firstOrFail()->qty_available, 0.001);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_service_rejects_duplicate_processing(): void
    {
        $this->actingAs($admin = $this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $service = app(StockService::class);
        $service->processPurchaseReceipt($receipt, $admin->id);

        $this->expectException(ValidationException::class);

        try {
            $service->processPurchaseReceipt($receipt->fresh(), $admin->id);
        } finally {
            $this->assertSame(1, StockMovement::count());
            $this->assertEqualsWithDelta(40.0, (float) StockBalance::firstOrFail()->qty_available, 0.001);
        }
    }

    public function test_processed_receipt_hides_process_action(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 40)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $this->get(route('purchase_receipts.show', $receipt))
            ->assertOk()
            ->assertSee('Process Stock IN')
            ->assertDontSee('Stock IN sudah diproses');

        $this->process($receipt)->assertSessionHasNoErrors();

        $this->get(route('purchase_receipts.show', $receipt))
            ->assertOk()
            ->assertSee('Stock IN sudah diproses')
            ->assertDontSee('Process Stock IN');
    }

    public function test_receipt_that_is_not_posted_cannot_be_processed(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 40)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $receipt->update(['status' => 'cancelled']);

        $this->process($receipt)->assertSessionHasErrors('stock_in');

        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, StockBalance::count());
    }

    public function test_stock_report_reads_updated_balance(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(['product_name' => 'Terigu Laporan']), 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $this->get(route('reports.stock'))
            ->assertOk()
            ->assertSee('Terigu Laporan')
            ->assertViewHas('totalAvailable', fn ($total) => (float) $total === 40.0);
    }

    public function test_stock_movement_appears_in_inventory_history(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product(['product_name' => 'Terigu Riwayat']);
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        $this->process($receipt)->assertSessionHasNoErrors();

        $balance = StockBalance::firstOrFail();

        $this->get(route('inventory.show', $balance))
            ->assertOk()
            ->assertSee('Terigu Riwayat')
            ->assertSee('Penerimaan barang PO ' . $purchaseOrder->po_number);
    }

    // ---------- rollback ----------

    public function test_rollback_when_movement_creation_fails(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product();
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $receipt = $this->receive($purchaseOrder, [$purchaseOrder->items->first()->id => 40]);

        StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => 100,
            'qty_reserved' => 20,
        ]);

        // gagal tepat setelah qty_available dinaikkan, sebelum commit
        StockMovement::creating(function () {
            throw new \RuntimeException('boom');
        });

        try {
            app(StockService::class)->processPurchaseReceipt($receipt, auth()->id());
        } catch (\RuntimeException $e) {
            // expected
        } finally {
            StockMovement::flushEventListeners();
        }

        $balance = StockBalance::firstOrFail();

        $this->assertEqualsWithDelta(100.0, (float) $balance->qty_available, 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $balance->qty_reserved, 0.001);
        $this->assertSame(0, StockMovement::count());
        $this->assertFalse(app(StockService::class)->receiptProcessed($receipt));

        // setelah rollback, receipt masih bisa diproses normal
        $this->process($receipt)->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(140.0, (float) $balance->fresh()->qty_available, 0.001);
        $this->assertSame(1, StockMovement::count());
    }
}
