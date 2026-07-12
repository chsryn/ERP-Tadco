@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')
@section('page_subtitle', 'Kelola akun pengguna sistem ERP')

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <div class="row align-items-center">

                <div class="col-md-6">
                    <h5 class="mb-0">
                        Daftar User
                    </h5>
                </div>

                <div class="col-md-6">

                    <form method="GET">

                        <div class="input-group">

                            <input type="text" class="form-control" name="search" value="{{ $search }}"
                                placeholder="Cari nama atau email...">

                            <button class="btn btn-primary">
                                Cari
                            </button>

                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                Reset
                            </a>

                            <a href="{{ route('users.create') }}" class="btn btn-success">
                                + User
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <table class="table table-hover table-bordered align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th width="60">#</th>

                        <th>Nama</th>

                        <th>Email</th>

                        <th width="180">Role</th>

                        <th width="180">Dibuat</th>

                        <th width="170">Aksi</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($users as $user)

                        <tr>

                            <td>
                                {{ $loop->iteration + ($users->firstItem() - 1) }}
                            </td>

                            <td>

                                <strong>{{ $user->name }}</strong>

                            </td>

                            <td>

                                {{ $user->email }}

                            </td>

                            <td>

                                @forelse($user->roles as $role)
                                    <span class="badge bg-primary">
                                        {{ ucfirst($role->name) }}
                                    </span>

                                @empty

                                    <span class="badge bg-secondary">
                                        No Role
                                    </span>
                                @endforelse

                            </td>

                            <td>

                                {{ $user->created_at->format('d M Y') }}

                            </td>

                            <td>

                                <a href="{{ route('users.show', $user) }}" class="btn btn-info btn-sm">
                                    Detail
                                </a>

                                <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">

                                    Edit

                                </a>

                                @if (auth()->id() != $user->id)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus user ini?')">

                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-danger btn-sm">

                                            Hapus

                                        </button>

                                    </form>
                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="text-center py-4">

                                Tidak ada data user.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-white">

                {{ $users->links() }}

            </div>
        @endif

    </div>

@endsection
