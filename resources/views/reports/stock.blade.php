@extends('layouts.app')

@section('title', 'Laporan Stok - ERP TADCO')
@section('page_title', 'Laporan Stok')
@section('page_subtitle', 'Ringkasan stok available dan reserved')

@section('content')
    <!-- Header & Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0">Data Stok Produk</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('reports.stock.export', request()->query()) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Summary Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase">Total Stock Available</small>
                    <h3 class="mb-0 mt-2 text-primary">{{ number_format($totalAvailable, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase">Total Stock Reserved</small>
                    <h3 class="mb-0 mt-2 text-warning">{{ number_format($totalReserved, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.stock') }}" class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label small text-muted">Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari produk, kode, segment..."
                        value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter Stok</label>
                    <select name="low_stock_only" class="form-select">
                        <option value="">Semua Stok</option>
                        <option value="1" {{ $lowStockOnly ? 'selected' : '' }}>Stok Rendah (≤ 10)</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark">Filter Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Produk</th>
                        <th>Kode Produk</th>
                        <th>Segment</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Bisa Dipakai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php $usableStock = (float) $stock->qty_available - (float) $stock->qty_reserved; @endphp
                        <tr>
                            <td>{{ $stocks->firstItem() + $loop->index }}</td>
                            <td>{{ $stock->product->product_name ?? '-' }}</td>
                            <td><strong>{{ $stock->product->product_code ?? '-' }}</strong></td>
                            <td>{{ $stock->product->segment ?? '-' }}</td>
                            <td class="text-end">{{ number_format($stock->qty_available, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($stock->qty_reserved, 2, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $usableStock <= 10 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($usableStock, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Data stok tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($stocks->hasPages())
                <div class="mt-3">
                    {{ $stocks->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
