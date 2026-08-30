@extends('layouts.app')

@section('title', 'Input Pembayaran - ERP TADCO')
@section('page_title', 'Catat Pembayaran Baru')
@section('page_subtitle', 'Pencatatan pembayaran kas/transfer untuk pelunasan invoice')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2 text-success"></i>Catat Pembayaran Baru</h4>
                <p class="text-muted small mb-0">Pilih Invoice penagihan dan masukkan nominal setoran pembayaran</p>
            </div>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
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

        <form action="{{ route('payments.store') }}" method="POST">
            @csrf

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-credit-card me-2 text-primary"></i>Informasi Pembayaran</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Pilih Invoice Target <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-receipt"></i></span>
                                <select name="invoice_id" class="form-select bg-light border-start-0" required>
                                    <option value="">-- Pilih Invoice yang belum lunas (Unpaid / Partial Paid) --</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                            {{ $invoice->invoice_number }} - {{ $invoice->customer->customer_name ?? '-' }} - Sisa Piutang: Rp {{ number_format($invoice->receivable_amount, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($invoices->isEmpty())
                                <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>Belum ada invoice yang membutuhkan pembayaran saat ini.</small>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Pembayaran <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control bg-light" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select bg-light" required>
                                <option value="">-- Pilih Metode --</option>
                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai / Cash</option>
                                <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" name="amount" class="form-control bg-light" value="{{ old('amount') }}" min="0.01" step="0.01" placeholder="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Nama Bank (Jika Transfer)</label>
                            <input type="text" name="bank_name" class="form-control bg-light" value="{{ old('bank_name') }}" placeholder="Contoh: BCA, Mandiri, BRI">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Nomor Referensi / Struk</label>
                            <input type="text" name="reference_no" class="form-control bg-light" value="{{ old('reference_no') }}" placeholder="Contoh: TRF-99887711">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Pembayaran</label>
                            <textarea name="notes" class="form-control bg-light" rows="3" placeholder="Catatan tambahan mengenai setoran ini...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                    <a href="{{ route('payments.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                    <button type="submit" class="btn btn-success px-5 rounded-pill fw-semibold shadow-sm" {{ $invoices->isEmpty() ? 'disabled' : '' }}>
                        <i class="bi bi-check-circle me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
