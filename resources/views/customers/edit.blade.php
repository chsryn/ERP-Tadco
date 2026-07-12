<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Customer - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Edit Customer</h3>
        <small class="text-muted">{{ $customer->customer_code }} - {{ $customer->customer_name }}</small>
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

    <form action="{{ route('customers.update', $customer) }}" method="POST" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kode Customer</label>
                    <input type="text" name="customer_code" class="form-control"
                           value="{{ old('customer_code', $customer->customer_code) }}" required>
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">Nama Customer</label>
                    <input type="text" name="customer_name" class="form-control"
                           value="{{ old('customer_name', $customer->customer_name) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Provinsi</label>
                    <input type="text" name="province" class="form-control"
                           value="{{ old('province', $customer->province) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Kota/Kabupaten</label>
                    <input type="text" name="city" class="form-control"
                           value="{{ old('city', $customer->city) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Kecamatan</label>
                    <input type="text" name="district" class="form-control"
                           value="{{ old('district', $customer->district) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Kelurahan/Desa</label>
                    <input type="text" name="sub_district" class="form-control"
                           value="{{ old('sub_district', $customer->sub_district) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Jenis Usaha</label>
                    <input type="text" name="type_of_business" class="form-control"
                           value="{{ old('type_of_business', $customer->type_of_business) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Market</label>
                    <input type="text" name="market" class="form-control"
                           value="{{ old('market', $customer->market) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipe Customer</label>
                    <input type="text" name="customer_type" class="form-control"
                           value="{{ old('customer_type', $customer->customer_type) }}">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address) }}</textarea>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                            {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">Customer aktif</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
</body>
</html>
