<div wire:poll.5s.visible="actualizarDatos">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <div class="space-y-4 w-full max-w-full overflow-x-hidden pb-6">

        @foreach (\App\Models\Tickets::COLUMNAS_VISTA as $key => $titulo)

        <div class="rounded-xl overflow-hidden shadow-sm dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A]">

            <div class="px-4 py-2.5 flex justify-between items-center bg-gray-50 dark:bg-[#242933] border-b border-gray-200 dark:border-[#2A2F3A]">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ \App\Models\Tickets::COLORES_COLUMNA[$key] ?? 'bg-gray-500' }}"></div>
                    <h3 class="font-bold text-xs text-gray-800 dark:text-gray-100 uppercase tracking-wide">{{ $titulo }}</h3>
                </div>
                <span data-categoria-header="{{ $key }}"
                    class="text-[11px] font-bold min-w-[1.5rem] text-center px-1.5 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                    {{ count($ticketsStatus[$key]) }}
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-[#2A2F3A]">
                @forelse ($ticketsStatus[$key] as $ticket)

                <div wire:key="ticket-lista-{{ $ticket['id'] }}"
                    class="group p-4 cursor-pointer transition-colors duration-150 dark:bg-[#1C1F26] hover:bg-blue-50/40 dark:hover:bg-[#242933] border-l-[3px]"
                    style="border-left-color: {{ $ticket['color_prioridad'] ?? '#94a3b8' }};"
                    @include('tickets.partials.ticket-card-data', ['ticket' => $ticket, 'columna' => $key])
                    @click="abrirModalDesdeElemento($el)">

                    <div class="flex items-start gap-4">
                        <div class="flex-1 min-w-0">
                            @include('tickets.partials.ticket-card-body', ['ticket' => $ticket, 'columna' => $key])
                        </div>
                        <div class="hidden sm:flex items-center self-center shrink-0 text-gray-300 dark:text-gray-600 group-hover:text-blue-400 transition-colors">
                            <i class="fas fa-chevron-right text-sm"></i>
                        </div>
                    </div>
                </div>

                @empty
                <div data-empty-placeholder class="p-10 text-center text-gray-400">
                    <i class="fas fa-inbox text-2xl mb-2 opacity-40"></i>
                    <p class="text-sm">No hay tickets en esta categoría.</p>
                </div>
                @endforelse
            </div>
        </div>

        @endforeach
    </div>
</div>
