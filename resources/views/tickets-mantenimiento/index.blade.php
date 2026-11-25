@extends('layouts.app')

@section('content')
@php
    $tabActiva = $tab ?? request('tab', 'mantenimientos');
    if ($tabActiva !== 'productividad') {
        $tabActiva = 'mantenimientos';
    }
    $esMantenimientos = $tabActiva === 'mantenimientos';
@endphp

{{-- El switch de vistas vive en la misma fila que las pestañas, así que la raíz Alpine/vistas
     sube hasta aquí: los botones tienen que quedar dentro de [data-vista-root]. --}}
<div data-app-tabset id="mantenimiento-tabset"
    @if($esMantenimientos)
        x-data="mantenimientoModal()"
        x-init="init()"
        data-vista-root
        data-vista-storage="mantenimientoVista"
        data-vista-event="mantenimiento-vista-activada"
        class="mantenimiento-container"
    @endif
>
<x-index-page
    title="Mantenimientos de compras"
    icon="fa-tools"
    subtitle="Tickets y productividad"
    :show-count="false"
    :card="false"
>
    <x-slot name="tabs">
        <div class="flex flex-wrap items-center justify-between gap-2 w-full">
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

            @if($esMantenimientos)
            <div class="flex items-center gap-2">
                <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-300 font-medium hidden sm:inline">Vista:</span>
                <div class="vista-switch">
                    <button type="button" data-vista-btn="kanban"
                        class="vista-switch__btn is-vista-active px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2 text-[#9CA3AF] hover:text-[#E5E7EB]">
                        <i class="fas fa-columns text-xs"></i><span class="hidden sm:inline">Kanban</span>
                    </button>
                    <button type="button" data-vista-btn="lista"
                        class="vista-switch__btn px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2 text-[#9CA3AF] hover:text-[#E5E7EB]">
                        <i class="fas fa-list text-xs"></i><span class="hidden sm:inline">Lista</span>
                    </button>
                    <button type="button" data-vista-btn="tabla"
                        class="vista-switch__btn px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2 text-[#9CA3AF] hover:text-[#E5E7EB]">
                        <i class="fas fa-table text-xs"></i><span class="hidden sm:inline">Tabla</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </x-slot>

    @if($esMantenimientos)
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
    (function (fn) { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); })(function () {
        setTimeout(function () {
            if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento();
        }, 300);
    });
    @endif

    // Las columnas del kanban miden justo lo que queda de pantalla: así solo scrollean por dentro
    // y la vista no. La variable va en el contenedor (fuera de Livewire) para que el refresco no la borre.
    (function () {
        function ajustarColumnasMantenimiento() {
            var raiz = document.getElementById('mantenimiento-tabset');
            var main = document.getElementById('app-main');
            if (!raiz || !main) return;
            var grid = raiz.querySelector('[data-vista-panel="kanban"]:not([hidden]) .tk-col');
            // Con menos de 5 columnas por fila se apilan y el scroll de la página es inevitable.
            if (!grid || !window.matchMedia('(min-width: 1280px)').matches) {
                raiz.style.removeProperty('--mant-col-alto');
                return;
            }
            grid = grid.parentElement;
            var cajaMain = main.getBoundingClientRect();
            var arriba = grid.getBoundingClientRect().top - cajaMain.top + main.scrollTop;
            // Lo que va debajo del tablero: paddings, bordes y márgenes inferiores de sus contenedores.
            var debajo = 0;
            for (var e = grid; e && e !== main.parentElement; e = e.parentElement) {
                var cs = getComputedStyle(e);
                debajo += parseFloat(cs.marginBottom) || 0;
                if (e !== grid) debajo += (parseFloat(cs.paddingBottom) || 0) + (parseFloat(cs.borderBottomWidth) || 0);
            }
            var alto = Math.max(320, Math.floor(main.clientHeight - arriba - debajo));
            raiz.style.setProperty('--mant-col-alto', alto + 'px');
        }

        window.ajustarColumnasMantenimiento = ajustarColumnasMantenimiento;
        if (!window.__mantColumnasListo) {
            window.__mantColumnasListo = true;
            window.addEventListener('resize', function () { window.ajustarColumnasMantenimiento(); });
            document.addEventListener('app-tab-change', function () { setTimeout(window.ajustarColumnasMantenimiento, 40); });
            document.addEventListener('livewire:load', function () {
                window.Livewire.hook('message.processed', function () { window.ajustarColumnasMantenimiento(); });
            });
            if (window.Livewire && window.Livewire.hook) {
                window.Livewire.hook('message.processed', function () { window.ajustarColumnasMantenimiento(); });
            }
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ajustarColumnasMantenimiento);
        else ajustarColumnasMantenimiento();
    })();
</script>
@endpush
