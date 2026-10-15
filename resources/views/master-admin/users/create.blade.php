@extends('master-admin.layout')

@section('title', 'Add user')

@section('content')
    <div class="page-heading"><h1>Add user</h1></div>
    <div class="panel">
        <form method="POST" action="{{ route('master-admin.users.store') }}">
            @csrf
            @include('master-admin.users.form', ['managedUser' => null])
        </form>
    </div>
@endsection
