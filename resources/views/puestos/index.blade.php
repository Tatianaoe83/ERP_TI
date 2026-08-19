@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Puestos"
    icon="fa-briefcase"
    :create-url="route('puestos.create')"
    create-permission="crear-puestos"
>
    @include('puestos.table')
</x-index-page>
@endsection
