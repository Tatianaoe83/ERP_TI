@extends('layouts.app')

@section('content')
<div class="bg-red-500">
    <div class="bg-white rounded-md border border-gray-500 mt-5 p-5">
        @include('facturas.table')
        <div class="card-footer clearfix"></div>
    </div>
</div>
@endsection