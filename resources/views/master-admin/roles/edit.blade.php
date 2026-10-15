@extends('master-admin.layout')

@section('title', 'Edit role')

@section('content')
    <div class="page-heading"><h1>Edit role</h1></div>
    <div class="panel">
        <form method="POST" action="{{ route('master-admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('master-admin.roles.form')
        </form>
    </div>
@endsection
