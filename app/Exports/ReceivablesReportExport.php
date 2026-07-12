<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReceivablesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        $overdueOnly = $this->filters['overdue_only'] ?? null;

        $query = Invoice::query()
            ->with(['customer', 'deliveryOrder'])
            ->where('receivable_amount', '>', 0)
            ->where(function ($q) {
                $q->where('status', '=', 'unpaid')
                    ->orWhere('status', '=', 'partial_paid')
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

        return $query
            ->orderBy('due_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'No DO',
            'Kode Customer',
            'Nama Customer',
            'Tanggal Invoice',
            'Due Date',
            'Grand Total',
            'Terbayar',
            'Sisa Piutang',
            'Status',
            'Keterangan',
        ];
    }

    public function map($invoice): array
    {
        $isOverdue = $invoice->due_date
            && $invoice->due_date->format('Y-m-d') < now()->toDateString()
            && (float) $invoice->receivable_amount > 0;

        return [
            $invoice->invoice_number,
            $invoice->deliveryOrder->do_number ?? '-',
            $invoice->customer->customer_code ?? '-',
            $invoice->customer->customer_name ?? '-',
            $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-',
            $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-',
            (float) $invoice->grand_total,
            (float) $invoice->paid_total,
            (float) $invoice->receivable_amount,
            $invoice->status,
            $isOverdue ? 'Overdue' : 'Belum jatuh tempo',
        ];
    }
}
