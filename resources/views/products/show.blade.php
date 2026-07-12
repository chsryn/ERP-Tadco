<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Produk - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Detail Produk</h3>
            <small class="text-muted">{{ $product->product_code }} - {{ $product->product_name }}</small>
        </div>

        <div>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Informasi Produk
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Kode Produk</th>
                    <td>{{ $product->product_code }}</td>
                </tr>

                <tr>
                    <th>Nama Produk</th>
                    <td>{{ $product->product_name }}</td>
                </tr>

                <tr>
                    <th>UoM</th>
                    <td>{{ $product->uom }}</td>
                </tr>

                <tr>
                    <th>UoM Secondary</th>
                    <td>{{ $product->uom_secondary }}</td>
                </tr>

                <tr>
                    <th>Segment</th>
                    <td>{{ $product->segment }}</td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>
                        @if($product->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Harga Berdasarkan Strata
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Strata</th>
                        <th>Harga</th>
                        <th>Diskon Rate</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($product->prices as $price)
                        <tr>
                            <td>{{ $price->tier_code }}</td>
                            <td>Rp {{ number_format($price->price, 0, ',', '.') }}</td>
                            <td>{{ $price->discount_rate }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                Data harga belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
