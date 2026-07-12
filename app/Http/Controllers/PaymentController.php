<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
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
            ->whereIn('status', ['unpaid', 'partial_paid'])
            ->where('receivable_amount', '>', 0)
            ->orderByDesc('invoice_date')
            ->get();

        return view('payments.create', compact('invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $invoice = Invoice::query()
                ->where('id', '=', $validated['invoice_id'])
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Invoice tidak ditemukan.',
                ]);
            }

            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Invoice yang sudah dibatalkan tidak bisa dibayar.',
                ]);
            }

            if ($invoice->status === 'paid') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Invoice ini sudah lunas.',
                ]);
            }

            $amount = (float) $validated['amount'];
            $receivableAmount = (float) $invoice->receivable_amount;

            if ($amount > $receivableAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran tidak boleh melebihi sisa piutang. Sisa piutang: Rp ' .
                        number_format($receivableAmount, 0, ',', '.'),
                ]);
            }

            Payment::query()->create([
                'payment_number' => $this->generatePaymentNumber(),
                'invoice_id' => $invoice->id,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'amount' => $amount,
                'bank_name' => $validated['bank_name'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $newPaidTotal = (float) $invoice->paid_total + $amount;
            $newReceivableAmount = max(0, (float) $invoice->grand_total - $newPaidTotal);

            if ($newReceivableAmount <= 0) {
                $status = 'paid';
            } elseif ($newPaidTotal > 0) {
                $status = 'partial_paid';
            } else {
                $status = 'unpaid';
            }

            $invoice->update([
                'paid_total' => $newPaidTotal,
                'receivable_amount' => $newReceivableAmount,
                'status' => $status,
            ]);
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pembayaran berhasil disimpan dan status invoice berhasil diperbarui.');
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
        DB::transaction(function () use ($payment) {
            $invoice = Invoice::query()
                ->where('id', '=', $payment->invoice_id)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw ValidationException::withMessages([
                    'payment' => 'Invoice terkait pembayaran tidak ditemukan.',
                ]);
            }

            $newPaidTotal = max(0, (float) $invoice->paid_total - (float) $payment->amount);
            $newReceivableAmount = max(0, (float) $invoice->grand_total - $newPaidTotal);

            if ($newPaidTotal <= 0) {
                $status = 'unpaid';
            } elseif ($newReceivableAmount <= 0) {
                $status = 'paid';
            } else {
                $status = 'partial_paid';
            }

            $invoice->update([
                'paid_total' => $newPaidTotal,
                'receivable_amount' => $newReceivableAmount,
                'status' => $status,
            ]);

            Payment::destroy($payment->id);
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pembayaran berhasil dihapus dan status invoice dikembalikan.');
    }

    private function generatePaymentNumber(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
