@extends('layouts.app')

@section('title', 'Laporan Piutang - ERP TADCO')
@section('page_title', 'Laporan Piutang')
@section('page_subtitle', 'Invoice yang masih memiliki sisa tagihan/piutang')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0">Daftar Tagihan Berjalan</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('reports.receivables.export', request()->query()) }}" class="btn btn-success">
                Export Excel
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm bg-danger bg-opacity-10 border-start border-danger border-4">
                <div class="card-body">
                    <small class="text-danger fw-bold text-uppercase">Total Sisa Piutang Berjalan</small>
                    <h2 class="mb-0 mt-1 text-danger">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.receivables') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted">Pencarian</label>
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari invoice atau nama customer..." value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status Pembayaran</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter Jatuh Tempo</label>
                    <select name="overdue_only" class="form-select">
                        <option value="">Tampilkan Semua Piutang</option>
                        <option value="1" {{ $overdueOnly ? 'selected' : '' }}>Hanya Lewat Jatuh Tempo (Overdue)
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
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
                        <th>No Invoice</th>
                        <th>Customer</th>
                        <th>Tgl Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-end">Terbayar</th>
                        <th class="text-end">Sisa Piutang</th>
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
                                <a href="{{ route('invoices.show', $invoice) }}" class="fw-bold text-decoration-none">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $invoice->customer->customer_name ?? '-' }}</td>
                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td>
                                {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
                                @if ($isOverdue)
                                    <br><span class="badge bg-danger mt-1">Overdue</span>
                                @endif
                            </td>
                            <td class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($invoice->paid_total, 0, ',', '.') }}
                            </td>
                            <td class="text-end text-danger fw-bold">Rp
                                {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada data piutang berjalan.</td>
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
