@extends('layouts.app')

@section('title', 'Detail User - ERP TADCO')
@section('page_title', 'Detail User')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Profil Pengguna</h3>
                <small class="text-muted">Detail akun dan informasi sistem pengguna</small>
            </div>

            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                Kembali
            </a>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold">Informasi Akun</h6>
                <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">
                    Edit User
                </a>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 250px; background-color: #f8f9fa;">ID Sistem</th>
                        <td>{{ $user->id }}</td>
                    </tr>
                    <tr>
                        <th style="background-color: #f8f9fa;">Nama Lengkap</th>
                        <td><strong>{{ $user->name }}</strong></td>
                    </tr>
                    <tr>
                        <th style="background-color: #f8f9fa;">Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th style="background-color: #f8f9fa;">Role (Hak Akses)</th>
                        <td>
                            @forelse ($user->roles as $role)
                                <span class="badge bg-primary">
                                    {{ ucfirst($role->name) }}
                                </span>
                            @empty
                                <span class="text-muted">Tidak ada role</span>
                            @endforelse
                        </td>
                    </tr>
                    <tr>
                        <th style="background-color: #f8f9fa;">Status Akun</th>
                        <td>
                            @if ($user->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Non-Aktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th style="background-color: #f8f9fa;">Tanggal Dibuat</th>
                        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th style="background-color: #f8f9fa;">Terakhir Diperbarui</th>
                        <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endsection
