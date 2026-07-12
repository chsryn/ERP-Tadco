<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Shipment - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Detail Shipment</h3>
            <small class="text-muted">{{ $shipment->shipment_number }}</small>
        </div>

        <a href="{{ route('shipments.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            Informasi Pengiriman
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Nomor Shipment</th>
                    <td>{{ $shipment->shipment_number }}</td>
                </tr>

                <tr>
                    <th>Nomor DO</th>
                    <td>{{ $shipment->deliveryOrder->do_number ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Customer</th>
                    <td>
                        {{ $shipment->deliveryOrder->customer->customer_code ?? '-' }}
                        -
                        {{ $shipment->deliveryOrder->customer->customer_name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <th>Gudang</th>
                    <td>{{ $shipment->deliveryOrder->warehouse->warehouse_name ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Tanggal Pengiriman</th>
                    <td>{{ $shipment->shipment_date->format('d/m/Y') }}</td>
                </tr>

                <tr>
                    <th>Sopir</th>
                    <td>{{ $shipment->driver_name ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Kendaraan</th>
                    <td>{{ $shipment->vehicle_no ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td><span class="badge bg-success">{{ $shipment->status }}</span></td>
                </tr>

                <tr>
                    <th>Catatan</th>
                    <td>{{ $shipment->notes ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Item yang Dikirim
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th>Strata</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($shipment->deliveryOrder->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_code ?? '-' }}</td>
                            <td>{{ $item->product->product_name ?? '-' }}</td>
                            <td>{{ $item->tier_code }}</td>
                            <td class="text-end">{{ number_format($item->qty, 2, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Total DO</th>
                        <th class="text-end">
                            Rp {{ number_format($shipment->deliveryOrder->total_amount, 0, ',', '.') }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Riwayat Stock Movement
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>Gudang</th>
                        <th>Produk</th>
                        <th>Jenis</th>
                        <th class="text-end">Qty</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($stockMovements as $movement)
                        <tr>
                            <td>{{ $movement->movement_date->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->warehouse->warehouse_name ?? '-' }}</td>
                            <td>{{ $movement->product->product_name ?? '-' }}</td>
                            <td><span class="badge bg-danger">{{ $movement->movement_type }}</span></td>
                            <td class="text-end">{{ number_format($movement->qty, 2, ',', '.') }}</td>
                            <td>{{ $movement->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada stock movement.
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
