@extends('layouts.app')

@section('content')
@php
    $tabHome = request('tab');
    $puedeControl = auth()->user()->can('ver-control');
    $tabInicial = $tabHome === 'compras' ? 2 : (($puedeControl && in_array($tabHome, ['control', 'productividad'], true)) ? 3 : 1);
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
            @can('ver-control')
            <button type="button"
                data-app-tab="3"
                class="app-tabs__btn {{ $tabInicial === 3 ? 'is-active' : '' }}"
                role="tab">
                <i class="fas fa-clipboard-check"></i>
                <span>Control</span>
            </button>
            @endcan
        </div>
    </x-slot>

    <div data-app-panel="1" role="tabpanel" @if($tabInicial !== 1) hidden @endif>
        @include('partials.dashboard-informatica')
    </div>
    <div data-app-panel="2" role="tabpanel" @if($tabInicial !== 2) hidden @endif>
        @include('partials.dashboard-compras')
    </div>
    @can('ver-control')
    <div data-app-panel="3" role="tabpanel" @if($tabInicial !== 3) hidden @endif>
        @include('partials.productividad-movimientos', ['tituloSeccion' => 'Control'])
    </div>
    @endcan
</x-index-page>
</div>
@elseif($tipoDashboard === 'compras')
@php $tabDash = (auth()->user()->can('ver-control') && in_array(request('tab'), ['control', 'productividad'], true)) ? 'control' : 'dashboard'; @endphp
<div data-app-tabset>
<x-index-page title="Dashboard" icon="fa-wrench" subtitle="Compras" :show-count="false" :card="false">
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist">
            <a href="{{ route('home') }}" data-app-tab="dashboard" class="app-tabs__btn {{ $tabDash === 'dashboard' ? 'is-active' : '' }}">
                <i class="fas fa-wrench"></i>
                <span>Dashboard</span>
            </a>
            @can('ver-control')
            <a href="{{ route('home', ['tab' => 'control']) }}" data-app-tab="control" class="app-tabs__btn {{ $tabDash === 'control' ? 'is-active' : '' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>Control</span>
            </a>
            @endcan
        </div>
    </x-slot>
    @if($tabDash === 'control' && auth()->user()->can('ver-control'))
        @include('partials.productividad-movimientos', ['tituloSeccion' => 'Control'])
    @else
        @include('partials.dashboard-compras')
    @endif
</x-index-page>
</div>
@else
@php $tabDash = (auth()->user()->can('ver-control') && in_array(request('tab'), ['control', 'productividad'], true)) ? 'control' : 'dashboard'; @endphp
<div data-app-tabset>
<x-index-page title="Dashboard" icon="fa-desktop" subtitle="Informática" :show-count="false" :card="false">
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist">
            <a href="{{ route('home') }}" data-app-tab="dashboard" class="app-tabs__btn {{ $tabDash === 'dashboard' ? 'is-active' : '' }}">
                <i class="fas fa-desktop"></i>
                <span>Dashboard</span>
            </a>
            @can('ver-control')
            <a href="{{ route('home', ['tab' => 'control']) }}" data-app-tab="control" class="app-tabs__btn {{ $tabDash === 'control' ? 'is-active' : '' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>Control</span>
            </a>
            @endcan
        </div>
    </x-slot>
    @if($tabDash === 'control' && auth()->user()->can('ver-control'))
        @include('partials.productividad-movimientos', ['tituloSeccion' => 'Control'])
    @else
        @include('partials.dashboard-informatica')
    @endif
</x-index-page>
</div>
@endif
@endsection
