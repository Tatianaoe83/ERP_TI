@extends('layouts.app')

@section('content')
@php
    $tabInicial = request('tab') === 'compras' ? 2 : 1;
@endphp
<div class="container mx-auto px-4 py-6">
    @if($tipoDashboard === 'completo')
        <h1 class="text-2xl font-bold text-[#101D49] dark:text-white mb-4">Dashboard General</h1>

        <div x-data="{ tab: {{ $tabInicial }} }" class="w-full">
            <nav class="flex items-center border-b border-gray-200 dark:border-gray-700 mb-6" role="tablist" aria-label="Secciones del dashboard">
                <button type="button"
                    @click="tab = 1"
                    :class="tab === 1 ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                    class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent"
                    role="tab"
                    :aria-selected="tab === 1">
                    <i class="fas fa-desktop text-xs"></i>
                    <span>Informática</span>
                </button>
                <!-- <button type="button"
                    @click="tab = 2"
                    :class="tab === 2 ? 'text-indigo-600 border-b-2 border-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                    class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent"
                    role="tab"
                    :aria-selected="tab === 2">
                    <i class="fas fa-wrench text-xs"></i>
                    <span>Compras</span>
                </button> -->
            </nav>

            <div x-show="tab === 1" x-transition.opacity x-cloak role="tabpanel">
                @include('partials.dashboard-informatica')
            </div>

            <!-- <div x-show="tab === 2" x-transition.opacity x-cloak role="tabpanel">
                @include('partials.dashboard-compras')
            </div> -->
        </div>
   <!--  @elseif($tipoDashboard === 'compras')
        <h1 class="text-2xl font-bold text-[#101D49] dark:text-white mb-6">Dashboard Compras</h1>
        @include('partials.dashboard-compras') -->
    @else
        <h1 class="text-2xl font-bold text-[#101D49] dark:text-white mb-6">Dashboard Informática</h1>
        @include('partials.dashboard-informatica')
    @endif
</div>
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
