@extends('layouts.app')

@section('title', 'Detail User')
@section('page_title', 'Detail User')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between">

            <h5 class="mb-0">Detail User</h5>

            <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">
                Edit
            </a>

        </div>

        <div class="card-body">

            <table class="table">

                <tr>
                    <th width="200">ID</th>
                    <td>{{ $user->id }}</td>
                </tr>

                <tr>
                    <th>Nama</th>
                    <td>{{ $user->name }}</td>
                </tr>

                <tr>
                    <th>Email</th>
                    <td>{{ $user->email }}</td>
                </tr>

                <tr>
                    <th>Role</th>
                    <td>
                        @foreach ($user->roles as $role)
                            <span class="badge bg-primary">
                                {{ ucfirst($role->name) }}
                            </span>
                        @endforeach
                    </td>
                </tr>

                <tr>
                    <th>Dibuat</th>
                    <td>{{ $user->created_at }}</td>
                </tr>

                <tr>
                    <th>Terakhir Update</th>
                    <td>{{ $user->updated_at }}</td>
                </tr>

            </table>

            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                Kembali
            </a>

        </div>

    </div>

@endsection
