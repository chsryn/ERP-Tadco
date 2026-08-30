@extends('layouts.app')

@section('title', 'Edit Customer - ERP TADCO')
@section('page_title', 'Edit Data Customer')
@section('page_subtitle', 'Perbarui profil identitas dan lokasi wilayah pelanggan')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Customer: {{ $customer->customer_name }}</h4>
                <p class="text-muted small mb-0">Kode Customer: <span class="badge bg-secondary">{{ $customer->customer_code }}</span></p>
            </div>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong class="d-block">Terjadi kesalahan validasi:</strong>
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-vcard me-2 text-primary"></i>Identitas Customer</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Kode Customer <span class="text-danger">*</span></label>
                            <input type="text" name="customer_code" class="form-control bg-light" value="{{ old('customer_code', $customer->customer_code) }}" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold small text-secondary">Nama Customer <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control bg-light" value="{{ old('customer_name', $customer->customer_name) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt me-2 text-danger"></i>Alamat & Wilayah Operasional</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Provinsi</label>
                            <input type="text" name="province" class="form-control bg-light" value="{{ old('province', $customer->province) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Kota / Kabupaten</label>
                            <input type="text" name="city" class="form-control bg-light" value="{{ old('city', $customer->city) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Kecamatan</label>
                            <input type="text" name="district" class="form-control bg-light" value="{{ old('district', $customer->district) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Kelurahan / Desa</label>
                            <input type="text" name="sub_district" class="form-control bg-light" value="{{ old('sub_district', $customer->sub_district) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Alamat Lengkap Jalan</label>
                            <textarea name="address" class="form-control bg-light" rows="3">{{ old('address', $customer->address) }}</textarea>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label fw-medium text-dark">Status Customer Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                    <a href="{{ route('customers.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                    <button type="submit" class="btn btn-warning px-5 rounded-pill fw-semibold shadow-sm text-dark">
                        <i class="bi bi-check-circle me-1"></i> Perbarui Customer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
