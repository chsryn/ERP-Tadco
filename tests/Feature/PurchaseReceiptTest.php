<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role = 'admin'): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function supplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'code' => 'SUP-EPFM',
            'name' => 'EPFM',
            'term_of_payment_days' => 30,
            'is_active' => true,
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_code' => 'TG-' . fake()->unique()->numerify('####'),
            'product_name' => 'Terigu Receipt',
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

    private function payload(array $items): array
    {
        return [
            'po_date' => '2026-09-05',
            'period' => '2026-09',
            'supplier_id' => $this->supplier()->id,
            'ppn_rate' => 11,
            'items' => $items,
        ];
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
     * Buat PO lewat alur normal SCM-03, lalu konfirmasi (status confirmed).
     */
    private function confirmedPurchaseOrder(array $items): PurchaseOrder
    {
        $this->post(route('purchase_orders.store'), $this->payload($items))->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::orderByDesc('id')->firstOrFail();

        $this->post(route('purchase_orders.confirm', $purchaseOrder))->assertSessionHasNoErrors();

        return $purchaseOrder->refresh();
    }

    private function receive(PurchaseOrder $purchaseOrder, array $items, array $overrides = [])
    {
        return $this->post(
            route('purchase_receipts.store', $purchaseOrder),
            array_merge(['receipt_date' => '2026-09-10'], $overrides, ['items' => $items])
        );
    }

    // ---------- akses ----------

    public function test_guest_cannot_access_receipts(): void
    {
        $this->actingAs($this->userWithRole());
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product())]);

        auth()->logout();

        $this->get(route('purchase_receipts.index'))->assertRedirect('/login');
        $this->get(route('purchase_receipts.create', $purchaseOrder))->assertRedirect('/login');
        $this->post(route('purchase_receipts.store', $purchaseOrder), ['receipt_date' => '2026-09-10', 'items' => []])
            ->assertRedirect('/login');
    }

    public function test_sales_and_finance_cannot_create_receipt(): void
    {
        $this->actingAs($this->userWithRole());
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product())]);
        $item = $purchaseOrder->items->first();

        foreach (['sales', 'finance'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('purchase_receipts.create', $purchaseOrder))
                ->assertForbidden();

            $this->actingAs($this->userWithRole($role))
                ->post(route('purchase_receipts.store', $purchaseOrder), [
                    'receipt_date' => '2026-09-10',
                    'items' => [$item->id => 10],
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('purchase_receipts', 0);
    }

    public function test_admin_can_open_receipt_pages(): void
    {
        $this->actingAs($this->userWithRole());
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product())]);

        $this->actingAs($this->userWithRole())
            ->get(route('purchase_receipts.index'))
            ->assertOk();

        $this->actingAs($this->userWithRole())
            ->get(route('purchase_receipts.create', $purchaseOrder))
            ->assertOk()
            ->assertSee($purchaseOrder->po_number)
            ->assertSee('Remaining');
    }

    // ---------- eligibility ----------

    public function test_confirmed_purchase_order_can_be_received(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 40])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('purchase_receipts', 1);
        $this->assertDatabaseCount('purchase_receipt_items', 1);
    }

    public function test_partial_purchase_order_can_be_received_again(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 40])->assertSessionHasNoErrors();

        $this->get(route('purchase_receipts.create', $purchaseOrder->fresh()))->assertOk();

        $this->receive($purchaseOrder, [$item->id => 20])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('purchase_receipts', 2);
        $this->assertSame(60.0, (float) $item->fresh()->qty_received);
    }

    public function test_draft_purchase_order_cannot_be_received(): void
    {
        $this->actingAs($this->userWithRole());

        $this->post(route('purchase_orders.store'), $this->payload([$this->itemPayload($this->product())]))
            ->assertSessionHasNoErrors();

        $draft = PurchaseOrder::firstOrFail();
        $item = $draft->items->first();

        $this->get(route('purchase_receipts.create', $draft))->assertForbidden();

        $this->receive($draft, [$item->id => 10])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_fully_received_purchase_order_cannot_be_received(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 30)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 30])->assertSessionHasNoErrors();
        $this->assertSame('received', $purchaseOrder->fresh()->status);

        $this->get(route('purchase_receipts.create', $purchaseOrder->fresh()))->assertForbidden();
        $this->receive($purchaseOrder, [$item->id => 1])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('purchase_receipts', 1);
    }

    public function test_cancelled_purchase_order_cannot_be_received(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product())]);
        $item = $purchaseOrder->items->first();

        $this->post(route('purchase_orders.cancel', $purchaseOrder));

        $this->get(route('purchase_receipts.create', $purchaseOrder->fresh()))->assertForbidden();
        $this->receive($purchaseOrder, [$item->id => 10])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertSame('cancelled', $purchaseOrder->fresh()->status);
    }

    // ---------- validasi quantity ----------

    public function test_quantity_zero_is_rejected(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 0])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertSame(0.0, (float) $item->fresh()->qty_received);
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => -5])->assertSessionHasErrors();

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertSame(0.0, (float) $item->fresh()->qty_received);
    }

    public function test_quantity_above_remaining_is_rejected(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 80])->assertSessionHasNoErrors();

        // remaining = 20, minta 21 → ditolak
        $this->receive($purchaseOrder, [$item->id => 21])->assertSessionHasErrors('items.' . $item->id);

        $this->assertSame(80.0, (float) $item->fresh()->qty_received);
        $this->assertSame('partial', $purchaseOrder->fresh()->status);
    }

    public function test_item_from_another_purchase_order_is_rejected(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);

        $this->post(route('purchase_orders.store'), [
            'po_date' => '2026-09-06',
            'period' => '2026-09',
            'supplier_id' => $this->supplier(['code' => 'SUP-LAIN'])->id,
            'ppn_rate' => 11,
            'items' => [$this->itemPayload($this->product(), 100)],
        ])->assertSessionHasNoErrors();

        $otherOrder = PurchaseOrder::orderByDesc('id')->firstOrFail();
        $this->post(route('purchase_orders.confirm', $otherOrder))->assertSessionHasNoErrors();
        $foreignItem = $otherOrder->items()->firstOrFail();

        $this->receive($purchaseOrder, [$foreignItem->id => 10])
            ->assertSessionHasErrors('items.' . $foreignItem->id);

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertSame(0.0, (float) $foreignItem->fresh()->qty_received);
    }

    public function test_receipt_without_any_item_is_rejected(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product())]);

        $this->receive($purchaseOrder, [])->assertSessionHasErrors('items');

        $this->post(route('purchase_receipts.store', $purchaseOrder), ['receipt_date' => '2026-09-10', 'items' => [1 => '']])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_receipts', 0);
    }

    public function test_receipt_date_is_required(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product())]);
        $item = $purchaseOrder->items->first();

        $this->post(route('purchase_receipts.store', $purchaseOrder), ['items' => [$item->id => 10]])
            ->assertSessionHasErrors('receipt_date');

        $this->assertDatabaseCount('purchase_receipts', 0);
    }

    // ---------- partial & full ----------

    public function test_partial_receipt_updates_qty_received_and_status(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 40])->assertSessionHasNoErrors();

        $this->assertSame(40.0, (float) $item->fresh()->qty_received);
        $this->assertSame(60.0, (float) $item->fresh()->qty_plan - (float) $item->fresh()->qty_received);
        $this->assertSame('partial', $purchaseOrder->fresh()->status);
    }

    public function test_second_receipt_completes_quantity_and_marks_received(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 40])->assertSessionHasNoErrors();
        $this->receive($purchaseOrder, [$item->id => 60])->assertSessionHasNoErrors();

        $this->assertSame(100.0, (float) $item->fresh()->qty_received);
        $this->assertSame('received', $purchaseOrder->fresh()->status);
        $this->assertDatabaseCount('purchase_receipts', 2);
    }

    public function test_all_items_received_marks_purchase_order_received(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([
            $this->itemPayload($this->product(), 100),
            $this->itemPayload($this->product(), 80),
        ]);

        [$first, $second] = $purchaseOrder->items;

        $this->receive($purchaseOrder, [$first->id => 100, $second->id => 80])->assertSessionHasNoErrors();

        $this->assertSame('received', $purchaseOrder->fresh()->status);
    }

    public function test_partially_received_multi_item_purchase_order_stays_partial(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([
            $this->itemPayload($this->product(), 100),
            $this->itemPayload($this->product(), 80),
        ]);

        [$first, $second] = $purchaseOrder->items;

        $this->receive($purchaseOrder, [$first->id => 100, $second->id => 50])->assertSessionHasNoErrors();

        $this->assertSame('partial', $purchaseOrder->fresh()->status);
        $this->assertSame(100.0, (float) $first->fresh()->qty_received);
        $this->assertSame(50.0, (float) $second->fresh()->qty_received);
    }

    // ---------- snapshot & qty ton ----------

    public function test_receipt_item_keeps_snapshot_weight_and_computes_qty_ton(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product(['weight_kg' => 25]);
        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 100])->assertSessionHasNoErrors();

        // berat produk diubah setelah PO dibuat → snapshot PO item tetap 25
        $product->update(['weight_kg' => 40]);

        $receiptItem = PurchaseReceipt::firstOrFail()->items->first();

        $this->assertSame($item->id, $receiptItem->purchase_order_item_id);
        $this->assertEqualsWithDelta(25.0, (float) $receiptItem->weight_kg, 0.001);
        $this->assertEqualsWithDelta(2.5, (float) $receiptItem->qty_ton, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $receiptItem->qty_received, 0.001);
    }

    public function test_receipt_number_uses_document_sequence_per_month(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 10], ['receipt_date' => '2026-09-10'])->assertSessionHasNoErrors();
        $this->receive($purchaseOrder, [$item->id => 10], ['receipt_date' => '2026-09-20'])->assertSessionHasNoErrors();
        $this->receive($purchaseOrder, [$item->id => 10], ['receipt_date' => '2026-10-02'])->assertSessionHasNoErrors();

        $numbers = PurchaseReceipt::orderBy('id')->pluck('receipt_number')->all();

        $this->assertSame(['001/09/2026', '002/09/2026', '001/10/2026'], $numbers);
        $this->assertDatabaseHas('document_sequences', [
            'document_type' => 'purchase_receipt',
            'period' => '2026-09',
            'last_number' => 2,
        ]);
    }

    // ---------- stock safety ----------

    public function test_receipt_does_not_touch_stock_balance_or_movements(): void
    {
        $this->actingAs($this->userWithRole());

        $product = $this->product();
        StockBalance::create([
            'product_id' => $product->id,
            'qty_available' => 40,
            'qty_reserved' => 10,
        ]);

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($product, 100)]);
        $item = $purchaseOrder->items->first();

        $balanceBefore = StockBalance::firstOrFail()->only(['qty_available', 'qty_reserved']);
        $movementsBefore = StockMovement::count();

        $this->receive($purchaseOrder, [$item->id => 100])->assertSessionHasNoErrors();

        $balance = StockBalance::firstOrFail();

        $this->assertEqualsWithDelta((float) $balanceBefore['qty_available'], (float) $balance->qty_available, 0.001);
        $this->assertEqualsWithDelta((float) $balanceBefore['qty_reserved'], (float) $balance->qty_reserved, 0.001);
        $this->assertSame($movementsBefore, StockMovement::count());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    // ---------- transaction / rollback ----------

    public function test_failed_validation_leaves_no_receipt_and_no_quantity_change(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 1000])->assertSessionHasErrors();

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertDatabaseCount('purchase_receipt_items', 0);
        $this->assertSame(0.0, (float) $item->fresh()->qty_received);
        $this->assertSame('confirmed', $purchaseOrder->fresh()->status);
    }

    public function test_rollback_reverts_receipt_and_sequence(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 100)]);
        $item = $purchaseOrder->items->first();

        // paksa gagal tepat setelah nomor receipt dibuat, di dalam transaction service
        PurchaseReceipt::creating(function () {
            throw new \RuntimeException('boom');
        });

        try {
            app(PurchaseReceiptService::class)->createReceipt(
                $purchaseOrder,
                ['receipt_date' => '2026-09-10', 'items' => [$item->id => 50]],
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            // expected
        }

        PurchaseReceipt::flushEventListeners();

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertDatabaseCount('purchase_receipt_items', 0);
        $this->assertSame(0.0, (float) $item->fresh()->qty_received);
        $this->assertSame('confirmed', $purchaseOrder->fresh()->status);
        $this->assertDatabaseMissing('document_sequences', ['document_type' => 'purchase_receipt']);

        // nomor berikutnya tetap mulai dari 001 (sequence tidak tertinggal)
        $this->receive($purchaseOrder, [$item->id => 50])->assertSessionHasNoErrors();
        $this->assertSame('001/09/2026', PurchaseReceipt::firstOrFail()->receipt_number);
    }

    // ---------- UI ----------

    public function test_receipt_show_page_displays_receipt_data(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(['product_name' => 'Terigu Receipt Show']), 100)]);
        $item = $purchaseOrder->items->first();

        $this->receive($purchaseOrder, [$item->id => 40], ['notes' => 'SJ-001'])->assertSessionHasNoErrors();

        $receipt = PurchaseReceipt::firstOrFail();

        $this->get(route('purchase_receipts.show', $receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee($purchaseOrder->po_number)
            ->assertSee('EPFM')
            ->assertSee('Terigu Receipt Show')
            ->assertSee('SJ-001')
            ->assertSee('Stock IN: belum diproses');

        $this->get(route('purchase_receipts.index'))
            ->assertOk()
            ->assertSee($receipt->receipt_number);
    }

    public function test_purchase_order_detail_shows_receive_action_only_when_receivable(): void
    {
        $this->actingAs($this->userWithRole());

        $purchaseOrder = $this->confirmedPurchaseOrder([$this->itemPayload($this->product(), 50)]);
        $item = $purchaseOrder->items->first();

        $this->get(route('purchase_orders.show', $purchaseOrder))
            ->assertOk()
            ->assertSee('Receive Goods');

        $this->receive($purchaseOrder, [$item->id => 50])->assertSessionHasNoErrors();

        $this->get(route('purchase_orders.show', $purchaseOrder->fresh()))
            ->assertOk()
            ->assertDontSee('Receive Goods');
    }
}
