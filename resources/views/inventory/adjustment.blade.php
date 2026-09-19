@extends('layouts.app')

@section('title', 'Adjustment Stok - ERP TADCO')
@section('page_title', 'Adjustment Stok Fisik')
@section('page_subtitle', 'Input transaksi stok masuk/keluar di luar transaksi DO biasa')

@section('page_action')
<a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
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

        <form action="{{ route('inventory.adjustment.store') }}" method="POST">
            @csrf

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-0 rounded-top-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-primary"></i>Form Penyesuaian Stok</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Pilih Produk <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select bg-light" required>
                                <option value="">-- Pilih Produk --</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->product_code }} - {{ $product->product_name }} [{{ strtoupper($product->uom ?? 'BOX') }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Jenis Pergerakan <span class="text-danger">*</span></label>
                            <select name="movement_type" class="form-select bg-light" required>
                                <option value="in" {{ old('movement_type') === 'in' ? 'selected' : '' }}>Stok Masuk (Penerimaan / Restock)</option>
                                <option value="out" {{ old('movement_type') === 'out' ? 'selected' : '' }}>Stok Keluar (Pemakaian)</option>
                                <option value="damage" {{ old('movement_type') === 'damage' ? 'selected' : '' }}>Barang Rusak / Afkir</option>
                                <option value="return" {{ old('movement_type') === 'return' ? 'selected' : '' }}>Retur Masuk</option>
                                <option value="adjustment" {{ old('movement_type') === 'adjustment' ? 'selected' : '' }}>Adjustment Opname (Penyesuaian Fisik)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Pergerakan <span class="text-danger">*</span></label>
                            <input type="date" name="movement_date" class="form-control bg-light" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="qty" class="form-control bg-light" value="{{ old('qty') }}" placeholder="0" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Alasan / Catatan Penyesuaian</label>
                            <textarea name="notes" class="form-control bg-light" rows="3" placeholder="Alasan penyesuaian stok (contoh: Barang rusak saat bongkar muat / Hasil stok opname bulanan)...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 px-4 border-top border-light d-flex justify-content-between align-items-center rounded-bottom-4">
                    <a href="{{ route('inventory.index') }}" class="btn btn-light px-4 rounded-pill fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Simpan Adjustment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
