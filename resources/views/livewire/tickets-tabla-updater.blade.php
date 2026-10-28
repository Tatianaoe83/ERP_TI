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
    <div class="index-page__card">
        <div class="index-page__table-wrap table-responsive">
            <table class="table index-table index-table--sin-accion w-full">
                <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Empleado</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Tiempo</th>
                        <th>Responsable</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ticketsTabla as $ticket)

                    @php
                    $tiempoInfo = $tiemposProgreso[$ticket->TicketID] ?? null;
                    $tiempoTarjeta = \App\Models\Tickets::formatearTiempoTarjeta($tiempoInfo);
                    $empleadoCorto = $ticket->empleado
                        ? $ticket->empleado->NombreEmpleado
                        : '-';
                    $responsableCorto = $ticket->responsableTI
                        ? \App\Models\Tickets::formatearNombreEmpleado($ticket->responsableTI->NombreEmpleado)
                        : '-';
                    $estadoActual = $ticket->Estatus ?? '-';
                    $categoria = $ticket->tipoticket?->NombreTipo ?? '-';
                    $notificaciones = (int) ($notificacionesMap[$ticket->TicketID] ?? 0);
                    @endphp

                    <tr wire:key="ticket-tabla-{{ $ticket->TicketID }}"
                        class="cursor-pointer"
                        style="box-shadow: inset 3px 0 0 {{ \App\Models\Tickets::colorPrioridad($ticket->Prioridad) }};"
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

                        <td>
                            @if($estadoActual === 'En progreso')
                                @include('tickets.partials.notificacion-badge', ['notificaciones' => $notificaciones])
                            @endif
                        </td>

                        <td>
                            <strong>#{{ $ticket->TicketID }}</strong>
                        </td>

                        <td>
                            {{ $ticket->Descripcion }}
                        </td>

                        <td title="{{ optional($ticket->empleado)->NombreEmpleado }}">
                            {{ $empleadoCorto }}
                        </td>

                        <td>{{ $categoria }}</td>

                        <td>
                            <span class="erp-chip
                                @if($ticket->Prioridad == 'Baja') erp-chip--ok
                                @elseif($ticket->Prioridad == 'Media') erp-chip--aviso
                                @elseif(empty($ticket->Prioridad)) erp-chip--neutro
                                @else erp-chip--alerta
                                @endif">
                                {{ $ticket->Prioridad ?? 'Sin prioridad' }}
                            </span>
                        </td>

                        <td>
                            <span class="erp-chip
                                @if($estadoActual == 'Pendiente') erp-chip--aviso
                                @elseif($estadoActual == 'En progreso') erp-chip--info
                                @elseif($estadoActual == 'Cerrado') erp-chip--ok
                                @else erp-chip--neutro
                                @endif">
                                {{ $estadoActual }}
                            </span>
                        </td>

                        <td>
                            @if($tiempoTarjeta)
                                @include('tickets.partials.tiempo-tarjeta', ['tiempo' => $tiempoTarjeta])
                            @else
                                —
                            @endif
                        </td>

                        <td>{{ $responsableCorto }}</td>

                        <td>
                            {{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="10">
                            No hay tickets disponibles.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $totalTickets = $ticketsTabla->total();
            $infoTickets = $totalTickets > 0
                ? 'Mostrando ' . $ticketsTabla->firstItem() . ' a ' . $ticketsTabla->lastItem() . ' de ' . $totalTickets
                : 'Mostrando 0 a 0 de 0';
        @endphp
        <div class="index-page__dt-footer">
            <div class="dataTables_info" role="status">{{ $infoTickets }}</div>
            {{ $ticketsTabla->links('livewire.paginacion-compacta') }}
        </div>
    </div>
</div>
