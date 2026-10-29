<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Auto PO = rekomendasi pengadaan + draft PO.
 * Tidak menyentuh stock_balances/stock_movements dan tidak pernah confirm sendiri.
 */
class ReorderService
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Rekomendasi real-time per produk (satu query agregat, tanpa N+1).
     *
     * available_for_reorder = qty_available - qty_reserved
     * suggested_qty         = max(0, target_stock - available_for_reorder - qty_on_order)
     */
    public function recommendations(): Collection
    {
        return Product::query()
            ->leftJoinSub($this->qtyOnOrderQuery(), 'on_order', 'on_order.product_id', '=', 'products.id')
            ->leftJoin('stock_balances', 'stock_balances.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->whereNotNull('products.target_stock')
            ->where('products.target_stock', '>', 0)
            ->whereNotNull('products.purchase_price')
            ->whereNotNull('products.weight_kg')
            ->orderBy('products.product_name')
            ->get([
                'products.id',
                'products.product_code',
                'products.product_name',
                'products.dynamics_code',
                'products.weight_kg',
                'products.purchase_price',
                'products.target_stock',
                'stock_balances.qty_available as qty_available',
                'stock_balances.qty_reserved as qty_reserved',
                'on_order.qty_on_order as qty_on_order',
            ])
            ->map(function (Product $row) {
                $available = round((float) $row->qty_available - (float) $row->qty_reserved, 2);
                $suggested = round((float) $row->target_stock - $available - (float) $row->qty_on_order, 2);

                $row->available_for_reorder = $available;
                $row->suggested_qty = max(0, $suggested);

                return $row;
            })
            // produk yang perlu dibeli tampil lebih dulu, sisanya urut nama
            ->sortBy([['suggested_qty', 'desc'], ['product_name', 'asc']])
            ->values();
    }

    public function getQtyOnOrder(int $productId): float
    {
        $row = $this->qtyOnOrderQuery()
            ->where('purchase_order_items.product_id', $productId)
            ->first();

        return (float) ($row->qty_on_order ?? 0);
    }

    /**
     * Generate draft PO dari item pilihan user.
     * Numbering, harga, PPN, alokasi mingguan, dan snapshot tetap milik PurchaseOrderService (SCM-03).
     */
    public function generateDraft(array $data, ?int $userId): PurchaseOrder
    {
        $items = collect($data['items'])
            ->map(fn ($row) => [
                'product_id' => (int) $row['product_id'],
                'qty_plan' => $row['qty_plan'],
            ])
            ->all();

        return $this->purchaseOrderService->generateDraft([
            'po_date' => $data['po_date'],
            'period' => $data['period'],
            'supplier_id' => $data['supplier_id'],
            'ppn_rate' => $data['ppn_rate'] ?? PurchaseOrderService::PPN_RATE_DEFAULT,
            'notes' => filled($data['notes'] ?? null) ? $data['notes'] : 'Dibuat dari rekomendasi Auto PO',
            'items' => $items,
        ], $userId);
    }

    /**
     * SUM(qty_plan - qty_received) per produk untuk PO yang masih outstanding.
     * draft tidak dihitung agar draft yang baru dibuat tidak menggeser rekomendasi;
     * cancelled & received juga tidak dihitung.
     */
    private function qtyOnOrderQuery(): Builder
    {
        return DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->whereIn('purchase_orders.status', [
                PurchaseOrderService::STATUS_CONFIRMED,
                PurchaseOrderService::STATUS_PARTIAL,
            ])
            ->groupBy('purchase_order_items.product_id')
            ->selectRaw('purchase_order_items.product_id as product_id, SUM(purchase_order_items.qty_plan - purchase_order_items.qty_received) as qty_on_order');
    }
}
