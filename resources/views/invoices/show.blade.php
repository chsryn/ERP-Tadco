@extends('layouts.app')

@section('title', 'Detail Invoice - ERP TADCO')
@section('page_title', 'Detail Invoice')
@section('page_subtitle', 'Informasi tagihan piutang dan rincian transaksi invoice')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-receipt me-2 text-primary"></i>INV: {{ $invoice->invoice_number }}</h4>
                <p class="text-muted small mb-0">Customer: <span class="fw-bold text-dark">{{ $invoice->customer->customer_name ?? '-' }}</span></p>
            </div>
            <div class="d-flex gap-2">
                @if($invoice->receivable_amount > 0 && $invoice->status !== 'cancelled')
                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#payModal">
                        <i class="bi bi-wallet2 me-1"></i> Bayar Tagihan
                    </button>
                @endif
                <a href="{{ route('invoices.export_excel', $invoice) }}" class="btn btn-success btn-sm px-3 rounded-pill">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                @if((float) $invoice->paid_total == 0 && $invoice->status !== 'cancelled')
                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan invoice ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                            <i class="bi bi-x-circle me-1"></i> Batalkan Invoice
                        </button>
                    </form>
                @endif
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
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

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Penagihan</h6>
                <div>
                    @if ($invoice->status === 'unpaid')
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">Belum Dibayar (Unpaid)</span>
                    @elseif($invoice->status === 'paid')
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">Lunas (Paid)</span>
                    @elseif($invoice->status === 'cancelled')
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-bold">Dibatalkan</span>
                    @else
                        <span class="badge bg-dark px-3 py-2 rounded-pill">{{ $invoice->status }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr class="border-bottom border-light">
                                <th style="width: 220px;" class="text-secondary fw-semibold">Nomor Invoice</th>
                                <td class="fw-bold text-dark">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Nomor DO Terkait</th>
                                <td class="fw-medium text-primary">{{ $invoice->deliveryOrder->do_number ?? '-' }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Customer</th>
                                <td class="fw-bold text-dark">
                                    {{ $invoice->customer->customer_code ?? '-' }} - {{ $invoice->customer->customer_name ?? '-' }}
                                </td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Tanggal Invoice</th>
                                <td class="fw-medium text-dark">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Termin Pembayaran (TOP)</th>
                                <td class="fw-medium text-dark">{{ $invoice->payment_term_days ?? 0 }} Hari</td>
                            </tr>
                            <tr class="border-bottom border-light">
                                <th class="text-secondary fw-semibold">Tanggal Jatuh Tempo</th>
                                <td class="fw-medium text-dark">
                                    {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="text-secondary fw-semibold">Catatan</th>
                                <td>{{ $invoice->notes ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                    <small class="text-secondary fw-semibold">Grand Total</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-success-subtle border-start border-success border-4">
                    <small class="text-success-emphasis fw-semibold">Total Dibayar</small>
                    <h4 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($invoice->paid_total, 0, ',', '.') }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-danger-subtle border-start border-danger border-4">
                    <small class="text-danger-emphasis fw-semibold">Sisa Piutang</small>
                    <h4 class="fw-bold text-danger mb-0 mt-1">Rp {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box me-2 text-success"></i>Rincian Item Invoice</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered border-light align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Diskon (%)</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Dasar</th>
                                <th class="text-end">Harga Bersih</th>
                                <th class="text-end">Total Line</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items as $item)
                                @php
                                    $discPercent = (float) ($item->discount_percentage ?? ($item->discount_rate ? $item->discount_rate * 100 : 0));
                                    $basePrice = (float) ($item->base_price ?? $item->unit_price);
                                    $finalPrice = (float) ($item->final_price ?? $item->unit_price);
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $item->product->product_name ?? '-' }}</td>
                                    <td class="text-end text-secondary">{{ number_format($discPercent, 0, ',', '.') }}%</td>
                                    <td class="text-end fw-medium">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                    <td class="text-end text-secondary">Rp {{ number_format($basePrice, 0, ',', '.') }}</td>
                                    <td class="text-end text-secondary">Rp {{ number_format($finalPrice, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada item barang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

@if($invoice->receivable_amount > 0 && $invoice->status !== 'cancelled')
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('invoices.quick_pay', $invoice->id) }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2 text-primary"></i>Bayar Tagihan {{ $invoice->invoice_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 text-start">
                    <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Sisa Tagihan:</span>
                            <span class="fw-bold text-danger fs-5">Rp {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah Bayar (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" required min="1" max="{{ (float) $invoice->receivable_amount }}" step="0.01" value="{{ (float) $invoice->receivable_amount }}">
                        <div class="form-text">Maksimal: Rp {{ number_format($invoice->receivable_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-circle me-1"></i>Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
