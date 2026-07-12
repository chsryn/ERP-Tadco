@extends('layouts.app')

@section('title', 'Customer - ERP TADCO')
@section('page_title', 'Customer')
@section('page_subtitle', 'Master data customer PT TADCO')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Daftar Customer</h5>
            <small class="text-muted">Data customer dari hasil import Excel dan input manual</small>
        </div>

        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            + Tambah Customer
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
            <form method="GET" action="{{ route('customers.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari kode customer, nama customer, kota, district..."
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
                        <th>Kode</th>
                        <th>Nama Customer</th>
                        <th>Provinsi</th>
                        <th>Kota</th>
                        <th>District</th>
                        <th>Sub District</th>
                        <th>Market</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customers->firstItem() + $loop->index }}</td>
                            <td>{{ $customer->customer_code }}</td>
                            <td>{{ $customer->customer_name }}</td>
                            <td>{{ $customer->province ?? '-' }}</td>
                            <td>{{ $customer->city ?? '-' }}</td>
                            <td>{{ $customer->district ?? '-' }}</td>
                            <td>{{ $customer->sub_district ?? '-' }}</td>
                            <td>{{ $customer->market ?? '-' }}</td>
                            <td>{{ $customer->customer_type ?? '-' }}</td>
                            <td>
                                @if($customer->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-info">
                                        Detail
                                    </a>

                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus customer ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">
                                Data customer belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
@endsection
