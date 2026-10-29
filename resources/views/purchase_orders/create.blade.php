@extends('layouts.app')

@section('title', 'Buat Purchase Order - ERP TADCO')
@section('page_title', 'Buat Purchase Order')
@section('page_subtitle', 'Draft PO pembelian ke supplier / principal')

@section('page_action')
<a href="{{ route('purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Kembali
</a>
@endsection

@section('content')
<form action="{{ route('purchase_orders.store') }}" method="POST">
    @csrf

    @include('purchase_orders._form', ['purchaseOrder' => null])
</form>
@endsection
