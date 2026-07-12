@extends('layouts.app')

@section('title', 'Payment - ERP TADCO')
@section('page_title', 'Payment Management')
@section('page_subtitle', 'Pencatatan pembayaran cash dan transfer untuk invoice customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Pembayaran</h5>
            <small class="text-muted">Pembayaran akan mengurangi piutang invoice</small>
        </div>

        <a href="{{ route('payments.create') }}" class="btn btn-primary">
            + Input Pembayaran
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari nomor payment, invoice, customer, atau referensi..." value="{{ $search ?? '' }}">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>No Payment</th>
                        <th>No Invoice</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Metode</th>
                        <th>Bank</th>
                        <th>Referensi</th>
                        <th class="text-end">Nominal</th>
                        <th style="width: 100px;">Aksi</th>
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

                            <td>
                                {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ $payment->invoice->customer->customer_code ?? '-' }}
                                -
                                {{ $payment->invoice->customer->customer_name ?? '-' }}
                            </td>

                            <td>
                                @if ($payment->payment_method === 'cash')
                                    <span class="badge bg-success">Cash</span>
                                @elseif($payment->payment_method === 'transfer')
                                    <span class="badge bg-primary">Transfer</span>
                                @else
                                    <span class="badge bg-dark">{{ $payment->payment_method }}</span>
                                @endif
                            </td>

                            <td>{{ $payment->bank_name ?? '-' }}</td>
                            <td>{{ $payment->reference_no ?? '-' }}</td>

                            <td class="text-end">
                                Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                            </td>

                            <td>
                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-info">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                Belum ada pembayaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
@endsection
