@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Insumos"
    icon="fa-box"
    :create-url="route('insumos.create')"
    create-permission="crear-insumos"
>
    @include('insumos.table')
</x-index-page>
@endsection
