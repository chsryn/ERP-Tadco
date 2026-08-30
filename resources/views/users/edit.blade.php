@extends('layouts.app')

@section('title', 'Edit User - ERP TADCO')
@section('page_title', 'Edit User')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h3 class="mb-0">Edit User</h3>
        <small class="text-muted">Perbarui informasi akun dan hak akses pengguna</small>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <!-- Melempar variabel role bawaan user agar terpilih otomatis -->
        @include('users._form', ['selectedRole' => $user->roles->first()->name ?? ''])
    </form>
</div>
@endsection
