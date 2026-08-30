@extends('layouts.app')

@section('title', 'Import Data Excel - ERP TADCO')
@section('page_title', 'Import Data Excel')
@section('page_subtitle', 'Import masal data master customer dan produk dari berkas Excel')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Import Data Master Excel</h4>
                <p class="text-muted small mb-0">Upload berkas spreadsheet master untuk memperbarui data otomatis</p>
            </div>
            <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong class="d-block">Terjadi kesalahan validasi berkas:</strong>
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

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-upload me-2 text-primary"></i>Upload Berkas Excel Master TADCO</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <form action="{{ route('imports.master') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-secondary">Pilih Berkas Excel (.xlsx / .xls / .csv) <span class="text-danger">*</span></label>
                        <input type="file" name="file_master" class="form-control bg-light p-3 border-2 border-dashed" accept=".xlsx, .xls, .csv" required>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i> Upload file master yang memiliki lembar kerja <strong>LIST CUSTOMER</strong> dan <strong>LIST ITEM</strong>.
                        </small>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success px-5 rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-cloud-upload me-1"></i> Mulai Proses Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
