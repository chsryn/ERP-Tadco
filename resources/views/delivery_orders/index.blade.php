@extends('layouts.app')

@section('title', 'Delivery Order - ERP TADCO')
@section('page_title', 'Daftar Delivery Order')
@section('page_subtitle', 'Pencatatan reservasi stok customer')

@section('page_action')
<a href="{{ route('delivery_orders.create') }}" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-plus-lg me-1"></i> Buat Delivery Order
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

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('delivery_orders.index') }}" class="row g-2">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        name="search"
                        class="form-control bg-light border-start-0"
                        placeholder="Cari nomor DO, nama customer, kode customer..."
                        value="{{ $search ?? '' }}"
                    >
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <div class="dropdown d-grid">
                    <button class="btn btn-outline-secondary rounded-pill fw-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-layout-three-columns me-1"></i> Kolom
                    </button>
                    <ul class="dropdown-menu shadow-sm p-2" id="columnToggleMenu">
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do-cust" checked> Customer</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do-no" checked> No. DO</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do-date" checked> Tanggal DO</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do-plan" checked> Rencana Kirim</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do-total" checked> Total Amount</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do-status" checked> Status</label></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">
                    Cari DO
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
                    <th class="col-do-cust">Customer</th>
                    <th class="col-do-no">No. DO</th>
                    <th class="col-do-date">Tanggal DO</th>
                    <th class="col-do-plan">Rencana Kirim</th>
                    <th class="col-do-total text-end">Total Amount</th>
                    <th class="col-do-status text-center">Status</th>
                    <th style="width: 160px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryOrders as $deliveryOrder)
                    <tr>
                        <td class="text-center fw-medium text-secondary text-nowrap">{{ $deliveryOrders->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold text-dark col-do-cust">
                            {{ $deliveryOrder->customer->customer_name ?? '-' }}
                        </td>
                        <td class="fw-bold text-dark text-nowrap col-do-no">{{ $deliveryOrder->do_number }}</td>
                        <td class="text-nowrap col-do-date">{{ $deliveryOrder->do_date->format('d/m/Y') }}</td>
                        <td class="text-nowrap col-do-plan">{{ $deliveryOrder->planned_delivery_date ? $deliveryOrder->planned_delivery_date->format('d/m/Y') : '-' }}</td>
                        <td class="text-end fw-bold text-dark text-nowrap col-do-total">
                            Rp {{ number_format($deliveryOrder->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center text-nowrap col-do-status">
                            @if($deliveryOrder->status === 'validated')
                                <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1 rounded-pill">Siap Dikirim</span>
                            @elseif($deliveryOrder->status === 'shipped')
                                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill">Sedang Dikirim</span>
                            @elseif($deliveryOrder->status === 'delivered')
                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Diterima</span>
                            @elseif($deliveryOrder->status === 'cancelled')
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">Dibatalkan</span>
                            @else
                                <span class="badge bg-dark px-3 py-1 rounded-pill">{{ ucfirst($deliveryOrder->status) }}</span>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex justify-content-center gap-2">
                                <!-- Detail -->
                                <a href="{{ route('delivery_orders.show', $deliveryOrder) }}" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Detail"><i class="bi bi-eye"></i></a>
                                
                                <!-- Batalkan DO -->
                                <form action="{{ route('delivery_orders.destroy', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan DO ini?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Batalkan" {{ $deliveryOrder->status !== 'validated' ? 'disabled' : '' }}><i class="bi bi-x-circle"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Data Delivery Order belum tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($deliveryOrders->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            {{ $deliveryOrders->links() }}
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
