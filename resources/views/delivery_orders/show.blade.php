@extends('layouts.app')

@section('title', 'Detail Delivery Order - ERP TADCO')
@section('page_title', 'Detail Delivery Order')
@section('page_subtitle', 'Dokumen reservasi stok dan detail transaksi DO')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-2 text-primary"></i>DO: {{ $deliveryOrder->do_number }}</h4>
                <p class="text-muted small mb-0">Customer: <span class="fw-bold text-dark">{{ $deliveryOrder->customer->customer_name ?? '-' }}</span></p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                @if($deliveryOrder->status !== 'cancelled' && $deliveryOrder->status !== 'shipped' && $deliveryOrder->status !== 'delivered')
                    <form action="{{ route('delivery_orders.destroy', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan DO ini? Stok reserved akan dikembalikan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                            <i class="bi bi-x-circle me-1"></i> Batalkan DO
                        </button>
                    </form>
                @endif
                <a href="{{ route('delivery_orders.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

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
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Transaksi</h6>
                <div>
                    @if ($deliveryOrder->status === 'validated')
                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill fw-bold">Siap Dikirim</span>
                    @elseif($deliveryOrder->status === 'shipped')
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold">Sedang Dikirim</span>
                    @elseif($deliveryOrder->status === 'delivered')
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">Diterima</span>
                    @elseif($deliveryOrder->status === 'cancelled')
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-bold">Dibatalkan</span>
                    @else
                        <span class="badge bg-dark px-3 py-2 rounded-pill">{{ $deliveryOrder->status }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr class="border-bottom border-light">
                                <th style="width: 220px;" class="text-secondary fw-semibold">Nomor DO</th>
                                <td class="fw-bold text-dark">{{ $deliveryOrder->do_number }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Customer</th>
                                <td class="fw-bold text-dark">
                                    {{ $deliveryOrder->customer->customer_code ?? '-' }} - {{ $deliveryOrder->customer->customer_name ?? '-' }}
                                </td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Tanggal DO</th>
                                <td class="fw-medium text-dark">{{ $deliveryOrder->do_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Rencana Kirim</th>
                                <td class="fw-medium text-dark">
                                    {{ $deliveryOrder->planned_delivery_date ? $deliveryOrder->planned_delivery_date->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                            @if($deliveryOrder->received_date)
                                <tr class="border-bottom border-light">
                                    <th class="text-secondary fw-semibold">Tanggal Diterima</th>
                                    <td class="fw-bold text-success">{{ $deliveryOrder->received_date->format('d M Y') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th class="text-secondary fw-semibold">Catatan</th>
                                <td>{{ $deliveryOrder->notes ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box me-2 text-success"></i>Item Produk</h6>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Total Amount: Rp {{ number_format($deliveryOrder->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered border-light align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Diskon (%)</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Dasar</th>
                                <th class="text-end">Harga Bersih</th>
                                <th class="text-end">Total Line</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deliveryOrder->items as $item)
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        {{ $item->product->product_name ?? '-' }}
                                        <span class="badge bg-light text-dark border ms-1">{{ strtoupper($item->product->uom ?? 'BOX') }}</span>
                                    </td>
                                    <td class="text-end text-secondary">{{ number_format((float) ($item->discount_percentage ?? 0), 0, ',', '.') }}%</td>
                                    <td class="text-end fw-medium">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                    <td class="text-end text-secondary">Rp {{ number_format((float) ($item->base_price ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-end text-secondary">Rp {{ number_format((float) ($item->final_price ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada item barang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Grand Total DO:</td>
                                <td class="text-end fs-6 text-primary">Rp {{ number_format($deliveryOrder->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
