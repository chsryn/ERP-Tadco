<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $search = $this->filters['search'] ?? null;
        $status = $this->filters['status'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

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

        return $query
            ->orderByDesc('invoice_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'No DO',
            'Tanggal Invoice',
            'Due Date',
            'Kode Customer',
            'Nama Customer',
            'Grand Total',
            'Terbayar',
            'Piutang',
            'Status',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->deliveryOrder->do_number ?? '-',
            $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-',
            $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-',
            $invoice->customer->customer_code ?? '-',
            $invoice->customer->customer_name ?? '-',
            (float) $invoice->grand_total,
            (float) $invoice->paid_total,
            (float) $invoice->receivable_amount,
            $invoice->status,
        ];
    }
}
