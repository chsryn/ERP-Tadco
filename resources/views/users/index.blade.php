@extends('layouts.app')

@section('title', 'User Management - ERP TADCO')
@section('page_title', 'Manajemen Pengguna System')
@section('page_subtitle', 'Kelola akun pengguna, peran hak akses (role), dan status aktifasi')

@section('page_action')
<a href="{{ route('users.create') }}" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-plus-lg me-1"></i> Tambah User
</a>
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Terjadi kesalahan:</strong>
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

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" name="search" value="{{ $search }}"
                        placeholder="Cari nama atau email user...">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">
                    Cari User
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover table-bordered border-light align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nama User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-center">Status</th>
                    <th style="width: 160px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="text-center fw-medium text-secondary">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="fw-bold text-dark">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @php
                                $roleClass = match($user->role) {
                                    'superadmin' => 'bg-danger-subtle text-danger',
                                    'direktur' => 'bg-purple-subtle text-purple',
                                    'admin_gudang' => 'bg-warning-subtle text-warning-emphasis',
                                    'admin_penjualan' => 'bg-info-subtle text-info-emphasis',
                                    'finance' => 'bg-success-subtle text-success',
                                    default => 'bg-secondary-subtle text-secondary'
                                };
                            @endphp
                            <span class="badge {{ $roleClass }} px-3 py-1 rounded-pill fw-bold">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if ($user->is_active)
                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Aktif</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light border text-warning rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Edit"><i class="bi bi-pencil"></i></a>
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Data user belum tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
