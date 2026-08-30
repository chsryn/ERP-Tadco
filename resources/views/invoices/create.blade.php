@extends('layouts.app')

@section('title', 'Buat Invoice - ERP TADCO')
@section('page_title', 'Buat Invoice Baru')
@section('page_subtitle', 'Penagihan piutang dari Delivery Order yang telah dikirim (shipped)')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Buat Invoice Penagihan</h4>
                <p class="text-muted small mb-0">Pilih Delivery Order (DO) berstatus shipped untuk penagihan piutang</p>
            </div>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong class="d-block">Terjadi kesalahan:</strong>
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

        <form action="{{ route('invoices.store') }}" method="POST">
            @csrf

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi DO & Penagihan</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Delivery Order Shipped <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-truck"></i></span>
                                <select name="delivery_order_id" class="form-select bg-light border-start-0" required>
                                    <option value="">-- Pilih DO yang sudah dikirim (Shipped) --</option>
                                    @foreach($deliveryOrders as $do)
                                        <option value="{{ $do->id }}" {{ old('delivery_order_id') == $do->id ? 'selected' : '' }}>
                                            {{ $do->do_number }} - {{ $do->customer->customer_name ?? '-' }} - Total Rp {{ number_format($do->total_amount, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($deliveryOrders->isEmpty())
                                <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>Belum ada DO shipped yang tersedia untuk dibuatkan invoice penagihan.</small>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Invoice <span class="text-danger">*</span></label>
                            <input type="date" name="invoice_date" class="form-control bg-light" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Termin Pembayaran (TOP Hari)</label>
                            <div class="input-group">
                                <input type="number" name="payment_term_days" class="form-control bg-light" value="{{ old('payment_term_days', 0) }}" min="0">
                                <span class="input-group-text bg-light">Hari</span>
                            </div>
                            <small class="text-muted">Isi 0 untuk Cash/Tunai, 14 atau 30 untuk tempo.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Invoice</label>
                            <textarea name="notes" class="form-control bg-light" rows="3" placeholder="Catatan syarat penagihan / nomor rekening pembayaran...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                    <a href="{{ route('invoices.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm" {{ $deliveryOrders->isEmpty() ? 'disabled' : '' }}>
                        <i class="bi bi-check-circle me-1"></i> Simpan Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
