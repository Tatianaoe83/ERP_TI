@extends('layouts.app')

@section('content')
@php
    $tabActiva = $tab ?? request('tab', 'mantenimientos');
    if ($tabActiva !== 'productividad') {
        $tabActiva = 'mantenimientos';
    }
@endphp

<div data-app-tabset id="mantenimiento-tabset">
<x-index-page
    title="Mantenimientos de compras"
    icon="fa-tools"
    subtitle="Tickets y productividad"
    :show-count="false"
    :card="false"
>
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist">
            <a
                href="{{ route('tickets-mantenimiento.index') }}"
                data-app-tab="mantenimientos"
                class="app-tabs__btn {{ $tabActiva === 'mantenimientos' ? 'is-active' : '' }}">
                <i class="fas fa-tools"></i>
                <span>Mantenimientos</span>
            </a>
            <a
                href="{{ route('tickets-mantenimiento.index', ['tab' => 'productividad']) }}"
                data-app-tab="productividad"
                class="app-tabs__btn {{ $tabActiva === 'productividad' ? 'is-active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Productividad</span>
            </a>
        </div>
    </x-slot>

    @if($tabActiva === 'mantenimientos')
    <div data-app-panel="mantenimientos" class="w-full max-w-full">
        @include('tickets-mantenimiento.indexTicket')
    </div>
    @else
    <div data-app-panel="productividad" id="productividad-mantenimiento-tab" class="w-full">
        @include('tickets-mantenimiento.productividad', [
            'metricasProductividad' => $metricasProductividad,
            'mes' => $mes ?? now()->month,
            'anio' => $anio ?? now()->year,
            'mesInicio' => $mesInicio ?? ($mes ?? now()->month),
            'anioInicio' => $anioInicio ?? ($anio ?? now()->year),
            'mesFin' => $mesFin ?? ($mes ?? now()->month),
            'anioFin' => $anioFin ?? ($anio ?? now()->year),
        ])
    </div>
    @endif
</x-index-page>
</div>
@endsection

@push('third_party_scripts')
<script>
    @if($tabActiva === 'productividad')
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento();
        }, 300);
    });
    @endif
</script>
@endpush
