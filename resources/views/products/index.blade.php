@extends('layouts.app')

@section('title', 'Product - ERP TADCO')
@section('page_title', 'Product')
@section('page_subtitle', 'Master data produk dan harga S1 sampai S4')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Product</h5>
            <small class="text-muted">Data item, segment, UoM, dan harga strata</small>
        </div>

        <a href="{{ route('products.create') }}" class="btn btn-primary">
            + Tambah Product
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
            <form method="GET" action="{{ route('products.index') }}" class="row g-2">
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
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th>Segment</th>
                        <th>UoM</th>
                        <th>UoM 2</th>
                        <th class="text-end">S1</th>
                        <th class="text-end">S2</th>
                        <th class="text-end">S3</th>
                        <th class="text-end">S4</th>
                        <th>Status</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($products as $product)
                        @php
                            $priceS1 = $product->prices->firstWhere('tier_code', 'S1');
                            $priceS2 = $product->prices->firstWhere('tier_code', 'S2');
                            $priceS3 = $product->prices->firstWhere('tier_code', 'S3');
                            $priceS4 = $product->prices->firstWhere('tier_code', 'S4');
                        @endphp

                        <tr>
                            <td>{{ $products->firstItem() + $loop->index }}</td>
                            <td>{{ $product->product_code }}</td>
                            <td>{{ $product->product_name }}</td>
                            <td>{{ $product->segment ?? '-' }}</td>
                            <td>{{ $product->uom ?? '-' }}</td>
                            <td>{{ $product->uom_secondary ?? '-' }}</td>

                            <td class="text-end">
                                Rp {{ number_format((float) ($priceS1->price ?? 0), 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                Rp {{ number_format((float) ($priceS2->price ?? 0), 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                Rp {{ number_format((float) ($priceS3->price ?? 0), 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                Rp {{ number_format((float) ($priceS4->price ?? 0), 0, ',', '.') }}
                            </td>

                            <td>
                                @if($product->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-info">
                                        Detail
                                    </a>

                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form action="{{ route('products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus product ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">
                                Data product belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
