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
    <div class="rounded-lg overflow-hidden w-full max-w-full bg-gray-50 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A]">

        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-[#2A2F3A]">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Total: {{ $total }} solicitudes
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 dark:bg-[#242933]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Asunto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Solicitante</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Prioridad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase min-w-[180px]">SLA</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Responsable</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                    @forelse($tickets as $ticket)
                    <tr wire:key="mantenimiento-tabla-{{ $ticket['id'] }}"
                        class="transition cursor-pointer hover:bg-gray-100 dark:hover:bg-[#273244]"
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
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">#{{ $ticket['id'] }}</td>
                        <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-300">{{ $ticket['asunto'] }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-200" title="{{ $ticket['solicitante'] ?? '' }}">{{ $ticket['solicitante_corto'] ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $ticket['categoria'] ?? '-' }}</td>
                        <td class="px-4 py-3">
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
                        <td class="px-4 py-3">
                            <span class="erp-chip
                                @if($ticket['estatus'] == 'Pendiente') erp-chip--aviso
                                @elseif($ticket['estatus'] == 'En proceso') erp-chip--info
                                @elseif($ticket['estatus'] == 'Atendido') erp-chip--ok
                                @elseif($ticket['estatus'] == 'Pausado') erp-chip--acento
                                @elseif($ticket['estatus'] == 'Cancelado') erp-chip--alerta
                                @else erp-chip--neutro
                                @endif">{{ $ticket['estatus'] }}</span>
                        </td>
                        <td class="px-4 py-3 min-w-[180px]">
                            @if(!empty($ticket['sla']))
                                @include('tickets-mantenimiento.partials.sla-tarjeta', ['sla' => $ticket['sla']])
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ticket['responsable_nombre'] ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">No hay solicitudes disponibles.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('tickets-mantenimiento.partials.paginador', [
            'pagina' => $pagina,
            'ultima' => $this->ultimaPagina(),
            'wireAnterior' => 'irAPagina(' . ($pagina - 1) . ')',
            'wireSiguiente' => 'irAPagina(' . ($pagina + 1) . ')',
        ])
    </div>
    @endif
</div>
