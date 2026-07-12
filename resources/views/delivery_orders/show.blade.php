<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail DO - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Detail Delivery Order</h3>
            <small class="text-muted">{{ $deliveryOrder->do_number }}</small>
        </div>

        <a href="{{ route('delivery_orders.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

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

    <div class="card mb-4">
        <div class="card-header">
            Informasi DO
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 250px;">Nomor DO</th>
                    <td>{{ $deliveryOrder->do_number }}</td>
                </tr>

                <tr>
                    <th>Customer</th>
                    <td>
                        {{ $deliveryOrder->customer->customer_code ?? '-' }}
                        -
                        {{ $deliveryOrder->customer->customer_name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <th>Gudang</th>
                    <td>{{ $deliveryOrder->warehouse->warehouse_name ?? '-' }}</td>
                </tr>

                <tr>
                    <th>Tanggal DO</th>
                    <td>{{ $deliveryOrder->do_date->format('d/m/Y') }}</td>
                </tr>

                <tr>
                    <th>Rencana Kirim</th>
                    <td>
                        {{ $deliveryOrder->planned_delivery_date ? $deliveryOrder->planned_delivery_date->format('d/m/Y') : '-' }}
                    </td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>
                        @if($deliveryOrder->status === 'validated')
                            <span class="badge bg-success">Validated</span>
                        @elseif($deliveryOrder->status === 'shipped')
                            <span class="badge bg-primary">Shipped</span>
                        @elseif($deliveryOrder->status === 'cancelled')
                            <span class="badge bg-secondary">Cancelled</span>
                        @else
                            <span class="badge bg-dark">{{ $deliveryOrder->status }}</span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Catatan</th>
                    <td>{{ $deliveryOrder->notes ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Item DO
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
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($deliveryOrder->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_code ?? '-' }}</td>
                            <td>{{ $item->product->product_name ?? '-' }}</td>
                            <td>{{ $item->tier_code }}</td>
                            <td class="text-end">{{ number_format($item->qty, 2, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-end">{{ $item->discount_rate }}</td>
                            <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="7" class="text-end">Total</th>
                        <th class="text-end">
                            Rp {{ number_format($deliveryOrder->total_amount, 0, ',', '.') }}
                        </th>
                    </tr>
                </tfoot>
            </table>

            @if($deliveryOrder->status !== 'cancelled' && $deliveryOrder->status !== 'shipped')
                <form action="{{ route('delivery_orders.destroy', $deliveryOrder) }}" method="POST"
                      onsubmit="return confirm('Yakin ingin membatalkan DO ini? Stok reserved akan dikembalikan.')">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        Batalkan DO
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
</body>
</html>
