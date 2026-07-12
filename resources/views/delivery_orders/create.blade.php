<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Delivery Order - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Buat Delivery Order</h3>
        <small class="text-muted">Input DO dan validasi stok tersedia</small>
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

    <form action="{{ route('delivery_orders.store') }}" method="POST" class="card">
        @csrf

        <div class="card-body">
            <h5 class="mb-3">Informasi DO</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->customer_code }} - {{ $customer->customer_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
                    <label class="form-label">Tanggal DO</label>
                    <input type="date" name="do_date" class="form-control"
                           value="{{ old('do_date', now()->format('Y-m-d')) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Rencana Tanggal Kirim</label>
                    <input type="date" name="planned_delivery_date" class="form-control"
                           value="{{ old('planned_delivery_date') }}">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Item DO</h5>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 45%;">Produk</th>
                            <th>Strata</th>
                            <th>Qty</th>
                        </tr>
                    </thead>

                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <select name="product_id[]" class="form-select">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ old("product_id.$i") == $product->id ? 'selected' : '' }}>
                                                {{ $product->product_code }} - {{ $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select name="tier_code[]" class="form-select">
                                        <option value="">-- Strata --</option>
                                        @foreach(['S1', 'S2', 'S3', 'S4'] as $tier)
                                            <option value="{{ $tier }}"
                                                {{ old("tier_code.$i") == $tier ? 'selected' : '' }}>
                                                {{ $tier }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        name="qty[]"
                                        class="form-control"
                                        value="{{ old("qty.$i") }}"
                                    >
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <small class="text-muted">
                Kosongkan baris item yang tidak digunakan. Sistem akan mengecek stok tersedia sebelum DO disimpan.
            </small>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('delivery_orders.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan DO</button>
        </div>
    </form>
</div>
</body>
</html>
