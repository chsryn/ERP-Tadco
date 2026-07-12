<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Shipment - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Buat Shipment</h3>
        <small class="text-muted">Proses DO menjadi pengiriman dan kurangi stok</small>
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

    <form action="{{ route('shipments.store') }}" method="POST" class="card">
        @csrf

        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Delivery Order</label>
                    <select name="delivery_order_id" class="form-select" required>
                        <option value="">-- Pilih DO yang sudah validated --</option>

                        @foreach($deliveryOrders as $do)
                            <option value="{{ $do->id }}" {{ old('delivery_order_id') == $do->id ? 'selected' : '' }}>
                                {{ $do->do_number }}
                                -
                                {{ $do->customer->customer_name ?? '-' }}
                                -
                                Total Rp {{ number_format($do->total_amount, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>

                    @if($deliveryOrders->isEmpty())
                        <small class="text-danger">
                            Belum ada DO dengan status validated. Buat DO terlebih dahulu.
                        </small>
                    @endif
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Pengiriman</label>
                    <input
                        type="date"
                        name="shipment_date"
                        class="form-control"
                        value="{{ old('shipment_date', now()->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Nama Sopir</label>
                    <input
                        type="text"
                        name="driver_name"
                        class="form-control"
                        value="{{ old('driver_name') }}"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Nomor Kendaraan</label>
                    <input
                        type="text"
                        name="vehicle_no"
                        class="form-control"
                        value="{{ old('vehicle_no') }}"
                    >
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('shipments.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary" {{ $deliveryOrders->isEmpty() ? 'disabled' : '' }}>
                Simpan Shipment
            </button>
        </div>
    </form>
</div>
</body>
</html>
