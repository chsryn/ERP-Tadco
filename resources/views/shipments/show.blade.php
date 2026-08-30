@extends('layouts.app')

@section('title', 'Detail Shipment - ERP TADCO')
@section('page_title', 'Detail Shipment')
@section('page_subtitle', 'Informasi pengiriman armada dan rincian item barang delivered')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-truck me-2 text-primary"></i>Surat Jalan / Shipment: {{ $shipment->shipment_number }}</h4>
                <p class="text-muted small mb-0">Customer: <span class="fw-bold text-dark">{{ $shipment->deliveryOrder->customer->customer_name ?? '-' }}</span></p>
            </div>
            <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Pengiriman</h6>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">Delivered (Terkirim)</span>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr class="border-bottom border-light">
                                <th style="width: 220px;" class="text-secondary fw-semibold">Nomor Shipment</th>
                                <td class="fw-bold text-dark">{{ $shipment->shipment_number }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nomor DO Terkait</th>
                                <td class="fw-bold text-primary">{{ $shipment->deliveryOrder->do_number ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Customer</th>
                                <td class="fw-bold text-dark">
                                    {{ $shipment->deliveryOrder->customer->customer_code ?? '-' }} - {{ $shipment->deliveryOrder->customer->customer_name ?? '-' }}
                                </td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Tanggal Pengiriman</th>
                                <td class="fw-medium text-dark">{{ $shipment->shipment_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nama Sopir / Pengemudi</th>
                                <td class="fw-medium text-dark">{{ $shipment->driver_name ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">No. Plat Kendaraan</th>
                                <td class="fw-medium text-dark">{{ $shipment->vehicle_no ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary fw-semibold">Catatan Pengiriman</th>
                                <td>{{ $shipment->notes ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box me-2 text-success"></i>Item Barang yang Dikirim</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered border-light align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Kode Produk</th>
                                <th>Nama Produk</th>
                                <th class="text-end">Diskon (%)</th>
                                <th class="text-end">Qty Dikirim</th>
                                <th class="text-end">Harga Dasar</th>
                                <th class="text-end">Harga Bersih</th>
                                <th class="text-end">Total Line</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shipment->deliveryOrder->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-medium">{{ $item->product->product_code ?? '-' }}</td>
                                    <td class="fw-semibold text-dark">{{ $item->product->product_name ?? '-' }}</td>
                                    <td class="text-end text-secondary">{{ number_format((float) ($item->discount_percentage ?? 0), 0, ',', '.') }}%</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                    <td class="text-end text-secondary">Rp {{ number_format((float) ($item->base_price ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-end text-secondary">Rp {{ number_format((float) ($item->final_price ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada rincian item.</td>
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
