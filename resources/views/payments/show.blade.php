@extends('layouts.app')

@section('title', 'Detail Pembayaran - ERP TADCO')
@section('page_title', 'Detail Pembayaran')
@section('page_subtitle', 'Rincian transaksi penerimaan kas/transfer pembayaran invoice')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2 text-success"></i>PAY: {{ $payment->payment_number }}</h4>
                <p class="text-muted small mb-0">Customer: <span class="fw-bold text-dark">{{ $payment->invoice->customer->customer_name ?? '-' }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran ini? Status invoice akan disesuaikan kembali.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                        <i class="bi bi-trash me-1"></i> Hapus Pembayaran
                    </button>
                </form>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Rincian Transaksi Pembayaran</h6>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold fs-6">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </span>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr class="border-bottom border-light">
                                <th style="width: 220px;" class="text-secondary fw-semibold">Nomor Payment</th>
                                <td class="fw-bold text-dark">{{ $payment->payment_number }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nomor Invoice</th>
                                <td class="fw-bold text-primary">{{ $payment->invoice->invoice_number ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nomor DO Terkait</th>
                                <td>{{ $payment->invoice->deliveryOrder->do_number ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Customer</th>
                                <td class="fw-bold text-dark">
                                    {{ $payment->invoice->customer->customer_code ?? '-' }} - {{ $payment->invoice->customer->customer_name ?? '-' }}
                                </td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Tanggal Pembayaran</th>
                                <td class="fw-medium text-dark">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Metode Pembayaran</th>
                                <td>
                                    @if($payment->payment_method === 'cash')
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i class="bi bi-cash me-1"></i> Cash / Tunai</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill"><i class="bi bi-bank me-1"></i> Transfer Bank</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nama Bank</th>
                                <td>{{ $payment->bank_name ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nomor Referensi</th>
                                <td class="fw-medium text-dark">{{ $payment->reference_no ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary fw-semibold">Catatan</th>
                                <td>{{ $payment->notes ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
