<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Create a new Payment and update Invoice receivable amount & status safely.
     *
     * @param array $validatedData
     * @param int|null $userId
     * @return Payment
     * @throws ValidationException
     */
    public function createPayment(array $validatedData, ?int $userId): Payment
    {
        return DB::transaction(function () use ($validatedData, $userId) {
            /** @var Invoice|null $invoice */
            $invoice = Invoice::query()
                ->where('id', '=', $validatedData['invoice_id'])
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

            $amount = round((float) $validatedData['amount'], 2);
            $receivableAmount = round((float) $invoice->receivable_amount, 2);

            if ($amount > $receivableAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran tidak boleh melebihi sisa piutang. Sisa piutang: Rp ' .
                        number_format($receivableAmount, 2, ',', '.'),
                ]);
            }

            $payment = Payment::query()->create([
                'payment_number' => $this->generatePaymentNumber(),
                'invoice_id' => $invoice->id,
                'payment_date' => $validatedData['payment_date'],
                'payment_method' => $validatedData['payment_method'],
                'amount' => $amount,
                'bank_name' => $validatedData['bank_name'] ?? null,
                'reference_no' => $validatedData['reference_no'] ?? null,
                'notes' => $validatedData['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $newPaidTotal = round((float) $invoice->paid_total + $amount, 2);
            $grandTotal = round((float) $invoice->grand_total, 2);
            $newReceivableAmount = max(0.0, round($grandTotal - $newPaidTotal, 2));

            if ($newReceivableAmount <= 0.0) {
                $status = 'paid';
            } else {
                $status = 'unpaid';
            }

            $invoice->update([
                'paid_total' => $newPaidTotal,
                'receivable_amount' => $newReceivableAmount,
                'status' => $status,
            ]);

            return $payment;
        });
    }

    /**
     * Delete a Payment and recalculate Invoice balance & status safely.
     *
     * @param Payment $payment
     * @return bool
     * @throws ValidationException
     */
    public function deletePayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            /** @var Invoice|null $invoice */
            $invoice = Invoice::query()
                ->where('id', '=', $payment->invoice_id)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw ValidationException::withMessages([
                    'payment' => 'Invoice terkait pembayaran tidak ditemukan.',
                ]);
            }

            $paymentAmount = round((float) $payment->amount, 2);
            $currentPaidTotal = round((float) $invoice->paid_total, 2);
            $grandTotal = round((float) $invoice->grand_total, 2);

            $newPaidTotal = max(0.0, round($currentPaidTotal - $paymentAmount, 2));
            $newReceivableAmount = max(0.0, round($grandTotal - $newPaidTotal, 2));

            if ($newReceivableAmount <= 0.0) {
                $status = 'paid';
            } else {
                $status = 'unpaid';
            }

            $invoice->update([
                'paid_total' => $newPaidTotal,
                'receivable_amount' => $newReceivableAmount,
                'status' => $status,
            ]);

            return (bool) $payment->delete();
        });
    }

    /**
     * Generate unique Payment number.
     *
     * @return string
     */
    private function generatePaymentNumber(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
