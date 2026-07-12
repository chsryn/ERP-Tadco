@extends('layouts.app')

@section('title', 'Inventory - ERP TADCO')
@section('page_title', 'Inventory')
@section('page_subtitle', 'Monitoring stok available, reserved, dan stok bisa dipakai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Stok Barang</h5>
            <small class="text-muted">Ringkasan stok per produk dan gudang</small>
        </div>

        <a href="{{ route('inventory.adjustment.create') }}" class="btn btn-primary">
            + Adjustment Stok
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari kode produk, nama produk, segment..."
                        value="{{ $search ?? '' }}"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-dark">
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Gudang</th>
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th>Segment</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Bisa Dipakai</th>
                        <th>Status</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($stockBalances as $stockBalance)
                        @php
                            $usableStock = (float) $stockBalance->qty_available - (float) $stockBalance->qty_reserved;
                        @endphp

                        <tr>
                            <td>{{ $stockBalances->firstItem() + $loop->index }}</td>
                            <td>{{ $stockBalance->warehouse->warehouse_name ?? '-' }}</td>
                            <td>{{ $stockBalance->product->product_code ?? '-' }}</td>
                            <td>{{ $stockBalance->product->product_name ?? '-' }}</td>
                            <td>{{ $stockBalance->product->segment ?? '-' }}</td>

                            <td class="text-end">
                                {{ number_format((float) $stockBalance->qty_available, 2, ',', '.') }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) $stockBalance->qty_reserved, 2, ',', '.') }}
                            </td>

                            <td class="text-end">
                                {{ number_format($usableStock, 2, ',', '.') }}
                            </td>

                            <td>
                                @if($usableStock <= 0)
                                    <span class="badge bg-danger">Habis / Tidak Bisa Dipakai</span>
                                @elseif($stockBalance->qty_available <= 10)
                                    <span class="badge bg-warning text-dark">Stok Rendah</span>
                                @else
                                    <span class="badge bg-success">Aman</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('inventory.show', $stockBalance) }}" class="btn btn-sm btn-info">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                Data stok belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $stockBalances->links() }}
            </div>
        </div>
    </div>
@endsection
