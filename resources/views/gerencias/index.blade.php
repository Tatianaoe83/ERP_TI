@extends('layouts.app')

@section('content')
<div class="px-3">
    @include('flash::message')

    @include('gerencias.table')
</div>
@endsection