<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Stok - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Detail Stok Produk</h3>
            <small class="text-muted">
                {{ $stockBalance->product->product_code ?? '-' }} -
                {{ $stockBalance->product->product_name ?? '-' }}
            </small>
        </div>

        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Informasi Stok
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Gudang</th>
                    <td>{{ $stockBalance->warehouse->warehouse_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Kode Produk</th>
                    <td>{{ $stockBalance->product->product_code ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Nama Produk</th>
                    <td>{{ $stockBalance->product->product_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Stok Tersedia</th>
                    <td>{{ number_format($stockBalance->qty_available, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Stok Reserved</th>
                    <td>{{ number_format($stockBalance->qty_reserved, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Stok Bisa Dipakai</th>
                    <td>{{ number_format($stockBalance->qty_available - $stockBalance->qty_reserved, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Riwayat Pergerakan Stok
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th class="text-end">Qty</th>
                        <th>Sumber</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->movement_date->format('d/m/Y H:i') }}</td>
                            <td>
                                @if(in_array($movement->movement_type, ['in', 'return', 'adjustment_in']))
                                    <span class="badge bg-success">{{ $movement->movement_type }}</span>
                                @else
                                    <span class="badge bg-danger">{{ $movement->movement_type }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($movement->qty, 2, ',', '.') }}</td>
                            <td>{{ $movement->source_type ?? '-' }}</td>
                            <td>{{ $movement->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                Belum ada riwayat pergerakan stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
