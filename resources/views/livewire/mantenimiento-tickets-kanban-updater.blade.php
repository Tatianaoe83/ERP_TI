<div @if ($activo) wire:poll.5s.visible="actualizarDatos" @endif>
    @php
        $coloresColumna = [
            'pendiente'  => 'bg-yellow-500',
            'en_proceso' => 'bg-blue-500',
            'pausado'    => 'bg-orange-500',
            'atendido'   => 'bg-green-500',
            'cancelado'  => 'bg-gray-500',
        ];
    @endphp
    @if (!$activo)
    <div class="p-10 text-center text-gray-400 dark:text-gray-500">
        <i class="fas fa-circle-notch fa-spin text-2xl mb-2 opacity-40"></i>
        <p class="text-xs">Cargando tablero...</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-start h-full">

        @foreach (\App\Models\TicketMantenimiento::COLUMNAS_VISTA as $key => $titulo)

        <div class="tk-col flex flex-col h-full max-h-[80vh]">

            <div class="tk-col__head">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ $coloresColumna[$key] ?? 'bg-gray-500' }}"></div>
                    <h3 class="tk-col__title">{{ $titulo }}</h3>
                </div>
                <span data-categoria-header="{{ $key }}" class="erp-chip erp-chip--neutro">
                    {{ $totales[$key] ?? count($ticketsStatus[$key]) }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto p-2.5 space-y-2.5 custom-scrollbar">

                @forelse ($ticketsStatus[$key] as $ticket)

                <div wire:key="mantenimiento-{{ $ticket['id'] }}"
                    class="tk-card group flex flex-col cursor-pointer"
                    style="border-left-color: {{ $ticket['color_prioridad'] ?? '#94a3b8' }};"
                    @include('tickets-mantenimiento.partials.ticket-card-data', ['ticket' => $ticket, 'columna' => $key])
                    @click="abrirModalDesdeElemento($el)">

                    @include('tickets-mantenimiento.partials.ticket-card-body', ['ticket' => $ticket])
                </div>

                @empty
                <div data-empty-placeholder class="p-8 text-center text-gray-400 dark:text-gray-500">
                    <i class="fas fa-inbox text-2xl mb-2 opacity-40"></i>
                    <p class="text-xs">Sin solicitudes</p>
                </div>
                @endforelse
            </div>

            @if (in_array($key, \App\Models\TicketMantenimiento::COLUMNAS_PAGINADAS, true))
                @include('tickets-mantenimiento.partials.paginador', [
                    'pagina' => $paginas[$key],
                    'ultima' => $this->ultimaPagina($key),
                    'wireAnterior' => "irAPagina('{$key}', " . ($paginas[$key] - 1) . ')',
                    'wireSiguiente' => "irAPagina('{$key}', " . ($paginas[$key] + 1) . ')',
                ])
            @endif
        </div>

        @endforeach
    </div>
    @endif
</div>
