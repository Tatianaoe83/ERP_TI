<div wire:poll.5s wire:poll.keep-alive>

    <div
        x-show="vista === 'lista'"
        x-transition
        class="space-y-4 w-full max-w-full overflow-x-hidden pb-6">

        @foreach ([
            'nuevos' => ['titulo' => 'Nuevos', 'data' => $ticketsNuevos],
            'proceso' => ['titulo' => 'En Progreso', 'data' => $ticketsProceso],
            'resueltos' => ['titulo' => 'Resueltos', 'data' => $ticketsResueltos],
        ] as $key => $grupoData)

        @php
            $titulo = $grupoData['titulo'];
            $grupo = $grupoData['data'];
        @endphp

        <div class="rounded-lg overflow-hidden shadow-sm bg-gray-50 dark:bg-[#1C1F26] border border-gray-300 dark:border-[#2A2F3A]">

            {{-- Header --}}
            <div class="px-4 py-3 flex justify-between items-center bg-gray-200 dark:bg-[#242933] border-b border-gray-300 dark:border-[#2A2F3A]">
                <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                    {{ $titulo }}
                </h3>
                <span class="text-xs font-semibold px-2 py-1 rounded bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                    {{ $grupo->count() }}
                </span>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                @forelse ($grupo as $ticket)

                @php
                    $nombreResponsable = null;
                    $nombreEmpleado = optional($ticket->empleado)->NombreEmpleado ?? '';
                    $correoEmpleado = optional($ticket->empleado)->Correo ?? optional($ticket->empleado)->correo ?? '';
                    $partes = preg_split('/\s+/', trim($nombreEmpleado));
                    if (count($partes) >= 3) array_splice($partes, 1, 1);
                    $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();

                    if (!empty($ticket->responsableTI)) {
                        $nombreResp = $ticket->responsableTI->NombreEmpleado ?? '';
                        $p = preg_split('/\s+/', trim($nombreResp));
                        if (count($p) >= 3) array_splice($p, 1, 1);
                        $nombreResponsable = \Illuminate\Support\Str::of(implode(' ', $p))->title();
                    }

                    $tiempoInfo = $tiemposProgreso[$ticket->TicketID] ?? null;
                @endphp

                <div
                    wire:key="ticket-lista-{{ $ticket->TicketID }}"
                    class="p-4 cursor-pointer transition-all duration-200 bg-gray-50 dark:bg-[#1C1F26] hover:bg-gray-100 dark:hover:bg-[#242933] border-l-4 border-l-transparent hover:border-l-blue-500"
                    
                    data-ticket-id="{{ $ticket->TicketID }}"
                    data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                    data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion ?? '', ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-prioridad="{{ $ticket->Prioridad ?? '' }}"
                    data-ticket-empleado="{{ $nombreFormateado }}"
                    data-ticket-responsable="{{ $nombreResponsable ?? '' }}"
                    data-ticket-correo="{{ $correoEmpleado }}"
                    data-ticket-fecha="{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i:s') }}"
                    data-ticket-numero="{{ $ticket->numero ?? $ticket->Numero ?? '' }}"
                    data-ticket-anydesk="{{ $ticket->code_anydesk ?? $ticket->CodeAnydesk ?? '' }}"
                    data-ticket-tiempo-estado="{{ $tiempoInfo['estado'] ?? '' }}"
                    
                    @click="abrirModalDesdeElemento($el)">

                    <div class="flex flex-col sm:flex-row justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="font-bold text-base text-gray-900 dark:text-white">
                                    Ticket #{{ $ticket->TicketID }}
                                </h4>

                                @if(!empty($ticket->Prioridad))
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border
                                    @if($ticket->Prioridad == 'Baja') bg-green-100 text-green-800 border-green-200
                                    @elseif($ticket->Prioridad == 'Media') bg-yellow-100 text-yellow-800 border-yellow-200
                                    @else bg-red-100 text-red-800 border-red-200
                                    @endif">
                                    {{ $ticket->Prioridad }}
                                </span>
                                @endif
                            </div>

                            <p class="text-sm mb-3 text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed">
                                {{ \Illuminate\Support\Str::limit($ticket->Descripcion, 160) }}
                            </p>

                            <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-gray-600 dark:text-gray-400">
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-user"></i>
                                    <span>{{ $nombreFormateado }}</span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}</span>
                                </span>

                                @if($key === 'proceso' && $nombreResponsable)
                                <span class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                    <i class="fas fa-user-tie"></i>
                                    <span>{{ $nombreResponsable }}</span>
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
                <div class="p-12 text-center text-gray-500">
                    <p class="text-sm font-medium">No hay tickets en esta categoría.</p>
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>