<div wire:poll.5s.visible="actualizarDatos">
    <div class="space-y-4 w-full max-w-full overflow-x-hidden pb-6">

        @foreach (\App\Models\TicketMantenimiento::COLUMNAS_VISTA as $key => $titulo)

        <div class="rounded-xl overflow-hidden shadow-sm bg-white dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A]">

            <div class="px-4 py-2.5 flex justify-between items-center bg-gray-50 dark:bg-[#242933] border-b border-gray-200 dark:border-[#2A2F3A]">
                <h3 class="font-bold text-xs text-gray-800 dark:text-gray-100 uppercase tracking-wide">{{ $titulo }}</h3>
                <span data-categoria-header="{{ $key }}"
                    class="text-[11px] font-bold min-w-[1.5rem] text-center px-1.5 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                    {{ count($ticketsStatus[$key]) }}
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-[#2A2F3A]">
                @forelse ($ticketsStatus[$key] as $ticket)

                <div wire:key="mantenimiento-lista-{{ $ticket['id'] }}"
                    class="group p-4 cursor-pointer transition-colors duration-150 dark:bg-[#1C1F26] hover:bg-blue-50/40 dark:hover:bg-[#242933] border-l-[3px]"
                    style="border-left-color: {{ $ticket['color_prioridad'] ?? '#94a3b8' }};"
                    @include('tickets-mantenimiento.partials.ticket-card-data', ['ticket' => $ticket, 'columna' => $key])
                    @click="abrirModalDesdeElemento($el)">

                    <div class="flex items-start gap-4">
                        <div class="flex-1 min-w-0">
                            @include('tickets-mantenimiento.partials.ticket-card-body', ['ticket' => $ticket])
                        </div>
                        <div class="hidden sm:flex items-center self-center shrink-0 text-gray-300 dark:text-gray-600 group-hover:text-blue-400 transition-colors">
                            <i class="fas fa-chevron-right text-sm"></i>
                        </div>
                    </div>
                </div>

                @empty
                <div data-empty-placeholder class="p-10 text-center text-gray-400 dark:bg-[#1C1F26] dark:text-gray-500">
                    <i class="fas fa-inbox text-2xl mb-2 opacity-40"></i>
                    <p class="text-sm">No hay solicitudes en esta categoría.</p>
                </div>
                @endforelse
            </div>
        </div>

        @endforeach
    </div>
</div>
