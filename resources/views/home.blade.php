@extends('layouts.app')

@section('content')
@php
    $tabInicial = request('tab') === 'compras' ? 2 : 1;
@endphp

@if($tipoDashboard === 'completo')
<div data-app-tabset>
<x-index-page
    title="Dashboard"
    icon="fa-th-large"
    subtitle="Resumen general"
    :show-count="false"
    :card="false"
>
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist" aria-label="Secciones del dashboard">
            <button type="button"
                data-app-tab="1"
                class="app-tabs__btn {{ $tabInicial === 1 ? 'is-active' : '' }}"
                role="tab">
                <i class="fas fa-desktop"></i>
                <span>Informática</span>
            </button>
            <button type="button"
                data-app-tab="2"
                class="app-tabs__btn {{ $tabInicial === 2 ? 'is-active' : '' }}"
                role="tab">
                <i class="fas fa-wrench"></i>
                <span>Compras</span>
            </button>
        </div>
    </x-slot>

    <div data-app-panel="1" role="tabpanel" @if($tabInicial !== 1) hidden @endif>
        @include('partials.dashboard-informatica')
    </div>
    <div data-app-panel="2" role="tabpanel" @if($tabInicial !== 2) hidden @endif>
        @include('partials.dashboard-compras')
    </div>
</x-index-page>
</div>
@elseif($tipoDashboard === 'compras')
<x-index-page title="Dashboard" icon="fa-wrench" subtitle="Compras" :show-count="false" :card="false">
    @include('partials.dashboard-compras')
</x-index-page>
@else
<x-index-page title="Dashboard" icon="fa-desktop" subtitle="Informática" :show-count="false" :card="false">
    @include('partials.dashboard-informatica')
</x-index-page>
@endif
@endsection

@push('third_party_stylesheets')
    <style>
        .dashboard-card {
            border: 1px solid rgba(255, 255, 255, .16);
        }

        .dashboard-card-orange {
            background: linear-gradient(135deg, #f97316, #ea580c);
        }

        .dashboard-card-blue {
            background: #3b82f6;
        }

        .dashboard-card-green {
            background: linear-gradient(90deg, #22c55e, #16a34a);
        }

        .dashboard-card-inner {
            background: rgba(255, 255, 255, .15);
            border-color: rgba(255, 255, 255, .3);
        }

        .dashboard-card-icon {
            background: rgba(255, 255, 255, .18);
        }

        .dark .dashboard-card {
            border-color: rgba(148, 163, 184, .22);
            box-shadow: 0 14px 30px rgba(0, 0, 0, .28);
        }

        .dark .dashboard-card-orange {
            background: linear-gradient(135deg, #7c2d12, #9a3412);
        }

        .dark .dashboard-card-blue {
            background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
        }

        .dark .dashboard-card-green {
            background: linear-gradient(135deg, #14532d, #166534);
        }

        .dark .dashboard-card-inner {
            background: rgba(15, 23, 42, .28);
            border-color: rgba(226, 232, 240, .28);
        }

        .dark .dashboard-card-icon {
            background: rgba(255, 255, 255, .12);
        }
    </style>
@endpush
