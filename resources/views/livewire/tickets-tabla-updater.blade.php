{{-- CONTENEDOR RAIZ DE LIVEWIRE PARA EL POLLING --}}
<div wire:poll.5s="actualizarDatos" wire:init="actualizarDatos" wire:poll.keep-alive>

    <div class="rounded-lg overflow-hidden w-full max-w-full bg-gray-50 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A]">

        {{-- HEADER SUPERIOR --}}
        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-[#2A2F3A]">

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

        {{-- FILTROS --}}
        <div class="px-4 py-3 bg-gray-50 dark:bg-[#1C1F26] border-b border-gray-200 dark:border-[#2A2F3A] grid grid-cols-1 md:grid-cols-4 gap-3">

            {{-- BUSCADOR --}}
            <input type="text"
                wire:model.debounce.500ms="search"
                placeholder="Buscar ticket..."
                class="px-3 py-2 rounded-lg border text-sm
                        bg-gray-50 dark:bg-[#242933]
                        border-gray-300 dark:border-[#2A2F3A]
                        focus:ring focus:ring-blue-200">

            {{-- PRIORIDAD --}}
            <select wire:model="filtroPrioridad"
                    class="px-3 py-2 rounded-lg border text-sm
                        bg-gray-50 dark:bg-[#242933]
                        border-gray-300 dark:border-[#2A2F3A]">
                <option value="">Todas las prioridades</option>
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
            </select>

            {{-- ESTADO --}}
            <select wire:model="filtroEstado"
                    class="px-3 py-2 rounded-lg border text-sm
                        bg-gray-50 dark:bg-[#242933]
                        border-gray-300 dark:border-[#2A2F3A]">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En progreso">En progreso</option>
                <option value="Resuelto">Resuelto</option>
            </select>

            {{-- LIMPIAR --}}
            <button wire:click="limpiarFiltros"
                    type="button"
                    class="px-3 py-2 rounded-lg text-sm bg-red-500 text-white hover:bg-red-600 transition">
                Limpiar filtros
            </button>

        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">

                {{-- HEADER --}}
                <thead class="bg-gray-100 dark:bg-[#242933]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Empleado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Prioridad</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Responsable</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Tiempo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Acciones</th>
                    </tr>
                </thead>

                {{-- BODY --}}
                <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">

                    @forelse($ticketsTabla as $ticket)

                        @php
                            $nombreEmpleado = optional($ticket->empleado)->NombreEmpleado ?? '';
                            $correoEmpleado = optional($ticket->empleado)->Correo ?? optional($ticket->empleado)->correo ?? '';

                            $partes = preg_split('/\s+/', trim($nombreEmpleado));
                            if (count($partes) >= 3) array_splice($partes, 1, 1);
                            $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();

                            $nombreResponsable = null;
                            if (!empty($ticket->responsableTI)) {
                                $resp = $ticket->responsableTI->NombreEmpleado ?? '';
                                $p = preg_split('/\s+/', trim($resp));
                                if (count($p) >= 3) array_splice($p, 1, 1);
                                $nombreResponsable = \Illuminate\Support\Str::of(implode(' ', $p))->title();
                            }

                            $tiempoInfo = $tiemposProgreso[$ticket->TicketID] ?? null;
                            
                            // Atrapamos el estado correctamente
                            $estadoActual = $ticket->Estatus ?? $ticket->Estado ?? $ticket->estatus ?? '-';
                        @endphp

                        <tr wire:key="ticket-tabla-{{ $ticket->TicketID }}"
                            class="transition cursor-pointer hover:bg-gray-100 dark:hover:bg-[#273244]"

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
                            data-ticket-estado="{{ $estadoActual }}"

                            @click="abrirModalDesdeElemento($el)">

                            {{-- ID --}}
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">
                                #{{ $ticket->TicketID }}
                            </td>

                            {{-- DESCRIPCIÓN --}}
                            <td class="px-6 py-4 max-w-md truncate text-gray-600 dark:text-gray-300">
                                {{ \Illuminate\Support\Str::limit($ticket->Descripcion, 80) }}
                            </td>

                            {{-- EMPLEADO --}}
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-200">
                                {{ $nombreFormateado }}
                            </td>

                            {{-- PRIORIDAD --}}
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full
                                    @if($ticket->Prioridad == 'Baja')
                                        text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-500/20
                                    @elseif($ticket->Prioridad == 'Media')
                                        text-yellow-700 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-500/20
                                    @else
                                        text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-500/20
                                    @endif">
                                    {{ $ticket->Prioridad ?? '-' }}
                                </span>
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full
                                    @if($estadoActual == 'Pendiente')
                                        text-yellow-700 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-500/20
                                    @elseif($estadoActual == 'En progreso')
                                        text-blue-700 bg-blue-100 dark:text-blue-400 dark:bg-blue-500/20
                                    @elseif($estadoActual == 'Resuelto')
                                        text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-500/20
                                    @else
                                        text-gray-700 bg-gray-100 dark:text-gray-400 dark:bg-gray-500/20
                                    @endif">
                                    {{ $estadoActual }}
                                </span>
                            </td>

                            {{-- FECHA --}}
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}
                            </td>

                            {{-- RESPONSABLE --}}
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                {{ $nombreResponsable ?? '-' }}
                            </td>

                            {{-- TIEMPO --}}
                            <td class="px-6 py-4 text-xs">
                                @if($tiempoInfo)
                                    {{ number_format($tiempoInfo['transcurrido'], 1) }}h /
                                    {{ number_format($tiempoInfo['estimado'], 1) }}h
                                @else
                                    -
                                @endif
                            </td>

                            {{-- ACCIONES --}}
                            <td class="px-6 py-4">
                                <i class="fas fa-eye text-blue-500"></i>
                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="9"
                                class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay tickets disponibles.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- PAGINACIÓN --}}
        @if(method_exists($ticketsTabla, 'links'))
            <div class="px-4 py-3 border-t border-gray-200 dark:border-[#2A2F3A]">
                {{ $ticketsTabla->links() }}
            </div>
        @endif

    </div>

</div>