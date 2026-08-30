@extends('layouts.app')

@section('title', 'Detail Stok - ERP TADCO')
@section('page_title', 'Detail Mutasi & Saldo Stok')
@section('page_subtitle', 'Informasi ketersediaan stok produk dan riwayat mutasi pergerakan fisik')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-box-seam me-2 text-primary"></i>Stok: {{ $stockBalance->product->product_name ?? '-' }}</h4>
                <p class="text-muted small mb-0">Kode Produk: <span class="badge bg-secondary">{{ $stockBalance->product->product_code ?? '-' }}</span></p>
            </div>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                    <small class="text-secondary fw-semibold">Stok Fisik Tersedia</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1">{{ number_format($stockBalance->qty_available, 0, ',', '.') }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-warning-subtle border-start border-warning border-4">
                    <small class="text-warning-emphasis fw-semibold">Stok Reserved (DO)</small>
                    <h4 class="fw-bold text-warning mb-0 mt-1">{{ number_format($stockBalance->qty_reserved, 0, ',', '.') }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-success-subtle border-start border-success border-4">
                    <small class="text-success-emphasis fw-semibold">Stok Bisa Dipakai (Nett)</small>
                    <h4 class="fw-bold text-success mb-0 mt-1">{{ number_format($stockBalance->qty_available - $stockBalance->qty_reserved, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Mutasi Stok Produk</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Mutasi</th>
                                <th class="text-end">Jumlah (Qty)</th>
                                <th>Sumber / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                <tr>
                                    <td class="fw-medium text-dark">{{ $movement->movement_date->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($movement->movement_type === 'in')
                                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill"><i class="bi bi-arrow-down-left me-1"></i> Masuk</span>
                                        @elseif($movement->movement_type === 'out')
                                            <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill"><i class="bi bi-arrow-up-right me-1"></i> Keluar</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info px-3 py-1 rounded-pill"><i class="bi bi-sliders me-1"></i> Adjustment</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-dark">{{ number_format($movement->qty, 0, ',', '.') }}</td>
                                    <td class="small text-muted">{{ $movement->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada riwayat mutasi stok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
