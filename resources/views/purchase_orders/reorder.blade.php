@extends('layouts.app')

@section('title', 'Auto PO / Reorder - ERP TADCO')
@section('page_title', 'Auto PO / Reorder')
@section('page_subtitle', 'Rekomendasi pengadaan berdasarkan stok dan outstanding PO')

@section('page_action')
<a href="{{ route('purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Daftar PO
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

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="metric-icon" style="--metric-bg: #eff6ff; --metric-color: #1B84FF;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="metric-label">Produk membutuhkan pengadaan</div>
                    <div class="metric-value">{{ $reorderCount }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="metric-icon" style="--metric-bg: #fef3c7; --metric-color: #b45309;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="metric-label">Total suggested quantity</div>
                    <div class="metric-value">{{ number_format((float) $suggestedTotal, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('purchase_orders.reorder.generate') }}" method="POST" id="reorder_form">
    @csrf

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Draft Purchase Order</h6>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Tanggal PO <span class="text-danger">*</span></label>
                    <input type="date" name="po_date" class="form-control bg-light" value="{{ old('po_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Period <span class="text-danger">*</span></label>
                    <input type="month" name="period" class="form-control bg-light" value="{{ old('period', now()->format('Y-m')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-secondary">Supplier / Distributor <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select bg-light" required>
                        <option value="">-- Pilih Supplier Aktif --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->code }} - {{ $supplier->name }} (TOP {{ $supplier->term_of_payment_days }} hari)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold small text-secondary">Catatan</label>
                    <input type="text" name="notes" class="form-control bg-light" value="{{ old('notes') }}" placeholder="Opsional — kosongkan untuk catatan default Auto PO">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-0">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-success"></i>Rekomendasi Pengadaan</h6>
        </div>

        @if($recommendations->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                Tidak ada produk yang membutuhkan pengadaan saat ini.
                <div class="small mt-2">Pastikan produk sudah memiliki Target Stock, Harga Pembelian, dan Berat (KG) pada Data Purchasing.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-bordered border-light align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-nowrap small">
                            <th style="width: 44px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="check_all" title="Pilih semua yang disarankan">
                            </th>
                            <th>Product Code</th>
                            <th style="min-width: 200px;">Product Name</th>
                            <th class="text-center">Dynamics Code</th>
                            <th class="text-end">Weight KG</th>
                            <th class="text-end">Available</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Avail. for Reorder</th>
                            <th class="text-end">Target Stock</th>
                            <th class="text-end">Qty on Order</th>
                            <th class="text-end">Suggested Qty</th>
                            <th class="text-end">Purchase Price</th>
                            <th style="min-width: 110px;" class="text-end">Qty Plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recommendations as $row)
                            @php
                                $suggested = (float) $row->suggested_qty;
                                $checked = old('items.' . $row->id . '.product_id') !== null;
                                $qtyValue = old('items.' . $row->id . '.qty_plan', $suggested > 0 ? $suggested : '');
                            @endphp
                            <tr class="reorder-row {{ $suggested > 0 ? '' : 'text-muted' }}" data-suggested="{{ $suggested }}">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input row-check" {{ $checked ? 'checked' : '' }}>
                                    <input type="hidden" name="items[{{ $row->id }}][product_id]" value="{{ $row->id }}" class="row-product" {{ $checked ? '' : 'disabled' }}>
                                </td>
                                <td class="text-nowrap">{{ $row->product_code }}</td>
                                <td class="fw-semibold text-dark">{{ $row->product_name }}</td>
                                <td class="text-center text-nowrap small text-secondary">{{ $row->dynamics_code ?? '-' }}</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->weight_kg, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->qty_available, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->qty_reserved, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->available_for_reorder, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->target_stock, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->qty_on_order, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap fw-semibold {{ $suggested > 0 ? 'text-primary' : '' }}">{{ number_format($suggested, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">Rp {{ number_format((float) $row->purchase_price, 0, ',', '.') }}</td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="items[{{ $row->id }}][qty_plan]"
                                        class="form-control form-control-sm text-end qty-input" value="{{ $qtyValue }}"
                                        {{ $checked ? '' : 'disabled' }}>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-secondary">
                    Dipilih: <strong id="selected_count">0</strong> produk ·
                    Total qty: <strong id="selected_qty">0</strong>
                </div>
                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm" id="generate_button">
                    <i class="bi bi-magic me-1"></i> Generate Draft PO
                </button>
            </div>
        @endif
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.reorder-row');
        const checkAll = document.getElementById('check_all');
        const countLabel = document.getElementById('selected_count');
        const qtyLabel = document.getElementById('selected_qty');

        function toggleRow(row, checked) {
            const product = row.querySelector('.row-product');
            const qty = row.querySelector('.qty-input');

            product.disabled = !checked;
            qty.disabled = !checked;

            if (checked && qty.value === '') {
                const suggested = parseFloat(row.dataset.suggested);
                qty.value = suggested > 0 ? suggested : '';
            }
        }

        function refreshSummary() {
            let count = 0;
            let total = 0;

            rows.forEach(function (row) {
                const checked = row.querySelector('.row-check').checked;
                const qty = parseFloat(row.querySelector('.qty-input').value);

                if (checked) {
                    count++;
                    total += isNaN(qty) ? 0 : qty;
                }
            });

            countLabel.textContent = count;
            qtyLabel.textContent = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(total);
        }

        rows.forEach(function (row) {
            const checkbox = row.querySelector('.row-check');
            const qty = row.querySelector('.qty-input');

            toggleRow(row, checkbox.checked);

            checkbox.addEventListener('change', function () {
                toggleRow(row, checkbox.checked);
                refreshSummary();
            });

            qty.addEventListener('input', refreshSummary);
        });

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                rows.forEach(function (row) {
                    const checkbox = row.querySelector('.row-check');
                    const suggested = parseFloat(row.dataset.suggested) || 0;

                    // "pilih semua" hanya memilih produk yang memang disarankan (suggested > 0)
                    const shouldCheck = checkAll.checked && suggested > 0;
                    checkbox.checked = shouldCheck;
                    toggleRow(row, shouldCheck);

                    if (!shouldCheck) {
                        row.querySelector('.qty-input').value = '';
                    }
                });

                refreshSummary();
            });
        }

        refreshSummary();
    });
</script>
@endpush

@endsection
