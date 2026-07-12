<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Edit Produk</h3>
        <small class="text-muted">{{ $product->product_code }} - {{ $product->product_name }}</small>
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

    <form action="{{ route('products.update', $product) }}" method="POST" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            <h5 class="mb-3">Informasi Produk</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kode Produk</label>
                    <input type="text" name="product_code" class="form-control"
                           value="{{ old('product_code', $product->product_code) }}" required>
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="product_name" class="form-control"
                           value="{{ old('product_name', $product->product_name) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">UoM</label>
                    <input type="text" name="uom" class="form-control"
                           value="{{ old('uom', $product->uom) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">UoM Secondary</label>
                    <input type="text" name="uom_secondary" class="form-control"
                           value="{{ old('uom_secondary', $product->uom_secondary) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Segment</label>
                    <input type="text" name="segment" class="form-control"
                           value="{{ old('segment', $product->segment) }}">
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                            {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">Produk aktif</label>
                    </div>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Harga Berdasarkan Strata</h5>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Strata</th>
                            <th>Harga</th>
                            <th>Diskon Rate</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach(['S1', 'S2', 'S3', 'S4'] as $tier)
                            @php
                                $priceData = $prices->get($tier);
                            @endphp

                            <tr>
                                <td>{{ $tier }}</td>
                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="prices[{{ $tier }}][price]"
                                        class="form-control"
                                        value="{{ old('prices.' . $tier . '.price', $priceData->price ?? 0) }}"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        step="0.00001"
                                        name="prices[{{ $tier }}][discount_rate]"
                                        class="form-control"
                                        value="{{ old('prices.' . $tier . '.discount_rate', $priceData->discount_rate ?? 0) }}"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
</body>
</html>
