{{-- resources/views/livewire/tickets-kanban-updater.blade.php --}}

<div wire:poll.15s="actualizarDatos" wire:poll.keep-alive>


    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start h-full" >

        @foreach (['nuevos' => 'Nuevos', 'proceso' => 'En Progreso', 'resueltos' => 'Resueltos'] as $key => $titulo)

            <div class="flex flex-col h-full max-h-[80vh] rounded-xl overflow-hidden bg-gray-200/70 dark:bg-[#161920] border border-gray-300 dark:border-[#2A2F3A]">

                {{-- Header --}}
                <div class="px-4 py-3 flex justify-between items-center bg-gray-300/50 dark:bg-[#1C1F26] border-b border-gray-300 dark:border-[#2A2F3A]">
                    
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full {{ $key === 'nuevos' ? 'bg-yellow-500' : ($key === 'proceso' ? 'bg-blue-500' : 'bg-green-500') }}"></div>
                        <h3 class="font-bold text-sm text-gray-700 dark:text-gray-100 uppercase tracking-wide">
                            {{ $titulo }}
                        </h3>
                    </div>

                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ count($ticketsStatus[$key]) }}
                    </span>
                </div>

                {{-- Contenedor --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">

                    @forelse ($ticketsStatus[$key] as $ticket)

                        @php
                            $nombreEmpleado = $ticket['empleado']['nombre'] ?? '';
                            $correoEmpleado = $ticket['empleado']['correo'] ?? '';
                            $nombreResponsable = $ticket['responsable']['nombre'] ?? null;

                            $partes = preg_split('/\s+/', trim($nombreEmpleado));
                            if (count($partes) >= 3) array_splice($partes, 1, 1);
                            $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();

                            $tiempoInfo = $tiemposProgreso[$ticket['id']] ?? null;

                            if ($nombreResponsable) {
                                $p = preg_split('/\s+/', trim($nombreResponsable));
                                if (count($p) >= 3) array_splice($p, 1, 1);
                                $nombreResponsable = \Illuminate\Support\Str::of(implode(' ', $p))->title();
                            }
                        @endphp

                        {{-- Card --}}
                        <div wire:key="ticket-{{ $ticket['id'] }}"
                             class="group cursor-pointer p-4 rounded-lg shadow-sm transition-all duration-200 bg-gray-50 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A] hover:shadow-md hover:translate-y-[-2px] border-l-[4px]"
                             style="border-left-color: {{ $ticket['prioridad'] == 'Baja' ? '#22c55e' : ($ticket['prioridad'] == 'Media' ? '#eab308' : '#ef4444') }};"
                             data-ticket-id="{{ $ticket['id'] }}"
                             data-ticket-asunto="Ticket #{{ $ticket['id'] }}"
                             data-ticket-descripcion="{{ htmlspecialchars($ticket['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                             data-ticket-prioridad="{{ $ticket['prioridad'] }}"
                             data-ticket-empleado="{{ $nombreFormateado }}"
                             data-ticket-responsable="{{ $nombreResponsable ?? '' }}"
                             data-ticket-correo="{{ $correoEmpleado }}"
                             data-ticket-fecha="{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i:s') }}"
                             data-ticket-numero="{{ $ticket['numero'] ?? '' }}"
                             data-ticket-anydesk="{{ $ticket['code_anydesk'] ?? '' }}"
                             data-ticket-tiempo-estado="{{ $tiempoInfo['estado'] ?? '' }}"
                             @click="abrirModalDesdeElemento($el)">

                            {{-- Header --}}
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400">
                                    #{{ $ticket['id'] }}
                                </span>
                                
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded
                                    @if($ticket['prioridad']=='Baja') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($ticket['prioridad']=='Media') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @endif">
                                    {{ $ticket['prioridad'] }}
                                </span>
                            </div>

                            {{-- Descripción --}}
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 line-clamp-3 mb-3 leading-relaxed">
                                {{ \Illuminate\Support\Str::limit($ticket['descripcion'], 100) }}
                            </p>

                            {{-- Footer --}}
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-col gap-2">
                                
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-user opacity-70"></i>
                                        <span class="truncate max-w-[80px]">{{ $nombreFormateado }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-[10px] text-gray-500 dark:text-gray-500">
                                        <i class="fas fa-clock opacity-70"></i>
                                        <span>{{ \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans() }}</span>
                                    </div>
                                </div>

                                @if($key === 'proceso' && $nombreResponsable)
                                <div class="mt-1 flex items-center gap-2 text-xs px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                    <i class="fas fa-user-tie"></i>
                                    <span class="font-semibold truncate">{{ $nombreResponsable }}</span>
                                </div>
                                @endif
                                
                                @if($tiempoInfo)
                                <div class="mt-2 w-full">
                                    <div class="flex justify-between text-[10px] mb-1 text-gray-500 dark:text-gray-400">
                                        <span>Tiempo:</span>
                                        <span class="{{ $tiempoInfo['estado'] == 'agotado' ? 'text-red-500 font-bold' : '' }}">
                                            {{ $tiempoInfo['transcurrido'] }}h / {{ $tiempoInfo['estimado'] }}h
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $tiempoInfo['estado'] == 'agotado' ? 'bg-red-500' : ($tiempoInfo['estado'] == 'por_vencer' ? 'bg-yellow-500' : 'bg-green-500') }}"
                                             style="width: {{ min($tiempoInfo['porcentaje'], 100) }}%">
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </div>

                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-gray-400 dark:text-gray-500 opacity-60">
                            <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                            <p class="text-sm">Sin tickets</p>
                        </div>
                    @endforelse

                </div>
            </div>
        @endforeach

    </div>
</div>
