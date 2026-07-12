<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\StockBalance;

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
                    ->orWhere('status', '=', 'partial_paid')
                    ->orWhere('status', '=', 'overdue');
            })
            ->sum('receivable_amount');

        $totalPayments = Payment::query()->sum('amount');

        $unpaidInvoices = Invoice::query()
            ->where('status', '=', 'unpaid')
            ->count('*');

        $partialPaidInvoices = Invoice::query()
            ->where('status', '=', 'partial_paid')
            ->count('*');

        $paidInvoices = Invoice::query()
            ->where('status', '=', 'paid')
            ->count('*');

        $overdueInvoices = Invoice::query()
            ->where(function ($query) {
                $query->where('status', '=', 'unpaid')
                    ->orWhere('status', '=', 'partial_paid');
            })
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->where('receivable_amount', '>', 0)
            ->count('*');

        $lowStocks = StockBalance::query()
            ->with(['product', 'warehouse'])
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
            'recentPayments'
        ));
    }
}
