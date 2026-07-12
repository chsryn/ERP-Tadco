@extends('layouts.app')

@section('title', 'Shipment - ERP TADCO')
@section('page_title', 'Shipment / Pengiriman')
@section('page_subtitle', 'Proses pengiriman barang dari Delivery Order dan pengurangan stok')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Shipment</h5>
            <small class="text-muted">Shipment dibuat dari DO yang sudah validated</small>
        </div>

        <a href="{{ route('shipments.create') }}" class="btn btn-primary">
            + Buat Shipment
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('shipments.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari nomor shipment, nomor DO, customer..." value="{{ $search ?? '' }}">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">
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
                        <th>No Shipment</th>
                        <th>No DO</th>
                        <th>Tanggal Kirim</th>
                        <th>Customer</th>
                        <th>Gudang</th>
                        <th>Sopir</th>
                        <th>Kendaraan</th>
                        <th>Status</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($shipments as $shipment)
                        <tr>
                            <td>{{ $shipments->firstItem() + $loop->index }}</td>

                            <td>
                                <a href="{{ route('shipments.show', $shipment) }}" class="text-decoration-none">
                                    {{ $shipment->shipment_number }}
                                </a>
                            </td>

                            <td>{{ $shipment->deliveryOrder->do_number ?? '-' }}</td>

                            <td>
                                {{ $shipment->shipment_date ? $shipment->shipment_date->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                {{ $shipment->deliveryOrder->customer->customer_code ?? '-' }}
                                -
                                {{ $shipment->deliveryOrder->customer->customer_name ?? '-' }}
                            </td>

                            <td>{{ $shipment->deliveryOrder->warehouse->warehouse_name ?? '-' }}</td>
                            <td>{{ $shipment->driver_name ?? '-' }}</td>
                            <td>{{ $shipment->vehicle_no ?? '-' }}</td>

                            <td>
                                @if ($shipment->status === 'delivered')
                                    <span class="badge bg-success">Delivered</span>
                                @else
                                    <span class="badge bg-dark">{{ $shipment->status }}</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-sm btn-info">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                Belum ada data shipment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $shipments->links() }}
            </div>
        </div>
    </div>
@endsection
