@extends('layouts.app')

@section('title', 'Laporan Penjualan - ERP TADCO')
@section('page_title', 'Laporan Penjualan')
@section('page_subtitle', 'Ringkasan invoice berdasarkan tanggal dan status')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0">Data Transaksi Penjualan</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('reports.sales.export', request()->query()) }}" class="btn btn-success">
                Export Excel
            </a>
        </div>
    </div>

    <!-- Summary Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase">Total Penjualan (Kotor)</small>
                    <h3 class="mb-0 mt-2">Rp {{ number_format($totalGrand, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-white border-bottom border-success border-3">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase">Total Dana Masuk</small>
                    <h3 class="mb-0 mt-2 text-success">Rp {{ number_format($totalPaid, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-white border-bottom border-danger border-3">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase">Total Sisa Piutang</small>
                    <h3 class="mb-0 mt-2 text-danger">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="No Invoice, DO, Customer..."
                        value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Customer</th>
                        <th>No Invoice</th>
                        <th>No DO</th>
                        <th>Tanggal</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-end">Terbayar</th>
                        <th class="text-end">Piutang</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoices->firstItem() + $loop->index }}</td>
                            <td>{{ $invoice->customer->customer_name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}" class="fw-bold text-decoration-none">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $invoice->deliveryOrder->do_number ?? '-' }}</td>
                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($invoice->paid_total, 0, ',', '.') }}
                            </td>
                            <td class="text-end text-danger">Rp
                                {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if ($invoice->status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($invoice->status === 'unpaid')
                                    <span class="badge bg-danger">Unpaid</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Data invoice tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($invoices->hasPages())
                <div class="mt-3">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
