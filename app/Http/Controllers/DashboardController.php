<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\StockBalance;
use App\Models\StockMovement;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCustomers = Customer::query()->count('*');
        $totalProducts = Product::query()->count('*');

        $totalStockAvailable = StockBalance::query()->sum('qty_available');
        $totalStockReserved = StockBalance::query()->sum('qty_reserved');

        $totalDeliveryOrders = DeliveryOrder::query()->count('*');
        $totalShipments = Shipment::query()->count('*');
        $totalInvoices = Invoice::query()->count('*');

        $totalReceivable = Invoice::query()
            ->where(function ($query) {
                $query->where('status', '=', 'unpaid')
                    ->orWhere('status', '=', 'overdue');
            })
            ->sum('receivable_amount');

        $totalPayments = Payment::query()->sum('amount');

        $unpaidInvoices = Invoice::query()
            ->where('status', '=', 'unpaid')
            ->count('*');

        $partialPaidInvoices = 0;

        $paidInvoices = Invoice::query()
            ->where('status', '=', 'paid')
            ->count('*');

        $overdueInvoices = Invoice::query()
            ->where('status', '=', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->where('receivable_amount', '>', 0)
            ->count('*');

        $lowStocks = StockBalance::query()
            ->with(['product'])
            ->where('qty_available', '<=', 10)
            ->orderBy('qty_available')
            ->limit(10)
            ->get();

        $recentDeliveryOrders = DeliveryOrder::query()
            ->with(['customer'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentInvoices = Invoice::query()
            ->with(['customer'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentPayments = Payment::query()
            ->with(['invoice.customer'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $movementStartDate = now()->subDays(6)->startOfDay();
        $movementEndDate = now()->endOfDay();
        $movementRecords = StockMovement::query()
            ->where('movement_date', '>=', $movementStartDate)
            ->where('movement_date', '<=', $movementEndDate)
            ->get(['movement_date', 'movement_type', 'qty']);

        $weeklyBars = collect();
        $baseDate = now();

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $baseDate->copy()->subDays($offset)->toDateString();
            $dayMovements = $movementRecords->filter(fn ($movement) => $movement->movement_date->toDateString() === $date);

            $incomingQty = (float) $dayMovements
                ->filter(fn ($movement) => in_array($movement->movement_type, ['in', 'return', 'adjustment_in'], true))
                ->sum('qty');

            $outgoingQty = (float) $dayMovements
                ->filter(fn ($movement) => in_array($movement->movement_type, ['out', 'damage', 'adjustment_out'], true))
                ->sum('qty');

            $weeklyBars->push([
                'day' => $baseDate->copy()->subDays($offset)->format('D'),
                'in' => $incomingQty,
                'out' => $outgoingQty,
            ]);
        }

        $maxMovementValue = max(1, (float) $weeklyBars->max(fn ($bar) => max($bar['in'], $bar['out'])));
        $weeklyBars = $weeklyBars->map(function ($bar) use ($maxMovementValue) {
            $bar['in_height'] = $bar['in'] > 0 ? round(($bar['in'] / $maxMovementValue) * 100) : 0;
            $bar['out_height'] = $bar['out'] > 0 ? round(($bar['out'] / $maxMovementValue) * 100) : 0;

            return $bar;
        });

        return view('dashboard.index', compact(
            'totalCustomers',
            'totalProducts',
            'totalStockAvailable',
            'totalStockReserved',
            'totalDeliveryOrders',
            'totalShipments',
            'totalInvoices',
            'totalReceivable',
            'totalPayments',
            'unpaidInvoices',
            'partialPaidInvoices',
            'paidInvoices',
            'overdueInvoices',
            'lowStocks',
            'recentDeliveryOrders',
            'recentInvoices',
            'recentPayments',
            'weeklyBars'
        ));
    }
}
