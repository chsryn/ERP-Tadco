@extends('layouts.app')

@section('title', 'Purchase Order - ERP TADCO')
@section('page_title', 'Purchase Order')
@section('page_subtitle', 'Daftar PO pembelian supplier / principal')

@section('page_action')
<div class="d-flex gap-2">
    <a href="{{ route('purchase_orders.reorder') }}" class="btn btn-outline-primary px-3 rounded-pill fw-semibold">
        <i class="bi bi-arrow-repeat me-1"></i> Auto PO
    </a>
    <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Buat PO
    </a>
</div>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Terjadi kesalahan:</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('purchase_orders.index') }}" class="row g-2">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari nomor PO, period, supplier..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status</option>
                    @foreach(['draft', 'confirmed', 'partial', 'received', 'cancelled'] as $option)
                        <option value="{{ $option }}" {{ ($status ?? '') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">Cari PO</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover table-bordered border-light align-middle mb-0">
            <thead class="table-light">
                <tr class="text-nowrap">
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>PO Number</th>
                    <th>Tanggal</th>
                    <th style="min-width: 180px;">Supplier</th>
                    <th class="text-center">Period</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Grand Total</th>
                    <th style="width: 200px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $purchaseOrder)
                    <tr>
                        <td class="text-center fw-medium text-secondary text-nowrap">{{ $purchaseOrders->firstItem() + $loop->index }}</td>
                        <td class="fw-bold text-dark text-nowrap">{{ $purchaseOrder->po_number }}</td>
                        <td class="text-nowrap">{{ $purchaseOrder->po_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="fw-semibold text-dark">{{ $purchaseOrder->supplier->name ?? '-' }}</td>
                        <td class="text-center text-nowrap">{{ $purchaseOrder->period }}</td>
                        <td class="text-center text-nowrap">
                            @php
                                $badge = match ($purchaseOrder->status) {
                                    'draft' => 'bg-secondary-subtle text-secondary',
                                    'confirmed' => 'bg-primary-subtle text-primary',
                                    'partial' => 'bg-warning-subtle text-warning-emphasis',
                                    'received' => 'bg-success-subtle text-success',
                                    'cancelled' => 'bg-danger-subtle text-danger',
                                    default => 'bg-light text-dark',
                                };
                            @endphp
                            <span class="badge {{ $badge }} px-3 py-1 rounded-pill">{{ ucfirst($purchaseOrder->status) }}</span>
                        </td>
                        <td class="text-end fw-bold text-primary text-nowrap">Rp {{ number_format((float) $purchaseOrder->grand_total, 0, ',', '.') }}</td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Detail"><i class="bi bi-eye"></i></a>
                                @if($purchaseOrder->status === 'draft')
                                    <a href="{{ route('purchase_orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-light border text-warning rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('purchase_orders.confirm', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Konfirmasi PO ini? Draft tidak dapat diedit setelah dikonfirmasi.')" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light border text-success rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Konfirmasi"><i class="bi bi-check2-circle"></i></button>
                                    </form>
                                @endif
                                @if(in_array($purchaseOrder->status, ['draft', 'confirmed'], true))
                                    <form action="{{ route('purchase_orders.cancel', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Batalkan PO ini?')" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light border text-danger rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Batalkan"><i class="bi bi-x-circle"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Belum ada Purchase Order.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($purchaseOrders->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            {{ $purchaseOrders->links() }}
        </div>
    @endif
</div>

@endsection
