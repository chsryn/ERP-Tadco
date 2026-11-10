@extends('layouts.app')

@section('title', 'Detail Purchase Receipt - ERP TADCO')
@section('page_title', 'Detail Purchase Receipt')
@section('page_subtitle', 'Penerimaan barang tercatat. Stock IN diproses terpisah dari receipt.')

@section('page_action')
<div class="d-flex gap-2 align-items-center">
    @if($stockInProcessed)
        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
            <i class="bi bi-check-circle me-1"></i> Stock IN Processed
        </span>
    @else
        @can('purchase-receipts.process-stock-in')
            <form action="{{ route('purchase_receipts.stock_in', $purchaseReceipt) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Apakah Anda yakin ingin memproses Stock IN?\n\nQuantity receipt akan ditambahkan ke stok gudang.\nTindakan ini tidak dapat diproses ulang.');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill fw-semibold">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Process Stock IN
                </button>
            </form>
        @endcan
    @endif
    <a href="{{ route('purchase_receipts.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
        <i class="bi bi-arrow-left me-1"></i> Daftar Receipt
    </a>
    <a href="{{ route('purchase_orders.show', $purchaseReceipt->purchaseOrder) }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
        <i class="bi bi-file-earmark-text me-1"></i> Detail PO
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
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>{{ $purchaseReceipt->receipt_number }}</h4>
        <p class="text-muted small mb-0">
            PO {{ $purchaseReceipt->purchaseOrder->po_number ?? '-' }} ·
            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">{{ ucfirst($purchaseReceipt->status) }}</span>
        </p>
    </div>
    <div>
        @if($stockInProcessed)
            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                <i class="bi bi-check-circle me-1"></i> Stock IN sudah diproses
            </span>
        @else
            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill">
                <i class="bi bi-exclamation-circle me-1"></i> Stock IN: belum diproses
            </span>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
        <h6 class="fw-bold mb-0 text-dark text-uppercase">Informasi Receipt</h6>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
                <tbody>
                    <tr class="border-bottom border-light">
                        <th style="width: 220px;" class="text-secondary fw-semibold">Receipt Number</th>
                        <td class="fw-bold text-dark">{{ $purchaseReceipt->receipt_number }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Receipt Date</th>
                        <td>{{ $purchaseReceipt->receipt_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">PO Number</th>
                        <td>{{ $purchaseReceipt->purchaseOrder->po_number ?? '-' }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Supplier</th>
                        <td>
                            {{ $purchaseReceipt->purchaseOrder->supplier->name ?? '-' }}
                            @if($purchaseReceipt->purchaseOrder->supplier)
                                <span class="badge bg-secondary ms-1">{{ $purchaseReceipt->purchaseOrder->supplier->code }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Dibuat oleh</th>
                        <td>{{ $purchaseReceipt->creator->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="text-secondary fw-semibold">Notes</th>
                        <td>{{ $purchaseReceipt->notes ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 border-0">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-success"></i>Item Diterima</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered border-light align-middle mb-0">
            <thead class="table-light">
                <tr class="text-nowrap small">
                    <th class="text-center" style="width: 44px;">No</th>
                    <th style="min-width: 200px;">Product</th>
                    <th class="text-center">Dynamics Code</th>
                    <th class="text-center">UOM</th>
                    <th class="text-end">Qty Received</th>
                    <th class="text-end">Weight (KG)</th>
                    <th class="text-end">Qty Ton</th>
                    <th class="text-end">Qty Plan PO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseReceipt->items as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="fw-semibold text-dark">{{ $item->product->product_name ?? '-' }}</td>
                        <td class="text-center text-nowrap small text-secondary">{{ $item->purchaseOrderItem->dynamics_code ?? '-' }}</td>
                        <td class="text-center">{{ $item->product->uom ?? '-' }}</td>
                        <td class="text-end text-nowrap fw-semibold text-primary">{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->weight_kg, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->qty_ton, 4, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) ($item->purchaseOrderItem->qty_plan ?? 0), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Receipt belum memiliki item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
