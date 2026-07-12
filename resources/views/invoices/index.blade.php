@extends('layouts.app')

@section('title', 'Invoice - ERP TADCO')
@section('page_title', 'Sales Invoice')
@section('page_subtitle', 'Invoice penjualan dari Delivery Order yang sudah dikirim')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Invoice</h5>
            <small class="text-muted">Invoice dibuat dari DO yang sudah shipped</small>
        </div>

        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            + Buat Invoice
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
            <form method="GET" action="{{ route('invoices.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari nomor invoice, nomor DO, atau customer..." value="{{ $search ?? '' }}">
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
                        <th>No Invoice</th>
                        <th>No DO</th>
                        <th>Tanggal</th>
                        <th>Due Date</th>
                        <th>Customer</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Terbayar</th>
                        <th class="text-end">Piutang</th>
                        <th>Status</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoices->firstItem() + $loop->index }}</td>

                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>

                            <td>{{ $invoice->deliveryOrder->do_number ?? '-' }}</td>

                            <td>
                                {{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ $invoice->customer->customer_code ?? '-' }}
                                -
                                {{ $invoice->customer->customer_name ?? '-' }}
                            </td>

                            <td class="text-end">
                                Rp {{ number_format((float) $invoice->grand_total, 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                Rp {{ number_format((float) $invoice->paid_total, 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                Rp {{ number_format((float) $invoice->receivable_amount, 0, ',', '.') }}
                            </td>

                            <td>
                                @if ($invoice->status === 'unpaid')
                                    <span class="badge bg-danger">Unpaid</span>
                                @elseif($invoice->status === 'partial_paid')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @elseif($invoice->status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($invoice->status === 'cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-dark">{{ $invoice->status }}</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">
                                Belum ada invoice.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
@endsection
