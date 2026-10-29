@extends('layouts.app')

@section('title', 'Edit Purchase Order - ERP TADCO')
@section('page_title', 'Edit Purchase Order')
@section('page_subtitle', 'Perbarui draft PO ' . $purchaseOrder->po_number)

@section('page_action')
<a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Kembali
</a>
@endsection

@section('content')
<form action="{{ route('purchase_orders.update', $purchaseOrder) }}" method="POST">
    @csrf
    @method('PUT')

    @include('purchase_orders._form', ['purchaseOrder' => $purchaseOrder])
</form>
@endsection
