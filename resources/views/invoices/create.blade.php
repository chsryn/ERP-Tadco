<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Invoice - ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h3 class="mb-0">Buat Invoice</h3>
        <small class="text-muted">Invoice dibuat dari DO yang sudah shipped</small>
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

    <form action="{{ route('invoices.store') }}" method="POST" class="card">
        @csrf

        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Delivery Order</label>
                    <select name="delivery_order_id" class="form-select" required>
                        <option value="">-- Pilih DO yang sudah shipped --</option>

                        @foreach($deliveryOrders as $do)
                            <option value="{{ $do->id }}" {{ old('delivery_order_id') == $do->id ? 'selected' : '' }}>
                                {{ $do->do_number }}
                                -
                                {{ $do->customer->customer_name ?? '-' }}
                                -
                                Total Rp {{ number_format($do->total_amount, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>

                    @if($deliveryOrders->isEmpty())
                        <small class="text-danger">
                            Belum ada DO shipped yang bisa dibuatkan invoice.
                        </small>
                    @endif
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Invoice</label>
                    <input
                        type="date"
                        name="invoice_date"
                        class="form-control"
                        value="{{ old('invoice_date', now()->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">TOP / Termin Pembayaran Hari</label>
                    <input
                        type="number"
                        name="payment_term_days"
                        class="form-control"
                        value="{{ old('payment_term_days', 0) }}"
                        min="0"
                    >
                    <small class="text-muted">Isi 0 untuk tunai, 14 untuk TOP 14 hari, 30 untuk TOP 30 hari.</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Due Date Manual</label>
                    <input
                        type="date"
                        name="due_date"
                        class="form-control"
                        value="{{ old('due_date') }}"
                    >
                    <small class="text-muted">Jika dikosongkan, sistem hitung dari tanggal invoice + TOP.</small>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary" {{ $deliveryOrders->isEmpty() ? 'disabled' : '' }}>
                Simpan Invoice
            </button>
        </div>
    </form>
</div>
</body>
</html>
