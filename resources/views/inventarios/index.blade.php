@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Inventario"
    icon="fa-clipboard-list"
    subtitle="Asignaciones por empleado"
>
    @include('inventarios.table')
</x-index-page>
@endsection
