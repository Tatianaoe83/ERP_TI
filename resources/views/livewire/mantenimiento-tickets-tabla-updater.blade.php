<div wire:poll.5s.visible="actualizarDatos">
    <div class="rounded-lg overflow-hidden w-full max-w-full bg-gray-50 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A]">

        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-[#2A2F3A]">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Total: {{ count($tickets) }} solicitudes
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Responsable</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                    @forelse($tickets as $ticket)
                    <tr wire:key="mantenimiento-tabla-{{ $ticket['id'] }}"
                        class="transition cursor-pointer hover:bg-gray-100 dark:hover:bg-[#273244]"
                        data-ticket-id="{{ $ticket['id'] }}"
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
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">#{{ $ticket['id'] }}</td>
                        <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-300">{{ $ticket['asunto'] }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-200">{{ $ticket['solicitante'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $ticket['categoria'] ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full
                                @if($ticket['prioridad'] == 'Baja') text-green-700 bg-green-100
                                @elseif($ticket['prioridad'] == 'Media') text-yellow-700 bg-yellow-100
                                @elseif($ticket['prioridad'] == 'Alta') text-red-700 bg-red-100
                                @else text-red-800 bg-red-200
                                @endif">{{ $ticket['prioridad'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full
                                @if($ticket['estatus'] == 'Pendiente') text-yellow-700 bg-yellow-100
                                @elseif($ticket['estatus'] == 'En proceso') text-blue-700 bg-blue-100
                                @elseif($ticket['estatus'] == 'Atendido') text-green-700 bg-green-100
                                @elseif($ticket['estatus'] == 'Pausado') text-orange-700 bg-orange-100
                                @elseif($ticket['estatus'] == 'Cancelado') text-gray-700 bg-gray-200
                                @else text-gray-700 bg-gray-100
                                @endif">{{ $ticket['estatus'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ticket['responsable'] ? \App\Models\TicketMantenimiento::formatearNombreResponsable(\App\Models\TicketMantenimiento::normalizarResponsable($ticket['responsable']) ?? '') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No hay solicitudes disponibles.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
