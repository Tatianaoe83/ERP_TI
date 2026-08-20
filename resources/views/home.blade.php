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
