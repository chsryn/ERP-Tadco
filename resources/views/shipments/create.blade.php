@extends('layouts.app')

@section('title', 'Buat Shipment - ERP TADCO')
@section('page_title', 'Buat Shipment Pengiriman')
@section('page_subtitle', 'Proses DO menjadi pengiriman, terbitkan Invoice, dan catat Pembayaran')

@section('page_action')
    <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary px-4 rounded-pill fw-semibold shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong class="d-block">Terjadi kesalahan validasi:</strong>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('shipments.store') }}" method="POST">
        @csrf

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi DO & Armada Pengiriman</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Pilih Delivery Order Validated <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-file-earmark-text"></i></span>
                            <select name="delivery_order_id" id="delivery_order_id" class="form-select bg-light border-start-0" required>
                                <option value="" data-total="0">-- Pilih DO yang antre pengiriman (Validated) --</option>
                                @foreach ($deliveryOrders as $do)
                                    <option value="{{ $do->id }}" data-total="{{ $do->total_amount }}"
                                        {{ (old('delivery_order_id') ?? request('do_id')) == $do->id ? 'selected' : '' }}>
                                        {{ $do->do_number }} - {{ $do->customer->customer_name ?? '-' }} (Total: Rp {{ number_format($do->total_amount, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($deliveryOrders->isEmpty())
                            <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>Belum ada DO berkategori Validated yang siap dikirim.</small>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Tanggal Pengiriman <span class="text-danger">*</span></label>
                        <input type="date" name="shipment_date" class="form-control bg-light" value="{{ old('shipment_date', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Nama Pengemudi / Sopir</label>
                        <input type="text" name="driver_name" class="form-control bg-light" value="{{ old('driver_name') }}" placeholder="Nama Sopir">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Nomor Plat Kendaraan</label>
                        <input type="text" name="vehicle_no" class="form-control bg-light" value="{{ old('vehicle_no') }}" placeholder="Contoh: DA 8899 AB">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Catatan Pengiriman</label>
                        <textarea name="notes" class="form-control bg-light" rows="2" placeholder="Catatan armada pengiriman...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                <a href="{{ route('shipments.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                <button type="button" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalInvoice" {{ $deliveryOrders->isEmpty() ? 'disabled' : '' }}>
                    Lanjut Opsi Invoice & Pembayaran <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- MODAL PENGATURAN INVOICE & PEMBAYARAN -->
        <div class="modal fade" id="modalInvoice" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-receipt-cutoff me-2 text-success"></i>Opsi Otomatisasi Penagihan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Status Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_status" id="payment_status" class="form-select bg-light" required>
                                <option value="top">TOP (Term of Payment / Tempo)</option>
                                <option value="paid">Langsung Lunas (Cash/Transfer)</option>
                            </select>
                        </div>

                        <div class="mb-3" id="termin_container">
                            <label class="form-label fw-semibold small text-secondary">Termin Pembayaran (Hari) <span class="text-danger">*</span></label>
                            <input type="number" name="payment_term_days" id="payment_term_days" class="form-control bg-light" value="14" min="0" required>
                            <small class="text-muted">Tanggal jatuh tempo invoice akan dihitung secara otomatis.</small>
                        </div>

                        <div id="payment_container" style="display: none;">
                            <div class="p-3 bg-light border rounded-3 mb-3">
                                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-credit-card me-2 text-primary"></i>Detail Pembayaran Langsung</h6>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-secondary">Metode Pembayaran <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-select">
                                        <option value="cash">Cash (Tunai)</option>
                                        <option value="transfer">Transfer Bank</option>
                                    </select>
                                </div>

                                <div class="row g-2" id="bank_details_container" style="display: none;">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-semibold small text-secondary">Nama Bank</label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control" placeholder="Contoh: BCA">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-semibold small text-secondary">No. Referensi</label>
                                        <input type="text" name="reference_no" id="reference_no" class="form-control" placeholder="Contoh: TRX-123">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small text-secondary">Catatan Invoice (Opsional)</label>
                            <textarea name="invoice_notes" class="form-control bg-light" rows="2" placeholder="Catatan khusus invoice..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Proses Pengiriman & Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        function updateModalState() {
            let status = $('#payment_status').val();

            if (status === 'paid') {
                $('#termin_container').hide();
                $('#payment_term_days').removeAttr('required').val(0);
                $('#payment_container').fadeIn();
                $('#payment_method').attr('required', 'required');
            } else if (status === 'top') {
                $('#termin_container').fadeIn();
                $('#payment_term_days').attr('required', 'required').val(14);
                $('#payment_container').hide();
                $('#payment_method').removeAttr('required');
                $('#bank_details_container').hide();
                $('#bank_name').val('');
                $('#reference_no').val('');
            }
        }

        $('#payment_status, #delivery_order_id').change(function() {
            updateModalState();
        });

        $('#payment_method').change(function() {
            if ($(this).val() === 'transfer') {
                $('#bank_details_container').fadeIn();
            } else {
                $('#bank_details_container').hide();
                $('#bank_name').val('');
                $('#reference_no').val('');
            }
        });

        updateModalState();
    });
</script>
@endpush
