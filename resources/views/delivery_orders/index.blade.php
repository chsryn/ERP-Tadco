@extends('layouts.app')

@section('title', 'Delivery Order - ERP TADCO')
@section('page_title', 'Delivery Order')
@section('page_subtitle', 'Dokumen pemesanan dan reservasi stok customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Delivery Order</h5>
            <small class="text-muted">DO akan melakukan reserve stok sebelum pengiriman</small>
        </div>

        <a href="{{ route('delivery_orders.create') }}" class="btn btn-primary">
            + Buat Delivery Order
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

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('delivery_orders.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nomor DO, kode customer, atau nama customer..."
                        value="{{ $search ?? '' }}"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-dark">
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>No DO</th>
                        <th>Tanggal DO</th>
                        <th>Rencana Kirim</th>
                        <th>Customer</th>
                        <th>Gudang</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($deliveryOrders as $deliveryOrder)
                        <tr>
                            <td>{{ $deliveryOrders->firstItem() + $loop->index }}</td>

                            <td>
                                <a href="{{ route('delivery_orders.show', $deliveryOrder) }}" class="text-decoration-none">
                                    {{ $deliveryOrder->do_number }}
                                </a>
                            </td>

                            <td>
                                {{ $deliveryOrder->do_date ? $deliveryOrder->do_date->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ $deliveryOrder->planned_delivery_date ? $deliveryOrder->planned_delivery_date->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ $deliveryOrder->customer->customer_code ?? '-' }}
                                -
                                {{ $deliveryOrder->customer->customer_name ?? '-' }}
                            </td>

                            <td>{{ $deliveryOrder->warehouse->warehouse_name ?? '-' }}</td>

                            <td class="text-end">
                                Rp {{ number_format((float) $deliveryOrder->total_amount, 0, ',', '.') }}
                            </td>

                            <td>
                                @if($deliveryOrder->status === 'validated')
                                    <span class="badge bg-primary">Validated</span>
                                @elseif($deliveryOrder->status === 'shipped')
                                    <span class="badge bg-success">Shipped</span>
                                @elseif($deliveryOrder->status === 'cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-dark">{{ $deliveryOrder->status }}</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('delivery_orders.show', $deliveryOrder) }}" class="btn btn-sm btn-info">
                                        Detail
                                    </a>

                                    @if($deliveryOrder->status === 'validated')
                                        <form action="{{ route('delivery_orders.destroy', $deliveryOrder) }}" method="POST"
                                              onsubmit="return confirm('Yakin ingin membatalkan Delivery Order ini? Reserved stock akan dikembalikan.')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                Belum ada Delivery Order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $deliveryOrders->links() }}
            </div>
        </div>
    </div>
@endsection
