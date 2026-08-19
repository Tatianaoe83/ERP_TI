@extends('layouts.app')

@section('content')
@php
    $tienePermisoProductividad = auth()->user()->can('tickets.ver-productividad');
    $tabSolicitudes = $tienePermisoProductividad ? 3 : 2;
    $tabInicial = 1;
    if (request('tab') === 'solicitudes') {
        $tabInicial = $tabSolicitudes;
    } elseif (request('tab') === 'productividad' && $tienePermisoProductividad) {
        $tabInicial = 2;
    }
@endphp

<div data-app-tabset id="soporte-tabset">
<x-index-page
    title="Soporte"
    icon="fa-desktop"
    subtitle="Tickets y solicitudes"
    :show-count="false"
    :card="false"
>
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist">
            <button
                type="button"
                data-app-tab="1"
                class="app-tabs__btn {{ $tabInicial === 1 ? 'is-active' : '' }}">
                <i class="fas fa-ticket-alt"></i>
                <span>Tickets</span>
            </button>

            @can('tickets.ver-productividad')
            <button
                type="button"
                data-app-tab="2"
                class="app-tabs__btn {{ $tabInicial === 2 ? 'is-active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Productividad</span>
            </button>
            @endcan

            <button
                type="button"
                data-app-tab="{{ $tabSolicitudes }}"
                class="app-tabs__btn {{ $tabInicial === $tabSolicitudes ? 'is-active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>Solicitudes</span>
            </button>
        </div>
    </x-slot>

    <div data-app-panel="1" class="w-full max-w-full" @if($tabInicial !== 1) hidden @endif>
        @include('tickets.indexTicket', ['ticketsStatus' => $ticketsStatus, 'responsablesTI' => $responsablesTI])
    </div>

    @can('tickets.ver-productividad')
    <div data-app-panel="2" id="productividad-tab" class="w-full" @if($tabInicial !== 2) hidden @endif>
        @include('tickets.productividad', ['metricasProductividad' => $metricasProductividad, 'mes' => $mes ?? now()->month, 'anio' => $anio ?? now()->year])
    </div>
    @endcan

    <div data-app-panel="{{ $tabSolicitudes }}" class="w-full max-w-full" @if($tabInicial !== $tabSolicitudes) hidden @endif>
        @include('tickets.indexSolicitudes', ['solicitudesStatus' => $solicitudesStatus ?? []])
    </div>
</x-index-page>
</div>
@endsection

@push('third_party_scripts')
<script>
    document.addEventListener('notif-abrir-solicitudes-tab', function () {
        var root = document.getElementById('soporte-tabset');
        if (root && window.AppTabs) window.AppTabs.show(root, '{{ $tabSolicitudes }}');
    });
    document.addEventListener('DOMContentLoaded', function () {
        var urlParams = new URLSearchParams(window.location.search);
        var solicitudId = urlParams.get('solicitud_id');
        var accionSolicitud = urlParams.get('accion') || 'ver';
        if (solicitudId && typeof window.ejecutarAccionSolicitudNotif === 'function') {
            var root = document.getElementById('soporte-tabset');
            if (root && window.AppTabs) window.AppTabs.show(root, '{{ $tabSolicitudes }}');
            setTimeout(function () {
                window.ejecutarAccionSolicitudNotif(solicitudId, accionSolicitud);
            }, 1500);
        }
    });
</script>
@endpush
