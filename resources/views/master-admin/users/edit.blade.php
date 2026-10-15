@extends('master-admin.layout')

@section('title', 'Edit user')

@section('content')
    <div class="page-heading"><h1>Edit user</h1></div>
    <div class="panel">
        <form method="POST" action="{{ route('master-admin.users.update', $managedUser) }}">
            @csrf
            @method('PUT')
            @include('master-admin.users.form')
        </form>
    </div>
@endsection
