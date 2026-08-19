@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Reporteador"
    icon="fa-book"
    :create-url="route('reportes.create')"
    create-permission="crear-reportes"
    create-label="+ Nuevo reporte"
>
    <x-slot name="headerActions">
        @can('ver-reportes-especificos')
        <a href="{{ route('reportes-especificos.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-chart-line"></i> Reportes específicos
        </a>
        @endcan
    </x-slot>

    @include('reportes.table')
</x-index-page>
@endsection
