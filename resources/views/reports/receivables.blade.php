<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Piutang - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Laporan Piutang</h3>
                <small class="text-muted">Invoice yang masih memiliki sisa piutang</small>
            </div>

            {{-- <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div> --}}

            <div class="d-flex gap-2">
                <a href="{{ route('reports.receivables.export', request()->query()) }}" class="btn btn-success btn-sm">
                    Export Excel
                </a>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <small class="text-muted">Total Piutang</small>
                    <h3 class="mb-0 text-danger">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</h3>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.receivables') }}" class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari invoice atau customer..." value="{{ $search }}">
                        </div>

                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial_paid" {{ $status === 'partial_paid' ? 'selected' : '' }}>Partial
                                    Paid</option>
                                <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="overdue_only" class="form-select">
                                <option value="">Semua Piutang</option>
                                <option value="1" {{ $overdueOnly ? 'selected' : '' }}>Jatuh Tempo Saja</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-grid">
                            <button class="btn btn-dark">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>No Invoice</th>
                                <th>Customer</th>
                                <th>Tanggal Invoice</th>
                                <th>Due Date</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Terbayar</th>
                                <th class="text-end">Sisa Piutang</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($invoices as $invoice)
                                @php
                                    $isOverdue =
                                        $invoice->due_date &&
                                        $invoice->due_date->format('Y-m-d') < now()->toDateString() &&
                                        (float) $invoice->receivable_amount > 0;
                                @endphp

                                <tr>
                                    <td>{{ $invoices->firstItem() + $loop->index }}</td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->customer->customer_name ?? '-' }}</td>
                                    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                    <td>
                                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}

                                        @if ($isOverdue)
                                            <span class="badge bg-danger">Overdue</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">Rp {{ number_format($invoice->paid_total, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">Rp
                                        {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-dark">{{ $invoice->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Tidak ada data piutang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
</body>

</html>
