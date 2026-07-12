<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Payment - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Detail Pembayaran</h3>
            <small class="text-muted">{{ $payment->payment_number }}</small>
        </div>

        <a href="{{ route('payments.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            Informasi Pembayaran
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Nomor Payment</th>
                    <td>{{ $payment->payment_number }}</td>
                </tr>

                <tr>
                    <th>Nomor Invoice</th>
                    <td>{{ $payment->invoice->invoice_number ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Nomor DO</th>
                    <td>{{ $payment->invoice->deliveryOrder->do_number ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Customer</th>
                    <td>
                        {{ $payment->invoice->customer->customer_code ?? '-' }}
                        -
                        {{ $payment->invoice->customer->customer_name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <th>Tanggal Pembayaran</th>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                </tr>

                <tr>
                    <th>Metode</th>
                    <td>
                        @if($payment->payment_method === 'cash')
                            <span class="badge bg-success">Cash</span>
                        @else
                            <span class="badge bg-primary">Transfer</span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Nominal</th>
                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                </tr>

                <tr>
                    <th>Bank</th>
                    <td>{{ $payment->bank_name ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Referensi</th>
                    <td>{{ $payment->reference_no ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Catatan</th>
                    <td>{{ $payment->notes ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Ringkasan Invoice Setelah Pembayaran
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Grand Total</th>
                    <td>Rp {{ number_format($payment->invoice->grand_total, 0, ',', '.') }}</td>
                </tr>

                <tr>
                    <th>Total Terbayar</th>
                    <td>Rp {{ number_format($payment->invoice->paid_total, 0, ',', '.') }}</td>
                </tr>

                <tr>
                    <th>Sisa Piutang</th>
                    <td>Rp {{ number_format($payment->invoice->receivable_amount, 0, ',', '.') }}</td>
                </tr>

                <tr>
                    <th>Status Invoice</th>
                    <td>
                        @if($payment->invoice->status === 'unpaid')
                            <span class="badge bg-danger">Unpaid</span>
                        @elseif($payment->invoice->status === 'partial_paid')
                            <span class="badge bg-warning text-dark">Partial Paid</span>
                        @elseif($payment->invoice->status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @else
                            <span class="badge bg-dark">{{ $payment->invoice->status }}</span>
                        @endif
                    </td>
                </tr>
            </table>

            <form action="{{ route('payments.destroy', $payment) }}" method="POST"
                  onsubmit="return confirm('Yakin ingin menghapus pembayaran ini? Nominal akan dikembalikan ke piutang invoice.')">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Hapus Pembayaran
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
