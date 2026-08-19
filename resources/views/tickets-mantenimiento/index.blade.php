@extends('layouts.app')

@section('content')
@php
    $tabInicial = request('tab') === 'productividad' ? 2 : 1;
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
            <button
                type="button"
                data-app-tab="1"
                class="app-tabs__btn {{ $tabInicial === 1 ? 'is-active' : '' }}">
                <i class="fas fa-tools"></i>
                <span>Mantenimientos</span>
            </button>
            <button
                type="button"
                data-app-tab="2"
                class="app-tabs__btn {{ $tabInicial === 2 ? 'is-active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Productividad</span>
            </button>
        </div>
    </x-slot>

    <div data-app-panel="1" class="w-full max-w-full" @if($tabInicial !== 1) hidden @endif>
        @include('tickets-mantenimiento.indexTicket')
    </div>

    <div data-app-panel="2" id="productividad-mantenimiento-tab" class="w-full" @if($tabInicial !== 2) hidden @endif>
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
</x-index-page>
</div>
@endsection

@push('third_party_scripts')
<script>
    document.addEventListener('app-tab-change', function (event) {
        if (event.detail && event.detail.tab === '2' && typeof inicializarGraficasMantenimiento === 'function') {
            setTimeout(inicializarGraficasMantenimiento, 200);
        }
    });
    @if($tabInicial === 2)
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento();
        }, 300);
    });
    @endif
</script>
@endpush
