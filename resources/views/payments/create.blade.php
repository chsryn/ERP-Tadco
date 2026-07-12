<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Payment - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Input Pembayaran</h3>
        <small class="text-muted">Pembayaran untuk invoice unpaid atau partial paid</small>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('payments.store') }}" method="POST" class="card">
        @csrf

        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Invoice</label>
                    <select name="invoice_id" class="form-select" required>
                        <option value="">-- Pilih Invoice --</option>

                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                {{ $invoice->invoice_number }}
                                -
                                {{ $invoice->customer->customer_name ?? '-' }}
                                -
                                Sisa Rp {{ number_format($invoice->receivable_amount, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>

                    @if($invoices->isEmpty())
                        <small class="text-danger">
                            Belum ada invoice yang bisa dibayar. Buat invoice terlebih dahulu.
                        </small>
                    @endif
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Pembayaran</label>
                    <input
                        type="date"
                        name="payment_date"
                        class="form-control"
                        value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Metode Pembayaran</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">-- Pilih Metode --</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Nominal Pembayaran</label>
                    <input
                        type="number"
                        name="amount"
                        class="form-control"
                        value="{{ old('amount') }}"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Bank</label>
                    <input
                        type="text"
                        name="bank_name"
                        class="form-control"
                        value="{{ old('bank_name') }}"
                        placeholder="Contoh: BCA, BRI, Mandiri"
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nomor Referensi / Bukti Transfer</label>
                    <input
                        type="text"
                        name="reference_no"
                        class="form-control"
                        value="{{ old('reference_no') }}"
                        placeholder="Contoh: TRX-001"
                    >
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                Kembali
            </a>

            <button type="submit" class="btn btn-primary" {{ $invoices->isEmpty() ? 'disabled' : '' }}>
                Simpan Pembayaran
            </button>
        </div>
    </form>
</div>
</body>
</html>
