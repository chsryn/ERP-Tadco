@extends('layouts.app')

@section('title','Edit User')
@section('page_title','Edit User')

@section('content')

<div class="card">

    <div class="card-header">
        Edit User
    </div>

    <div class="card-body">

        <form
            action="{{ route('users.update',$user) }}"
            method="POST">

            @method('PUT')

            @include('users._form')

        </form>

    </div>

</div>

@endsection
