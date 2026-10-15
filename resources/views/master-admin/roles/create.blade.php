@extends('master-admin.layout')

@section('title', 'Add role')

@section('content')
    <div class="page-heading"><h1>Add role</h1></div>
    <div class="panel">
        <form method="POST" action="{{ route('master-admin.roles.store') }}">
            @csrf
            @include('master-admin.roles.form', ['role' => null])
        </form>
    </div>
@endsection
