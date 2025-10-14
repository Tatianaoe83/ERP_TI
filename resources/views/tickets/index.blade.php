@extends('layouts.app')

@section('content')
<div x-data="{ tab: 1 }" class="px-2">

    <div class="flex justify-start mb-2">
        <div
            class="relative grid grid-cols-3 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1 shadow-sm w-full max-w-md"
            role="tablist">
            <div
                class="absolute top-1 bottom-1 rounded-md bg-gradient-to-r from-blue-500 to-blue-700 shadow-md transition-all duration-300 ease-in-out"
                :style="`left:${(tab-1)*100/3}%; width:${100/3}%`"></div>

            <template x-for="(label, index) in ['Tickets', 'Solicitudes', 'Productividad']" :key="index">
                <button
                    @click="tab = index + 1"
                    :class="tab === index + 1 ? 'text-white' : 'text-gray-600 hover:text-gray-800'"
                    class="relative z-10 block rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200">
                    <span class="flex items-center justify-center gap-2">
                        <i :class="[
                            index === 0 ? 'fas fa-ticket-alt' :
                            index === 1 ? 'fas fa-clipboard-list' :
                            'fas fa-chart-line',
                            'text-xs'
                        ]"></i>
                        <span x-text="label"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>

    <div class="mt-2">
        <div
            x-show="tab === 1"
            x-transition.opacity
            x-cloak>
            @include('tickets.indexTicket')
        </div>

        <div
            x-show="tab === 2"
            x-transition.opacity
            x-cloak
            class="text-gray-500 text-center py-10">
            Contenido de solicitudes
        </div>

        <div
            x-show="tab === 3"
            x-transition.opacity
            x-cloak
            class="text-gray-500 text-center py-10">
            Dashboard de productividad
        </div>
    </div>
</div>
@endsection