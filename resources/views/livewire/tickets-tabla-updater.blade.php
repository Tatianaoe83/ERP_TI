<div wire:poll.5s.visible="actualizarDatos" wire:init="actualizarDatos">
    <div class="index-page__filters">
        <div class="form-group">
            <label for="ticket-buscar">Buscar</label>
            <input id="ticket-buscar" type="text"
                wire:model.debounce.500ms="search"
                placeholder="Buscar ticket..."
                class="form-control">
        </div>
        <div class="form-group">
            <label for="ticket-prioridad">Prioridad</label>
            <select id="ticket-prioridad" wire:model.live="filtroPrioridad" class="form-control">
                <option value="">Todas las prioridades</option>
                <option value="sin">Sin Prioridad</option>
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
            </select>
        </div>
        <div class="form-group">
            <label for="ticket-estado">Estado</label>
            <select id="ticket-estado" wire:model.live="filtroEstado" class="form-control">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En progreso">En progreso</option>
                <option value="Cerrado">Cerrado</option>
            </select>
        </div>
        <div class="form-group d-flex align-items-end">
            <button wire:click="limpiarFiltros" type="button" class="index-page__btn-primary">
                <i class="fa fa-times"></i> Limpiar filtros
            </button>
        </div>
    </div>
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
                            <span class="erp-chip
                                @if($ticket->Prioridad == 'Baja') erp-chip--ok
                                @elseif($ticket->Prioridad == 'Media') erp-chip--aviso
                                @elseif(empty($ticket->Prioridad)) erp-chip--neutro
                                @else erp-chip--alerta
                                @endif">
                                {{ $ticket->Prioridad ?? 'Sin prioridad' }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span class="erp-chip
                                @if($estadoActual == 'Pendiente') erp-chip--aviso
                                @elseif($estadoActual == 'En progreso') erp-chip--info
                                @elseif($estadoActual == 'Cerrado') erp-chip--ok
                                @else erp-chip--neutro
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
                        <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:bg-[#1C1F26]">
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
