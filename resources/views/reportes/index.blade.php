@extends('layouts.app')
@section('content')

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card-body p-0">
        @include('reportes.table')
    </div>
</div>
@endsection