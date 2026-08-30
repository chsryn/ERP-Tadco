@extends('layouts.app')

@section('title', 'Shipment - ERP TADCO')
@section('page_title', 'Daftar Pengiriman (Shipment)')
@section('page_subtitle', 'Pengiriman armada barang, penerbitan surat jalan, dan aktualisasi fisik stok')

@section('page_action')
<a href="{{ route('shipments.create') }}" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-plus-lg me-1"></i> Buat Pengiriman
</a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('shipments.index') }}" class="row g-2">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        name="search"
                        class="form-control bg-light border-start-0"
                        placeholder="Cari nomor shipment, nomor DO, nama sopir, plat kendaraan..."
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
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-cust" checked> Customer</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-no" checked> No. Shipment</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-do" checked> No. DO</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-date" checked> Tanggal Kirim</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-driver" checked> Sopir</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-vehicle" checked> No. Plat</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-shp-status" checked> Status</label></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">
                    Cari Shipment
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
                    <th class="col-shp-cust">Customer</th>
                    <th class="col-shp-no">No. Shipment</th>
                    <th class="col-shp-do">No. DO</th>
                    <th class="col-shp-date">Tanggal Kirim</th>
                    <th class="col-shp-driver">Sopir</th>
                    <th class="col-shp-vehicle">No. Plat</th>
                    <th class="col-shp-status text-center">Status</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $shipment)
                    <tr>
                        <td class="text-center fw-medium text-secondary text-nowrap">{{ $shipments->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold text-dark col-shp-cust">{{ $shipment->deliveryOrder->customer->customer_name ?? '-' }}</td>
                        <td class="fw-bold text-dark text-nowrap col-shp-no">{{ $shipment->shipment_number }}</td>
                        <td class="fw-medium text-primary text-nowrap col-shp-do">{{ $shipment->deliveryOrder->do_number ?? '-' }}</td>
                        <td class="text-nowrap col-shp-date">{{ $shipment->shipment_date->format('d/m/Y') }}</td>
                        <td class="text-nowrap col-shp-driver">{{ $shipment->driver_name ?? '-' }}</td>
                        <td class="text-nowrap col-shp-vehicle"><span class="badge bg-light text-dark border">{{ $shipment->vehicle_no ?? '-' }}</span></td>
                        <td class="text-center text-nowrap col-shp-status">
                            @if($shipment->status === 'shipped')
                                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill">Shipped</span>
                            @elseif($shipment->status === 'received')
                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Received</span>
                            @elseif($shipment->status === 'cancelled')
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">Cancelled</span>
                            @else
                                <span class="badge bg-dark px-3 py-1 rounded-pill">{{ ucfirst($shipment->status) }}</span>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex justify-content-center gap-2">
                                <!-- Detail -->
                                <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Detail"><i class="bi bi-eye"></i></a>
                                
                                <!-- Tandai Diterima -->
                                <button type="button" class="btn btn-sm btn-light border text-success rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#receiveShipmentModal{{ $shipment->id }}" title="Tandai Diterima" {{ $shipment->status !== 'shipped' ? 'disabled' : '' }}><i class="bi bi-check2-circle"></i></button>
                                
                                <!-- Batalkan Shipment -->
                                <form action="{{ route('shipments.destroy', $shipment) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pengiriman ini? Stok akan dikembalikan.')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Batalkan Pengiriman" {{ $shipment->status !== 'shipped' ? 'disabled' : '' }}><i class="bi bi-x-circle"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Data pengiriman belum tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($shipments->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            {{ $shipments->links() }}
        </div>
    @endif
</div>

@foreach($shipments as $shipment)
    @if($shipment->status === 'shipped')
    <div class="modal fade" id="receiveShipmentModal{{ $shipment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('shipments.mark_received', $shipment->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Konfirmasi Terima Barang</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <p>Tandai pengiriman <strong>{{ $shipment->shipment_number }}</strong> sebagai diterima.</p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Diterima</label>
                            <input type="date" name="received_date" class="form-control" value="{{ date('Y-m-d') }}" required max="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4">Simpan & Potong Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

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
