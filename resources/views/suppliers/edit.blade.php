@extends('layouts.app')

@section('title', 'Edit Supplier - ERP TADCO')
@section('page_title', 'Edit Supplier')
@section('page_subtitle', 'Perbarui data supplier / principal')

@section('page_action')
<a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Kembali
</a>
@endsection

@section('content')

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

<form action="{{ route('suppliers.update', $supplier) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-2 text-primary"></i>Identitas Supplier</h6>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary">Kode Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control bg-light" value="{{ old('code', $supplier->code) }}" maxlength="30" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold small text-secondary">Nama Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control bg-light" value="{{ old('name', $supplier->name) }}" maxlength="150" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt me-2 text-danger"></i>Informasi Pengiriman & Pembayaran</h6>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary">Region</label>
                    <input type="text" name="region" class="form-control bg-light" value="{{ old('region', $supplier->region) }}" maxlength="100">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary">Port / Depo</label>
                    <input type="text" name="port_depo" class="form-control bg-light" value="{{ old('port_depo', $supplier->port_depo) }}" maxlength="100">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary">Delivery Mode</label>
                    <input type="text" name="delivery_mode" class="form-control bg-light" value="{{ old('delivery_mode', $supplier->delivery_mode) }}" maxlength="100">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary">Term of Payment (hari)</label>
                    <input type="number" name="term_of_payment_days" class="form-control bg-light" value="{{ old('term_of_payment_days', $supplier->term_of_payment_days) }}" min="0">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold small text-secondary">PIC</label>
                    <input type="text" name="pic_name" class="form-control bg-light" value="{{ old('pic_name', $supplier->pic_name) }}" maxlength="150">
                </div>

                <div class="col-12 mt-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" @checked(old('is_active', $supplier->is_active))>
                        <label for="is_active" class="form-check-label fw-medium text-dark">Status Supplier Aktif</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
            <a href="{{ route('suppliers.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
            <button type="submit" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-check-circle me-1"></i> Perbarui Supplier
            </button>
        </div>
    </div>
</form>

@endsection
