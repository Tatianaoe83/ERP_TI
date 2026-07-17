<div wire:poll.5s.visible="actualizarDatos" wire:init="actualizarDatos">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <div class="rounded-xl overflow-hidden w-full max-w-full bg-white dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A] shadow-sm">

        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-[#2A2F3A] dark:bg-[#1C1F26]">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                @if($ticketsTabla->count())
                Mostrando {{ $ticketsTabla->firstItem() }} - {{ $ticketsTabla->lastItem() }}
                de {{ $ticketsTabla->total() }} tickets
                @else
                Sin registros
                @endif
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Total: {{ $ticketsTabla->total() }}
            </div>
        </div>

        <div class="px-4 py-3 bg-gray-50 dark:bg-[#1C1F26] border-b border-gray-200 dark:border-[#2A2F3A] grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text"
                wire:model.debounce.500ms="search"
                placeholder="Buscar ticket..."
                class="px-3 py-2 rounded-lg border text-sm dark:bg-[#242933] border-gray-200 dark:border-[#2A2F3A] focus:ring focus:ring-blue-200">

            <select wire:model.live="filtroPrioridad"
                class="px-3 py-2 rounded-lg border text-sm bg-white dark:bg-[#242933] border-gray-200 dark:border-[#2A2F3A]">
                <option value="">Todas las prioridades</option>
                <option value="sin">Sin Prioridad</option>
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
            </select>

            <select wire:model.live="filtroEstado"
                class="px-3 py-2 rounded-lg border text-sm bg-white dark:bg-[#242933] border-gray-200 dark:border-[#2A2F3A]">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En progreso">En progreso</option>
                <option value="Cerrado">Cerrado</option>
            </select>

            <button wire:click="limpiarFiltros"
                type="button"
                class="px-3 py-2 rounded-lg text-sm bg-red-500 text-white hover:bg-red-600 transition">
                Limpiar filtros
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 dark:bg-[#242933]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase w-10"></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Prioridad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase min-w-[160px]">Tiempo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Responsable</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                    @forelse($ticketsTabla as $ticket)

                    @php
                    $tiempoInfo = $tiemposProgreso[$ticket->TicketID] ?? null;
                    $tiempoTarjeta = \App\Models\Tickets::formatearTiempoTarjeta($tiempoInfo);
                    $empleadoCorto = $ticket->empleado
                        ? \App\Models\Tickets::formatearNombreEmpleado($ticket->empleado->NombreEmpleado)
                        : '-';
                    $responsableCorto = $ticket->responsableTI
                        ? \App\Models\Tickets::formatearNombreEmpleado($ticket->responsableTI->NombreEmpleado)
                        : '-';
                    $estadoActual = $ticket->Estatus ?? '-';
                    $categoria = $ticket->tipoticket?->NombreTipo ?? '-';
                    $notificaciones = (int) ($notificacionesMap[$ticket->TicketID] ?? 0);
                    @endphp

                    <tr wire:key="ticket-tabla-{{ $ticket->TicketID }}"
                        class="transition cursor-pointer hover:bg-gray-50 dark:hover:bg-[#273244] border-l-[3px] dark:bg-[#1C1F26]"
                        style="border-left-color: {{ \App\Models\Tickets::colorPrioridad($ticket->Prioridad) }};"
                        data-ticket-id="{{ $ticket->TicketID }}"
                        data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                        data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion ?? '', ENT_QUOTES, 'UTF-8') }}"
                        data-ticket-prioridad="{{ $ticket->Prioridad ?? '' }}"
                        data-ticket-empleado="{{ $empleadoCorto }}"
                        data-ticket-responsable="{{ $responsableCorto !== '-' ? $responsableCorto : '' }}"
                        data-ticket-correo="{{ optional($ticket->empleado)->Correo ?? '' }}"
                        data-ticket-fecha="{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i:s') }}"
                        data-ticket-numero="{{ $ticket->Numero ?? '' }}"
                        data-ticket-anydesk="{{ $ticket->CodeAnyDesk ?? '' }}"
                        data-ticket-tiempo-estado="{{ $tiempoInfo['estado'] ?? '' }}"
                        data-ticket-estatus="{{ $estadoActual }}"
                        @click="abrirModalDesdeElemento($el)">

                        <td class="px-4 py-3">
                            @if($estadoActual === 'En progreso')
                                @include('tickets.partials.notificacion-badge', ['notificaciones' => $notificaciones])
                            @else
                                <div class="w-5 h-5"></div>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex items-center justify-center min-w-[2rem] h-6 px-1.5 rounded-md bg-gray-100 dark:bg-gray-800 text-[11px] font-bold text-gray-600 dark:text-gray-300">
                                #{{ $ticket->TicketID }}
                            </span>
                        </td>

                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ \Illuminate\Support\Str::limit($ticket->Descripcion, 80) }}</p>
                        </td>

                        <td class="px-4 py-3 text-gray-900 dark:text-gray-200" title="{{ optional($ticket->empleado)->NombreEmpleado }}">
                            {{ $empleadoCorto }}
                        </td>

                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $categoria }}</td>

                        <td class="px-4 py-3">
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full
                                @if($ticket->Prioridad == 'Baja') bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-900/30 dark:text-green-300
                                @elseif($ticket->Prioridad == 'Media') bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300
                                @elseif(empty($ticket->Prioridad)) bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300
                                @else bg-red-100 text-red-800 ring-1 ring-red-200 dark:bg-red-900/30 dark:text-red-300
                                @endif">
                                {{ $ticket->Prioridad ?? 'Sin Prioridad' }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full
                                @if($estadoActual == 'Pendiente') bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300
                                @elseif($estadoActual == 'En progreso') bg-blue-100 text-blue-800 ring-1 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300
                                @elseif($estadoActual == 'Cerrado') bg-green-100 text-green-800 ring-1 ring-green-200 dark:bg-green-900/30 dark:text-green-300
                                @else bg-gray-100 text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300
                                @endif">
                                {{ $estadoActual }}
                            </span>
                        </td>

                        <td class="px-4 py-3 min-w-[160px]">
                            @if($tiempoTarjeta)
                                @include('tickets.partials.tiempo-tarjeta', ['tiempo' => $tiempoTarjeta])
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $responsableCorto }}</td>

                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No hay tickets disponibles.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($ticketsTabla, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 dark:border-[#2A2F3A] dark:bg-[#1C1F26]">
            {{ $ticketsTabla->links() }}
        </div>
        @endif
    </div>
</div>
