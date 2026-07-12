@extends('layouts.app')

@section('title','Tambah User')
@section('page_title','Tambah User')

@section('content')

<div class="card">

    <div class="card-header">
        Tambah User
    </div>

    <div class="card-body">

        <form action="{{ route('users.store') }}" method="POST">

            @include('users._form')

        </form>

    </div>

</div>

@endsection
