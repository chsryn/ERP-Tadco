@extends('layouts.app')

@section('title', 'Detail Customer - ERP TADCO')
@section('page_title', 'Detail Customer')
@section('page_subtitle', 'Profil identitas dan alamat lokasi pelanggan')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-person-vcard me-2 text-primary"></i>{{ $customer->customer_name }}</h4>
                <p class="text-muted small mb-0">Kode Customer: <span class="badge bg-secondary">{{ $customer->customer_code }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm px-3 rounded-pill text-dark fw-semibold">
                    <i class="bi bi-pencil me-1"></i> Edit Customer
                </a>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Customer</h6>
                <div>
                    @if($customer->is_active)
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Customer Aktif</span>
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
                                <th style="width: 220px;" class="text-secondary fw-semibold">Kode Customer</th>
                                <td class="fw-bold text-dark">{{ $customer->customer_code }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nama Customer</th>
                                <td class="fw-bold text-dark">{{ $customer->customer_name }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Wilayah</th>
                                <td>
                                    {{ implode(', ', array_filter([$customer->sub_district, $customer->district, $customer->city, $customer->province])) ?: '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="text-secondary fw-semibold">Alamat Lengkap</th>
                                <td>{{ $customer->address ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
