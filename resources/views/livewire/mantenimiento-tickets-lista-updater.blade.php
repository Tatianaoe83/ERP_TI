<div wire:poll.5s.visible="actualizarDatos">
    <div class="space-y-4 w-full max-w-full overflow-x-hidden pb-6">

        @foreach (\App\Models\TicketMantenimiento::COLUMNAS_VISTA as $key => $titulo)

        <div class="rounded-lg overflow-hidden shadow-sm bg-gray-50 dark:bg-[#1C1F26] border border-gray-300 dark:border-[#2A2F3A]">

            <div class="px-4 py-3 flex justify-between items-center bg-gray-200 dark:bg-[#242933] border-b border-gray-300 dark:border-[#2A2F3A]">
                <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100 uppercase tracking-wide">{{ $titulo }}</h3>
                <span data-categoria-header="{{ $key }}"
                    class="text-xs font-semibold px-2 py-1 rounded bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                    {{ count($ticketsStatus[$key]) }}
                </span>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                @forelse ($ticketsStatus[$key] as $ticket)

                <div wire:key="mantenimiento-lista-{{ $ticket['id'] }}"
                    class="p-4 cursor-pointer transition-all duration-200 bg-gray-50 dark:bg-[#1C1F26] hover:bg-gray-100 dark:hover:bg-[#242933] border-l-4 border-l-transparent hover:border-l-blue-500"
                    data-ticket-id="{{ $ticket['id'] }}"
                    data-categoria="{{ $key }}"
                    data-ticket-asunto="{{ htmlspecialchars($ticket['asunto'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-descripcion="{{ htmlspecialchars($ticket['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-prioridad="{{ $ticket['prioridad'] }}"
                    data-ticket-estatus="{{ $ticket['estatus'] }}"
                    data-ticket-categoria="{{ $ticket['categoria'] ?? '' }}"
                    data-ticket-responsable="{{ \App\Models\TicketMantenimiento::normalizarResponsable($ticket['responsable'] ?? '') }}"
                    data-ticket-solicitante="{{ htmlspecialchars($ticket['solicitante'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-correo="{{ $ticket['correo'] ?? '' }}"
                    data-ticket-area="{{ htmlspecialchars($ticket['area'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-fecha="{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i:s') }}"
                    data-ticket-imagen="{{ htmlspecialchars(is_array($ticket['imagen'] ?? null) ? json_encode($ticket['imagen']) : ($ticket['imagen'] ?? ''), ENT_QUOTES, 'UTF-8') }}"
                    @if(!empty($ticket['sla'])) data-ticket-sla="{{ htmlspecialchars(json_encode($ticket['sla']), ENT_QUOTES, 'UTF-8') }}" @endif
                    @click="abrirModalDesdeElemento($el)">

                    <div class="flex flex-col sm:flex-row justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h4 class="font-bold text-base text-gray-900 dark:text-white">#{{ $ticket['id'] }} — {{ $ticket['asunto'] }}</h4>
                                @if(!empty($ticket['prioridad']))
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border
                                    @if($ticket['prioridad'] == 'Baja') bg-green-100 text-green-800 border-green-200
                                    @elseif($ticket['prioridad'] == 'Media') bg-yellow-100 text-yellow-800 border-yellow-200
                                    @elseif($ticket['prioridad'] == 'Alta') bg-red-100 text-red-800 border-red-200
                                    @else bg-red-200 text-red-900 border-red-300
                                    @endif">
                                    {{ $ticket['prioridad'] }}
                                </span>
                                @else
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                                    Sin prioridad
                                </span>
                                @endif
                            </div>

                            <p class="text-sm mb-3 text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed">
                                {{ \Illuminate\Support\Str::limit($ticket['descripcion'], 160) }}
                            </p>

                            @if(!empty($ticket['sla']))
                            <div class="mb-3 max-w-md">
                                @include('tickets-mantenimiento.partials.sla-tarjeta', ['sla' => $ticket['sla']])
                            </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-gray-600 dark:text-gray-400">
                                <span class="flex items-center gap-1.5"><i class="fas fa-user"></i>{{ $ticket['solicitante'] }}</span>
                                <span class="flex items-center gap-1.5"><i class="fas fa-calendar-alt"></i>{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i') }}</span>
                                @if($ticket['categoria'])
                                <span class="flex items-center gap-1.5"><i class="fas fa-tag"></i>{{ $ticket['categoria'] }}</span>
                                @endif
                                @if(in_array($key, ['en_proceso', 'pausado']) && !empty($ticket['responsable']))
                                <span class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                    <i class="fas fa-user-tie"></i>{{ \App\Models\TicketMantenimiento::formatearNombreResponsable(\App\Models\TicketMantenimiento::normalizarResponsable($ticket['responsable'] ?? '') ?? '') ?: ($ticket['responsable'] ?? '') }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-center sm:justify-end">
                            <div class="p-2 rounded-full hover:bg-gray-200 transition-colors">
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                @empty
                <div data-empty-placeholder class="p-12 text-center text-gray-500">
                    <p class="text-sm font-medium">No hay solicitudes en esta categoría.</p>
                </div>
                @endforelse
            </div>
        </div>

        @endforeach
    </div>
</div>
