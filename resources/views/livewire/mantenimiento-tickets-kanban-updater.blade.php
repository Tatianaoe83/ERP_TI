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

        <div class="flex flex-col h-full max-h-[80vh] rounded-xl overflow-hidden bg-gray-200/70 dark:bg-[#161920] border border-gray-300 dark:border-[#2A2F3A]">

            <div class="px-4 py-3 flex justify-between items-center bg-gray-300/50 dark:bg-[#1C1F26] border-b border-gray-300 dark:border-[#2A2F3A]">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ $coloresColumna[$key] ?? 'bg-gray-500' }}"></div>
                    <h3 class="font-bold text-sm text-gray-700 dark:text-gray-100 uppercase tracking-wide">{{ $titulo }}</h3>
                </div>
                <span data-categoria-header="{{ $key }}"
                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    {{ count($ticketsStatus[$key]) }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">

                @forelse ($ticketsStatus[$key] as $ticket)

                <div wire:key="mantenimiento-{{ $ticket['id'] }}"
                    class="group cursor-pointer p-4 rounded-lg shadow-sm transition-all duration-200 bg-gray-50 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A] hover:shadow-md hover:translate-y-[-2px] border-l-[4px]"
                    style="border-left-color: {{ $ticket['prioridad'] == 'Baja' ? '#22c55e' : ($ticket['prioridad'] == 'Media' ? '#eab308' : ($ticket['prioridad'] == 'Alta' ? '#ef4444' : '#dc2626')) }};"
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
                    @click="abrirModalDesdeElemento($el)">

                    <div class="flex justify-between items-start gap-2 mb-2">
                        <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400">#{{ $ticket['id'] }}</span>
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded
                            @if($ticket['prioridad']=='Baja') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @elseif($ticket['prioridad']=='Media') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                            @elseif($ticket['prioridad']=='Alta') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                            @else bg-red-200 text-red-800 dark:bg-red-900/50 dark:text-red-300
                            @endif">
                            {{ $ticket['prioridad'] }}
                        </span>
                    </div>

                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 line-clamp-1 mb-1">
                        {{ $ticket['asunto'] }}
                    </p>

                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 line-clamp-2 mb-3 leading-relaxed">
                        {{ \Illuminate\Support\Str::limit($ticket['descripcion'], 100) }}
                    </p>

                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <i class="fas fa-user opacity-70"></i>
                                <span class="truncate max-w-[120px]">{{ $ticket['solicitante'] }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500">
                                <i class="fas fa-clock opacity-70"></i>
                                <span>{{ \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans() }}</span>
                            </div>
                        </div>

                        @if($ticket['categoria'])
                        <div class="text-[10px] text-gray-500 dark:text-gray-400">
                            <i class="fas fa-tag mr-1"></i>{{ $ticket['categoria'] }}
                        </div>
                        @endif

                        @if(in_array($key, ['en_proceso', 'pausado']) && !empty($ticket['responsable']))
                        <div class="flex items-center gap-2 text-xs px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                            <i class="fas fa-user-tie"></i>
                            <span class="font-semibold truncate">{{ \App\Models\TicketMantenimiento::formatearNombreResponsable(\App\Models\TicketMantenimiento::normalizarResponsable($ticket['responsable'] ?? '') ?? '') ?: ($ticket['responsable'] ?? '') }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                @empty
                <div data-empty-placeholder class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <p class="text-sm">No hay solicitudes en esta categoría.</p>
                </div>
                @endforelse
            </div>
        </div>

        @endforeach
    </div>
</div>
