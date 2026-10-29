<?php

namespace App\Services\Ai;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Membangun snapshot agregat yang boleh dikirim ke provider AI sesuai role.
 * Hanya query baca (aggregate/select); tidak ada operasi tulis.
 */
class AiContextBuilder
{
    public const ROLES = ['admin', 'sales', 'finance'];

    public static function resolveRole(User $user): ?string
    {
        foreach (self::ROLES as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    public function build(User $user, string $role): array
    {
        return match ($role) {
            'admin' => $this->adminContext(),
            'sales' => $this->salesContext($user),
            'finance' => $this->financeContext(),
        };
    }

    private function adminContext(): array
    {
        return [
            'role' => 'admin',
            'generated_at' => now()->toIso8601String(),
            'customers' => [
                'total' => Customer::query()->count(),
                'active' => Customer::query()->where('is_active', true)->count(),
            ],
            'products' => [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('is_active', true)->count(),
            ],
            'stock' => [
                'available_total' => (float) StockBalance::query()->sum('qty_available'),
                'reserved_total' => (float) StockBalance::query()->sum('qty_reserved'),
                'low_stock' => StockBalance::query()
                    ->with('product:id,product_name')
                    ->where('qty_available', '<=', 10)
                    ->orderBy('qty_available')
                    ->limit(5)
                    ->get()
                    ->map(fn ($row) => [
                        'product' => $row->product?->product_name ?? '-',
                        'available' => (float) $row->qty_available,
                        'reserved' => (float) $row->qty_reserved,
                    ])->all(),
            ],
            'delivery_orders' => [
                'total' => DeliveryOrder::query()->count(),
                'by_status' => $this->countByStatus(DeliveryOrder::query()),
                'last_30_days' => DeliveryOrder::query()
                    ->where('do_date', '>=', now()->subDays(30)->toDateString())
                    ->count(),
            ],
            'invoices' => [
                'by_status' => $this->countByStatus(Invoice::query()),
                'receivable_total' => (float) Invoice::query()->sum('receivable_amount'),
                'overdue_count' => $this->overdueInvoices()->count(),
            ],
            'payments' => [
                'total' => (float) Payment::query()->sum('amount'),
                'this_month' => (float) Payment::query()
                    ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
            ],
            'sales' => [
                'top_products' => $this->topProducts(),
            ],
        ];
    }

    private function salesContext(User $user): array
    {
        $orders = DeliveryOrder::query()->where('sales_id', $user->id);

        return [
            'role' => 'sales',
            'generated_at' => now()->toIso8601String(),
            'my_delivery_orders' => [
                'total' => (clone $orders)->count(),
                'by_status' => $this->countByStatus($orders),
                'total_amount' => (float) (clone $orders)->sum('total_amount'),
                'last_30_days' => (clone $orders)
                    ->where('do_date', '>=', now()->subDays(30)->toDateString())
                    ->count(),
                'awaiting_shipment' => (clone $orders)->where('status', 'validated')->count(),
            ],
            'my_customers' => Customer::query()
                ->whereIn('id', (clone $orders)->select('customer_id'))
                ->count(),
            'my_top_products' => $this->topProducts($user->id),
            'my_stock' => [
                'available_total' => (float) StockBalance::query()->sum('qty_available'),
                'reserved_total' => (float) StockBalance::query()->sum('qty_reserved'),
            ],
        ];
    }

    private function financeContext(): array
    {
        return [
            'role' => 'finance',
            'generated_at' => now()->toIso8601String(),
            'invoices' => [
                'by_status' => $this->countByStatus(Invoice::query()),
                'receivable_total' => (float) Invoice::query()->sum('receivable_amount'),
                'overdue_count' => $this->overdueInvoices()->count(),
                'revenue_this_month' => (float) Invoice::query()
                    ->whereBetween('invoice_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                    ->sum('grand_total'),
            ],
            'payments' => [
                'total' => (float) Payment::query()->sum('amount'),
                'this_month' => (float) Payment::query()
                    ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
                'this_month_by_method' => Payment::query()
                    ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->selectRaw('payment_method, SUM(amount) as total')
                    ->groupBy('payment_method')
                    ->pluck('total', 'payment_method'),
            ],
            'top_receivables' => Invoice::query()
                ->with('customer:id,customer_name')
                ->where('receivable_amount', '>', 0)
                ->orderByDesc('receivable_amount')
                ->limit(5)
                ->get()
                ->map(fn ($invoice) => [
                    'invoice_number' => $invoice->invoice_number,
                    'customer' => $invoice->customer?->customer_name ?? '-',
                    'receivable' => (float) $invoice->receivable_amount,
                    'due_date' => $invoice->due_date?->toDateString(),
                    'status' => $invoice->status,
                ])->all(),
        ];
    }

    private function countByStatus($query): array
    {
        return $query->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    private function overdueInvoices()
    {
        return Invoice::query()
            ->where('receivable_amount', '>', 0)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    private function topProducts(?int $salesId = null): array
    {
        $query = DB::table('delivery_order_items')
            ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
            ->join('products', 'products.id', '=', 'delivery_order_items.product_id')
            ->whereNull('delivery_orders.deleted_at')
            ->selectRaw('products.product_name, SUM(delivery_order_items.qty) as qty_sold')
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('qty_sold')
            ->limit(5);

        if ($salesId !== null) {
            $query->where('delivery_orders.sales_id', $salesId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'product' => $row->product_name,
                'qty_sold' => (float) $row->qty_sold,
            ])->all();
    }
}
