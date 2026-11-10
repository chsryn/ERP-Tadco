@extends('layouts.app')

@section('title', 'Purchase Receipt - ERP TADCO')
@section('page_title', 'Purchase Receipt')
@section('page_subtitle', 'Catatan penerimaan barang dari Purchase Order (Stock IN belum diproses)')

@section('page_action')
@can('purchase-orders.view')
<a href="{{ route('purchase_orders.index') }}" class="btn btn-outline-secondary px-3 rounded-pill fw-semibold">
    <i class="bi bi-file-earmark-text me-1"></i> Purchase Order
</a>
@endcan
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body px-4 py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ $search }}" class="form-control bg-light"
                    placeholder="Cari nomor receipt atau nomor PO...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary px-3 rounded-pill"><i class="bi bi-search me-1"></i> Cari</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('purchase_receipts.index') }}" class="btn btn-light border px-3 rounded-pill">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="text-nowrap small">
                    <th>Receipt Number</th>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Receipt Date</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseReceipts as $receipt)
                    <tr>
                        <td class="fw-semibold text-dark text-nowrap">{{ $receipt->receipt_number }}</td>
                        <td class="text-nowrap">{{ $receipt->purchaseOrder->po_number ?? '-' }}</td>
                        <td>{{ $receipt->purchaseOrder->supplier->name ?? '-' }}</td>
                        <td class="text-nowrap">{{ $receipt->receipt_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">{{ ucfirst($receipt->status) }}</span>
                        </td>
                        <td>{{ $receipt->creator->name ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('purchase_receipts.show', $receipt) }}"
                                class="btn btn-sm btn-light border rounded d-inline-flex align-items-center justify-content-center shadow-sm"
                                style="width: 32px; height: 32px;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            Belum ada Purchase Receipt.
                            <div class="small mt-1">Buat receipt dari halaman detail PO berstatus confirmed / partial.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($purchaseReceipts->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            {{ $purchaseReceipts->links() }}
        </div>
    @endif
</div>

@endsection
