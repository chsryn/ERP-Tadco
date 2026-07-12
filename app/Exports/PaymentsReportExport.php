<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $search = $this->filters['search'] ?? null;
        $method = $this->filters['payment_method'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

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

        return $query
            ->orderByDesc('payment_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No Payment',
            'No Invoice',
            'Tanggal Pembayaran',
            'Kode Customer',
            'Nama Customer',
            'Metode Pembayaran',
            'Bank',
            'No Referensi',
            'Nominal',
            'Catatan',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->payment_number,
            $payment->invoice->invoice_number ?? '-',
            $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-',
            $payment->invoice->customer->customer_code ?? '-',
            $payment->invoice->customer->customer_name ?? '-',
            $payment->payment_method,
            $payment->bank_name ?? '-',
            $payment->reference_no ?? '-',
            (float) $payment->amount,
            $payment->notes ?? '-',
        ];
    }
}
