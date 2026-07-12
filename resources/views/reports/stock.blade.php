<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Stok - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Laporan Stok</h3>
                <small class="text-muted">Ringkasan stok available dan reserved</small>
                {{-- </div>

        <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div> --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.stock.export', request()->query()) }}" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <small class="text-muted">Total Stock Available</small>
                                <h3 class="mb-0">{{ number_format($totalAvailable, 2, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <small class="text-muted">Total Stock Reserved</small>
                                <h3 class="mb-0">{{ number_format($totalReserved, 2, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.stock') }}" class="row g-2">
                            <div class="col-md-8">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Cari produk, kode produk, segment, gudang..."
                                    value="{{ $search }}">
                            </div>

                            <div class="col-md-2">
                                <select name="low_stock_only" class="form-select">
                                    <option value="">Semua Stok</option>
                                    <option value="1" {{ $lowStockOnly ? 'selected' : '' }}>Stok Rendah ≤ 10
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2 d-grid">
                                <button class="btn btn-dark">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Kode Produk</th>
                                    <th>Nama Produk</th>
                                    <th>Segment</th>
                                    <th>Gudang</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Reserved</th>
                                    <th class="text-end">Bisa Dipakai</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($stocks as $stock)
                                    @php
                                        $usableStock = (float) $stock->qty_available - (float) $stock->qty_reserved;
                                    @endphp

                                    <tr>
                                        <td>{{ $stocks->firstItem() + $loop->index }}</td>
                                        <td>{{ $stock->product->product_code ?? '-' }}</td>
                                        <td>{{ $stock->product->product_name ?? '-' }}</td>
                                        <td>{{ $stock->product->segment ?? '-' }}</td>
                                        <td>{{ $stock->warehouse->warehouse_name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($stock->qty_available, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end">{{ number_format($stock->qty_reserved, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end">{{ number_format($usableStock, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Data stok tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{ $stocks->links() }}
                    </div>
                </div>
            </div>
</body>

</html>
