@extends('layouts.app')

@section('content')
@php
    $tienePermisoProductividad = auth()->user()->can('tickets.ver-productividad');
    $tienePermisoTareas = auth()->user()->can('tickets.ver-tareas');
    $tabActiva = $tab ?? request('tab', 'tickets');
    if (! in_array($tabActiva, ['tickets', 'productividad', 'solicitudes', 'tareas'], true)) {
        $tabActiva = 'tickets';
    }
    if ($tabActiva === 'productividad' && ! $tienePermisoProductividad) {
        $tabActiva = 'tickets';
    }
    if ($tabActiva === 'tareas' && ! $tienePermisoTareas) {
        $tabActiva = 'tickets';
    }
@endphp

{{-- El switch de vistas y el botón de métricas van en la fila de las pestañas, así que la raíz
     Alpine/vistas sube hasta aquí: esos botones tienen que quedar dentro de [data-vista-root]. --}}
<div data-app-tabset id="soporte-tabset"
    @if($tabActiva === 'tickets')
        x-data="ticketsModal()"
        x-init="
            const vistaGuardada = localStorage.getItem('ticketsVista') || 'kanban';
            vista = vistaGuardada;
            if (vistaGuardada !== 'kanban' && window.Livewire) window.Livewire.emit('soporte-vista-activa', vistaGuardada);
            init();
        "
        data-vista-root
        data-vista-storage="ticketsVista"
        data-vista-event="soporte-vista-activa"
        class="tickets-container"
    @endif
>
<x-index-page
    title="Soporte"
    icon="fa-desktop"
    subtitle="Tareas, tickets y solicitudes"
    :show-count="false"
    :card="false"
>
    <x-slot name="tabs">
        <div class="flex flex-wrap items-center justify-between gap-2 w-full">
        <div class="app-tabs" role="tablist">
            @can('tickets.ver-tareas')
            <a
                href="{{ route('tickets.index', ['tab' => 'tareas']) }}"
                data-app-tab="tareas"
                class="app-tabs__btn {{ $tabActiva === 'tareas' ? 'is-active' : '' }}">
                <i class="fas fa-tasks"></i>
                <span>Tareas</span>
            </a>
            @endcan

            <a
                href="{{ route('tickets.index') }}"
                data-app-tab="tickets"
                class="app-tabs__btn {{ $tabActiva === 'tickets' ? 'is-active' : '' }}">
                <i class="fas fa-ticket-alt"></i>
                <span>Tickets</span>
            </a>

            <a
                href="{{ route('tickets.index', ['tab' => 'solicitudes']) }}"
                data-app-tab="solicitudes"
                class="app-tabs__btn {{ $tabActiva === 'solicitudes' ? 'is-active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>Solicitudes</span>
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
        </div>

        @if($tabActiva === 'tickets')
        <div class="flex items-center gap-2">
            @can('tickets.ajustar-metricas')
            <button
                @click="mostrarModalMetricas = true; cargarMetricas()"
                class="index-page__btn-primary">
                <i class="fas fa-cog text-sm"></i>
                <span class="hidden sm:inline">Ajustar Métricas</span>
                <span class="sm:hidden">Métricas</span>
            </button>
            @endcan

            <span class="text-xs sm:text-sm text-[#9CA3AF] font-medium hidden sm:inline">Vista:</span>
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A] rounded-lg p-1">
                <button type="button"
                    data-vista-btn="kanban"
                    class="vista-switch__btn is-vista-active px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 justify-center text-[#9CA3AF] hover:text-[#E5E7EB]">
                    <i class="fas fa-columns text-xs"></i>
                    <span class="hidden sm:inline">Kanban</span>
                </button>
                <button type="button"
                    data-vista-btn="lista"
                    class="vista-switch__btn px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 justify-center text-[#9CA3AF] hover:text-[#E5E7EB]">
                    <i class="fas fa-list text-xs"></i>
                    <span class="hidden sm:inline">Lista</span>
                </button>
                <button type="button"
                    data-vista-btn="tabla"
                    class="vista-switch__btn px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 justify-center text-[#9CA3AF] hover:text-[#E5E7EB]">
                    <i class="fas fa-table text-xs"></i>
                    <span class="hidden sm:inline">Tabla</span>
                </button>
            </div>
        </div>
        @endif
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
    @elseif($tabActiva === 'tareas')
    <div data-app-panel="tareas" class="w-full max-w-full">
        @include('tickets.indexTareas')
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
    (function (fn) { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); })(function () {
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
