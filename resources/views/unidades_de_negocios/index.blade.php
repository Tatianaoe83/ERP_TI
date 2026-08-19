@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Unidades de negocio"
    icon="fa-city"
    :create-url="route('unidadesDeNegocios.create')"
    create-permission="crear-unidadesdenegocio"
>
    @include('unidades_de_negocios.table')
</x-index-page>
@endsection
