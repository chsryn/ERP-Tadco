@extends('layouts.app')

@section('title', 'Laporan Pembayaran - ERP TADCO')
@section('page_title', 'Laporan Pembayaran')
@section('page_subtitle', 'Histori dan ringkasan penerimaan kas & bank')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0">Data Histori Pembayaran</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('reports.payments.export', request()->query()) }}" class="btn btn-success">
                Export Excel
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm bg-success bg-opacity-10 border-start border-success border-4">
                <div class="card-body">
                    <small class="text-success fw-bold text-uppercase">Total Dana Diterima (Filtered)</small>
                    <h2 class="mb-0 mt-1 text-success">Rp {{ number_format($totalPayment, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.payments') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="No Payment, Invoice, Customer..."
                        value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Metode Bayar</label>
                    <select name="payment_method" class="form-select">
                        <option value="">Semua Metode</option>
                        <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Cash (Tunai)</option>
                        <option value="transfer" {{ $method === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-dark">Filter Data</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>No Payment</th>
                        <th>No Invoice</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th class="text-center">Metode</th>
                        <th>Referensi/Bank</th>
                        <th class="text-end">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('payments.show', $payment) }}" class="fw-bold text-decoration-none">
                                    {{ $payment->payment_number }}
                                </a>
                            </td>
                            <td>{{ $payment->invoice->invoice_number ?? '-' }}</td>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>{{ $payment->invoice->customer->customer_name ?? '-' }}</td>
                            <td class="text-center">
                                @if ($payment->payment_method === 'cash')
                                    <span class="badge bg-success">Cash</span>
                                @else
                                    <span class="badge bg-info text-dark">Transfer</span>
                                @endif
                            </td>
                            <td>{{ $payment->reference_no ?? ($payment->bank_name ?? '-') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Data histori pembayaran tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($payments->hasPages())
                <div class="mt-3">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
