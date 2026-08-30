@extends('layouts.app')

@section('title', 'Buat Delivery Order - ERP TADCO')
@section('page_title', 'Buat Delivery Order')
@section('page_subtitle', 'Input DO baru dan validasi ketersediaan stok produk')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 42px;
        padding-top: 5px;
    }
    .form-control-plaintext {
        outline: none;
        background-color: transparent;
    }

    /* Mengunci teks panjang agar terpotong rapi di dalam TD yang sudah dikunci */
    .select2-container .select2-selection--single .select2-selection__rendered {
        padding-right: 45px !important; /* Ruang aman untuk ikon */
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: block !important;
    }
    .select2-container .select2-selection--single .select2-selection__clear {
        margin-right: 18px !important; /* Geser ikon X agar tidak menimpa panah */
    }

    /* Jika menggunakan TomSelect */
    .ts-control {
        padding-right: 3rem !important;
    }
</style>
@endpush

@section('page_action')
<a href="{{ route('delivery_orders.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
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

        <form action="{{ route('delivery_orders.store') }}" method="POST">
            @csrf

            <!-- Form Header Customer (Tetap Sama) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge me-2 text-primary"></i>Informasi Customer & Tanggal</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCustomer">
                        <i class="bi bi-plus-lg me-1"></i> Customer Baru
                    </button>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Pilih Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select select2" required data-placeholder="Ketik untuk mencari customer...">
                                <option value="">-- Pilih Customer --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->customer_code }} - {{ $customer->customer_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tanggal DO <span class="text-danger">*</span></label>
                            <input type="date" name="do_date" id="do_date" class="form-control bg-light @error('do_date') is-invalid @enderror" value="{{ old('do_date', now()->format('Y-m-d')) }}" required>
                            @error('do_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Rencana Tanggal Kirim</label>
                            <input type="date" name="planned_delivery_date" id="planned_delivery_date" class="form-control bg-light @error('planned_delivery_date') is-invalid @enderror" value="{{ old('planned_delivery_date') }}" min="{{ old('do_date', now()->format('Y-m-d')) }}">
                            @error('planned_delivery_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan DO</label>
                            <textarea name="notes" class="form-control bg-light" rows="2" placeholder="Catatan khusus transaksi pengiriman...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian Tabel Keranjang -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box me-2 text-success"></i>Daftar Item & Kalkulasi Harga</h6>
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" id="btnAddRow">
                        <i class="bi bi-plus-lg"></i> Tambah Baris
                    </button>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%; min-width: 320px;">Produk</th>
                                    <th style="width: 15%;">Qty</th>
                                    <th style="width: 15%; text-align: right;">Harga Dasar</th>
                                    <th style="width: 10%; text-align: center;">Diskon</th>
                                    <th style="width: 20%; text-align: right;">Harga Bersih</th>
                                    <th style="width: 5%;" class="text-center">Hapus Baris</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Minimal 1 baris kosong saat pertama dimuat -->
                                @for ($i = 0; $i < (old('product_id') ? count(old('product_id')) : 1); $i++)
                                    <tr class="item-row">
                                        <td style="max-width: 320px;">
                                            <select name="product_id[]" class="form-select select2-product input-product" required data-placeholder="Ketik untuk mencari produk...">
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach ($products as $product)
                                                    @php
                                                        $hasStockRecord = $product->stockBalances->isNotEmpty();
                                                        $availStock = $product->stockBalances->sum(function($sb) {
                                                            return max(0, (float)$sb->qty_available - (float)$sb->qty_reserved);
                                                        });
                                                    @endphp
                                                    <option value="{{ $product->id }}" 
                                                        data-base-price="{{ $product->base_price }}" 
                                                        @if($hasStockRecord) data-stock="{{ $availStock }}" @endif
                                                        data-discounts='{{ $product->discounts ?? "[]" }}'
                                                        {{ old("product_id.$i") == $product->id ? 'selected' : '' }}>
                                                        {{ $product->product_code }} - {{ $product->product_name }} [{{ strtoupper($product->uom ?? 'BOX') }}] @if($hasStockRecord) - Stok: {{ number_format($availStock, 0, ',', '.') }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <!-- Input Qty akan menjadi trigger perhitungan -->
                                            <input type="number" name="qty[]" class="form-control text-center qty-input input-qty" style="width: 90px; min-width: 90px;" min="1" value="{{ old("qty.$i", 1) }}" required>
                                        </td>
                                        <td>
                                            <!-- Tampilan Text & Input Hidden -->
                                            <input type="text" class="form-control-plaintext text-end display-base-price" readonly value="Rp 0">
                                            <input type="hidden" name="base_price[]" class="hidden-base-price">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control-plaintext text-center display-discount text-success fw-bold" readonly value="0%">
                                            <input type="hidden" name="discount_percentage[]" class="hidden-discount">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control-plaintext text-end display-final-price fw-bold text-primary" readonly value="Rp 0">
                                            <input type="hidden" name="final_price[]" class="hidden-final-price">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle btn-remove-row" title="Hapus baris"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                    <a href="{{ route('delivery_orders.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Simpan Delivery Order
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Customer (Tetap Sama) -->
<div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formCustomerAjax">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus me-2 text-primary"></i>Tambah Customer Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="ajaxError" class="alert alert-danger d-none rounded-3 mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Nama Customer <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required placeholder="Nama Toko / Perusahaan">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSaveCustomer">Simpan Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2
        function initSelect2() {
            $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
            $('.select2-product').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true });
        }
        initSelect2();

        // Sinkronisasi Batasan Tanggal DO dan Rencana Tanggal Kirim
        const doDateInput = document.getElementById('do_date');
        const plannedDateInput = document.getElementById('planned_delivery_date');
        if (doDateInput && plannedDateInput) {
            const syncMinDeliveryDate = () => {
                if (doDateInput.value) {
                    plannedDateInput.min = doDateInput.value;
                    if (plannedDateInput.value && plannedDateInput.value < doDateInput.value) {
                        plannedDateInput.value = doDateInput.value;
                    }
                }
            };
            doDateInput.addEventListener('change', syncMinDeliveryDate);
            syncMinDeliveryDate();
        }

        // Format Angka ke Rupiah
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
        }

        // --- FUNGSI UTAMA KALKULASI REAL-TIME ---
        function calculateRow(row) {
            let productSelect = row.find('.input-product');
            let selectedOption = productSelect.find(':selected');
            let qtyInput = row.find('.input-qty').val();
            
            // Ambil data dari atribut HTML option yang dipilih
            let basePrice = parseFloat(selectedOption.attr('data-base-price')) || 0;
            let discountsJson = selectedOption.attr('data-discounts');
            let qty = parseFloat(qtyInput) || 0;
            let finalDiscountRate = 0;

            if (discountsJson && qty > 0) {
                try {
                    let discounts = JSON.parse(discountsJson);
                    // Cari diskon yang cocok dengan range QTY
                    let matchedDiscount = discounts.find(function(d) {
                        let min = parseFloat(d.min_qty);
                        let max = d.max_qty ? parseFloat(d.max_qty) : Infinity;
                        return qty >= min && qty <= max;
                    });

                    if (matchedDiscount) {
                        finalDiscountRate = parseFloat(matchedDiscount.discount_percentage);
                    }
                } catch (e) {
                    console.error("Gagal membaca aturan diskon");
                }
            }

            // Rumus: Harga Final = Harga Dasar - (Harga Dasar * (Diskon / 100))
            let discountNominal = basePrice * (finalDiscountRate / 100);
            let finalPrice = basePrice - discountNominal;

            // Bebaskan input Qty dari atribut max agar pengguna dapat mengetik desimal & kuantitas secara bebas
            row.find('.input-qty').removeAttr('max');

            // Tampilkan ke layar (UI)
            row.find('.display-base-price').val(formatRupiah(basePrice));
            row.find('.display-discount').val(finalDiscountRate > 0 ? finalDiscountRate + '%' : '0%');
            row.find('.display-final-price').val(formatRupiah(finalPrice));

            // Simpan ke input hidden untuk dikirim ke Backend
            row.find('.hidden-base-price').val(basePrice);
            row.find('.hidden-discount').val(finalDiscountRate);
            row.find('.hidden-final-price').val(finalPrice);
        }

        // Hapus baris kosong secara otomatis saat submit form
        $('form').on('submit', function() {
            $('#itemsTable tbody tr.item-row').each(function() {
                let pVal = $(this).find('.input-product').val();
                let qVal = $(this).find('.input-qty').val();
                let totalRows = $('#itemsTable tbody tr.item-row').length;
                if (!pVal && !qVal && totalRows > 1) {
                    $(this).remove();
                }
            });
        });

        // Trigger kalkulasi saat Produk diubah atau Qty diketik
        $(document).on('change', '.input-product', function() {
            let row = $(this).closest('tr');
            let qtyInput = row.find('.input-qty');
            if ($(this).val() && (!qtyInput.val() || parseFloat(qtyInput.val()) <= 0)) {
                qtyInput.val(1);
            }
            calculateRow(row);
        });
        $(document).on('input change', '.input-qty', function() {
            calculateRow($(this).closest('tr'));
        });

        // Trigger saat halaman dimuat (untuk data Old Validation)
        $('.item-row').each(function() {
            calculateRow($(this));
        });

        // Tambah Baris Baru
        $('#btnAddRow').click(function() {
            let firstRow = $('#itemsTable tbody tr:first').clone();
            firstRow.find('input').val('');
            firstRow.find('.input-qty').val(1);
            firstRow.find('.display-base-price, .display-final-price').val('Rp 0');
            firstRow.find('.display-discount').val('0%');
            firstRow.find('select').val('');
            firstRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
            firstRow.find('.select2-container').remove();
            
            $('#itemsTable tbody').append(firstRow);
            initSelect2(); // Re-init Select2 untuk baris baru
        });

        // Hapus Baris
        $(document).on('click', '.btn-remove-row', function() {
            let rowCount = $('#itemsTable tbody tr').length;
            if (rowCount > 1) {
                $(this).closest('tr').remove();
            } else {
                let row = $(this).closest('tr');
                row.find('input').val('');
                row.find('.display-base-price, .display-final-price').val('Rp 0');
                row.find('.display-discount').val('0%');
                row.find('select').val('').trigger('change');
            }
        });

        // AJAX Customer
        $('#formCustomerAjax').submit(function(e) {
            e.preventDefault();
            let form = $(this);
            let btnSave = $('#btnSaveCustomer');
            let errorBox = $('#ajaxError');

            btnSave.prop('disabled', true).text('Menyimpan...');
            errorBox.addClass('d-none').html('');

            $.ajax({
                url: "{{ route('customers.store_ajax') }}",
                type: "POST",
                data: form.serialize(),
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
                success: function(response) {
                    if (response.customer) {
                        let newOption = new Option(response.customer.customer_code + ' - ' + response.customer.customer_name, response.customer.id, true, true);
                        $('#customer_id').append(newOption).trigger('change');
                        $('#modalCustomer').modal('hide');
                        form[0].reset();
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan sistem.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        let errorHtml = '<ul class="mb-0 ps-3">';
                        $.each(errors, function(key, value) { errorHtml += '<li>' + value[0] + '</li>'; });
                        errorHtml += '</ul>';
                        errorMsg = errorHtml;
                    }
                    errorBox.removeClass('d-none').html(errorMsg);
                },
                complete: function() {
                    btnSave.prop('disabled', false).text('Simpan Customer');
                }
            });
        });
    });
</script>
@endpush