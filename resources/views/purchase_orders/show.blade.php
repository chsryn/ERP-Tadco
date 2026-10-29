@extends('layouts.app')

@section('title', 'Detail Purchase Order - ERP TADCO')
@section('page_title', 'Detail Purchase Order')
@section('page_subtitle', 'Ringkasan dokumen PO (bukan layout print final)')

@section('page_action')
<a href="{{ route('purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Kembali
</a>
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

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-2 text-primary"></i>{{ $purchaseOrder->po_number }}</h4>
        <p class="text-muted small mb-0">
            Period {{ $purchaseOrder->period }} ·
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
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($purchaseOrder->status === 'draft')
            <a href="{{ route('purchase_orders.edit', $purchaseOrder) }}" class="btn btn-warning btn-sm px-3 rounded-pill text-dark fw-semibold">
                <i class="bi bi-pencil me-1"></i> Edit Draft
            </a>
            <form action="{{ route('purchase_orders.confirm', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Konfirmasi PO ini? Draft tidak dapat diedit setelah dikonfirmasi.')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Konfirmasi PO
                </button>
            </form>
        @endif
        @if(in_array($purchaseOrder->status, ['draft', 'confirmed'], true))
            <form action="{{ route('purchase_orders.cancel', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Batalkan PO ini?')">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                    <i class="bi bi-x-circle me-1"></i> Batalkan
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
        <h6 class="fw-bold mb-0 text-dark text-uppercase">Confirm Order</h6>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
                <tbody>
                    <tr class="border-bottom border-light">
                        <th style="width: 220px;" class="text-secondary fw-semibold">PO Number</th>
                        <td class="fw-bold text-dark">{{ $purchaseOrder->po_number }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">PO Period</th>
                        <td>{{ $purchaseOrder->period }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Distributor / Supplier</th>
                        <td class="fw-semibold text-dark">
                            {{ $purchaseOrder->supplier->name ?? '-' }}
                            @if($purchaseOrder->supplier)
                                <span class="badge bg-secondary ms-1">{{ $purchaseOrder->supplier->code }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Date</th>
                        <td>{{ $purchaseOrder->po_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">TOP</th>
                        <td>{{ $purchaseOrder->supplier->term_of_payment_days ?? 0 }} hari</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">PIC</th>
                        <td>{{ $purchaseOrder->supplier->pic_name ?? '-' }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Region / Port Depo</th>
                        <td>{{ $purchaseOrder->supplier->region ?? '-' }} / {{ $purchaseOrder->supplier->port_depo ?? '-' }}</td>
                    </tr>
                    <tr class="border-bottom border-light">
                        <th class="text-secondary fw-semibold">Dibuat oleh</th>
                        <td>{{ $purchaseOrder->creator->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="text-secondary fw-semibold">Catatan</th>
                        <td>{{ $purchaseOrder->notes ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 border-0">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-success"></i>Item Purchase Order</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered border-light align-middle mb-0">
            <thead class="table-light">
                <tr class="text-nowrap small">
                    <th class="text-center">Dynamics Code</th>
                    <th class="text-center">Weight</th>
                    <th style="min-width: 200px;">Product</th>
                    <th class="text-end">Qty Plan</th>
                    <th class="text-end">Ton</th>
                    <th class="text-end">W1</th>
                    <th class="text-end">W2</th>
                    <th class="text-end">W3</th>
                    <th class="text-end">W4</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Nett</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrder->items as $item)
                    <tr>
                        <td class="text-center text-nowrap small">{{ $item->dynamics_code ?? '-' }}</td>
                        <td class="text-center text-nowrap">{{ $item->weight_kg }} KG</td>
                        <td class="fw-semibold text-dark">{{ $item->product->product_name ?? '-' }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->qty_plan, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->qty_ton, 4, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->week_1, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->week_2, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->week_3, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $item->week_4, 2, ',', '.') }}</td>
                        <td class="text-end text-nowrap">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                        <td class="text-end text-nowrap">Rp {{ number_format((float) $item->special_discount, 0, ',', '.') }}</td>
                        <td class="text-end text-nowrap">Rp {{ number_format((float) $item->price_nett, 0, ',', '.') }}</td>
                        <td class="text-end text-nowrap fw-semibold">Rp {{ number_format((float) $item->total_price, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">PO belum memiliki item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3 px-4 border-top border-light">
        <div class="row justify-content-end">
            <div class="col-md-5">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-secondary fw-semibold">Subtotal</th>
                        <td class="text-end fw-semibold">Rp {{ number_format((float) $purchaseOrder->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th class="text-secondary fw-semibold">Total Discount</th>
                        <td class="text-end fw-semibold">Rp {{ number_format((float) $purchaseOrder->special_discount_total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th class="text-secondary fw-semibold">PPN ({{ rtrim(rtrim(number_format((float) $purchaseOrder->ppn_rate, 2, ',', '.'), '0'), ',') }}%)</th>
                        <td class="text-end fw-semibold">Rp {{ number_format((float) $purchaseOrder->ppn_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="border-top">
                        <th class="fw-bold text-dark">Grand Total</th>
                        <td class="text-end fw-bold text-primary fs-5">Rp {{ number_format((float) $purchaseOrder->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
