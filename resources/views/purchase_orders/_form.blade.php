@php
    $po = $purchaseOrder ?? null;

    $rows = old('items');

    if ($rows === null) {
        $rows = $po && $po->relationLoaded('items')
            ? $po->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'qty_plan' => (float) $item->qty_plan,
                'week_1' => (float) $item->week_1,
                'week_2' => (float) $item->week_2,
                'week_3' => (float) $item->week_3,
                'week_4' => (float) $item->week_4,
                'price' => (float) $item->price,
                'special_discount' => (float) $item->special_discount,
            ])->all()
            : [[]];
    }
@endphp

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

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Informasi Purchase Order</h6>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-secondary">Tanggal PO <span class="text-danger">*</span></label>
                <input type="date" name="po_date" class="form-control bg-light" value="{{ old('po_date', $po?->po_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-secondary">Period <span class="text-danger">*</span></label>
                <input type="month" name="period" class="form-control bg-light" value="{{ old('period', $po?->period ?? now()->format('Y-m')) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Supplier / Distributor <span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-select bg-light" required>
                    <option value="">-- Pilih Supplier Aktif --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) old('supplier_id', $po?->supplier_id ?? '') === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->code }} - {{ $supplier->name }} (TOP {{ $supplier->term_of_payment_days }} hari)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-secondary">PPN (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="ppn_rate" id="ppn_rate" class="form-control bg-light" value="{{ old('ppn_rate', $po?->ppn_rate ?? 11) }}">
            </div>

            <div class="col-md-9">
                <label class="form-label fw-semibold small text-secondary">Catatan</label>
                <input type="text" name="notes" class="form-control bg-light" value="{{ old('notes', $po?->notes ?? '') }}" placeholder="Opsional">
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-success"></i>Item Purchase Order</h6>
        <button type="button" id="add_item" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="table-responsive">
            <table class="table table-hover table-bordered border-light align-middle mb-0" id="items_table">
                <thead class="table-light">
                    <tr class="text-nowrap small">
                        <th style="min-width: 220px;">Produk</th>
                        <th class="text-center">Dynamics Code</th>
                        <th class="text-center">Berat (KG)</th>
                        <th style="min-width: 90px;" class="text-end">Qty Plan</th>
                        <th class="text-end">W1</th>
                        <th class="text-end">W2</th>
                        <th class="text-end">W3</th>
                        <th class="text-end">W4</th>
                        <th style="min-width: 110px;" class="text-end">Harga</th>
                        <th style="min-width: 100px;" class="text-end">Disc</th>
                        <th class="text-end">Nett</th>
                        <th class="text-end">Total</th>
                        <th style="width: 44px;"></th>
                    </tr>
                </thead>
                <tbody id="items_body">
                    @foreach($rows as $index => $row)
                        <tr class="item-row">
                            <td>
                                <select name="items[{{ $index }}][product_id]" class="form-select form-select-sm product-select" required>
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                            data-dynamics="{{ $product->dynamics_code }}"
                                            data-weight="{{ $product->weight_kg !== null ? (float) $product->weight_kg : '' }}"
                                            data-price="{{ $product->purchase_price !== null ? (float) $product->purchase_price : '' }}"
                                            {{ (string) ($row['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                            {{ $product->product_code }} - {{ $product->product_name }} [{{ strtoupper($product->uom ?? 'BOX') }}]
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center snapshot-dynamics text-nowrap small text-secondary">-</td>
                            <td class="text-center snapshot-weight text-nowrap small text-secondary">-</td>
                            <td><input type="number" step="0.01" min="0.01" name="items[{{ $index }}][qty_plan]" class="form-control form-control-sm text-end qty-input" value="{{ $row['qty_plan'] ?? '' }}" required></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][week_1]" class="form-control form-control-sm text-end week-input" value="{{ $row['week_1'] ?? '' }}" placeholder="auto"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][week_2]" class="form-control form-control-sm text-end week-input" value="{{ $row['week_2'] ?? '' }}" placeholder="auto"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][week_3]" class="form-control form-control-sm text-end week-input" value="{{ $row['week_3'] ?? '' }}" placeholder="auto"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][week_4]" class="form-control form-control-sm text-end week-input" value="{{ $row['week_4'] ?? '' }}" placeholder="auto"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][price]" class="form-control form-control-sm text-end price-input" value="{{ $row['price'] ?? '' }}" placeholder="0"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][special_discount]" class="form-control form-control-sm text-end discount-input" value="{{ $row['special_discount'] ?? '' }}" placeholder="0"></td>
                            <td class="text-end text-nowrap nett-display">0</td>
                            <td class="text-end text-nowrap fw-semibold total-display">0</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-light border text-danger rounded remove-item" title="Hapus"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end mt-4">
            <div class="col-md-5">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-secondary fw-semibold">Subtotal</th>
                        <td class="text-end fw-semibold" id="summary_subtotal">Rp 0</td>
                    </tr>
                    <tr>
                        <th class="text-secondary fw-semibold">Total Discount</th>
                        <td class="text-end fw-semibold" id="summary_discount">Rp 0</td>
                    </tr>
                    <tr>
                        <th class="text-secondary fw-semibold">PPN</th>
                        <td class="text-end fw-semibold" id="summary_ppn">Rp 0</td>
                    </tr>
                    <tr class="border-top">
                        <th class="fw-bold text-dark">Grand Total</th>
                        <td class="text-end fw-bold text-primary fs-5" id="summary_grand">Rp 0</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
        <a href="{{ route('purchase_orders.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
        <button type="submit" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-check-circle me-1"></i> Simpan Draft PO
        </button>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.getElementById('items_body');
        const table = document.getElementById('items_table');
        const addButton = document.getElementById('add_item');
        const ppnInput = document.getElementById('ppn_rate');

        let nextIndex = body.querySelectorAll('tr').length;

        function money(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value || 0);
        }

        function number(input) {
            const value = parseFloat(input.value);
            return isNaN(value) ? 0 : value;
        }

        function refreshRow(row) {
            const select = row.querySelector('.product-select');
            const option = select.options[select.selectedIndex];
            const dynamics = option ? (option.dataset.dynamics || '-') : '-';
            const weight = option ? (option.dataset.weight || '') : '';
            const priceInput = row.querySelector('.price-input');

            row.querySelector('.snapshot-dynamics').textContent = dynamics || '-';
            row.querySelector('.snapshot-weight').textContent = weight === '' ? '-' : weight;

            if (weight !== '' && priceInput.value === '') {
                priceInput.value = option.dataset.price || '';
            }

            const qty = number(row.querySelector('.qty-input'));
            const price = number(priceInput);
            const discount = number(row.querySelector('.discount-input'));
            const nett = Math.max(0, price - discount);

            row.querySelector('.nett-display').textContent = money(nett);
            row.querySelector('.total-display').textContent = money(qty * nett);
        }

        function refreshSummary() {
            let subtotal = 0;
            let discountTotal = 0;

            body.querySelectorAll('.item-row').forEach(function (row) {
                const qty = number(row.querySelector('.qty-input'));
                const price = number(row.querySelector('.price-input'));
                const discount = number(row.querySelector('.discount-input'));

                subtotal += qty * Math.max(0, price - discount);
                discountTotal += qty * discount;
            });

            const ppnRate = number(ppnInput);
            const ppn = subtotal * ppnRate / 100;

            document.getElementById('summary_subtotal').textContent = money(subtotal);
            document.getElementById('summary_discount').textContent = money(discountTotal);
            document.getElementById('summary_ppn').textContent = money(ppn);
            document.getElementById('summary_grand').textContent = money(subtotal + ppn);
        }

        function bindRow(row) {
            row.querySelectorAll('input, select').forEach(function (element) {
                element.addEventListener('input', function () {
                    refreshRow(row);
                    refreshSummary();
                });
                element.addEventListener('change', function () {
                    refreshRow(row);
                    refreshSummary();
                });
            });

            row.querySelector('.remove-item').addEventListener('click', function () {
                const rows = body.querySelectorAll('.item-row');
                if (rows.length === 1) {
                    return;
                }
                row.remove();
                refreshSummary();
            });

            refreshRow(row);
        }

        body.querySelectorAll('.item-row').forEach(bindRow);

        addButton.addEventListener('click', function () {
            const template = table.querySelector('.item-row');
            const clone = template.cloneNode(true);
            const index = nextIndex++;

            clone.querySelectorAll('[name]').forEach(function (element) {
                element.name = element.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                if (element.classList.contains('product-select')) {
                    element.value = '';
                } else {
                    element.value = '';
                }
            });

            clone.querySelector('.snapshot-dynamics').textContent = '-';
            clone.querySelector('.snapshot-weight').textContent = '-';
            clone.querySelector('.nett-display').textContent = '0';
            clone.querySelector('.total-display').textContent = '0';

            body.appendChild(clone);
            bindRow(clone);
            refreshSummary();
        });

        refreshSummary();
    });
</script>
@endpush
