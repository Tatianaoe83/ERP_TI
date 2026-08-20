@extends('layouts.app')

@section('content')
@php
    $tienePermisoProductividad = auth()->user()->can('tickets.ver-productividad');
    $tabActiva = $tab ?? request('tab', 'tickets');
    if (! in_array($tabActiva, ['tickets', 'productividad', 'solicitudes'], true)) {
        $tabActiva = 'tickets';
    }
    if ($tabActiva === 'productividad' && ! $tienePermisoProductividad) {
        $tabActiva = 'tickets';
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
            <a
                href="{{ route('tickets.index') }}"
                data-app-tab="tickets"
                class="app-tabs__btn {{ $tabActiva === 'tickets' ? 'is-active' : '' }}">
                <i class="fas fa-ticket-alt"></i>
                <span>Tickets</span>
            </a>

            @can('tickets.ver-productividad')
            <a
                href="{{ route('tickets.index', ['tab' => 'productividad']) }}"
                data-app-tab="productividad"
                class="app-tabs__btn {{ $tabActiva === 'productividad' ? 'is-active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Productividad</span>
            </a>
            @endcan

            <a
                href="{{ route('tickets.index', ['tab' => 'solicitudes']) }}"
                data-app-tab="solicitudes"
                class="app-tabs__btn {{ $tabActiva === 'solicitudes' ? 'is-active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>Solicitudes</span>
            </a>
        </div>
    </x-slot>

    @if($tabActiva === 'tickets')
    <div data-app-panel="tickets" class="w-full max-w-full">
        @include('tickets.indexTicket', ['ticketsStatus' => $ticketsStatus, 'responsablesTI' => $responsablesTI])
    </div>
    @elseif($tabActiva === 'productividad')
    <div data-app-panel="productividad" id="productividad-tab" class="w-full">
        @include('tickets.productividad', ['metricasProductividad' => $metricasProductividad, 'mes' => $mes ?? now()->month, 'anio' => $anio ?? now()->year])
    </div>
    @elseif($tabActiva === 'solicitudes')
    <div data-app-panel="solicitudes" class="w-full max-w-full">
        @include('tickets.indexSolicitudes', ['solicitudesStatus' => $solicitudesStatus ?? []])
    </div>
    @endif
</x-index-page>
</div>
@endsection

@push('third_party_scripts')
<script>
    document.addEventListener('notif-abrir-solicitudes-tab', function () {
        if (!document.querySelector('[data-app-panel="solicitudes"]')) {
            window.location.href = @json(route('tickets.index', ['tab' => 'solicitudes']));
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        var urlParams = new URLSearchParams(window.location.search);
        var solicitudId = urlParams.get('solicitud_id');
        var accionSolicitud = urlParams.get('accion') || 'ver';
        if (solicitudId && typeof window.ejecutarAccionSolicitudNotif === 'function') {
            setTimeout(function () {
                window.ejecutarAccionSolicitudNotif(solicitudId, accionSolicitud);
            }, 1500);
        }
    });
</script>
@endpush
