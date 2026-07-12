<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Invoice - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Detail Invoice</h3>
                <small class="text-muted">{{ $invoice->invoice_number }}</small>
            </div>

            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                Informasi Invoice
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 250px;">Nomor Invoice</th>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>

                    <tr>
                        <th>Nomor DO</th>
                        <td>{{ $invoice->deliveryOrder->do_number ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>Customer</th>
                        <td>
                            {{ $invoice->customer->customer_code ?? '-' }}
                            -
                            {{ $invoice->customer->customer_name ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Tanggal Invoice</th>
                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    </tr>

                    <tr>
                        <th>TOP</th>
                        <td>{{ $invoice->payment_term_days ?? 0 }} hari</td>
                    </tr>

                    <tr>
                        <th>Due Date</th>
                        <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            @if ($invoice->status === 'unpaid')
                                <span class="badge bg-danger">Unpaid</span>
                            @elseif($invoice->status === 'partial_paid')
                                <span class="badge bg-warning text-dark">Partial Paid</span>
                            @elseif($invoice->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($invoice->status === 'cancelled')
                                <span class="badge bg-secondary">Cancelled</span>
                            @else
                                <span class="badge bg-dark">{{ $invoice->status }}</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <th>Catatan</th>
                        <td>{{ $invoice->notes ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Item Invoice
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Kode Produk</th>
                            <th>Nama Produk</th>
                            <th>Strata</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->product->product_code ?? '-' }}</td>
                                <td>{{ $item->product->product_name ?? '-' }}</td>
                                <td>{{ $item->tier_code }}</td>
                                <td class="text-end">{{ number_format($item->qty, 2, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ $item->discount_rate }}</td>
                                <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Subtotal</th>
                            <th class="text-end">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="7" class="text-end">Total Diskon</th>
                            <th class="text-end">Rp {{ number_format($invoice->discount_total, 0, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="7" class="text-end">Grand Total</th>
                            <th class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="7" class="text-end">Terbayar</th>
                            <th class="text-end">Rp {{ number_format($invoice->paid_total, 0, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="7" class="text-end">Piutang</th>
                            <th class="text-end">Rp {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>

                @if ($invoice->status !== 'cancelled' && (float) $invoice->paid_total <= 0)
                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                        onsubmit="return confirm('Yakin ingin membatalkan invoice ini?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger">
                            Batalkan Invoice
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</body>

</html>
