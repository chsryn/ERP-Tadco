<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $payments = Payment::query()
            ->with(['invoice.customer'])
            ->when($search, function ($query, $search) {
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
            })
            ->orderByDesc('payment_date')
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', compact('payments', 'search'));
    }

    public function create()
    {
        $invoices = Invoice::query()
            ->with(['customer'])
            ->where('status', '=', 'unpaid')
            ->where('receivable_amount', '>', 0)
            ->orderByDesc('invoice_date')
            ->get();

        return view('payments.create', compact('invoices'));
    }

    public function store(StorePaymentRequest $request)
    {
        try {
            $this->paymentService->createPayment(
                $request->validated(),
                auth()->id()
            );

            return redirect()
                ->route('payments.index')
                ->with('success', 'Pembayaran berhasil disimpan dan status invoice berhasil diperbarui.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'invoice.customer',
            'invoice.deliveryOrder',
        ]);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pembayaran tidak diedit langsung. Hapus pembayaran lalu input ulang jika ada kesalahan.');
    }

    public function update(Request $request, Payment $payment)
    {
        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Update pembayaran belum diaktifkan.');
    }

    public function destroy(Payment $payment)
    {
        try {
            $this->paymentService->deletePayment($payment);

            return redirect()
                ->route('payments.index')
                ->with('success', 'Pembayaran berhasil dihapus dan status invoice dikembalikan.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
