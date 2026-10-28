<div @if ($activo) wire:poll.5s.visible="actualizarDatos" @endif>
    @if (!$activo)
    <div class="p-10 text-center text-gray-400 dark:text-gray-500">
        <i class="fas fa-circle-notch fa-spin text-2xl mb-2 opacity-40"></i>
        <p class="text-sm">Cargando solicitudes...</p>
    </div>
    @else
    <div class="index-page__filters">
        <div class="form-group">
            <label for="mant-ticket-buscar">Buscar</label>
            <input id="mant-ticket-buscar" type="text"
                wire:model.debounce.500ms="search"
                placeholder="Buscar solicitud..."
                class="form-control">
        </div>
        <div class="form-group">
            <label for="mant-ticket-prioridad">Prioridad</label>
            <select id="mant-ticket-prioridad" wire:model="filtroPrioridad" class="form-control">
                <option value="">Todas las prioridades</option>
                <option value="sin">Sin prioridad</option>
                @foreach(\App\Models\TicketMantenimiento::PRIORIDADES as $prioridad)
                <option value="{{ $prioridad }}">{{ $prioridad }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="mant-ticket-estado">Estado</label>
            <select id="mant-ticket-estado" wire:model="filtroEstado" class="form-control">
                <option value="">Todos los estados</option>
                @foreach(\App\Models\TicketMantenimiento::ESTATUS as $estatus)
                <option value="{{ $estatus }}">{{ $estatus }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="mant-ticket-categoria">Categoría</label>
            <select id="mant-ticket-categoria" wire:model="filtroCategoria" class="form-control">
                <option value="">Todas las categorías</option>
                <option value="sin">Sin categoría</option>
                @foreach(\App\Models\TicketMantenimiento::CATEGORIAS as $categoria)
                <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
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
                        <th>ID</th>
                        <th>Asunto</th>
                        <th>Solicitante</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>SLA</th>
                        <th>Responsable</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr wire:key="mantenimiento-tabla-{{ $ticket['id'] }}"
                        class="cursor-pointer"
                        data-ticket-id="{{ $ticket['id'] }}"
                        {{-- Sin htmlspecialchars: Blade ya escapa con {{ }}; escapar dos veces deja &quot; literal en el dataset --}}
                        data-ticket-asunto="{{ $ticket['asunto'] ?? '' }}"
                        data-ticket-descripcion="{{ $ticket['descripcion'] ?? '' }}"
                        data-ticket-prioridad="{{ $ticket['prioridad'] }}"
                        data-ticket-estatus="{{ $ticket['estatus'] }}"
                        data-ticket-categoria="{{ $ticket['categoria'] ?? '' }}"
                        data-ticket-responsable="{{ $ticket['responsable_id'] ?? '' }}"
                        data-ticket-solicitante="{{ $ticket['solicitante'] ?? '' }}"
                        data-ticket-correo="{{ $ticket['correo'] ?? '' }}"
                        data-ticket-area="{{ $ticket['area'] ?? '' }}"
                        data-ticket-fecha="{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i:s') }}"
                        data-ticket-imagen="{{ is_array($ticket['imagen'] ?? null) ? json_encode($ticket['imagen'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ($ticket['imagen'] ?? '') }}"
                        @click="abrirModalDesdeElemento($el)">
                        <td><strong>#{{ $ticket['id'] }}</strong></td>
                        <td>{{ $ticket['asunto'] }}</td>
                        <td>{{ $ticket['solicitante'] ?: '-' }}</td>
                        <td>{{ $ticket['categoria'] ?? '-' }}</td>
                        <td>
                            @if(!empty($ticket['prioridad']))
                            <span class="erp-chip
                                @if($ticket['prioridad'] == 'Baja') erp-chip--ok
                                @elseif($ticket['prioridad'] == 'Media') erp-chip--aviso
                                @elseif($ticket['prioridad'] == 'Alta') erp-chip--alerta
                                @else erp-chip--neutro
                                @endif">{{ $ticket['prioridad'] }}</span>
                            @else
                            <span class="erp-chip erp-chip--neutro">Sin prioridad</span>
                            @endif
                        </td>
                        <td>
                            <span class="erp-chip
                                @if($ticket['estatus'] == 'Pendiente') erp-chip--aviso
                                @elseif($ticket['estatus'] == 'En proceso') erp-chip--info
                                @elseif($ticket['estatus'] == 'Atendido') erp-chip--ok
                                @elseif($ticket['estatus'] == 'Pausado') erp-chip--acento
                                @elseif($ticket['estatus'] == 'Cancelado') erp-chip--alerta
                                @else erp-chip--neutro
                                @endif">{{ $ticket['estatus'] }}</span>
                        </td>
                        <td>
                            @if(!empty($ticket['sla']))
                                @include('tickets-mantenimiento.partials.sla-tarjeta', ['sla' => $ticket['sla']])
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $ticket['responsable_nombre'] ?: '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">No hay solicitudes disponibles.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $porPaginaMant = \App\Http\Livewire\MantenimientoTicketsTablaUpdater::POR_PAGINA;
            $desdeMant = $total > 0 ? (($pagina - 1) * $porPaginaMant) + 1 : 0;
            $hastaMant = $total > 0 ? min($total, $pagina * $porPaginaMant) : 0;
            $infoMant = $total > 0
                ? 'Mostrando ' . $desdeMant . ' a ' . $hastaMant . ' de ' . $total
                : 'Mostrando 0 a 0 de 0';
        @endphp
        <div class="index-page__dt-footer">
            <div class="dataTables_info" role="status">{{ $infoMant }}</div>
            @include('tickets-mantenimiento.partials.paginador', [
                'pagina' => $pagina,
                'ultima' => $this->ultimaPagina(),
                'wireAnterior' => 'irAPagina(' . ($pagina - 1) . ')',
                'wireSiguiente' => 'irAPagina(' . ($pagina + 1) . ')',
            ])
        </div>
    </div>
    @endif
</div>
