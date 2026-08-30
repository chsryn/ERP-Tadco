@extends('layouts.app')

@section('title', 'Tambah User - ERP TADCO')
@section('page_title', 'Tambah User')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h3 class="mb-0">Tambah User Baru</h3>
        <small class="text-muted">Buat akun pengguna dan tentukan hak akses (role)</small>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="card border-0 shadow-sm">
        @csrf
        @include('users._form')
    </form>
</div>
@endsection
