@extends('layouts.app')

@section('content')
@php
    $tabInicial = request('tab') === 'productividad' ? 2 : 1;
@endphp
<div x-data="{
    tab: {{ $tabInicial }},
    cambiarTab(numeroTab) { this.tab = numeroTab; },
    init() {
        this.$watch('tab', val => {
            if (val === 2) {
                setTimeout(() => { if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento(); }, 200);
            }
        });
        if (this.tab === 2) {
            setTimeout(() => { if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento(); }, 300);
        }
    }
}" class="px-2 w-full max-w-full overflow-x-hidden">

    <div class="w-full mb-2">
        <div class="flex items-center border-b border-gray-200 dark:border-gray-700 w-full" role="tablist">
            <button @click="cambiarTab(1)"
                :class="tab === 1 ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">
                <i :class="tab === 1 ? 'fas fa-tools text-xs text-blue-600' : 'fas fa-tools text-xs text-gray-500'"></i>
                <span>Mantenimientos</span>
            </button>
            <button @click="cambiarTab(2); setTimeout(() => { if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento(); }, 150);"
                :class="tab === 2 ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">
                <i :class="tab === 2 ? 'fas fa-chart-line text-xs text-blue-600' : 'fas fa-chart-line text-xs text-gray-500'"></i>
                <span>Productividad</span>
            </button>
        </div>
    </div>

    <div class="mt-2 w-full max-w-full overflow-x-hidden">
        <div x-show="tab === 1" x-transition.opacity x-cloak class="w-full max-w-full overflow-x-hidden">
            @include('tickets-mantenimiento.indexTicket', ['ticketsStatus' => $ticketsStatus])
        </div>

        <div x-show="tab === 2" x-transition.opacity x-cloak id="productividad-mantenimiento-tab" class="w-full">
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
    </div>
</div>
@endsection
