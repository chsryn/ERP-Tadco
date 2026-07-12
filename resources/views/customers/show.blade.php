<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Customer - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Detail Customer</h3>
            <small class="text-muted">{{ $customer->customer_code }} - {{ $customer->customer_name }}</small>
        </div>

        <div>
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Kode Customer</th>
                    <td>{{ $customer->customer_code }}</td>
                </tr>

                <tr>
                    <th>Nama Customer</th>
                    <td>{{ $customer->customer_name }}</td>
                </tr>

                <tr>
                    <th>Provinsi</th>
                    <td>{{ $customer->province }}</td>
                </tr>

                <tr>
                    <th>Kota/Kabupaten</th>
                    <td>{{ $customer->city }}</td>
                </tr>

                <tr>
                    <th>Kecamatan</th>
                    <td>{{ $customer->district }}</td>
                </tr>

                <tr>
                    <th>Kelurahan/Desa</th>
                    <td>{{ $customer->sub_district }}</td>
                </tr>

                <tr>
                    <th>Alamat</th>
                    <td>{{ $customer->address }}</td>
                </tr>

                <tr>
                    <th>Jenis Usaha</th>
                    <td>{{ $customer->type_of_business }}</td>
                </tr>

                <tr>
                    <th>Market</th>
                    <td>{{ $customer->market }}</td>
                </tr>

                <tr>
                    <th>Tipe Customer</th>
                    <td>{{ $customer->customer_type }}</td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>
                        @if($customer->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
