<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Laporan Pembayaran</h3>
                <small class="text-muted">Ringkasan pembayaran cash dan transfer</small>
            </div>

            {{-- <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div> --}}

            <div class="d-flex gap-2">
                <a href="{{ route('reports.payments.export', request()->query()) }}" class="btn btn-success btn-sm">
                    Export Excel
                </a>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <small class="text-muted">Total Pembayaran</small>
                    <h3 class="mb-0 text-success">Rp {{ number_format($totalPayment, 0, ',', '.') }}</h3>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.payments') }}" class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari payment, invoice, customer..." value="{{ $search }}">
                        </div>

                        <div class="col-md-2">
                            <select name="payment_method" class="form-select">
                                <option value="">Semua Metode</option>
                                <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="transfer" {{ $method === 'transfer' ? 'selected' : '' }}>Transfer
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>

                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>

                        <div class="col-md-3 d-grid">
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
                                <th>No Payment</th>
                                <th>No Invoice</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Metode</th>
                                <th>Referensi</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payments->firstItem() + $loop->index }}</td>
                                    <td>
                                        <a href="{{ route('payments.show', $payment) }}" class="text-decoration-none">
                                            {{ $payment->payment_number }}
                                        </a>
                                    </td>
                                    <td>{{ $payment->invoice->invoice_number ?? '-' }}</td>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td>{{ $payment->invoice->customer->customer_name ?? '-' }}</td>
                                    <td>
                                        @if ($payment->payment_method === 'cash')
                                            <span class="badge bg-success">Cash</span>
                                        @else
                                            <span class="badge bg-primary">Transfer</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->reference_no ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Data pembayaran tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $payments->links() }}
                </div>
            </div>
        </div>
</body>

</html>
