@extends('layouts.app')

@section('content')
@php
    $tienePermisoProductividad = auth()->user()->can('tickets.ver-productividad');
    $totalTabs = $tienePermisoProductividad ? 3 : 2;
    $tabSolicitudes = $tienePermisoProductividad ? 3 : 2;
@endphp
<div x-data="{
    tab: 1,
    cambiarTab(numeroTab) {
        this.tab = numeroTab;
    }
}" class="px-2 w-full max-w-full overflow-x-hidden">

    <div class="w-full mb-2">
        <div
            class="flex items-center border-b border-gray-200 w-full"
            role="tablist">
            <button
                @click="cambiarTab(1)"
                :class="tab === 1 ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">
                <i :class="tab === 1 ? 'fas fa-ticket-alt text-xs text-blue-600' : 'fas fa-ticket-alt text-xs text-gray-500'"></i>
                <span>Tickets</span>
            </button>

            @can('tickets.ver-productividad')
            <button
                @click="cambiarTab(2)"
                :class="tab === 2 ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">
                <i :class="tab === 2 ? 'fas fa-chart-line text-xs text-blue-600' : 'fas fa-chart-line text-xs text-gray-500'"></i>
                <span>Productividad</span>
            </button>
            @endcan

            <button
                @click="cambiarTab({{ $tabSolicitudes }})"
                :class="tab === {{ $tabSolicitudes }} ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">
                <i :class="tab === {{ $tabSolicitudes }} ? 'fas fa-chart-line text-xs text-blue-600' : 'fas fa-chart-line text-xs text-gray-500'"></i>
                <span>Solicitudes</span>
            </button>
        </div>
    </div>

    <div class="mt-2 w-full max-w-full overflow-x-hidden">
        <div
            x-show="tab === 1"
            x-transition.opacity
            x-cloak
            class="w-full max-w-full overflow-x-hidden">
            @include('tickets.indexTicket', ['ticketsStatus' => $ticketsStatus, 'responsablesTI' => $responsablesTI])
        </div>

        @can('tickets.ver-productividad')
        <div
            x-show="tab === 2"
            x-transition.opacity
            x-cloak
            id="productividad-tab"
            class="w-full">
            @include('tickets.productividad', ['metricasProductividad' => $metricasProductividad, 'mes' => $mes ?? now()->month, 'anio' => $anio ?? now()->year])
        </div>
        @endcan
        <div
            x-show="tab === {{ $tabSolicitudes }}"
            x-transition.opacity
            x-cloak
            class="text-gray-500 text-center py-10">
            Contenido de solicitudes
        </div>
    </div>
</div>
@endsection