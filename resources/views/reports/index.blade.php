@extends('layouts.app')

@section('title', 'Reports - ERP TADCO')
@section('page_title', 'Reporting ERP TADCO')
@section('page_subtitle', 'Laporan operasional, penjualan, piutang, dan pembayaran')

@section('content')
    <div class="row g-3">
        <div class="col-md-3">
            <a href="{{ route('reports.stock') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="text-dark">Laporan Stok</h5>
                        <p class="text-muted mb-0">Stok available, reserved, dan stok rendah.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('reports.sales') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="text-dark">Laporan Penjualan</h5>
                        <p class="text-muted mb-0">Invoice, total penjualan, terbayar, dan piutang.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('reports.receivables') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="text-dark">Laporan Piutang</h5>
                        <p class="text-muted mb-0">Invoice unpaid, partial paid, dan overdue.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('reports.payments') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="text-dark">Laporan Pembayaran</h5>
                        <p class="text-muted mb-0">Pembayaran cash, transfer, dan referensi bayar.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
