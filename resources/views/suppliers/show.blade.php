@extends('layouts.app')

@section('title', 'Detail Supplier - ERP TADCO')
@section('page_title', 'Detail Supplier')
@section('page_subtitle', 'Profil supplier / principal')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-building me-2 text-primary"></i>{{ $supplier->name }}</h4>
                <p class="text-muted small mb-0">Kode Supplier: <span class="badge bg-secondary">{{ $supplier->code }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning btn-sm px-3 rounded-pill text-dark fw-semibold">
                    <i class="bi bi-pencil me-1"></i> Edit Supplier
                </a>
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Supplier</h6>
                <div>
                    @if($supplier->is_active)
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Supplier Aktif</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> Nonaktif</span>
                    @endif
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr class="border-bottom border-light">
                                <th style="width: 220px;" class="text-secondary fw-semibold">Kode Supplier</th>
                                <td class="fw-bold text-dark">{{ $supplier->code }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nama Supplier</th>
                                <td class="fw-bold text-dark">{{ $supplier->name }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Region</th>
                                <td>{{ $supplier->region ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Port / Depo</th>
                                <td>{{ $supplier->port_depo ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Delivery Mode</th>
                                <td>{{ $supplier->delivery_mode ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Term of Payment</th>
                                <td>{{ $supplier->term_of_payment_days }} hari</td>
                            </tr>
                            <tr>
                                <th class="text-secondary fw-semibold">PIC</th>
                                <td>{{ $supplier->pic_name ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
