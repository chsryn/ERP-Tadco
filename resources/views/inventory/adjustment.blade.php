<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Adjustment Stok - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Adjustment Stok</h3>
        <small class="text-muted">Catat barang masuk, barang keluar, rusak, retur, atau penyesuaian manual</small>
    </div>

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

    <form action="{{ route('inventory.adjustment.store') }}" method="POST" class="card">
        @csrf

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gudang</label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->warehouse_code }} - {{ $warehouse->warehouse_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Produk</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->product_code }} - {{ $product->product_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Jenis Pergerakan</label>
                    <select name="movement_type" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="in" {{ old('movement_type') == 'in' ? 'selected' : '' }}>Barang Masuk</option>
                        <option value="out" {{ old('movement_type') == 'out' ? 'selected' : '' }}>Barang Keluar</option>
                        <option value="return" {{ old('movement_type') == 'return' ? 'selected' : '' }}>Retur Masuk</option>
                        <option value="damage" {{ old('movement_type') == 'damage' ? 'selected' : '' }}>Barang Rusak</option>
                        <option value="adjustment_in" {{ old('movement_type') == 'adjustment_in' ? 'selected' : '' }}>Adjustment Tambah</option>
                        <option value="adjustment_out" {{ old('movement_type') == 'adjustment_out' ? 'selected' : '' }}>Adjustment Kurang</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Jumlah Qty</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="qty"
                        class="form-control"
                        value="{{ old('qty') }}"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Pergerakan</label>
                    <input
                        type="datetime-local"
                        name="movement_date"
                        class="form-control"
                        value="{{ old('movement_date', now()->format('Y-m-d\TH:i')) }}"
                        required
                    >
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan Adjustment</button>
        </div>
    </form>
</div>
</body>
</html>
