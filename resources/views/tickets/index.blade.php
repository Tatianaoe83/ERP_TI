@extends('layouts.app')

@section('content')
@php
    $tienePermisoProductividad = auth()->user()->can('tickets.ver-productividad');
    $totalTabs = $tienePermisoProductividad ? 3 : 2;
    $tabSolicitudes = $tienePermisoProductividad ? 3 : 2;
@endphp
<div x-data="{ tab: 1 }" class="px-2 w-full max-w-full overflow-x-hidden">

    <div class="flex justify-start mb-2">
        <div
            class="relative grid items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1 shadow-sm w-full max-w-md {{ $tienePermisoProductividad ? 'grid-cols-3' : 'grid-cols-2' }}"
            role="tablist">
            <div
                class="absolute top-1 bottom-1 rounded-md bg-gradient-to-r from-blue-500 to-blue-700 shadow-md transition-all duration-300 ease-in-out"
                :style="`left:${(tab-1)*100/{{ $totalTabs }}%; width:${100/{{ $totalTabs }}%}`"></div>

            <button
                @click="tab = 1"
                :class="tab === 1 ? 'text-white' : 'text-gray-600 hover:text-gray-800'"
                class="relative z-10 block rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fas fa-ticket-alt text-xs"></i>
                    <span>Tickets</span>
                </span>
            </button>

            @can('tickets.ver-productividad')
            <button
                @click="tab = 2"
                :class="tab === 2 ? 'text-white' : 'text-gray-600 hover:text-gray-800'"
                class="relative z-10 block rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fas fa-chart-line text-xs"></i>
                    <span>Productividad</span>
                </span>
            </button>
            @endcan

            <button
                @click="tab = {{ $tabSolicitudes }}"
                :class="tab === {{ $tabSolicitudes }} ? 'text-white' : 'text-gray-600 hover:text-gray-800'"
                class="relative z-10 block rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fas fa-chart-line text-xs"></i>
                    <span>Solicitudes</span>
                </span>
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
            id="productividad-tab">
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