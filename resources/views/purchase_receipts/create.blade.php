@extends('layouts.app')

@section('title', 'Buat Purchase Receipt - ERP TADCO')
@section('page_title', 'Buat Purchase Receipt')
@section('page_subtitle', 'Catat barang yang diterima. Stock IN belum dilakukan pada tahap ini.')

@section('page_action')
<a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Detail PO
</a>
@endsection

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Terjadi kesalahan validasi:</strong>
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

<form action="{{ route('purchase_receipts.store', $purchaseOrder) }}" method="POST">
    @csrf

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Header Receipt</h6>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">PO Number</label>
                    <input type="text" class="form-control bg-light" value="{{ $purchaseOrder->po_number }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">PO Date</label>
                    <input type="text" class="form-control bg-light" value="{{ $purchaseOrder->po_date?->format('d/m/Y') ?? '-' }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Supplier</label>
                    <input type="text" class="form-control bg-light"
                        value="{{ $purchaseOrder->supplier->name ?? '-' }} ({{ $purchaseOrder->supplier->code ?? '-' }})" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Receipt Date <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" class="form-control bg-light"
                        value="{{ old('receipt_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold small text-secondary">Notes</label>
                    <input type="text" name="notes" class="form-control bg-light" value="{{ old('notes') }}"
                        placeholder="Opsional — mis. nomor surat jalan supplier">
                </div>
            </div>

            <div class="alert alert-info border-0 rounded-3 small mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Receipt hanya mencatat penerimaan dan tracking qty. <strong>Stok belum berubah</strong> — Stock IN diproses terpisah lewat tombol Process Stock IN di halaman detail receipt.
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-0">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-success"></i>Item PO</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered border-light align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-nowrap small">
                        <th class="text-center" style="width: 44px;">No</th>
                        <th style="min-width: 200px;">Product</th>
                        <th class="text-center">Dynamics Code</th>
                        <th class="text-center">UOM</th>
                        <th class="text-end">PO Qty</th>
                        <th class="text-end">Already Received</th>
                        <th class="text-end">Remaining</th>
                        <th class="text-end" style="min-width: 120px;">Receive Now</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrder->items as $item)
                        @php
                            $remaining = round((float) $item->qty_plan - (float) $item->qty_received, 2);
                        @endphp
                        <tr class="{{ $remaining > 0 ? '' : 'text-muted' }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="fw-semibold {{ $remaining > 0 ? 'text-dark' : '' }}">{{ $item->product->product_name ?? '-' }}</td>
                            <td class="text-center text-nowrap small text-secondary">{{ $item->dynamics_code ?? '-' }}</td>
                            <td class="text-center">{{ $item->product->uom ?? '-' }}</td>
                            <td class="text-end text-nowrap">{{ number_format((float) $item->qty_plan, 2, ',', '.') }}</td>
                            <td class="text-end text-nowrap">{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                            <td class="text-end text-nowrap fw-semibold {{ $remaining > 0 ? 'text-primary' : '' }}">{{ number_format($remaining, 2, ',', '.') }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="{{ $remaining }}"
                                    name="items[{{ $item->id }}]" class="form-control form-control-sm text-end"
                                    value="{{ old('items.' . $item->id) }}" placeholder="0"
                                    {{ $remaining > 0 ? '' : 'disabled' }}>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">PO belum memiliki item.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-secondary">Kosongkan / isi 0 untuk item yang belum diterima.</div>
            <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> Simpan Receipt
            </button>
        </div>
    </div>
</form>

@endsection
