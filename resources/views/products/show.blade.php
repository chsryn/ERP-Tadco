@extends('layouts.app')

@section('title', 'Detail Produk - ERP TADCO')
@section('page_title', 'Detail Produk')
@section('page_subtitle', 'Informasi detail master produk, harga dasar (base price), dan skema diskon volume')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-box-seam me-2 text-primary"></i>{{ $product->product_name }}</h4>
                <p class="text-muted small mb-0">Kode Produk: <span class="badge bg-secondary">{{ $product->product_code }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm px-3 rounded-pill text-dark fw-semibold">
                    <i class="bi bi-pencil me-1"></i> Edit Produk
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Utama Produk</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr class="border-bottom border-light">
                                <th style="width: 220px;" class="text-secondary fw-semibold">Kode Produk</th>
                                <td class="fw-bold text-dark">{{ $product->product_code }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nama Produk</th>
                                <td class="fw-bold text-dark">{{ $product->product_name }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Harga Dasar (Base Price)</th>
                                <td class="fw-bold text-primary fs-5">Rp {{ number_format($product->base_price, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Satuan Barang (UoM)</th>
                                <td>
                                    @php
                                        $currentUom = strtoupper($product->uom ?? 'BOX');
                                    @endphp
                                    <span class="badge {{ $currentUom === 'SACK' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis' }} px-3 py-2 rounded-pill fw-bold fs-6">
                                        <i class="bi {{ $currentUom === 'SACK' ? 'bi-archive' : 'bi-box' }} me-1"></i>
                                        {{ $currentUom }}
                                    </span>
                                </td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Berat Bersih (Net Weight)</th>
                                <td class="fw-medium text-dark">{{ $product->net_weight ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Segmentasi</th>
                                <td>{{ $product->segment ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary fw-semibold">Status Operasional</th>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-percent me-2 text-success"></i>Skema Diskon Kuantitas (Volume Discount)</h6>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Aturan: Base Price - (Base Price * Diskon %)</span>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Strata</th>
                                <th>Min. Qty</th>
                                <th>Max. Qty</th>
                                <th class="text-center">Persentase Diskon</th>
                                <th class="text-end">Harga Bersih Satuan (Net Price)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->discounts as $discount)
                                @php
                                    $netPrice = round((float) $product->base_price * (1 - ((float) $discount->discount_percentage / 100)), 2);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-dark px-3 py-2">{{ $discount->strata_level }}</span>
                                    </td>
                                    <td class="fw-medium text-dark">{{ number_format($discount->min_qty, 2, ',', '.') }}</td>
                                    <td class="fw-medium text-dark">{{ $discount->max_qty ? number_format($discount->max_qty, 2, ',', '.') : 'Tak Terhingga (∞)' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fs-6 fw-bold">{{ number_format($discount->discount_percentage, 2) }}%</span>
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        Rp {{ number_format($netPrice, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                        Data skema diskon belum dikonfigurasi.
                                    </td>
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
