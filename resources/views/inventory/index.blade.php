@extends('layouts.app')

@section('title', 'Inventory - ERP TADCO')
@section('page_title', 'Monitoring Stok Barang')
@section('page_subtitle', 'Perhitungan pergerakan stok barang secara real-time')

@section('page_action')
<a href="{{ route('inventory.adjustment.create') }}" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-sliders me-1"></i> Adjustment Stok
</a>
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Terjadi kesalahan:</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
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
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0"
                        placeholder="Cari kode produk, nama produk, segment..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <div class="dropdown d-grid">
                    <button class="btn btn-outline-secondary rounded-pill fw-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-layout-three-columns me-1"></i> Kolom
                    </button>
                    <ul class="dropdown-menu shadow-sm p-2" id="columnToggleMenu">
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-prod" checked> Nama Produk</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-code" checked> Kode Produk</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-uom" checked> Kemasan</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-segment" checked> Segment</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-avail" checked> Stok Fisik</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-res" checked> Reserved (DO)</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv-ready" checked> Stok Ready</label></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">
                    Cari Stok
                </button>
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
                    <th class="col-inv-prod" style="min-width: 200px;">Nama Produk</th>
                    <th class="col-inv-code">Kode Produk</th>
                    <th class="col-inv-uom text-center">Kemasan</th>
                    <th class="col-inv-segment">Segment</th>
                    <th class="col-inv-avail text-end">Stok Fisik</th>
                    <th class="col-inv-res text-end">Reserved (DO)</th>
                    <th class="col-inv-ready text-end">Stok Ready</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                    @php
                        $qtyAvailable = (float) $stock->qty_available;
                        $qtyReserved = (float) $stock->qty_reserved;
                        $qtyNet = $qtyAvailable - $qtyReserved;
                    @endphp
                    <tr>
                        <td class="text-center fw-medium text-secondary text-nowrap">{{ $stocks->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold text-dark col-inv-prod">{{ $stock->product->product_name ?? '-' }}</td>
                        <td class="fw-bold text-dark text-nowrap col-inv-code">{{ $stock->product->product_code ?? '-' }}</td>
                        <td class="text-center text-nowrap col-inv-uom">
                            @php
                                $uom = strtoupper($stock->product->uom ?? 'BOX');
                            @endphp
                            <span class="badge {{ $uom === 'SACK' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis' }} px-3 py-1 rounded-pill fw-bold">
                                {{ $uom }}
                            </span>
                        </td>
                        <td class="text-nowrap col-inv-segment">{{ $stock->product->segment ?? '-' }}</td>
                        <td class="text-end fw-medium text-dark text-nowrap col-inv-avail">{{ number_format($qtyAvailable, 0, ',', '.') }}</td>
                        <td class="text-end fw-medium text-warning text-nowrap col-inv-res">{{ number_format($qtyReserved, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-success text-nowrap col-inv-ready">{{ number_format($qtyNet, 0, ',', '.') }}</td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('inventory.show', $stock) }}" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Riwayat Mutasi"><i class="bi bi-clock-history"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Data stok barang belum tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            {{ $stocks->links() }}
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageKey = 'toggle_cols_' + window.location.pathname.replace(/\//g, '_');
    const checkboxes = document.querySelectorAll('.toggle-column');
    const savedState = JSON.parse(localStorage.getItem(pageKey)) || {};

    checkboxes.forEach(cb => {
        const colClass = cb.getAttribute('data-col');
        if (savedState[colClass] !== undefined) {
            cb.checked = savedState[colClass];
        }
        toggleVisibility(colClass, cb.checked);

        cb.addEventListener('change', function() {
            toggleVisibility(colClass, this.checked);
            saveState();
        });
    });

    function toggleVisibility(colClass, isVisible) {
        document.querySelectorAll('.' + colClass).forEach(el => {
            el.style.display = isVisible ? '' : 'none';
        });
    }

    function saveState() {
        const state = {};
        checkboxes.forEach(cb => {
            state[cb.getAttribute('data-col')] = cb.checked;
        });
        localStorage.setItem(pageKey, JSON.stringify(state));
    }
});
</script>
@endsection
