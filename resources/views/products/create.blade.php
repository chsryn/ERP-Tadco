@extends('layouts.app')

@section('title', 'Tambah Produk - ERP TADCO')
@section('page_title', 'Tambah Produk Baru')
@section('page_subtitle', 'Input item barang baru, harga dasar (base price), dan skema diskon strata')

@section('page_action')
<a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
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

        <form action="{{ route('products.store') }}" method="POST" id="formProduct">
            @csrf

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Utama Produk</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Kode Produk <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-barcode"></i></span>
                                <input type="text" name="product_code" class="form-control bg-light border-start-0" value="{{ old('product_code') }}" placeholder="Contoh: TG-GM-25" required>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold small text-secondary">Nama Produk <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-tag"></i></span>
                                <input type="text" name="product_name" class="form-control bg-light border-start-0" value="{{ old('product_name') }}" placeholder="Contoh: Terigu Gerbang Mas" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Harga Dasar (Base Price Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">Rp</span>
                                <input type="number" step="0.01" min="0" name="base_price" id="base_price" class="form-control bg-light border-start-0" value="{{ old('base_price', 0) }}" placeholder="0" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Satuan Barang (UoM) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-box-fill"></i></span>
                                <select name="uom" id="uom_select" class="form-select bg-light border-start-0" required>
                                    <option value="BOX" {{ old('uom', 'BOX') === 'BOX' ? 'selected' : '' }}>BOX (Dus - Strata S1 s/d S5)</option>
                                    <option value="SACK" {{ old('uom') === 'SACK' ? 'selected' : '' }}>SACK (Karung - Strata S1 s/d S3)</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Berat Bersih (Net Weight)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-speedometer2"></i></span>
                                <input type="text" name="net_weight" class="form-control bg-light border-start-0" value="{{ old('net_weight') }}" placeholder="Misal: 250GR, 10KG">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Segmentasi Pasar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-diagram-3"></i></span>
                                <input type="text" name="segment" class="form-control bg-light border-start-0" value="{{ old('segment', 'Terigu') }}" placeholder="Contoh: Terigu Premium">
                            </div>
                        </div>

                        <div class="col-md-6 d-flex align-items-end mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" checked>
                                <label for="is_active" class="form-check-label fw-medium text-dark">Status Produk Aktif (Siap dijual)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-percent me-2 text-success"></i>Skema Diskon Berdasarkan Strata Kuantitas</h6>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3" id="strata_badge">BOX: Strata S1 s/d S5</span>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="discountsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;">Strata</th>
                                    <th>Min. Qty</th>
                                    <th>Max. Qty (Kosong = Tak Terhingga)</th>
                                    <th>Diskon (%)</th>
                                    <th style="min-width: 200px;">Harga Bersih (Net Price)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['S1', 'S2', 'S3', 'S4', 'S5'] as $tier)
                                    <tr class="strata-row" data-strata="{{ $tier }}">
                                        <td>
                                            <span class="badge bg-dark px-3 py-2 fw-semibold">{{ $tier }}</span>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                name="discounts[{{ $tier }}][min_qty]"
                                                class="form-control"
                                                value="{{ old('discounts.' . $tier . '.min_qty', $loop->first ? 1 : '') }}"
                                                placeholder="0"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                name="discounts[{{ $tier }}][max_qty]"
                                                class="form-control"
                                                value="{{ old('discounts.' . $tier . '.max_qty') }}"
                                                placeholder="Max Qty"
                                            >
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    name="discounts[{{ $tier }}][discount_percentage]"
                                                    class="form-control discount-input"
                                                    data-strata="{{ $tier }}"
                                                    value="{{ old('discounts.' . $tier . '.discount_percentage', 0) }}"
                                                    placeholder="0"
                                                >
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success net-price-display" id="net_price_{{ $tier }}">
                                                Harga Bersih: Rp 0
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                    <a href="{{ route('products.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Simpan Produk
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uomSelect = document.getElementById('uom_select');
        const basePriceInput = document.getElementById('base_price');
        const strataBadge = document.getElementById('strata_badge');
        const strataRows = document.querySelectorAll('.strata-row');
        const discountInputs = document.querySelectorAll('.discount-input');

        function updateUomState() {
            const uom = uomSelect.value;
            if (uom === 'SACK') {
                strataBadge.textContent = 'SACK: Strata S1 s/d S3';
                strataRows.forEach(row => {
                    const strata = row.dataset.strata;
                    if (strata === 'S4' || strata === 'S5') {
                        row.style.display = 'none';
                    } else {
                        row.style.display = '';
                    }
                });
            } else {
                strataBadge.textContent = 'BOX: Strata S1 s/d S5';
                strataRows.forEach(row => row.style.display = '');
            }
        }

        function calculateNetPrices() {
            const basePrice = parseFloat(basePriceInput.value) || 0;

            discountInputs.forEach(input => {
                const strata = input.dataset.strata;
                const discount = parseFloat(input.value) || 0;
                const netPrice = basePrice - (basePrice * (discount / 100));
                const formattedNetPrice = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Math.max(0, netPrice));
                
                const displayEl = document.getElementById('net_price_' + strata);
                if (displayEl) {
                    displayEl.textContent = 'Harga Bersih: Rp ' + formattedNetPrice;
                }
            });
        }

        uomSelect.addEventListener('change', updateUomState);
        basePriceInput.addEventListener('input', calculateNetPrices);
        discountInputs.forEach(input => input.addEventListener('input', calculateNetPrices));

        // Initial calculation and layout check
        updateUomState();
        calculateNetPrices();
    });
</script>
@endpush
