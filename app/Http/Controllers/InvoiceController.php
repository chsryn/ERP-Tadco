<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $invoices = Invoice::query()
            ->with(['customer', 'deliveryOrder'])
            ->when($search, function ($query, $search) {
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
            })
            ->orderByDesc('invoice_date')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'search'));
    }

    public function create()
    {
        $deliveryOrders = DeliveryOrder::query()
            ->with(['customer', 'warehouse', 'items.product'])
            ->where('status', '=', 'shipped')
            ->doesntHave('invoice')
            ->orderByDesc('do_date')
            ->get();

        return view('invoices.create', compact('deliveryOrders'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        try {
            $this->invoiceService->createInvoice(
                $request->validated(),
                auth()->id()
            );

            return redirect()
                ->route('invoices.index')
                ->with('success', 'Invoice berhasil dibuat dari DO yang sudah shipped.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'deliveryOrder.warehouse',
            'items.product',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice tidak diedit langsung pada versi ini.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Update invoice belum diaktifkan.');
    }

    public function destroy(Invoice $invoice)
    {
        try {
            $this->invoiceService->cancelInvoice($invoice);

            return redirect()
                ->route('invoices.index')
                ->with('success', 'Invoice berhasil dibatalkan.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function exportExcel(Invoice $invoice)
    {
        // Pastikan relasi diload
        $invoice->load(['customer', 'items.product']);

        // Path ke template
        $templatePath = storage_path('app/templates/invoice_template.xlsx');

        if (!file_exists($templatePath)) {
            return back()->with('error', 'Template Excel tidak ditemukan di storage/app/templates/');
        }

        // Load Template
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Isi Header Invoice
        $sheet->setCellValue('H4', Carbon::parse($invoice->invoice_date)->format('Y-m-d'));
        $sheet->setCellValue('H5', $invoice->invoice_number);
        $sheet->setCellValue('H6', $invoice->payment_term_days ?? 14);
        $sheet->setCellValue('H7', Carbon::parse($invoice->due_date)->format('Y-m-d'));

        // 2. Isi Data Customer
        $sheet->setCellValue('A10', strtoupper($invoice->customer->customer_name ?? 'UMUM'));
        if ($invoice->customer && $invoice->customer->address) {
            $sheet->setCellValue('A11', $invoice->customer->address);
        }

        // 3. Isi Daftar Item Barang
        $startRow = 15; // Baris awal item di template
        $currentRow = $startRow;

        foreach ($invoice->items as $index => $item) {
            // Jika item lebih dari 1, sisipkan baris baru agar rumus Footer di bawahnya tidak tertimpa
            if ($index > 0) {
                $sheet->insertNewRowBefore($currentRow, 1);
            }

            $productName = $item->product ? $item->product->product_name : 'Item Tidak Dikenal';
            $productCode = $item->product ? $item->product->product_code : '-';

            $sheet->setCellValue('A' . $currentRow, $productName);
            $sheet->setCellValue('C' . $currentRow, $item->tier_code ?? ''); // Strata
            $sheet->setCellValue('D' . $currentRow, $productCode);
            $sheet->setCellValue('E' . $currentRow, ($item->discount_percentage ?? 0) / 100); // Diskon
            $sheet->setCellValue('F' . $currentRow, $item->base_price ?? 0);
            $sheet->setCellValue('G' . $currentRow, $item->qty ?? 0);
            $sheet->setCellValue('H' . $currentRow, $item->line_total ?? 0);

            $currentRow++;
        }

        // Generate dan Download File
        $fileName = 'Invoice_' . str_replace('/', '_', $invoice->invoice_number) . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function quickPay(\Illuminate\Http\Request $request, \App\Models\Invoice $invoice)
    {
        $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string'],
        ]);

        if ($invoice->status === 'paid' || $invoice->receivable_amount <= 0) return back()->with('error', 'Invoice lunas.');
        if ($request->amount > $invoice->receivable_amount) return back()->with('error', 'Lebih dari sisa tagihan.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $invoice) {
            \App\Models\Payment::create([
                'payment_number' => 'PAY-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'invoice_id' => $invoice->id,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'notes' => 'Pembayaran cepat dari UI',
                'created_by' => auth()->id(),
            ]);

            $newPaidTotal = $invoice->paid_total + $request->amount;
            $newReceivable = $invoice->grand_total - $newPaidTotal;
            $invoice->update([
                'paid_total' => $newPaidTotal,
                'receivable_amount' => $newReceivable,
                'status' => $newReceivable <= 0 ? 'paid' : 'unpaid',
            ]);
        });
        return back()->with('success', 'Pembayaran berhasil dicatat!');
    }
}
