@php
    $coloresColumna = [
        'pendiente'  => 'bg-yellow-500',
        'en_proceso' => 'bg-blue-500',
        'pausado'    => 'bg-orange-500',
        'atendido'   => 'bg-green-500',
        'cancelado'  => 'bg-gray-500',
    ];
@endphp
<div wire:poll.5s.visible="actualizarDatos">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-start h-full">

        @foreach (\App\Models\TicketMantenimiento::COLUMNAS_VISTA as $key => $titulo)

        <div class="flex flex-col h-full max-h-[80vh] rounded-xl overflow-hidden bg-gray-100/80 dark:bg-[#161920] border border-gray-200 dark:border-[#2A2F3A] shadow-sm">

            <div class="px-3 py-2.5 flex justify-between items-center bg-white/70 dark:bg-[#1C1F26] border-b border-gray-200 dark:border-[#2A2F3A]">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ $coloresColumna[$key] ?? 'bg-gray-500' }}"></div>
                    <h3 class="font-bold text-xs text-gray-700 dark:text-gray-100 uppercase tracking-wide">{{ $titulo }}</h3>
                </div>
                <span data-categoria-header="{{ $key }}"
                    class="text-[11px] font-bold min-w-[1.5rem] text-center px-1.5 py-0.5 rounded-full bg-gray-200/80 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    {{ count($ticketsStatus[$key]) }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto p-2.5 space-y-2.5 custom-scrollbar">

                @forelse ($ticketsStatus[$key] as $ticket)

                <div wire:key="mantenimiento-{{ $ticket['id'] }}"
                    class="group flex flex-col cursor-pointer p-3 rounded-xl transition-all duration-200 bg-white dark:bg-[#1C1F26] border border-gray-200/80 dark:border-[#2A2F3A] hover:shadow-md hover:border-blue-200 dark:hover:border-blue-800/50 hover:-translate-y-0.5 border-l-[3px]"
                    style="border-left-color: {{ $ticket['color_prioridad'] ?? '#94a3b8' }};"
                    @include('tickets-mantenimiento.partials.ticket-card-data', ['ticket' => $ticket, 'columna' => $key])
                    @click="abrirModalDesdeElemento($el)">

                    @include('tickets-mantenimiento.partials.ticket-card-body', ['ticket' => $ticket])
                </div>

                @empty
                <div data-empty-placeholder class="p-8 text-center text-gray-400 dark:text-gray-500">
                    <i class="fas fa-inbox text-2xl mb-2 opacity-40"></i>
                    <p class="text-xs">Sin solicitudes</p>
                </div>
                @endforelse
            </div>
        </div>

        @endforeach
    </div>
</div>
