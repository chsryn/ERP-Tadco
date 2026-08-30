<?php

namespace App\Http\Controllers;

use App\Exports\PaymentsReportExport;
use App\Exports\ReceivablesReportExport;
use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        // 1. Data Grafik Stok
        $totalAvailable = StockBalance::query()->sum('qty_available');
        $totalReserved = StockBalance::query()->sum('qty_reserved');
        $lowStockCount = StockBalance::query()->where('qty_available', '<=', 10)->count();

        // 2. Data Grafik Penjualan
        $totalSales = Invoice::query()->sum('grand_total');
        $totalPaid = Invoice::query()->sum('paid_total');
        $totalReceivable = Invoice::query()->sum('receivable_amount');

        // 3. Data Grafik Piutang
        $unpaidAmount = Invoice::query()->where('status', 'unpaid')->sum('receivable_amount');
        $partialPaidAmount = 0;

        $overdueAmount = Invoice::query()
            ->where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->sum('receivable_amount');

        // 4. Data Grafik Pembayaran
        $cashPayments = Payment::query()->where('payment_method', 'cash')->sum('amount');
        $transferPayments = Payment::query()->where('payment_method', 'transfer')->sum('amount');

        return view('reports.index', compact(
            'totalAvailable', 'totalReserved', 'lowStockCount',
            'totalSales', 'totalPaid', 'totalReceivable',
            'unpaidAmount', 'partialPaidAmount', 'overdueAmount',
            'cashPayments', 'transferPayments'
        ));
    }

    public function stock(Request $request)
    {
        $search = $request->get('search');
        $lowStockOnly = $request->get('low_stock_only');

        $query = StockBalance::query()
            ->with(['product', 'warehouse']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('product_code', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('segment', 'like', "%{$search}%");
                })->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                    $warehouseQuery->where('warehouse_name', 'like', "%{$search}%")
                        ->orWhere('warehouse_code', 'like', "%{$search}%");
                });
            });
        }

        if ($lowStockOnly) {
            $query->where('qty_available', '<=', 10);
        }

        $totalAvailable = (clone $query)->sum('qty_available');
        $totalReserved = (clone $query)->sum('qty_reserved');

        $stocks = $query
            ->orderBy('qty_available')
            ->paginate(10)
            ->withQueryString();

        return view('reports.stock', compact(
            'stocks',
            'search',
            'lowStockOnly',
            'totalAvailable',
            'totalReserved'
        ));
    }

    public function sales(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Invoice::query()
            ->with(['customer', 'deliveryOrder']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('deliveryOrder', function ($doQuery) use ($search) {
                        $doQuery->where('do_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', '=', $status);
        }

        if ($startDate) {
            $query->where('invoice_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('invoice_date', '<=', $endDate);
        }

        $totalGrand = (clone $query)->sum('grand_total');
        $totalPaid = (clone $query)->sum('paid_total');
        $totalReceivable = (clone $query)->sum('receivable_amount');

        $invoices = $query
            ->orderByDesc('invoice_date')
            ->paginate(10)
            ->withQueryString();

        return view('reports.sales', compact(
            'invoices',
            'search',
            'status',
            'startDate',
            'endDate',
            'totalGrand',
            'totalPaid',
            'totalReceivable'
        ));
    }

    public function receivables(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $overdueOnly = $request->get('overdue_only');

        $query = Invoice::query()
            ->with(['customer', 'deliveryOrder'])
            ->where('receivable_amount', '>', 0)
            ->where(function ($q) {
                $q->where('status', '=', 'unpaid')
                    ->orWhere('status', '=', 'overdue');
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', '=', $status);
        }

        if ($overdueOnly) {
            $query->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString());
        }

        $totalReceivable = (clone $query)->sum('receivable_amount');

        $invoices = $query
            ->orderBy('due_date')
            ->paginate(10)
            ->withQueryString();

        return view('reports.receivables', compact(
            'invoices',
            'search',
            'status',
            'overdueOnly',
            'totalReceivable'
        ));
    }

    public function payments(Request $request)
    {
        $search = $request->get('search');
        $method = $request->get('payment_method');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Payment::query()
            ->with(['invoice.customer']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                        $invoiceQuery->where('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery->where('customer_name', 'like', "%{$search}%")
                                    ->orWhere('customer_code', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($method) {
            $query->where('payment_method', '=', $method);
        }

        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }

        $totalPayment = (clone $query)->sum('amount');

        $payments = $query
            ->orderByDesc('payment_date')
            ->paginate(10)
            ->withQueryString();

        return view('reports.payments', compact(
            'payments',
            'search',
            'method',
            'startDate',
            'endDate',
            'totalPayment'
        ));
    }

    public function exportStock(Request $request)
    {
        return Excel::download(
            new StockReportExport($request->only(['search', 'low_stock_only'])),
            'laporan_stok_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportSales(Request $request)
    {
        return Excel::download(
            new SalesReportExport($request->only(['search', 'status', 'start_date', 'end_date'])),
            'laporan_penjualan_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportReceivables(Request $request)
    {
        return Excel::download(
            new ReceivablesReportExport($request->only(['search', 'status', 'overdue_only'])),
            'laporan_piutang_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportPayments(Request $request)
    {
        return Excel::download(
            new PaymentsReportExport($request->only(['search', 'payment_method', 'start_date', 'end_date'])),
            'laporan_pembayaran_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
