<style>
    /* =========================================
       1. ESTILOS BASE (Modo Claro / Estructura)
       ========================================= */

    /* TinyMCE Base */
    .tox-tinymce {
        border-radius: 0.5rem !important;
        border: 1px solid #e5e7eb !important;
        /* Gris claro */
        background-color: #ffffff !important;
    }

    #editor-mensaje {
        min-height: 300px;
    }

    /* Scrollbars Base (Gris suave para modo claro) */
    .tickets-container ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .tickets-container ::-webkit-scrollbar-track {
        background: #f3f4f6;
        /* Gris muy claro */
        border-radius: 4px;
    }

    .tickets-container ::-webkit-scrollbar-thumb {
        background: #d1d5db;
        /* Gris medio */
        border-radius: 4px;
    }

    .tickets-container ::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    /* =========================================
       2. MODO OSCURO (Se activa con la clase .dark)
       ========================================= */

    /* Contenedor Principal TinyMCE */
    .dark .tox-tinymce {
        border: 1px solid #2A2F3A !important;
        background-color: #0F1115 !important;
    }

    /* Fondo del área de edición (alrededor del iframe) */
    .dark .tox .tox-edit-area__iframe {
        background-color: #0F1115 !important;
    }

    /* Cabecera y Barra de Herramientas */
    .dark .tox .tox-editor-header {
        background-color: #1C1F26 !important;
        border-bottom: 1px solid #2A2F3A !important;
    }

    .dark .tox .tox-toolbar,
    .dark .tox .tox-toolbar__primary {
        background-color: #1C1F26 !important;
        background: #1C1F26 !important;
    }

    /* Botones de la barra de herramientas */
    .dark .tox .tox-tbtn {
        color: #9CA3AF !important;
    }

    .dark .tox .tox-tbtn:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #F3F4F6 !important;
    }

    .dark .tox .tox-tbtn--enabled,
    .dark .tox .tox-tbtn--enabled:hover {
        background-color: rgba(59, 130, 246, 0.15) !important;
        color: #3B82F6 !important;
    }

    /* Menús desplegables y botones split */
    .dark .tox .tox-split-button {
        background-color: transparent !important;
    }

    .dark .tox .tox-menu {
        background-color: #1C1F26 !important;
        border: 1px solid #2A2F3A !important;
    }

    .dark .tox .tox-collection__item {
        color: #E5E7EB !important;
    }

    .dark .tox .tox-collection__item--active {
        background-color: rgba(59, 130, 246, 0.15) !important;
    }

    .dark .tox .tox-menu__label {
        color: #E5E7EB !important;
    }

    .dark .tox .tox-menu__label:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #F3F4F6 !important;
    }

    /* Scrollbars Dark Mode */
    .dark .tickets-container ::-webkit-scrollbar-track {
        background: #1C1F26;
    }

    .dark .tickets-container ::-webkit-scrollbar-thumb {
        background: #2A2F3A;
    }

    .dark .tickets-container ::-webkit-scrollbar-thumb:hover {
        background: #3A3F4A;
    }

    /* Selects nativos en modo oscuro */
    .dark select {
        background-color: #374151 !important;
        color: #ffffff !important;
        border-color: #4b5563 !important;
    }

    .dark select option {
        background-color: #374151 !important;
        color: #ffffff !important;
    }

    .dark select:focus {
        border-color: #3b82f6 !important;
        ring-color: #3b82f6 !important;
    }


    @media (max-width: 640px) {
        .tickets-container {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        .tickets-container * {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
    }
</style>
<div
    x-data="ticketsModal()"
    x-init="
        init(); 
        const vistaGuardada = localStorage.getItem('ticketsVista') || 'kanban';
        vista = vistaGuardada;
        // Si la vista inicial es tabla, asegurar que se preparen los datos después de que el DOM esté listo
        if (vistaGuardada === 'tabla') {
            setTimeout(() => {
                prepararDatosTabla();
            }, 800);
        }
    "
    class="tickets-container space-y-4 w-full max-w-full overflow-x-hidden min-h-screen p-6">

    <!-- Alert de Tickets Excedidos -->
    <div
        x-show="mostrarPopupExcedidos && ticketsExcedidos.length > 0"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-[-10px]"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-[-10px]"
        x-cloak
        class="fixed top-4 right-4 left-4 md:left-auto md:max-w-md z-50">
        <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 dark:border-red-700 rounded-lg p-4 shadow-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-300 mb-1">
                        <span x-text="ticketsExcedidos.length"></span>
                        <span x-text="ticketsExcedidos.length === 1 ? 'ticket excediendo tiempo' : 'tickets excediendo tiempo'"></span>
                    </h3>
                    <div class="mt-2 text-sm text-red-700 space-y-1">
                        <template x-for="(ticket, index) in ticketsExcedidos.slice(0, 3)" :key="ticket.id">
                            <div
                                @click="abrirTicketDesdePopup(ticket.id)"
                                class="cursor-pointer hover:text-red-900 hover:underline">
                                <span class="font-semibold" x-text="'Ticket #' + ticket.id"></span>
                                <span x-text="' - ' + ticket.descripcion"></span>

                            </div>
                        </template>
                        <template x-if="ticketsExcedidos.length > 3">
                            <p class="text-xs text-red-600 dark:text-red-400 italic">
                                y <span x-text="ticketsExcedidos.length - 3"></span> más...
                            </p>
                        </template>
                    </div>
                    <div class="mt-2 text-xs text-red-600 dark:text-red-400">
                        <i class="fas fa-sync-alt mr-1" :class="{'animate-spin': cargandoExcedidos}"></i>
                        <span x-text="cargandoExcedidos ? 'Verificando...' : 'Actualización automática cada 5 min'"></span>
                        <span x-show="!cargandoExcedidos && mostrarPopupExcedidos" class="ml-2">
                            • Se cerrará en <span x-text="tiempoRestantePopup" class="font-semibold"></span>s
                        </span>
                    </div>
                </div>
                <div class="ml-4 flex-shrink-0 flex flex-col gap-2">
                    <button
                        @click="verificarTicketsExcedidos()"
                        class="inline-flex text-red-400 dark:text-red-500 hover:text-red-600 dark:hover:text-red-400 focus:outline-none transition"
                        title="Actualizar ahora">
                        <i class="fas fa-sync-alt" :class="{'animate-spin': cargandoExcedidos}"></i>
                    </button>
                    <button
                        @click="cerrarPopupExcedidos()"
                        class="inline-flex text-red-400 dark:text-red-500 hover:text-red-600 dark:hover:text-red-400 focus:outline-none transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Selector de Vista -->
    <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2 mb-4 w-full">
        @can('tickets.ajustar-metricas')
        <button
            @click="mostrarModalMetricas = true; cargarMetricas()"
            class="px-3 sm:px-4 py-2 bg-[#3B82F6] hover:bg-[#2563EB] text-white font-medium rounded-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm sm:text-base whitespace-nowrap">
            <i class="fas fa-cog text-sm"></i>
            <span class="hidden sm:inline">Ajustar Métricas</span>
            <span class="sm:hidden">Métricas</span>
        </button>
        @endcan
        <div class="flex items-center gap-2 w-full sm:w-auto justify-center sm:justify-end">
            <span class="text-xs sm:text-sm text-[#9CA3AF] font-medium hidden sm:inline">Vista:</span>
            <div class="flex items-center gap-1 bg-[#fffff] border border-[#2A2F3A] rounded-lg p-1 w-full sm:w-auto justify-center">
                <button
                    @click="vista = 'kanban'; localStorage.setItem('ticketsVista', 'kanban')"
                    :class="vista === 'kanban' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 flex-1 sm:flex-initial justify-center">
                    <i class="fas fa-columns text-xs"></i>
                    <span class="hidden sm:inline">Kanban</span>
                </button>
                <button
                    @click="vista = 'lista'; localStorage.setItem('ticketsVista', 'lista'); prepararDatosLista()"
                    :class="vista === 'lista' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 flex-1 sm:flex-initial justify-center">
                    <i class="fas fa-list text-xs"></i>
                    <span class="hidden sm:inline">Lista</span>
                </button>
                <button
                    @click="vista = 'tabla'; localStorage.setItem('ticketsVista', 'tabla'); $nextTick(() => { prepararDatosTabla(); })"
                    :class="vista === 'tabla' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 flex-1 sm:flex-initial justify-center">
                    <i class="fas fa-table text-xs"></i>
                    <span class="hidden sm:inline">Tabla</span>
                </button>
            </div>
        </div>
    </div>

    <!-- KANBAN -->

<div
        class="kanban-root w-full h-full"
        x-show="vista === 'kanban'"
        x-transition>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start h-full">

            @foreach (['nuevos' => 'Nuevos', 'proceso' => 'En Progreso', 'resueltos' => 'Resueltos'] as $key => $titulo)

            <div class="flex flex-col h-full max-h-[80vh] rounded-xl overflow-hidden
                        bg-gray-200/70 dark:bg-[#161920]
                        border border-gray-300 dark:border-[#2A2F3A]">

                {{-- Header de Columna --}}
                <div class="px-4 py-3 flex justify-between items-center
                            bg-gray-300/50 dark:bg-[#1C1F26]
                            border-b border-gray-300 dark:border-[#2A2F3A]">
                    
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full
                            {{ $key === 'nuevos' ? 'bg-yellow-500' : ($key === 'proceso' ? 'bg-blue-500' : 'bg-green-500') }}">
                        </div>
                        <h3 class="font-bold text-sm text-gray-700 dark:text-gray-100 uppercase tracking-wide">
                            {{ $titulo }}
                        </h3>
                    </div>

                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ count($ticketsStatus[$key]) }}
                    </span>
                </div>

                {{-- Área de Scroll --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">

                    @forelse ($ticketsStatus[$key] as $ticket)

                    @php
                        $nombreResponsable = null;
                        $tiempoInfo = null;

                       $partes = preg_split('/\s+/', trim($ticket->empleado->NombreEmpleado));
                        if (count($partes) >= 3) array_splice($partes, 1, 1);
                        $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();

                        if ($key === 'proceso') {
                            if ($ticket->responsableTI) {
                                $p = preg_split('/\s+/', trim($ticket->responsableTI->NombreEmpleado));
                                if (count($p) >= 3) array_splice($p, 1, 1);
                                $nombreResponsable = \Illuminate\Support\Str::of(implode(' ', $p))->title();
                            }

                            // CÁLCULO DE TIEMPO CON FORMATO
                            if ($ticket->FechaInicioProgreso && $ticket->tipoticket?->TiempoEstimadoMinutos) {
                                $estimado = $ticket->tipoticket->TiempoEstimadoMinutos / 60;
                                $trans = $ticket->tiempo_respuesta ?? 0;
                                $porcentaje = $estimado > 0 ? ($trans / $estimado) * 100 : 0;

                                // --- Lógica de Formato (Horas y Minutos) ---
                                // Transcurrido
                                $hTrans = floor($trans);
                                $mTrans = round(($trans - $hTrans) * 60);
                                $textoTrans = ($hTrans > 0 ? $hTrans.'h ' : '') . $mTrans.'m';

                                // Estimado
                                $hEst = floor($estimado);
                                $mEst = round(($estimado - $hEst) * 60);
                                $textoEst = ($hEst > 0 ? $hEst.'h ' : '') . $mEst.'m';

                                $tiempoInfo = [
                                    'texto_transcurrido' => $textoTrans, // Ej: "1h 30m"
                                    'texto_estimado' => $textoEst,       // Ej: "2h 0m"
                                    'porcentaje' => round($porcentaje, 1),
                                    'estado' => $porcentaje >= 100 ? 'agotado' : ($porcentaje >= 80 ? 'por_vencer' : 'normal')
                                ];
                            }
                        }
                    @endphp

                    {{-- Tarjeta (Card) --}}
                    <div
                        class="group cursor-pointer p-4 rounded-lg shadow-sm transition-all duration-200
                               bg-gray-50 dark:bg-[#1C1F26]
                               border border-gray-200 dark:border-[#2A2F3A]
                               hover:shadow-md hover:translate-y-[-2px]
                               border-l-[4px]"
                        
                        style="border-left-color: {{ $ticket->Prioridad == 'Baja' ? '#22c55e' : ($ticket->Prioridad == 'Media' ? '#eab308' : '#ef4444') }};"

                        data-categoria="{{ $key }}"
                        data-ticket-id="{{ $ticket->TicketID }}"
                        data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                        data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion, ENT_QUOTES, 'UTF-8') }}"
                        data-ticket-prioridad="{{ $ticket->Prioridad }}"
                        data-ticket-empleado="{{ $nombreFormateado }}"
                        data-ticket-responsable="{{ $nombreResponsable ?? '' }}"
                        data-ticket-correo="{{ $ticket->empleado->Correo ?? '' }}"
                        data-ticket-fecha="{{ $ticket->created_at->format('d/m/Y H:i:s') }}"
                        data-ticket-numero="{{ $ticket->Numero ?? '' }}"
                        data-ticket-anydesk="{{ $ticket->CodeAnyDesk ?? '' }}"
                        data-ticket-tiempo-estado="{{ $tiempoInfo['estado'] ?? '' }}"

                        @click="abrirModalDesdeElemento($el)">

                        {{-- Header de la Tarjeta --}}
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400">
                                #{{ $ticket->TicketID }}
                            </span>
                            
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded
                                @if($ticket->Prioridad=='Baja') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                @elseif($ticket->Prioridad=='Media') bg-<span class="font-semibold truncate">{{ $nombreResponsable }}</span>yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                @endif">
                                {{ $ticket->Prioridad }}
                            </span>
                        </div>

                        {{-- Descripción --}}
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 line-clamp-3 mb-3 leading-relaxed">
                            {{ Str::limit($ticket->Descripcion, 100) }}
                        </p>

                        {{-- Footer de la Tarjeta --}}
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-col gap-2">
                            
                            {{-- Usuario y Fecha (Igual que antes) --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-user opacity-70"></i>
                                    <span class="truncate max-w-[80px]">{{ $nombreFormateado }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] text-gray-500 dark:text-gray-500">
                                    <i class="fas fa-clock opacity-70"></i>
                                    <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            {{-- Responsable --}}
                            @if($key === 'proceso' && $nombreResponsable)
                            <div class="mt-1 flex items-center gap-2 text-xs px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                <i class="fas fa-user-tie"></i>
                                <span class="font-semibold truncate">{{ $nombreResponsable }}</span>
                            </div>
                            @endif
                            
                            {{-- Barra de Tiempo con Formato H/M --}}
                            @if($tiempoInfo)
                            <div class="mt-2 w-full">
                                <div class="flex justify-between text-[10px] mb-1 text-gray-500 dark:text-gray-400">
                                    <span>Tiempo:</span>
                                    <span class="{{ $tiempoInfo['estado'] == 'agotado' ? 'text-red-500 font-bold' : '' }}">
                                        {{ $tiempoInfo['texto_transcurrido'] }} / {{ $tiempoInfo['texto_estimado'] }}
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


    <!-- Vista Lista -->
<div
        x-show="vista === 'lista'"
        x-transition
        class="space-y-4 w-full max-w-full overflow-x-hidden pb-6">

        @foreach (['nuevos' => 'Nuevos', 'proceso' => 'En Progreso', 'resueltos' => 'Resueltos'] as $key => $titulo)

        <div class="rounded-lg overflow-hidden shadow-sm
                    bg-gray-50 dark:bg-[#1C1F26]
                    border border-gray-300 dark:border-[#2A2F3A]">

            {{-- Header de Categoría --}}
            <div class="px-4 py-3 flex justify-between items-center
                        bg-gray-200 dark:bg-[#242933]
                        border-b border-gray-300 dark:border-[#2A2F3A]">
                <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                    {{ $titulo }}
                </h3>

                <span class="text-xs font-semibold px-2 py-1 rounded bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                    x-text="`Total: ${ticketsLista['{{ $key }}'] || 0}`">
                </span>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">

                @forelse ($ticketsStatus[$key] as $ticket)

@php
    $nombreResponsable = null;
    $tiempoInfo = null;

    // Formatear nombre del empleado
    $partes = preg_split('/\s+/', trim($ticket->empleado->NombreEmpleado));
    if (count($partes) >= 3) array_splice($partes, 1, 1);
    $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();

    // Obtener nombre del responsable TI si existe
    if ($ticket->responsableTI) {
        $p = preg_split('/\s+/', trim($ticket->responsableTI->NombreEmpleado));
        if (count($p) >= 3) array_splice($p, 1, 1);
        $nombreResponsable = \Illuminate\Support\Str::of(implode(' ', $p))->title();
    }

    // Calcular TIEMPO (Lógica principal corregida)
    if ($ticket->Estatus === 'En progreso' && $ticket->FechaInicioProgreso && $ticket->tipoticket?->TiempoEstimadoMinutos) {
        $estimado = $ticket->tipoticket->TiempoEstimadoMinutos / 60; // Horas
        $trans = $ticket->tiempo_respuesta ?? 0; // Horas transcurridas
        
        // Evitar división por cero
        $porcentaje = $estimado > 0 ? ($trans / $estimado) * 100 : 0;

        $tiempoInfo = [
            'transcurrido' => round($trans, 1),
            'estimado' => round($estimado, 1),
            'porcentaje' => round($porcentaje, 1),
            'estado' => $porcentaje >= 100 ? 'agotado' : ($porcentaje >= 80 ? 'por_vencer' : 'normal')
        ];
    }
@endphp

                <div
                    class="p-4 cursor-pointer transition-all duration-200
                           bg-gray-50 dark:bg-[#1C1F26]
                           hover:bg-gray-100 dark:hover:bg-[#242933]
                           border-l-4 border-l-transparent hover:border-l-blue-500"

                    data-categoria="{{ $key }}"
                    data-ticket-id="{{ $ticket->TicketID }}"
                    data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                    data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion, ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-prioridad="{{ $ticket->Prioridad }}"
                    data-ticket-empleado="{{ $nombreFormateado }}"
                    data-ticket-responsable="{{ $nombreResponsable ?? '' }}"
                    data-ticket-correo="{{ $ticket->empleado->Correo ?? '' }}"
                    data-ticket-fecha="{{ $ticket->created_at->format('d/m/Y H:i:s') }}"
                    data-ticket-numero="{{ $ticket->empleado->Numero ?? '' }}"
                    data-ticket-anydesk="{{ $ticket->empleado->CodeAnyDesk ?? '' }}"




                    data-ticket-tiempo-estado="{{ $tiempoInfo['estado'] ?? '' }}"

                    x-show="estaEnPaginaListaPorElemento('{{ $key }}', $el)"
                    @click="abrirModalDesdeElemento($el)">

                    <div class="flex flex-col sm:flex-row justify-between gap-4">

                        <div class="flex-1 min-w-0">

                            {{-- Título y Prioridad --}}
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="font-bold text-base text-gray-900 dark:text-white">
                                    Ticket #{{ $ticket->TicketID }}
                                </h4>

                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border
                                @if($ticket->Prioridad == 'Baja')
                                    bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800
                                @elseif($ticket->Prioridad == 'Media')
                                    bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800
                                @else
                                    bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800
                                @endif">
                                    {{ $ticket->Prioridad }}
                                </span>
                            </div>

                            {{-- Descripción --}}
                            <p class="text-sm mb-3 text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed">
                                {{ Str::limit($ticket->Descripcion, 160) }}
                            </p>

                            {{-- Info Footer --}}
                            <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-gray-600 dark:text-gray-400">

                                <span class="flex items-center gap-1.5" title="Solicitante">
                                    <i class="fas fa-user text-gray-500 dark:text-gray-500"></i>
                                    <span class="text-gray-800 dark:text-gray-300">{{ $nombreFormateado }}</span>
                                </span>

                                <span class="flex items-center gap-1.5" title="Fecha de creación">
                                    <i class="fas fa-calendar-alt text-gray-500 dark:text-gray-500"></i>
                                    <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                                </span>

                                @if($key === 'proceso' && $nombreResponsable)
                                <span class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                    <i class="fas fa-user-tie"></i>
                                    <span>{{ $nombreResponsable }}</span>
                                </span>
                                @endif

                            </div>
                        </div>

                        {{-- Icono de acción --}}
                        <div class="flex items-center justify-center sm:justify-end">
                            <div class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                @empty
                <div class="p-12 text-center flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-full mb-3">
                        <i class="fas fa-inbox text-3xl opacity-50"></i>
                    </div>
                    <p class="text-sm font-medium">No hay tickets en esta categoría.</p>
                </div>
                @endforelse

            </div>
        </div>

        @endforeach
    </div>

    <!-- Vista Tabla -->
    <div x-show="vista === 'tabla'"
         x-transition
         class="rounded-lg overflow-hidden w-full max-w-full bg-gray-50 dark:bg-[#1C1F26] border border-gray-200 dark:border-[#2A2F3A]">
        
        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-[#2A2F3A]">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="`Mostrando ${(paginaTabla - 1) * elementosPorPagina + 1} - ${Math.min(paginaTabla * elementosPorPagina, ticketsTabla.length)} de ${ticketsTabla.length} tickets`"></span>
            </div>
            <div class="text-sm flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <span>Elementos por página:</span>
                <select x-model="elementosPorPagina" 
                        @change="paginaTabla = 1" 
                        class="px-2 py-1 rounded text-sm border bg-gray-100 border-gray-300 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:bg-[#0F1115] dark:border-[#2A2F3A] dark:text-gray-200">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 dark:bg-[#242933]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer transition text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A] hover:bg-gray-200 dark:hover:bg-white/5"
                            @click="cambiarOrden('id')">
                            <div class="flex items-center gap-2">
                                <span>ID</span>
                                <i class="fas fa-sort text-xs"
                                   :class="ordenColumna === 'id' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600 dark:text-[#3B82F6]' : 'fa-sort-down text-blue-600 dark:text-[#3B82F6]') : 'text-gray-400 dark:text-gray-600'"></i>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer transition text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A] hover:bg-gray-200 dark:hover:bg-white/5"
                            @click="cambiarOrden('descripcion')">
                            <div class="flex items-center gap-2">
                                <span>Descripción</span>
                                <i class="fas fa-sort text-xs"
                                   :class="ordenColumna === 'descripcion' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600 dark:text-[#3B82F6]' : 'fa-sort-down text-blue-600 dark:text-[#3B82F6]') : 'text-gray-400 dark:text-gray-600'"></i>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer transition text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A] hover:bg-gray-200 dark:hover:bg-white/5"
                            @click="cambiarOrden('empleado')">
                            <div class="flex items-center gap-2">
                                <span>Empleado</span>
                                <i class="fas fa-sort text-xs"
                                   :class="ordenColumna === 'empleado' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600 dark:text-[#3B82F6]' : 'fa-sort-down text-blue-600 dark:text-[#3B82F6]') : 'text-gray-400 dark:text-gray-600'"></i>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer transition text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A] hover:bg-gray-200 dark:hover:bg-white/5"
                            @click="cambiarOrden('prioridad')">
                            <div class="flex items-center gap-2">
                                <span>Prioridad</span>
                                <i class="fas fa-sort text-xs"
                                   :class="ordenColumna === 'prioridad' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600 dark:text-[#3B82F6]' : 'fa-sort-down text-blue-600 dark:text-[#3B82F6]') : 'text-gray-400 dark:text-gray-600'"></i>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer transition text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A] hover:bg-gray-200 dark:hover:bg-white/5"
                            @click="cambiarOrden('estado')">
                            <div class="flex items-center gap-2">
                                <span>Estado</span>
                                <i class="fas fa-sort text-xs"
                                   :class="ordenColumna === 'estado' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600 dark:text-[#3B82F6]' : 'fa-sort-down text-blue-600 dark:text-[#3B82F6]') : 'text-gray-400 dark:text-gray-600'"></i>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer transition text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A] hover:bg-gray-200 dark:hover:bg-white/5"
                            @click="cambiarOrden('fecha')">
                            <div class="flex items-center gap-2">
                                <span>Fecha</span>
                                <i class="fas fa-sort text-xs"
                                   :class="ordenColumna === 'fecha' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600 dark:text-[#3B82F6]' : 'fa-sort-down text-blue-600 dark:text-[#3B82F6]') : 'text-gray-400 dark:text-gray-600'"></i>
                            </div>
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A]">Responsable</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A]">Tiempo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-[#2A2F3A]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#2A2F3A]">
                    <template x-for="(ticket, index) in obtenerTicketsTablaPagina()" :key="`ticket-${paginaTabla}-${index}-${ticket.id || index}`">
                        <tr class="transition cursor-pointer hover:bg-gray-100 dark:hover:bg-[#273244]"
                            :data-ticket-id="ticket.id"
                            :data-ticket-asunto="ticket.asunto"
                            :data-ticket-descripcion="ticket.descripcion"
                            :data-ticket-prioridad="ticket.prioridad"
                            :data-ticket-empleado="ticket.empleado"
                            :data-ticket-anydesk="ticket.anydesk"
                            :data-ticket-numero="ticket.numero"
                            :data-ticket-correo="ticket.correo"
                            :data-ticket-fecha="ticket.fecha"
                            @click="abrirModalDesdeElemento($el)">
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="'#' + ticket.id"></div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm max-w-md truncate text-gray-600 dark:text-gray-300" 
                                     x-text="(ticket.descripcion || '').substring(0, 80) + ((ticket.descripcion || '').length > 80 ? '...' : '')"></div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-200" x-text="ticket.empleado || ''"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="ticket.correo || ''"></div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                    :class="ticket.prioridad == 'Baja' ? 'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-500/20' : 
                                           (ticket.prioridad == 'Media' ? 'text-yellow-700 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-500/20' : 
                                           'text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-500/20')"
                                    x-text="ticket.prioridad || 'Media'"></span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                    :class="ticket.estatus == 'Pendiente' ? 'text-yellow-700 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-500/20' : 
                                           (ticket.estatus == 'En progreso' ? 'text-blue-700 bg-blue-100 dark:text-blue-400 dark:bg-blue-500/20' : 
                                           'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-500/20')"
                                    x-text="ticket.estatus || 'Pendiente'"></span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" 
                                x-text="(ticket.fecha || '').split(' ').slice(0, 2).join(' ')"></td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div x-show="ticket.responsable && ticket.responsable.trim() !== ''" 
                                     class="text-sm text-gray-700 dark:text-gray-300" x-text="ticket.responsable"></div>
                                <div x-show="!ticket.responsable || ticket.responsable.trim() === ''" 
                                     class="text-xs text-gray-400 dark:text-gray-500">-</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div x-show="ticket.tiempoTranscurrido && ticket.tiempoEstimado" class="flex flex-col gap-1">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold"
                                        :class="ticket.tiempoEstado === 'agotado' ? 'text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-500/20' : 
                                               (ticket.tiempoEstado === 'por_vencer' ? 'text-yellow-700 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-500/20' : 
                                               (ticket.tiempoEstado === 'normal' ? 'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-500/20' : 
                                               'text-gray-600 bg-gray-200 dark:text-gray-400 dark:bg-gray-700'))"
                                        x-text="formatearHorasDecimales(ticket.tiempoTranscurrido) + ' / ' + formatearHorasDecimales(ticket.tiempoEstimado)"></span>
                                </div>
                                <div x-show="!ticket.tiempoTranscurrido || !ticket.tiempoEstimado" class="text-xs text-gray-400 dark:text-gray-500">-</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <i class="fas fa-eye text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"></i>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!ticketsTabla || ticketsTabla.length === 0">
                        <td colspan="9" class="px-6 py-12 text-center text-sm bg-gray-50 dark:bg-[#1F2937] text-gray-500 dark:text-gray-400">
                            No hay tickets disponibles.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div x-show="obtenerTotalPaginasTabla() > 1" class="px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-[#2A2F3A]">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="`Página ${paginaTabla} de ${obtenerTotalPaginasTabla()}`"></span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="cambiarPaginaTabla(paginaTabla - 1)"
                    :disabled="paginaTabla === 1"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition border"
                    :class="paginaTabla === 1 
                        ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400 border-gray-200 dark:bg-[#1F2937] dark:text-gray-500 dark:border-[#2A2F3A]' 
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-[#1F2937] dark:text-gray-200 dark:border-[#2A2F3A] dark:hover:bg-[#242933]'">
                    <i class="fas fa-chevron-left text-xs"></i> Anterior
                </button>

                <template x-for="pagina in Array.from({length: Math.min(obtenerTotalPaginasTabla(), 10)}, (_, i) => {
                    const total = obtenerTotalPaginasTabla();
                    const current = paginaTabla;
                    let start = Math.max(1, current - 4);
                    let end = Math.min(total, start + 9);
                    if (end - start < 9) start = Math.max(1, end - 9);
                    return start + i;
                }).filter((p, i, arr) => p <= obtenerTotalPaginasTabla() && (i === 0 || p !== arr[i-1]))" :key="pagina">
                    <button
                        @click="cambiarPaginaTabla(pagina)"
                        class="px-3 py-1.5 text-sm font-medium border rounded-md transition"
                        :class="paginaTabla === pagina 
                            ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-600 dark:border-blue-600' 
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-[#1F2937] dark:text-gray-200 dark:border-[#2A2F3A] dark:hover:bg-[#242933]'">
                        <span x-text="pagina"></span>
                    </button>
                </template>

                <button
                    @click="cambiarPaginaTabla(paginaTabla + 1)"
                    :disabled="paginaTabla === obtenerTotalPaginasTabla()"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition border"
                    :class="paginaTabla === obtenerTotalPaginasTabla()
                        ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400 border-gray-200 dark:bg-[#1F2937] dark:text-gray-500 dark:border-[#2A2F3A]' 
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-[#1F2937] dark:text-gray-200 dark:border-[#2A2F3A] dark:hover:bg-[#242933]'">
                    Siguiente <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>




    <!-- modal -->
    <div
        x-show="mostrar && selected.id"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
        @click.self="cerrarModal"
        x-cloak>
        <div
            class="w-[95%] md:w-[90%] lg:w-[40%] xl:w-[86%] rounded-2xl overflow-hidden shadow-2xl transition-all duration-300
           bg-white dark:bg-[#1A1D24] 
           border border-transparent dark:border-gray-700"
            @click.stop>

            <div class="grid grid-cols-1 md:grid-cols-[35%_65%] h-[95vh] rounded-2xl overflow-hidden">

                <aside class="p-6 flex flex-col overflow-y-auto
                      border-r border-gray-200 dark:border-gray-700
                      bg-gray-50 dark:bg-[#0F1116] ">

                    <h2 class="text-sm font-semibold mb-4 uppercase text-gray-900 dark:text-gray-100">
                        Propiedades del Ticket
                    </h2>

                    <div class="space-y-5 text-sm flex-1">

                        <div class="rounded-lg p-4 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-xs font-semibold uppercase mb-2 text-gray-500 dark:text-gray-400">
                                Descripción de ticket
                            </h3>
                            <div class="font-medium whitespace-pre-wrap ticket-description text-gray-900 dark:text-gray-100"
                                x-text="selected.descripcion">
                            </div>
                        </div>

                        <div x-show="obtenerAdjuntos().length > 0"
                            class="rounded-lg p-4 mt-5 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">

                            <h3 class="text-xs font-semibold uppercase mb-3 text-gray-500 dark:text-gray-400">
                                Documentos Adjuntos
                            </h3>

                            <div class="space-y-2">
                                <template x-for="(adjunto, index) in obtenerAdjuntos()" :key="index">
                                    <div class="flex items-center justify-between p-2 rounded-lg transition 
                        border border-gray-200 dark:border-gray-700 
                        bg-gray-50 dark:bg-gray-700/50 
                        hover:bg-gray-100 dark:hover:bg-gray-700">

                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div class="flex-shrink-0 text-gray-400 dark:text-gray-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate text-gray-700 dark:text-gray-200" x-text="obtenerNombreArchivo(adjunto)"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="obtenerExtensionArchivo(adjunto)"></p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <a :href="obtenerUrlArchivo(adjunto)"
                                                target="_blank"
                                                class="p-1.5 rounded transition text-gray-500 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-600"
                                                title="Ver archivo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a :href="obtenerUrlArchivo(adjunto)"
                                                download
                                                class="p-1.5 rounded transition hover:bg-green-50 dark:hover:bg-green-900/30"
                                                style="color: #22C55E;"
                                                title="Descargar archivo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 mb-4 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
    <h3 class="text-xs font-semibold uppercase mb-3 text-gray-500 dark:text-gray-400 flex items-center gap-2">
        <i class="fas fa-address-card"></i> Información de Contacto
    </h3>
    
    <div class="space-y-3">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center">
                <i class="fas fa-user"></i>
            </div>
            <p class="font-medium text-sm text-gray-900 dark:text-gray-100 break-words" 
               x-text="selected.empleado || 'Sin nombre assigned'"></p>
        </div>

        <div class="flex items-start gap-3" x-show="selected.correo">
            <div class="mt-0.5 text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center">
                <i class="fas fa-envelope"></i>
            </div>
            <a :href="'mailto:' + selected.correo" 
               class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors break-all" 
               x-text="selected.correo"></a>
        </div>

        <div class="flex items-center gap-3" x-show="selected.numero">
            <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Tel/Ext:</span>
                <span x-text="selected.numero" class="font-mono"></span>
            </div>
        </div>

        <div class="flex items-center gap-3" x-show="selected.anydesk">
            <div class="text-red-500 dark:text-red-400 flex-shrink-0 w-4 text-center">
                <i class="fas fa-desktop"></i>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">AnyDesk:</span>
                <span class="font-mono bg-gray-100 dark:bg-gray-900 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 select-all" 
                      x-text="selected.anydesk"></span>
            </div>
        </div>

        <div x-show="!selected.numero && !selected.anydesk" class="pt-2 pl-7">
            <p class="text-xs italic text-gray-400 dark:text-gray-600">
                Sin datos de contacto adicionales.
            </p>
        </div>
    </div>
</div>

                        <div class="rounded-lg p-4 flex flex-col gap-3 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-xs font-semibold uppercase mb-2 text-gray-500 dark:text-gray-400">
                                Detalles del Ticket
                            </h3>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Prioridad</label>
                                <select
                                    x-model="ticketPrioridad"
                                    :disabled="selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="Baja">Baja</option>
                                    <option value="Media">Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Estado</label>
                                <select
                                    x-model="ticketEstatus"
                                    :disabled="selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En progreso">En progreso</option>
                                    <option value="Cerrado">Cerrado</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Clasificación <span class="text-red-500">*</span>
                                </label>
                                <select
                                    x-model="ticketClasificacion"
                                    :disabled="selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="">Seleccione</option>
                                    <option value="Problema">Problema</option>
                                    <option value="Servicio">Servicio</option>
                                </select>
                            </div>

                            <div x-show="selected.estatus === 'En progreso' && ticketEstatus !== 'Cerrado'"
                                class="p-2 rounded-md text-xs border flex items-center gap-2
                bg-blue-50 text-blue-700 border-blue-200
                dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800">
                                <i class="fas fa-info-circle"></i>
                                <span>El Responsable no se puede modificar cuando el ticket está en "En progreso"</span>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Responsable <span class="text-red-500">*</span>
                                </label>
                                <select
                                    x-model="ticketResponsableTI"
                                    :disabled="selected.estatus === 'Cerrado' || (selected.estatus === 'En progreso' && ticketEstatus !== 'Cerrado')"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="">Seleccione</option>
                                    @foreach($responsablesTI as $responsable)
                                    <option value="{{ $responsable->EmpleadoID }}">{{ $responsable->NombreEmpleado }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Categoría <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="tipo-select"
                                    x-model="ticketTipoID"
                                    :disabled="selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Grupo <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="subtipo-select"
                                    x-model="ticketSubtipoID"
                                    :disabled="!ticketTipoID || selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Subgrupo</label>
                                <select
                                    id="tertipo-select"
                                    x-model="ticketTertipoID"
                                    :disabled="!ticketSubtipoID || selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-white text-gray-900 
                   focus:border-blue-500 focus:ring-blue-500 
                   dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                            <button
                                @click="guardarCambiosTicket()"
                                :disabled="guardandoTicket"
                                class="mt-4 w-full py-2.5 px-4 rounded-lg font-medium shadow-sm transition-all
               flex items-center justify-center gap-2
               text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
               dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-offset-gray-900
               disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 dark:disabled:bg-gray-700">

                                <svg x-show="!guardandoTicket" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>

                                <svg x-show="guardandoTicket" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span x-text="guardandoTicket ? 'Guardando...' : 'Guardar Cambios'"></span>
                            </button>
                        </div>
                    </div>
                </aside>

                <main class="flex flex-col overflow-hidden  dark:bg-[#1A1D24]">
                    <div class="flex justify-between items-start p-6 border-b border-gray-200 dark:border-gray-700 dark:bg-[#1A1D24]">
                        <div>
                            <h1 class="text-2xl font-semibold mb-1 text-gray-900 dark:text-gray-100"
                                x-text="selected.asunto"></h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <span x-text="selected.fecha"></span>
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="cerrarModal"
                                class="transition p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700">
                                <span class="text-xl">×</span>
                            </button>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-[#0F1116]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 text-sm">

                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Correos Enviados:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_enviados || 0"></span>
                                </span>

                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Respuestas:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_recibidos || 0"></span>
                                </span>
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Total: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="estadisticas?.total_correos || 0"></span> correos
                            </div>
                        </div>
                    </div>



                    <!-- Área de Conversaciones -->


                    <!-- Encabezado de Composición -->
                    <div class="p-4 transition-opacity duration-200"
                        :class="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado' ? 'opacity-50 pointer-events-none' : ''">

                        <div class="space-y-4">

                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">Para:</label>
                                <input
                                    type="email"
                                    :value="selected.correo || ''"
                                    readonly
                                    :disabled="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado'"
                                    class="flex-1 px-3 py-2 rounded-md text-sm border shadow-sm transition-colors
                        border-gray-300 text-gray-900
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                       dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:ring-blue-500
                       disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                       dark:disabled:bg-gray-900 dark:disabled:border-gray-800 dark:disabled:text-gray-500">
                            </div>

                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">
                                    Asunto: <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    x-model="asuntoCorreo"
                                    required
                                    readonly
                                    :disabled="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado'"
                                    class="flex-1 px-3 py-2 rounded-md text-sm border shadow-sm cursor-not-allowed
                       bg-gray-100 border-gray-300 text-gray-500
                       focus:outline-none
                       dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400">
                            </div>

                        </div>
                    </div>

                    <div x-show="mostrarBcc" x-transition class="flex items-center gap-2 mt-3">
                        <label class="text-sm font-medium w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">
                            Copia Oculta:
                        </label>
                        <input
                            type="email"
                            x-model="correoBcc"
                            placeholder="correo@ejemplo.com"
                            class="flex-1 px-3 py-2 rounded-md text-sm border shadow-sm transition-colors
                border-gray-300 text-gray-900
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
               dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:ring-blue-500">
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50 dark:bg-[#0F1116]" id="chat-container"> <!-- Mensajes dinámicos del chat -->
                        <template x-for="mensaje in mensajes" :key="mensaje.id">
                            <div class="flex gap-4 mb-4" :class="mensaje.remitente === 'soporte' ? 'justify-end' : 'justify-start'">

                                <div class="flex-shrink-0" :class="mensaje.remitente === 'soporte' ? 'order-2' : 'order-1'">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm"
                                        :class="mensaje.remitente === 'soporte' ? 'bg-blue-600' : 'bg-green-500'"
                                        x-text="obtenerIniciales(mensaje.nombre_remitente)">
                                    </div>
                                </div>

                                <div class="flex-1 max-w-[85%]" :class="mensaje.remitente === 'soporte' ? 'order-1 items-end flex flex-col' : 'order-2 items-start flex flex-col'">

                                    <div class="flex flex-wrap items-center gap-2 mb-2" :class="mensaje.remitente === 'soporte' ? 'justify-end' : 'justify-start'">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="mensaje.nombre_remitente"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="mensaje.created_at"></span>

                                        <span x-show="mensaje.es_correo && mensaje.remitente === 'soporte'"
                                            class="text-xs px-2 py-0.5 rounded flex items-center gap-1 border border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                            📤 Enviado
                                        </span>

                                        <span x-show="mensaje.es_correo && mensaje.remitente === 'usuario'"
                                            class="text-xs px-2 py-0.5 rounded flex items-center gap-1 border border-green-200 bg-green-50 text-green-600 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                                            📥 Recibido
                                        </span>

                                        <span x-show="!mensaje.es_correo"
                                            class="text-xs px-2 py-0.5 rounded flex items-center gap-1 border border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            💬 Nota Interna
                                        </span>

                                        <span x-show="mensaje.thread_id"
                                            class="text-xs px-2 py-0.5 rounded flex items-center gap-1 border border-purple-200 bg-purple-50 text-purple-600 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-300">
                                            🔗 En Hilo
                                        </span>

                                        <span x-show="!mensaje.leido"
                                            class="text-xs px-2 py-0.5 rounded flex items-center gap-1 border border-orange-200 bg-orange-50 text-orange-600 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300">
                                            ⚠ No Leído
                                        </span>
                                    </div>

                                    <div class="rounded-lg p-4 border shadow-sm w-full text-left"
                                        :class="mensaje.remitente === 'soporte' 
                    ? 'bg-blue-50 border-blue-100 dark:bg-blue-900/10 dark:border-blue-800' 
                    : 'border-gray-200 dark:bg-[#1C1F26] dark:border-[#2A2F3A]'">

                                        <div x-show="mensaje.es_correo" class="text-xs mb-3 pb-2 border-b border-gray-200 dark:border-gray-700/50 space-y-1">
                                            <div x-show="mensaje.correo_remitente" class="text-gray-600 dark:text-gray-400">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">Desde:</span> <span x-text="mensaje.correo_remitente"></span>
                                            </div>
                                            <div x-show="mensaje.message_id" class="text-gray-400 dark:text-gray-500 font-mono text-[10px] truncate">
                                                ID: <span x-text="mensaje.message_id"></span>
                                            </div>
                                            <div x-show="mensaje.thread_id" class="text-gray-400 dark:text-gray-500 font-mono text-[10px] truncate">
                                                Thread: <span x-text="mensaje.thread_id"></span>
                                            </div>
                                        </div>

                                        <div class="prose prose-sm max-w-none text-gray-800 dark:text-gray-200"
                                            x-html="formatearMensaje(mensaje.mensaje)">
                                        </div>

                                        <div x-show="mensaje.adjuntos && mensaje.adjuntos.length > 0"
                                            class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <div class="text-xs mb-2 font-semibold text-gray-500 dark:text-gray-400">Adjuntos:</div>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="adjunto in mensaje.adjuntos" :key="adjunto.name">
                                                    <span class="text-xs px-2 py-1 rounded flex items-center gap-1 transition-colors
                                         bg-gray-100 border border-gray-200 text-gray-600 hover:bg-gray-200
                                         dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                                        📎 <span x-text="adjunto.name"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </template>



                        <!-- Mensaje cuando no hay conversaciones -->
                        <div x-show="mensajes.length === 0" class="text-center py-8">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                No hay mensajes aún. Envía una respuesta para iniciar la conversación.
                            </div>
                        </div>

                        <!-- Área para escribir nueva respuesta - Estilo Cliente de Correo -->
                        <div class="rounded-lg  border border-gray-200 dark:bg-[#1F2937] dark:border-[#2A2F3A]"> <!-- Mensaje informativo cuando está en Pendiente -->
                            <div x-show="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado'"
                                class="p-4 border-b transition-colors
            bg-yellow-50 border-yellow-200
            dark:bg-yellow-500/15 dark:border-yellow-500/30">

                                <p class="text-sm flex items-center gap-2
              text-yellow-800
              dark:text-yellow-400">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span>El ticket está en estado "Pendiente". Para enviar mensajes, cambia el estado a "En progreso" en los detalles del ticket.</span>
                                </p>
                            </div>

                            <div class="p-3 transition-opacity duration-200"
                                :class="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado' ? 'opacity-50 pointer-events-none' : ''">

                                <div x-show="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado'"
                                    class="mb-2 p-2 rounded-lg border transition-colors
                bg-yellow-50 border-yellow-200 text-yellow-800
                dark:bg-yellow-900/20 dark:border-yellow-700/50 dark:text-yellow-200">
                                    <p class="text-xs flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <span>Este ticket está cerrado. No se pueden agregar nuevos mensajes o adjuntos.</span>
                                    </p>
                                </div>


                                <div class="p-4 transition-opacity duration-200 dark:bg-[#0F1115]"
                                    :class="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado' ? 'opacity-50' : ''">

                                    <textarea
                                        id="editor-mensaje"
                                        x-model="nuevoMensaje"
                                        :disabled="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado'"
                                        class="w-full rounded-md border shadow-sm p-3 min-h-[300px] transition-colors resize-y
                   bg-white border-gray-300 text-gray-900 placeholder-gray-400
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   dark:bg-[#0F1115] dark:border-[#2A2F3A] dark:text-gray-100 dark:placeholder-gray-500
                   disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-[#1C1F26] dark:disabled:text-gray-500"
                                        placeholder="Escribe tu mensaje aquí..."></textarea>

                                    <div
                                        id="drag-drop-area"
                                        :class="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente')) ? 'cursor-not-allowed' : 'cursor-pointer'"
                                        class="border-2 border-dashed rounded-lg p-4 transition-all duration-200 mb-2
               bg-blue-50 border-blue-200
               dark:bg-blue-900/10 dark:border-blue-800"
                                        @dragover.prevent="handleDragOver($event)"
                                        @dragleave.prevent="handleDragLeave($event)"
                                        @drop.prevent="handleDrop($event)"
                                        @click="!((selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente'))) && document.getElementById('adjuntos').click()"
                                        :title="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado') ? 'El ticket está cerrado' : ((selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') ? 'El ticket está en Pendiente. Cambia a En progreso para enviar mensajes' : 'Arrastra archivos aquí o haz clic para seleccionar')">

                                        <div class="flex flex-col items-center justify-center gap-2 text-center">
                                            <svg class="w-8 h-8 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>

                                            <div>
                                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Arrastra archivos aquí o </span>
                                                <label
                                                    for="adjuntos"
                                                    class="text-sm font-medium underline cursor-pointer text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                                    @click.stop
                                                    :class="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente')) ? 'cursor-not-allowed' : ''">
                                                    haz clic para seleccionar
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, TXT, JPG, PNG, GIF (máx. 10MB por archivo)</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end mb-2">
                                        <span x-show="archivosAdjuntos.length > 0" class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            <span x-text="archivosAdjuntos.length"></span> archivo<span x-show="archivosAdjuntos.length !== 1">s</span> seleccionado<span x-show="archivosAdjuntos.length !== 1">s</span>
                                        </span>
                                    </div>
                                </div>
                                <div x-show="archivosAdjuntos.length > 0" class="mt-3 space-y-2">
                                    <template x-for="(archivo, index) in archivosAdjuntos" :key="index">
                                        <div class="flex items-center gap-3 p-2 rounded-lg border transition-colors
                    bg-white border-gray-200 hover:bg-gray-50
                    dark:bg-[#1F2937] dark:border-[#2A2F3A] dark:hover:bg-[#242933]">

                                            <div class="flex-shrink-0">
                                                <svg x-show="archivo.type && archivo.type.startsWith('image/')"
                                                    class="w-6 h-6 text-green-500 dark:text-green-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <svg x-show="archivo.type && archivo.type === 'application/pdf'"
                                                    class="w-6 h-6 text-red-500 dark:text-red-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <svg x-show="archivo.type && (archivo.type.includes('word') || archivo.type.includes('document') || archivo.name.endsWith('.doc') || archivo.name.endsWith('.docx'))"
                                                    class="w-6 h-6 text-blue-500 dark:text-blue-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <svg x-show="!archivo.type || (!archivo.type.startsWith('image/') && archivo.type !== 'application/pdf' && !archivo.type.includes('word') && !archivo.type.includes('document') && !archivo.name.endsWith('.doc') && !archivo.name.endsWith('.docx'))"
                                                    class="w-6 h-6 text-gray-400 dark:text-gray-500"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate text-gray-700 dark:text-gray-200" x-text="archivo.name"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatearTamañoArchivo(archivo.size)"></p>
                                            </div>

                                            <button
                                                type="button"
                                                @click="eliminarArchivo(index)"
                                                :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente')"
                                                class="flex-shrink-0 p-1.5 rounded transition 
                       text-red-500 hover:bg-red-50 
                       dark:text-red-400 dark:hover:bg-red-900/20
                       disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                                                title="Eliminar archivo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <input
                                    type="file"
                                    id="adjuntos"
                                    name="adjuntos[]"
                                    multiple
                                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif"
                                    class="hidden"
                                    :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente')"
                                    @change="manejarArchivosSeleccionados($event)">

                                <div class="flex justify-end items-center gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700/50">

                                    <button
                                        type="button"
                                        @click="limpiarEditor()"
                                        :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente')"
                                        class="px-4 py-2 rounded-lg text-sm transition font-medium
                       text-gray-500 hover:text-gray-700 hover:bg-gray-100
                       dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800
                       disabled:opacity-50 disabled:cursor-not-allowed">
                                        Descartar
                                    </button>

                                    <button
                                        @click="enviarRespuesta()"
                                        :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') || !tieneContenido() || !asuntoCorreo || asuntoCorreo.trim().length === 0"
                                        class="font-medium py-2 px-6 rounded-lg transition text-sm flex items-center gap-2 text-white
                       bg-blue-600 hover:bg-blue-700
                       dark:bg-blue-600 dark:hover:bg-blue-500
                       disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed
                       dark:disabled:bg-[#1C1F26] dark:disabled:text-gray-600"
                                        :title="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado') ? 'El ticket está cerrado' : ((selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') ? 'El ticket está en Pendiente. Cambia a En progreso para enviar mensajes' : 'El botón se activará cuando haya contenido en el mensaje y un asunto')">

                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        Enviar
                                    </button>
                                </div>
                            </div>
                            <div x-show="mostrarProcesarRespuesta"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                class="rounded-lg p-4 mt-4 border transition-colors
            bg-green-50 border-green-200
            dark:bg-green-900/10 dark:border-green-500/30">

                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-sm font-medium text-green-700 dark:text-green-400">
                                        📧 Procesar Respuesta de Correo:
                                    </span>
                                    <span class="text-xs text-green-600 dark:text-green-500">
                                        (Procesamiento manual cuando Webklex no funciona)
                                    </span>
                                </div>

                                <div class="rounded-lg p-3 mb-3 border
                bg-green-100 border-green-200
                dark:bg-green-900/20 dark:border-green-500/30">
                                    <div class="flex items-start gap-2">
                                        <div class="mt-0.5 text-green-600 dark:text-green-400">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="text-sm text-green-800 dark:text-green-200">
                                            <p class="font-medium mb-1">¿Cómo procesar respuestas de correo?</p>
                                            <ol class="text-xs space-y-1 list-decimal list-inside text-green-700 dark:text-green-300/70">
                                                <li><strong>Automático:</strong> El procesamiento automático se ejecuta cada 5 minutos mediante un job programado</li>
                                                <li><strong>Manual:</strong> Si el automático falla, usa esta área</li>
                                                <li>El usuario recibe tu correo con instrucciones</li>
                                                <li>El usuario responde por correo</li>
                                                <li>Copia y pega su respuesta aquí</li>
                                                <li>La respuesta aparecerá en el chat del ticket</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-400">Nombre del usuario:</label>
                                        <input
                                            x-model="respuestaManual.nombre"
                                            type="text"
                                            class="w-full p-2 rounded text-sm border shadow-sm transition-colors
                       bg-white border-gray-300 text-gray-900 placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                       dark:bg-[#1F2937] dark:border-[#2A2F3A] dark:text-gray-100 dark:placeholder-gray-500"
                                            placeholder="Nombre del usuario">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-400">Correo del usuario:</label>
                                        <input
                                            x-model="respuestaManual.correo"
                                            type="email"
                                            class="w-full p-2 rounded text-sm border shadow-sm transition-colors
                       bg-white border-gray-300 text-gray-900 placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                       dark:bg-[#1F2937] dark:border-[#2A2F3A] dark:text-gray-100 dark:placeholder-gray-500"
                                            placeholder="correo@usuario.com">
                                    </div>
                                </div>

                                <textarea
                                    x-model="respuestaManual.mensaje"
                                    class="w-full h-20 p-3 rounded-lg resize-none text-sm border shadow-sm transition-colors
               bg-white border-gray-300 text-gray-900 placeholder-gray-400
               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
               dark:bg-[#1F2937] dark:border-[#2A2F3A] dark:text-gray-100 dark:placeholder-gray-500"
                                    placeholder="Copia y pega aquí la respuesta que recibiste por correo..."></textarea>

                                <div class="flex justify-end mt-3">
                                    <button
                                        @click="agregarRespuestaManual()"
                                        :disabled="!respuestaManual.mensaje.trim()"
                                        class="font-medium py-2 px-4 rounded-lg transition text-sm text-white
                   bg-green-600 hover:bg-green-700
                   dark:bg-green-600 dark:hover:bg-green-500
                   disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed
                   dark:disabled:bg-[#1C1F26] dark:disabled:text-gray-600">
                                        Procesar Respuesta de Correo
                                    </button>
                                </div>
                            </div>

                </main>
            </div>
        </div>
    </div>

    <!-- Modal de Métricas -->
    <div
        x-show="mostrarModalMetricas"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm bg-black/50"
        @click.self="mostrarModalMetricas = false">

        <div
            class="rounded-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col border shadow-xl bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
            @click.stop>

            <div class="px-6 py-4 flex justify-between items-center bg-gradient-to-r from-purple-600 to-purple-700">
                <h2 class="text-white text-xl font-semibold flex items-center gap-2">
                    <i class="fas fa-chart-line"></i>
                    Ajustar Métricas
                </h2>
                <button
                    @click="mostrarModalMetricas = false"
                    class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-800">

                <div class="mb-4 text-sm rounded-lg p-3 border bg-blue-100/50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Configure el tiempo estimado en minutos para cada tipo de ticket.
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <table class="min-w-full border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                    Tipo de Ticket
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                    Tiempo Estimado
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                    Equivalente
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                            <template x-if="cargandoMetricas">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center justify-center gap-2">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            <span>Cargando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!cargandoMetricas && metricasTipos && metricasTipos.length > 0">
                                <template x-for="(tipo, index) in metricasTipos" :key="tipo.TipoID">
                                    <tr class="transition hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                                x-text="tipo.NombreTipo"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                :value="tipo.TiempoEstimadoMinutos || ''"
                                                @input="tipo.TiempoEstimadoMinutos = $event.target.value ? parseInt($event.target.value) : null; tipo.cambiado = true"
                                                placeholder="0"
                                                class="w-32 px-3 py-2 rounded-md text-sm border transition
                                                   bg-[#ffffff] dark:bg-gray-900
                                                   border-gray-300 dark:border-gray-600
                                                   text-gray-900 dark:text-white
                                                   placeholder-gray-400 dark:placeholder-gray-500
                                                   focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent">
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm text-gray-600 dark:text-gray-400"
                                                x-text="formatearTiempo(tipo.TiempoEstimadoMinutos)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </template>

                            <template x-if="!cargandoMetricas && (!metricasTipos || metricasTipos.length === 0)">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <i class="fas fa-info-circle text-2xl"></i>
                                            <span>No hay datos</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-6 py-4 flex justify-between items-center border-t bg-gray-100 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span x-text="`${metricasTipos.filter(t => t.cambiado).length} cambios pendientes`"></span>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="mostrarModalMetricas = false"
                        class="px-4 py-2 font-medium rounded-lg transition border
                           bg-transparent
                           text-gray-700 dark:text-gray-300
                           border-gray-300 dark:border-gray-600
                           hover:bg-gray-200 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button
                        @click="guardarMetricas()"
                        :disabled="guardandoMetricas || metricasTipos.filter(t => t.cambiado).length === 0"
                        class="px-4 py-2 font-medium rounded-lg transition flex items-center gap-2 shadow-sm
                           text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800
                           disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save" :class="{'fa-spin': guardandoMetricas}"></i>
                        <span x-text="guardandoMetricas ? 'Guardando...' : 'Guardar Cambios'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" defer></script>
<script>
    function ticketsModal() {
        return {
            vista: 'kanban',
            mostrar: false,
            selected: {},
            mensajes: [],
            nuevoMensaje: '',
            cargando: false,
            sincronizando: false,
            buscandoCorreos: false,
            guardandoCorreos: false,
            estadisticas: null,
            respuestaManual: {
                nombre: '',
                correo: '',
                mensaje: ''
            },
            mostrarProcesarRespuesta: false,
            // Variables para el editor de correo
            mostrarCc: false,
            mostrarBcc: false,
            prioridadCorreo: 'normal',
            asuntoCorreo: '',
            correoCc: '',
            correoBcc: '',
            tinyMCEInstance: null, // Instancia del editor TinyMCE
            archivosAdjuntos: [], // Array para almacenar los archivos seleccionados
            // URL base para archivos de storage
            // Usar la ruta de Laravel que sirve archivos sin enlace simbólico (útil para HostGator)
            storageBaseUrl: '{{ url("/storage") }}',
            // Variables para detalles del ticket
            ticketPrioridad: '',
            ticketEstatus: '',
            ticketClasificacion: '',
            ticketResponsableTI: '',
            ticketTipoID: '',
            ticketSubtipoID: '',
            ticketTertipoID: '',
            guardandoTicket: false,
            // Variables de paginación
            paginaLista: {
                'nuevos': 1,
                'proceso': 1,
                'resueltos': 1
            },
            paginaTabla: 1,
            elementosPorPagina: 5,
            // Variables de ordenamiento
            ordenColumna: 'fecha',
            ordenDireccion: 'desc',
            ticketsLista: {
                'nuevos': {{ count($ticketsStatus['nuevos']) }},
                'proceso': {{ count($ticketsStatus['proceso']) }},
                'resueltos': {{ count($ticketsStatus['resueltos']) }}
            },
            ticketsTabla: [],
            // Variables para métricas
            mostrarModalMetricas: false,
            metricasTipos: [],
            cargandoMetricas: false,
            guardandoMetricas: false,
            // Variables para tickets excedidos
            mostrarPopupExcedidos: false,
            ticketsExcedidos: [],
            timerPopupExcedidos: null,
            intervaloContadorPopup: null,
            tiempoRestantePopup: 10,
            cargandoExcedidos: false,
            intervaloVerificacionExcedidos: null,
            // Variables para verificación automática de mensajes nuevos
            intervaloVerificacionMensajes: null,
            ultimoMensajeId: 0,

            init() {
                // Los datos de ticketsLista ya están inicializados desde el servidor
                // Preparar datos para tabla
                this.prepararDatosTabla();
             
                this.mostrar = false;
                this.selected = {};
                this.mensajes = [];
                this.nuevoMensaje = '';
                this.asuntoCorreo = '';
                
                // Watcher para ejecutar prepararDatosTabla cuando se cambie a vista tabla
                this.$watch('vista', (newValue) => {
                    if (newValue === 'tabla') {
                        // Esperar un momento para que el DOM se actualice y los elementos estén disponibles
                        setTimeout(() => {
                            this.prepararDatosTabla();
                        }, 200);
                        // Iniciar actualización en tiempo real también en vista tabla
                        this.iniciarActualizacionTiempoReal();
                    } else if (newValue === 'kanban' || newValue === 'lista') {
                        // Iniciar actualización en tiempo real cuando se cambia a kanban o lista
                        this.iniciarActualizacionTiempoReal();
                    }
                });
                
                // Iniciar actualización en tiempo real de indicadores de tiempo para todas las vistas
                this.iniciarActualizacionTiempoReal();
                
                // Watcher para forzar actualización cuando cambie paginaTabla o elementosPorPagina
                this.$watch('paginaTabla', () => {
                    this.$nextTick(() => {
                        // Forzar actualización de la vista
                    });
                });
                
                this.$watch('elementosPorPagina', () => {
                    this.$nextTick(() => {
                        // Forzar actualización de la vista
                    });
                });
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                
                // Inicializar TinyMCE Editor
                this.$nextTick(() => {
                    this.inicializarTinyMCE();
                });
                
                // Verificar tickets excedidos al cargar
                this.verificarTicketsExcedidos();
                
                // Configurar verificación periódica cada 2 minutos
                // Usar arrow function para mantener el contexto de 'this'
                const iniciarVerificacionPeriodica = () => {
                    // Limpiar intervalo anterior si existe
                    if (this.intervaloVerificacionExcedidos) {
                        clearInterval(this.intervaloVerificacionExcedidos);
                    }
                    
                    this.intervaloVerificacionExcedidos = setInterval(() => {
                      
                        this.verificarTicketsExcedidos();
                    }, 300000); // 5 minutos = 300000 ms
                    
                };
                
                // Iniciar la verificación periódica
                iniciarVerificacionPeriodica();
                
                // Reiniciar verificación si la página vuelve a estar visible (cuando el usuario regresa a la pestaña)
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.verificarTicketsExcedidos();
                    }
                });
                
                // La actualización de mensajes se manejará mediante cron job
                // No se configura recarga automática
            },

            iniciarActualizacionTiempoReal() {
                // Limpiar intervalo anterior si existe
                if (this.intervaloTiempoReal) {
                    clearInterval(this.intervaloTiempoReal);
                }
                
                // Actualizar indicadores de tiempo inmediatamente
                this.actualizarIndicadoresTiempo();
                
                // Configurar intervalo para actualizar cada 30 segundos (tiempo real)
                this.intervaloTiempoReal = setInterval(() => {
                    this.actualizarIndicadoresTiempo();
                }, 30000); // 30 segundos = 30000 ms
            },

            async actualizarIndicadoresTiempo() {
                try {
                    const response = await fetch('/tickets/tiempo-progreso', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    
                    if (data.success && data.tiempos) {
                        // Actualizar cada ticket en el DOM
                        Object.keys(data.tiempos).forEach(ticketId => {
                            const tiempoInfo = data.tiempos[ticketId];
                            if (!tiempoInfo) return;
                            
                            // Actualizar vista Kanban
                            const ticketElementKanban = document.querySelector(`[data-ticket-id="${ticketId}"][data-categoria="proceso"]`);
                            if (ticketElementKanban) {
                                const tiempoContainer = ticketElementKanban.querySelector('.tiempo-indicador-container');
                                if (tiempoContainer) {
                                    // Actualizar el badge de estado
                                    const badgeEstado = tiempoContainer.querySelector('.badge-estado');
                                    if (badgeEstado) {
                                        const estado = tiempoInfo.estado;
                                        badgeEstado.className = `text-xs px-2 py-0.5 rounded-full font-semibold ${
                                            estado === 'agotado' ? 'bg-red-100 text-red-700' : 
                                            (estado === 'por_vencer' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700')
                                        }`;
                                        badgeEstado.innerHTML = estado === 'agotado' 
                                            ? '<i class="fas fa-exclamation-triangle"></i> Tiempo Agotado'
                                            : (estado === 'por_vencer' 
                                                ? '<i class="fas fa-clock"></i> Por Vencer'
                                                : '<i class="fas fa-check-circle"></i> En Tiempo');
                                    }
                                    
                                    // Actualizar el texto de tiempo
                                    const tiempoTexto = tiempoContainer.querySelector('.tiempo-texto');
                                    if (tiempoTexto) {
                                        // Convertir horas decimales a horas y minutos
                                        const formatearHoras = (horas) => {
                                            if (!horas || horas === 0 || horas === '') return '-';
                                            const h = parseFloat(horas);
                                            if (isNaN(h)) return '-';
                                            const horasEnteras = Math.floor(h);
                                            const minutos = Math.round((h - horasEnteras) * 60);
                                            if (horasEnteras > 0 && minutos > 0) {
                                                return `${horasEnteras}h ${minutos}m`;
                                            } else if (horasEnteras > 0) {
                                                return `${horasEnteras}h`;
                                            } else if (minutos > 0) {
                                                return `${minutos}m`;
                                            } else {
                                                return '0m';
                                            }
                                        };
                                        tiempoTexto.textContent = `${formatearHoras(tiempoInfo.transcurrido)} / ${formatearHoras(tiempoInfo.estimado)}`;
                                    }
                                    
                                    // Actualizar la barra de progreso
                                    const barraProgreso = tiempoContainer.querySelector('.barra-progreso');
                                    if (barraProgreso) {
                                        barraProgreso.style.width = `${Math.min(tiempoInfo.porcentaje, 100)}%`;
                                        barraProgreso.className = `h-1.5 rounded-full transition-all duration-300 ${
                                            tiempoInfo.estado === 'agotado' ? 'bg-red-500' : 
                                            (tiempoInfo.estado === 'por_vencer' ? 'bg-yellow-500' : 'bg-green-500')
                                        }`;
                                    }
                                }
                            }
                            
                            // Actualizar vista Lista (usando Alpine.js)
                            if (this.ticketsTabla && Array.isArray(this.ticketsTabla)) {
                                const ticketEnLista = this.ticketsTabla.find(t => t.id == ticketId);
                                if (ticketEnLista && ticketEnLista.tiempoTranscurrido !== undefined) {
                                    ticketEnLista.tiempoTranscurrido = tiempoInfo.transcurrido.toString();
                                    ticketEnLista.tiempoEstimado = tiempoInfo.estimado.toString();
                                    ticketEnLista.tiempoEstado = tiempoInfo.estado;
                                    
                                    // Actualizar también los atributos data-* en el elemento DOM si existe
                                    if (ticketEnLista.elemento) {
                                        ticketEnLista.elemento.setAttribute('data-ticket-tiempo-transcurrido', tiempoInfo.transcurrido);
                                        ticketEnLista.elemento.setAttribute('data-ticket-tiempo-estimado', tiempoInfo.estimado);
                                        ticketEnLista.elemento.setAttribute('data-ticket-tiempo-estado', tiempoInfo.estado);
                                    }
                                }
                            }
                            
                            // Actualizar atributos data-* en todos los elementos del ticket para mantener consistencia
                            const todosLosElementosTicket = document.querySelectorAll(`[data-ticket-id="${ticketId}"]`);
                            todosLosElementosTicket.forEach(elemento => {
                                elemento.setAttribute('data-ticket-tiempo-transcurrido', tiempoInfo.transcurrido);
                                elemento.setAttribute('data-ticket-tiempo-estimado', tiempoInfo.estimado);
                                elemento.setAttribute('data-ticket-tiempo-estado', tiempoInfo.estado);
                            });
                        });
                    }
                } catch (error) {
                    console.error('Error actualizando indicadores de tiempo:', error);
                }
            },

            inicializarTinyMCE() {
                const editorElement = document.getElementById('editor-mensaje');
                
                if (!editorElement || this.tinyMCEInstance) return;

                // Esperar a que TinyMCE esté disponible
                if (typeof tinymce === 'undefined') {
                    setTimeout(() => this.inicializarTinyMCE(), 100);
                    return;
                }

                // Destruir instancia anterior si existe
                if (tinymce.get('editor-mensaje')) {
                    tinymce.remove('editor-mensaje');
                }

                // Detectar modo oscuro
                const isDarkMode = document.documentElement.classList.contains('dark');
                
                // Inicializar TinyMCE
                tinymce.init({
                    selector: '#editor-mensaje',
                    height: 300,
                    menubar: false,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                        'bold italic underline strikethrough | forecolor backcolor | ' +
                        'alignleft aligncenter alignright alignjustify | ' +
                        'bullist numlist | outdent indent | ' +
                        'removeformat | link image | code | help',
                    content_style: isDarkMode 
                        ? 'body { font-family: Arial, sans-serif; font-size: 14px; background-color: #1f2937 !important; color: #ffffff !important; } body * { color: #ffffff !important; }' 
                        : 'body { font-family: Arial, sans-serif; font-size: 14px; }',
                    language: 'es',
                    placeholder: 'Escribe tu mensaje aquí...',
                    setup: (editor) => {
                        this.tinyMCEInstance = editor;
                        
                        // Sincronizar contenido con Alpine.js en tiempo real
                        editor.on('input', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                            // Forzar actualización de Alpine.js
                            this.$nextTick(() => {
                                // Trigger para que Alpine detecte el cambio
                            });
                        });
                        
                        editor.on('change', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                            // Forzar actualización de Alpine.js
                            this.$nextTick(() => {
                                // Trigger para que Alpine detecte el cambio
                            });
                        });
                        
                        editor.on('keyup', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                        });
                        
                        editor.on('NodeChange', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                        });
                    },
                    init_instance_callback: (editor) => {
                        // Verificar estado al inicializar y deshabilitar si está cerrado
                        this.$nextTick(() => {
                            this.actualizarEstadoEditor();
                        });
                    },
                    file_picker_types: 'file',
                    file_picker_callback: (callback, value, meta) => {
                        // Abrir el input de archivos existente
                        const fileInput = document.getElementById('adjuntos');
                        if (fileInput) {
                            fileInput.click();
                            fileInput.onchange = (e) => {
                                const file = e.target.files[0];
                                if (file) {
                                    // Mostrar el archivo como enlace en el editor
                                    const reader = new FileReader();
                                    reader.onload = () => {
                                        const fileUrl = reader.result;
                                        callback(fileUrl, { text: file.name });
                                    };
                                    reader.readAsDataURL(file);
                                }
                            };
                        }
                    }
                });
            },

            prepararDatosLista() {
                // Los contadores ya están inicializados desde el servidor
                // Esta función solo se usa para recalcular si es necesario
                // No necesita hacer nada ya que los datos vienen del servidor
            },

            prepararDatosTabla() {
                // Preparar todos los tickets para la tabla desde los elementos del DOM
                // Usar getAttribute en lugar de dataset para asegurar que funcione incluso con elementos ocultos
                const preparar = () => {
                    const todosTickets = [];
                    let totalElementosEncontrados = 0;
                    
                    ['nuevos', 'proceso', 'resueltos'].forEach(categoria => {
                        // Buscar todos los elementos con data-categoria, incluso si están ocultos
                        // Usar querySelectorAll que encuentra elementos incluso si están dentro de x-show="false"
                        const elementos = document.querySelectorAll(`[data-categoria="${categoria}"]`);
                        totalElementosEncontrados += elementos.length;
                        
                        elementos.forEach(el => {
                            // Usar getAttribute para obtener los valores incluso si el elemento está oculto
                            const ticketId = el.getAttribute('data-ticket-id') || el.dataset.ticketId;
                            const ticketAsunto = el.getAttribute('data-ticket-asunto') || el.dataset.ticketAsunto;
                            const ticketDescripcion = el.getAttribute('data-ticket-descripcion') || el.dataset.ticketDescripcion;
                            const ticketPrioridad = el.getAttribute('data-ticket-prioridad') || el.dataset.ticketPrioridad;
                            const ticketEmpleado = el.getAttribute('data-ticket-empleado') || el.dataset.ticketEmpleado;
                            const ticketAnydesk = el.getAttribute('data-ticket-anydesk') || el.dataset.ticketAnydesk || '';
                            const ticketNumero = el.getAttribute('data-ticket-numero') || el.dataset.ticketNumero || '';
                            const ticketCorreo = el.getAttribute('data-ticket-correo') || el.dataset.ticketCorreo;
                            const ticketFecha = el.getAttribute('data-ticket-fecha') || el.dataset.ticketFecha;
                            // Leer responsable y tiempo - usar getAttribute con el nombre completo del atributo
                            const ticketResponsable = el.getAttribute('data-ticket-responsable') || '';
                            const ticketTiempoTranscurrido = el.getAttribute('data-ticket-tiempo-transcurrido') || '';
                            const ticketTiempoEstimado = el.getAttribute('data-ticket-tiempo-estimado') || '';
                            const ticketTiempoEstado = el.getAttribute('data-ticket-tiempo-estado') || '';
                            
                            if (ticketId) {
                            todosTickets.push({
                                    id: ticketId,
                                    asunto: ticketAsunto || `Ticket #${ticketId}`,
                                    descripcion: ticketDescripcion || '',
                                    prioridad: ticketPrioridad || 'Media',
                                    empleado: ticketEmpleado || '',
                                    anydesk: ticketAnydesk,
                                    numero: ticketNumero,
                                    correo: ticketCorreo || '',
                                    fecha: ticketFecha || '',
                                estatus: categoria === 'nuevos' ? 'Pendiente' : (categoria === 'proceso' ? 'En progreso' : 'Cerrado'),
                                responsable: ticketResponsable ? ticketResponsable.trim() : '',
                                tiempoTranscurrido: ticketTiempoTranscurrido ? ticketTiempoTranscurrido.trim() : '',
                                tiempoEstimado: ticketTiempoEstimado ? ticketTiempoEstimado.trim() : '',
                                tiempoEstado: ticketTiempoEstado ? ticketTiempoEstado.trim() : '',
                                elemento: el
                            });
                            }
                        });
                    });
                    
                    // Si no se encontraron elementos, intentar nuevamente después de un breve delay
                    if (totalElementosEncontrados === 0 && this.vista === 'tabla') {
                        setTimeout(() => {
                            this.prepararDatosTabla();
                        }, 500);
                        return;
                    }
                    
                    // Eliminar duplicados basados en el ID del ticket
                    const ticketsUnicos = [];
                    const idsVistos = new Set();
                    
                    todosTickets.forEach(ticket => {
                        const idUnico = ticket.id ? `ticket-${ticket.id}` : `ticket-${Date.now()}-${Math.random()}`;
                        if (!idsVistos.has(ticket.id)) {
                            idsVistos.add(ticket.id);
                            ticketsUnicos.push(ticket);
                        }
                    });
                    
                    // Asignar los tickets únicos y ordenar
                    // Crear un nuevo array para asegurar que Alpine.js detecte el cambio
                    this.ticketsTabla = [...ticketsUnicos];
                    this.ordenarTabla();
                    
                    // Forzar actualización de Alpine.js
                    this.$nextTick(() => {
                        // Asegurar que Alpine detecte el cambio
                        if (this.ticketsTabla.length > 0) {
                            // Forzar actualización reactiva
                            this.ticketsTabla = [...this.ticketsTabla];
                        } else {
                        }
                    });
                };
                
                // Ejecutar después de que Alpine.js haya procesado el DOM
                this.$nextTick(() => {
                    preparar();
                });
            },

            obtenerTicketsListaPagina(categoria) {
                const tickets = this.ticketsLista[categoria] || [];
                const inicio = (this.paginaLista[categoria] - 1) * this.elementosPorPagina;
                const fin = inicio + this.elementosPorPagina;
                return tickets.slice(inicio, fin);
            },

            estaEnPaginaLista(categoria, indice) {
                const inicio = (this.paginaLista[categoria] - 1) * this.elementosPorPagina;
                const fin = inicio + this.elementosPorPagina;
                return indice >= inicio && indice < fin;
            },

            estaEnPaginaListaPorElemento(categoria, elemento) {
                // Calcular el índice del elemento dentro de su contenedor padre
                const contenedor = elemento?.parentElement;
                if (!contenedor) return false;
                
                const elementosEnSeccion = Array.from(contenedor.children);
                const indice = elementosEnSeccion.indexOf(elemento);
                
                return this.estaEnPaginaLista(categoria, indice);
            },

            obtenerTotalPaginasLista(categoria) {
                const totalTickets = this.ticketsLista[categoria] || 0;
                return Math.ceil(totalTickets / this.elementosPorPagina);
            },

            cambiarPaginaLista(categoria, pagina) {
                const totalPaginas = this.obtenerTotalPaginasLista(categoria);
                if (pagina >= 1 && pagina <= totalPaginas) {
                    this.paginaLista[categoria] = pagina;
                }
            },

            obtenerTicketsTablaPagina() {
                const inicio = (this.paginaTabla - 1) * this.elementosPorPagina;
                const fin = inicio + this.elementosPorPagina;
                return this.ticketsTabla.slice(inicio, fin);
            },

            obtenerTotalPaginasTabla() {
                return Math.ceil(this.ticketsTabla.length / this.elementosPorPagina);
            },

            cambiarPaginaTabla(pagina) {
                const totalPaginas = this.obtenerTotalPaginasTabla();
                if (pagina >= 1 && pagina <= totalPaginas) {
                    this.paginaTabla = pagina;
                }
            },

            ordenarTabla() {
                this.ticketsTabla.sort((a, b) => {
                    let valorA, valorB;
                    
                    switch(this.ordenColumna) {
                        case 'id':
                            valorA = parseInt(a.id);
                            valorB = parseInt(b.id);
                            break;
                        case 'descripcion':
                            valorA = a.descripcion.toLowerCase();
                            valorB = b.descripcion.toLowerCase();
                            break;
                        case 'empleado':
                            valorA = a.empleado.toLowerCase();
                            valorB = b.empleado.toLowerCase();
                            break;
                        case 'prioridad':
                            const prioridades = { 'Alta': 3, 'Media': 2, 'Baja': 1 };
                            valorA = prioridades[a.prioridad] || 0;
                            valorB = prioridades[b.prioridad] || 0;
                            break;
                        case 'estado':
                            valorA = a.estatus.toLowerCase();
                            valorB = b.estatus.toLowerCase();
                            break;
                        case 'fecha':
                        default:
                            valorA = new Date(a.fecha.split(' ')[0].split('/').reverse().join('-'));
                            valorB = new Date(b.fecha.split(' ')[0].split('/').reverse().join('-'));
                            break;
                    }
                    
                    if (valorA < valorB) return this.ordenDireccion === 'asc' ? -1 : 1;
                    if (valorA > valorB) return this.ordenDireccion === 'asc' ? 1 : -1;
                    return 0;
                });
                
                // Resetear a página 1 después de ordenar
                this.paginaTabla = 1;
            },

            cambiarOrden(columna) {
                if (this.ordenColumna === columna) {
                    this.ordenDireccion = this.ordenDireccion === 'asc' ? 'desc' : 'asc';
                } else {
                    this.ordenColumna = columna;
                    this.ordenDireccion = 'asc';
                }
                this.ordenarTabla();
            },

            // Función eliminada: La actualización de mensajes se manejará mediante cron job
            // configurarActualizacionAutomatica() {
            //     setInterval(() => {
            //         if (this.mostrar && this.selected.id) {
            //             this.cargarMensajes();
            //         }
            //     }, 30000); // 30 segundos
            // },

            abrirModal(datos) {
                this.selected = datos;
                this.mostrar = true;
                this.asuntoCorreo = `Re: Ticket #${datos.id}`;
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                // Limpiar archivos adjuntos al abrir un nuevo modal
                this.archivosAdjuntos = [];
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    adjuntosInput.value = '';
                }
                // Cargar datos del ticket para el formulario
                this.cargarDatosTicket(datos.id);
                this.cargarMensajes();
                // Iniciar verificación automática de mensajes nuevos
                this.iniciarVerificacionMensajes();
                // Inicializar TinyMCE si no está inicializado
                this.$nextTick(() => {
                    if (!this.tinyMCEInstance) {
                        this.inicializarTinyMCE();
                    } else {
                        // Si ya está inicializado, actualizar su estado
                        this.actualizarEstadoEditor();
                    }
                });
            },

            async cargarDatosTicket(ticketId) {
                try {
                    const response = await fetch(`/tickets/${ticketId}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.ticket) {
                            this.ticketPrioridad = data.ticket.Prioridad || '';
                            this.ticketEstatus = data.ticket.Estatus || '';
                            this.ticketClasificacion = data.ticket.Clasificacion || '';
                            this.ticketResponsableTI = data.ticket.ResponsableTI ? String(data.ticket.ResponsableTI) : '';
                            this.ticketTipoID = data.ticket.TipoID ? String(data.ticket.TipoID) : '';
                            this.ticketSubtipoID = data.ticket.SubtipoID ? String(data.ticket.SubtipoID) : '';
                            this.ticketTertipoID = data.ticket.TertipoID ? String(data.ticket.TertipoID) : '';
                            this.selected.numero = data.ticket.numero || '';  // Actualiza el número con lo que viene del server
                            this.selected.anydesk = data.ticket.anydesk || '';
                            
                            // Actualizar también el estatus e imagen en selected para el bloqueo visual
                            if (this.selected) {
                                this.selected.estatus = data.ticket.Estatus || '';
                                this.selected.imagen = data.ticket.imagen || '';
                            }
                            
                            // Deshabilitar/habilitar editor según el estado
                            this.$nextTick(() => {
                                this.actualizarEstadoEditor();
                            });
                            
                            // Esperar a que los selects estén cargados y luego establecer valores
                            this.$nextTick(() => {
                                setTimeout(() => {
                                    const tipoSelect = document.getElementById('tipo-select');
                                    if (tipoSelect && this.ticketTipoID) {
                                        tipoSelect.value = this.ticketTipoID;
                                        const changeEvent = new Event('change', { bubbles: true });
                                        tipoSelect.dispatchEvent(changeEvent);
                                        
                                        // Esperar a que se carguen los subtipos y establecer el valor
                                        setTimeout(() => {
                                            const subtipoSelect = document.getElementById('subtipo-select');
                                            if (subtipoSelect && this.ticketSubtipoID) {
                                                // Cargar las opciones si no están cargadas (importante para tickets cerrados)
                                                if (subtipoSelect.options.length <= 1) {
                                                    // Forzar la carga de subtipos
                                                    if (typeof loadSubtipos === 'function') {
                                                        loadSubtipos(this.ticketTipoID);
                                                    }
                                                }
                                                
                                                // Esperar un poco más para que se carguen las opciones
                                                setTimeout(() => {
                                                    if (subtipoSelect.options.length > 1) {
                                                subtipoSelect.value = this.ticketSubtipoID;
                                                const subtipoChangeEvent = new Event('change', { bubbles: true });
                                                subtipoSelect.dispatchEvent(subtipoChangeEvent);
                                                
                                                // Esperar a que se carguen los tertipos y establecer el valor
                                                setTimeout(() => {
                                                    const tertipoSelect = document.getElementById('tertipo-select');
                                                    if (tertipoSelect && this.ticketTertipoID) {
                                                                // Cargar las opciones si no están cargadas
                                                                if (tertipoSelect.options.length <= 1) {
                                                                    // Forzar la carga de tertipos
                                                                    if (typeof loadTertipos === 'function') {
                                                                        loadTertipos(this.ticketSubtipoID);
                                                                    }
                                                                }
                                                                
                                                                setTimeout(() => {
                                                                    if (tertipoSelect.options.length > 1) {
                                                        tertipoSelect.value = this.ticketTertipoID;
                                                                    }
                                                                }, 300);
                                                            }
                                                        }, 500);
                                                    }
                                                }, 300);
                                            }
                                        }, 500);
                                    }
                                }, 300);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error cargando datos del ticket:', error);
                }
            },

            async guardarCambiosTicket() {
                if (!this.selected.id) {
                    this.mostrarNotificacion('No hay ticket seleccionado', 'error');
                    return;
                }

                this.guardandoTicket = true;

                try {
                    const formData = {
                        ticketId: this.selected.id,
                        prioridad: this.ticketPrioridad,
                        estatus: this.ticketEstatus,
                        clasificacion: this.ticketClasificacion || null,
                        responsableTI: this.ticketResponsableTI || null,
                        tipoID: this.ticketTipoID || null,
                        subtipoID: this.ticketSubtipoID || null,
                        tertipoID: this.ticketTertipoID || null
                    };

                    const response = await fetch('/tickets/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion('Cambios guardados correctamente', 'success');
                        
                        // Actualizar los datos seleccionados
                        if (data.ticket) {
                            const estatusAnterior = this.selected.estatus;
                            
                            this.selected.prioridad = data.ticket.Prioridad;
                            this.selected.estatus = data.ticket.Estatus;
                            this.ticketEstatus = data.ticket.Estatus;
                            
                            // Determinar la nueva categoría basada en el estatus
                            let nuevaCategoria = '';
                            let nuevoEstatusTexto = '';
                            if (data.ticket.Estatus === 'Pendiente' || data.ticket.Estatus === 'Nuevo') {
                                nuevaCategoria = 'nuevos';
                                nuevoEstatusTexto = 'Pendiente';
                            } else if (data.ticket.Estatus === 'En progreso' || data.ticket.Estatus === 'Proceso') {
                                nuevaCategoria = 'proceso';
                                nuevoEstatusTexto = 'En progreso';
                            } else if (data.ticket.Estatus === 'Cerrado' || data.ticket.Estatus === 'Resuelto') {
                                nuevaCategoria = 'resueltos';
                                nuevoEstatusTexto = 'Cerrado';
                            }
                            
                            // Actualizar todas las vistas sin recargar la página
                            this.actualizarVistasDespuesDeGuardar(data.ticket, estatusAnterior, nuevaCategoria, nuevoEstatusTexto);
                        }
                    } else {
                        this.mostrarNotificacion(data.message || 'Error al guardar los cambios', 'error');
                    }
                } catch (error) {
                    console.error('Error guardando cambios:', error);
                    this.mostrarNotificacion('Error al guardar los cambios', 'error');
                } finally {
                    this.guardandoTicket = false;
                }
            },

            actualizarVistasDespuesDeGuardar(ticketData, estatusAnterior, nuevaCategoria, nuevoEstatusTexto) {
                // Esta función actualiza todas las vistas sin recargar la página
                            this.$nextTick(() => {
                                // Buscar todos los elementos con el mismo ticket-id (puede haber múltiples en diferentes vistas)
                                const ticketElements = document.querySelectorAll(`[data-ticket-id="${this.selected.id}"]`);
                    
                    // Determinar la categoría anterior
                    let categoriaAnterior = '';
                    if (estatusAnterior === 'Pendiente' || estatusAnterior === 'Nuevo') {
                        categoriaAnterior = 'nuevos';
                    } else if (estatusAnterior === 'En progreso' || estatusAnterior === 'Proceso') {
                        categoriaAnterior = 'proceso';
                    } else if (estatusAnterior === 'Cerrado' || estatusAnterior === 'Resuelto') {
                        categoriaAnterior = 'resueltos';
                    }
                    
                    const estatusCambio = estatusAnterior !== ticketData.Estatus;
                                
                                ticketElements.forEach(ticketElement => {
                                    // Actualizar atributos data-* del elemento
                        ticketElement.setAttribute('data-ticket-prioridad', ticketData.Prioridad);
                                    
                        // Si el estatus cambió, mover el ticket entre secciones (kanban y lista)
                        if (estatusCambio && nuevaCategoria && categoriaAnterior) {
                            // Solo mover si el elemento tiene data-categoria
                                        if (ticketElement.hasAttribute('data-categoria')) {
                                const categoriaActual = ticketElement.getAttribute('data-categoria');
                                
                                // Si está en una categoría diferente, moverlo físicamente
                                if (categoriaActual !== nuevaCategoria) {
                                    // Determinar si es vista kanban o lista
                                    const esVistaKanban = ticketElement.closest('[x-show*="kanban"]');
                                    const esVistaLista = ticketElement.closest('[x-show*="lista"]');
                                    
                                    let contenedorNuevaSeccion = null;
                                    
                                    if (esVistaKanban) {
                                        // Mover en vista kanban
                                        const vistaKanban = document.querySelector('[x-show*="kanban"]');
                                        if (vistaKanban && vistaKanban.offsetParent !== null) {
                                            const todasLasColumnas = Array.from(vistaKanban.querySelectorAll('.shadow-lg.rounded-md'));
                                            const indiceCategoria = {
                                                'nuevos': 0,
                                                'proceso': 1,
                                                'resueltos': 2
                                            };
                                            const indiceNueva = indiceCategoria[nuevaCategoria];
                                            contenedorNuevaSeccion = todasLasColumnas[indiceNueva]?.querySelector('.space-y-3');
                                        }
                                    } else if (esVistaLista) {
                                        // Mover en vista lista
                                        const vistaLista = document.querySelector('[x-show*="lista"]');
                                        if (vistaLista && vistaLista.offsetParent !== null) {
                                            // Buscar todas las secciones de lista (divs con bg-white rounded-lg)
                                            const todasLasSecciones = Array.from(vistaLista.querySelectorAll('.bg-white.rounded-lg.shadow-sm'));
                                            
                                            // Mapeo de categorías a índices de sección (orden: nuevos, proceso, resueltos)
                                            const indiceCategoria = {
                                                'nuevos': 0,
                                                'proceso': 1,
                                                'resueltos': 2
                                            };
                                            
                                            const indiceNueva = indiceCategoria[nuevaCategoria];
                                            contenedorNuevaSeccion = todasLasSecciones[indiceNueva]?.querySelector('.divide-y.divide-gray-200');
                                        }
                                    }
                                    
                                    if (contenedorNuevaSeccion) {
                                        // Actualizar el atributo antes de mover
                                            ticketElement.setAttribute('data-categoria', nuevaCategoria);
                                    
                                        // Mover el elemento a la nueva sección
                                        contenedorNuevaSeccion.appendChild(ticketElement);
                                        
                                        // Alpine.js recalculará automáticamente x-show usando estaEnPaginaListaPorElemento
                                        
                                        // Actualizar contadores
                                        if (this.ticketsLista[categoriaAnterior] > 0) {
                                            this.ticketsLista[categoriaAnterior]--;
                                        }
                                        if (!this.ticketsLista[nuevaCategoria]) {
                                            this.ticketsLista[nuevaCategoria] = 0;
                                        }
                                        this.ticketsLista[nuevaCategoria]++;
                                    } else {
                                        // Si no se encuentra el contenedor, solo actualizar el atributo
                                        ticketElement.setAttribute('data-categoria', nuevaCategoria);
                                    }
                                }
                                        }
                                    }
                                    
                                    // Actualizar el badge de prioridad visualmente (todas las vistas)
                                    const badgesPrioridad = ticketElement.querySelectorAll('.text-xs.font-semibold.px-2, .text-xs.font-semibold.px-2.py-0\\.5, .text-xs.font-semibold.px-2.py-1');
                                    badgesPrioridad.forEach(badge => {
                                        // Verificar si es un badge de prioridad (no de estatus)
                                        const texto = badge.textContent.trim();
                                        if (texto === 'Baja' || texto === 'Media' || texto === 'Alta' || 
                                            texto === this.selected.prioridad || 
                                (badge.classList.contains('rounded-full') && !badge.textContent.includes('Ticket'))) {
                                badge.textContent = ticketData.Prioridad;
                                            // Actualizar clases de color según prioridad
                                            const clasesBase = badge.className.split(' ').filter(c => 
                                                !c.startsWith('bg-') && !c.startsWith('text-')
                                            ).join(' ');
                                const clasesColor = ticketData.Prioridad === 'Baja' 
                                                ? 'bg-green-200 text-green-600' 
                                    : ticketData.Prioridad === 'Media' 
                                                ? 'bg-yellow-200 text-yellow-600' 
                                                : 'bg-red-200 text-red-600';
                                            badge.className = clasesBase + ' ' + clasesColor;
                                        }
                                    });
                                });
                                
                                // Actualizar los datos de la tabla (siempre, para que se refleje en todas las vistas)
                                this.prepararDatosTabla();
                                
                    // Actualizar manualmente el estatus en ticketsTabla
                        setTimeout(() => {
                                    if (this.ticketsTabla && this.ticketsTabla.length > 0) {
                                        const ticketEnTabla = this.ticketsTabla.find(t => t.id == this.selected.id);
                            if (ticketEnTabla) {
                                ticketEnTabla.prioridad = ticketData.Prioridad;
                                if (nuevoEstatusTexto) {
                                            ticketEnTabla.estatus = nuevoEstatusTexto;
                                }
                                        }
                                    }
                                }, 100);
                                
                                // Actualizar estado del editor
                                this.actualizarEstadoEditor();
                            });
            },

            abrirModalDesdeElemento(elemento) {
                // Buscar el elemento padre que tenga los atributos data-ticket-*
                // Esto es necesario porque el clic puede ser en un elemento hijo
                let elementoConDatos = elemento;
                if (!elemento.dataset || !elemento.dataset.ticketId) {
                    elementoConDatos = elemento.closest('[data-ticket-id]');
                }
                
                if (!elementoConDatos) {
                    console.error('No se encontró el elemento con datos del ticket');
                    return;
                }
                
                // Usar getAttribute para asegurar que obtenemos los valores correctos
                const datos = {
                    id: elementoConDatos.getAttribute('data-ticket-id') || elementoConDatos.dataset.ticketId,
                    asunto: elementoConDatos.getAttribute('data-ticket-asunto') || elementoConDatos.dataset.ticketAsunto || `Ticket #${elementoConDatos.getAttribute('data-ticket-id')}`,
                    descripcion: elementoConDatos.getAttribute('data-ticket-descripcion') || elementoConDatos.dataset.ticketDescripcion || '',
                    prioridad: elementoConDatos.getAttribute('data-ticket-prioridad') || elementoConDatos.dataset.ticketPrioridad || 'Media',
                    empleado: elementoConDatos.getAttribute('data-ticket-empleado') || elementoConDatos.dataset.ticketEmpleado || '',
                    anydesk: elementoConDatos.getAttribute('data-ticket-anydesk') || elementoConDatos.dataset.ticketAnydesk || '',
                    numero: elementoConDatos.getAttribute('data-ticket-numero') || elementoConDatos.dataset.ticketNumero || '',
                    correo: elementoConDatos.getAttribute('data-ticket-correo') || elementoConDatos.dataset.ticketCorreo || '',
                    fecha: elementoConDatos.getAttribute('data-ticket-fecha') || elementoConDatos.dataset.ticketFecha || new Date().toLocaleString('es-ES'),
                    imagen: elementoConDatos.getAttribute('data-ticket-imagen') || elementoConDatos.dataset.ticketImagen || ''
                };
                
                // Decodificar HTML entities en la descripción
                if (datos.descripcion) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = datos.descripcion;
                    datos.descripcion = tempDiv.textContent || tempDiv.innerText || datos.descripcion;
                }
                
                this.abrirModal(datos);
            },

            cerrarModal() {
                this.mostrar = false;
                this.mensajes = [];
                this.nuevoMensaje = '';
                this.asuntoCorreo = '';
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                // Detener verificación automática de mensajes nuevos
                this.detenerVerificacionMensajes();
                // Limpiar el editor TinyMCE
                if (this.tinyMCEInstance) {
                    this.tinyMCEInstance.setContent('');
                }
                // Limpiar archivos adjuntos
                this.archivosAdjuntos = [];
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    adjuntosInput.value = '';
                }
                setTimeout(() => this.selected = {}, 200);
            },

            obtenerContenidoEditor() {
                // Intentar obtener contenido de TinyMCE primero
                if (this.tinyMCEInstance) {
                    try {
                        const contenido = this.tinyMCEInstance.getContent();
                        // Remover etiquetas vacías y espacios HTML
                        const textoLimpio = contenido
                            .replace(/<p><\/p>/g, '')
                            .replace(/<p>\s*<\/p>/g, '')
                            .replace(/<br\s*\/?>/gi, '')
                            .replace(/&nbsp;/g, ' ')
                            .trim();
                        // Si después de limpiar hay contenido, retornar el contenido original
                        if (textoLimpio.length > 0) {
                            return contenido;
                        }
                    } catch (e) {
                        console.warn('Error obteniendo contenido de TinyMCE:', e);
                    }
                }
                // Fallback: usar nuevoMensaje si está disponible
                if (this.nuevoMensaje && this.nuevoMensaje.trim().length > 0) {
                    return this.nuevoMensaje;
                }
                return '';
            },
            
            tieneContenido() {
                // Verificar primero si TinyMCE está inicializado y tiene contenido
                if (this.tinyMCEInstance) {
                    try {
                        const contenido = this.tinyMCEInstance.getContent();
                        if (contenido) {
                            // Remover etiquetas HTML y espacios
                            const textoLimpio = contenido
                                .replace(/<[^>]*>/g, '') // Remover todas las etiquetas HTML
                                .replace(/&nbsp;/g, ' ')
                                .replace(/&amp;/g, '&')
                                .replace(/&lt;/g, '<')
                                .replace(/&gt;/g, '>')
                                .replace(/\s+/g, ' ')
                                .trim();
                            if (textoLimpio.length > 0) {
                                return true;
                            }
                        }
                    } catch (e) {
                        console.warn('Error verificando contenido de TinyMCE:', e);
                    }
                }
                
                // Fallback: verificar nuevoMensaje
                if (this.nuevoMensaje) {
                    const textoLimpio = this.nuevoMensaje
                        .replace(/<[^>]*>/g, '')
                        .replace(/&nbsp;/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim();
                    return textoLimpio.length > 0;
                }
                
                return false;
            },

            limpiarEditor() {
                this.nuevoMensaje = '';
                if (this.tinyMCEInstance) {
                    this.tinyMCEInstance.setContent('');
                }
                this.archivosAdjuntos = [];
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    adjuntosInput.value = '';
                }
            },

            manejarArchivosSeleccionados(event) {
                const input = event.target;
                const files = Array.from(input.files || []);
                
                if (files.length === 0) {
                    this.archivosAdjuntos = [];
                    return;
                }
                
                // Validar y agregar archivos
                this.procesarArchivos(files);
            },
            
            procesarArchivos(files) {
                const archivosValidos = [];
                const tiposPermitidos = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png', '.gif'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                files.forEach(file => {
                    const extension = '.' + file.name.split('.').pop().toLowerCase();
                    if (tiposPermitidos.includes(extension)) {
                        if (file.size <= maxSize) {
                            archivosValidos.push(file);
                        } else {
                            alert(`El archivo "${file.name}" excede el tamaño máximo de 10MB`);
                        }
                    } else {
                        alert(`El archivo "${file.name}" no es un tipo permitido`);
                    }
                });
                
                // Agregar archivos válidos a la lista
                archivosValidos.forEach(file => {
                    this.archivosAdjuntos.push(file);
                });
                
                // Actualizar el input file
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    const dataTransfer = new DataTransfer();
                    this.archivosAdjuntos.forEach(archivo => {
                        dataTransfer.items.add(archivo);
                    });
                    adjuntosInput.files = dataTransfer.files;
                }
                
                // Forzar actualización de Alpine.js
                this.$nextTick(() => {
                    console.log('Archivos adjuntos actualizados:', this.archivosAdjuntos.length);
                });
            },
            
            handleDragOver(event) {
                if (this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado' || 
                    (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const dragArea = document.getElementById('drag-drop-area');
                if (dragArea) {
                    dragArea.style.backgroundColor = 'rgba(59, 130, 246, 0.15)';
                    dragArea.style.borderColor = '#3B82F6';
                    dragArea.style.borderStyle = 'solid';
                }
            },
            
            handleDragLeave(event) {
                if (this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado' || 
                    (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const dragArea = document.getElementById('drag-drop-area');
                if (dragArea && !dragArea.contains(event.relatedTarget)) {
                    dragArea.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
                    dragArea.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                    dragArea.style.borderStyle = 'dashed';
                }
            },
            
            handleDrop(event) {
                if (this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado' || 
                    (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                
                const dragArea = document.getElementById('drag-drop-area');
                if (dragArea) {
                    dragArea.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
                    dragArea.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                    dragArea.style.borderStyle = 'dashed';
                }
                
                const files = Array.from(event.dataTransfer.files || []);
                if (files.length > 0) {
                    this.procesarArchivos(files);
                }
            },

            eliminarArchivo(index) {
                if (index >= 0 && index < this.archivosAdjuntos.length) {
                    this.archivosAdjuntos.splice(index, 1);
                    
                    // Actualizar el input file para reflejar los cambios
                    const adjuntosInput = document.getElementById('adjuntos');
                    if (adjuntosInput) {
                        // Crear un nuevo DataTransfer para actualizar el input
                        const dataTransfer = new DataTransfer();
                        this.archivosAdjuntos.forEach(archivo => {
                            dataTransfer.items.add(archivo);
                        });
                        adjuntosInput.files = dataTransfer.files;
                    }
                }
            },

            formatearTamañoArchivo(bytes) {
                if (!bytes || bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            },

            actualizarEstadoEditor() {
                const estaCerrado = this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado';
                const estaPendiente = this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente';
                
                if (this.tinyMCEInstance) {
                    try {
                        // Cambiar el modo del editor a readonly si está cerrado o pendiente
                        if (estaCerrado || estaPendiente) {
                            this.tinyMCEInstance.mode.set('readonly');
                        } else {
                            this.tinyMCEInstance.mode.set('design');
                        }
                    } catch (e) {
                        console.warn('Error actualizando estado del editor:', e);
                    }
                }
                
                // También deshabilitar el textarea si TinyMCE no está inicializado
                const textarea = document.getElementById('editor-mensaje');
                if (textarea) {
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    textarea.disabled = estaCerrado || estaPendiente;
                    if (estaCerrado || estaPendiente) {
                        textarea.style.cursor = 'not-allowed';
                        textarea.style.backgroundColor = isDarkMode ? '#374151' : '#f3f4f6';
                    } else {
                        textarea.style.cursor = 'text';
                        textarea.style.backgroundColor = isDarkMode ? '#374151' : 'white';
                        textarea.style.color = isDarkMode ? '#f9fafb' : '#000000';
                    }
                }
            },

            async cargarMensajes() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/chat-messages?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    
                    if (data.success) {
                        this.mensajes = data.messages;
                        // Actualizar el último mensaje ID para la verificación automática
                        if (this.mensajes && this.mensajes.length > 0) {
                            this.ultimoMensajeId = Math.max(...this.mensajes.map(m => m.id));
                        } else {
                            this.ultimoMensajeId = 0;
                        }
                        this.marcarMensajesComoLeidos();
                        this.scrollToBottom();
                    
                        // Actualizar estadísticas después de cargar mensajes
                    this.estadisticas = await this.obtenerEstadisticasCorreos();
                    } else {
                        console.error('Error en la API:', data.message);
                    }
                } catch (error) {
                    console.error('Error cargando mensajes:', error);
                }
            },

            iniciarVerificacionMensajes() {
                // Limpiar intervalo anterior si existe
                if (this.intervaloVerificacionMensajes) {
                    clearInterval(this.intervaloVerificacionMensajes);
                }
                
                // Verificar inmediatamente al iniciar
                this.verificarMensajesNuevos();
                
                // Configurar intervalo para verificar cada 30 segundos
                // Esto coincide con la frecuencia del job que se ejecuta cada 5 minutos
                // pero verificamos más frecuentemente para mejor UX
                this.intervaloVerificacionMensajes = setInterval(() => {
                    if (this.mostrar && this.selected.id) {
                        this.verificarMensajesNuevos();
                    }
                }, 30000); // 30 segundos
            },

            detenerVerificacionMensajes() {
                if (this.intervaloVerificacionMensajes) {
                    clearInterval(this.intervaloVerificacionMensajes);
                    this.intervaloVerificacionMensajes = null;
                }
                this.ultimoMensajeId = 0;
            },

            async verificarMensajesNuevos() {
                if (!this.selected.id || !this.mostrar) return;

                try {
                    const response = await fetch(
                        `/tickets/verificar-mensajes-nuevos?ticket_id=${this.selected.id}&ultimo_mensaje_id=${this.ultimoMensajeId}`,
                        {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            }
                        }
                    );

                    const data = await response.json();

                    if (data.success && data.tiene_nuevos) {
                        // Hay mensajes nuevos, recargar la lista de mensajes
                        await this.cargarMensajes();
                    }
                } catch (error) {
                    // Silenciar errores de verificación para no molestar al usuario
                    // Solo loguear en consola para debugging
                    console.debug('Error verificando mensajes nuevos:', error);
                }
            },

            normalizarAsunto(asunto) {
                // Asegurar que el asunto siempre tenga la nomenclatura con el ID del ticket
                const ticketId = this.selected.id;
                const patronTicket = new RegExp(`Ticket\\s*#?\\s*${ticketId}`, 'i');
                
                if (!patronTicket.test(asunto)) {
                    // Si no tiene el ID del ticket, agregarlo
                    if (asunto.trim().startsWith('Re:')) {
                        return `Re: Ticket #${ticketId} ${asunto.replace(/^Re:\s*/i, '').trim()}`;
                    } else {
                        return `Re: Ticket #${ticketId} ${asunto.trim()}`;
                    }
                }
                // Si ya tiene el ID, mantenerlo pero asegurar formato consistente
                return asunto.replace(/Ticket\s*#?\s*(\d+)/i, `Ticket #${ticketId}`);
            },

            async enviarRespuesta() {
                // Validar que el ticket no esté en Pendiente
                if (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente') {
                    this.mostrarNotificacion('No se pueden enviar mensajes cuando el ticket está en estado "Pendiente". Cambia el estado a "En progreso" para enviar mensajes.', 'error');
                    return;
                }
                
                // Obtener el contenido HTML de TinyMCE
                let contenidoMensaje = '';
                if (this.tinyMCEInstance) {
                    contenidoMensaje = this.tinyMCEInstance.getContent();
                    // Limpiar contenido vacío
                    if (contenidoMensaje === '<p><br></p>' || contenidoMensaje.trim() === '') {
                        contenidoMensaje = '';
                    }
                } else {
                    contenidoMensaje = this.nuevoMensaje;
                }

                if (!contenidoMensaje.trim()) return;
                if (!this.asuntoCorreo.trim()) {
                    this.mostrarNotificacion('El asunto es requerido', 'error');
                    return;
                }

                this.cargando = true;

                try {
                    // Normalizar el asunto para mantener la nomenclatura con el ID
                    const asuntoNormalizado = this.normalizarAsunto(this.asuntoCorreo);
                    // Actualizar el campo con el asunto normalizado
                    this.asuntoCorreo = asuntoNormalizado;
                    
                    const formData = new FormData();
                    formData.append('ticket_id', this.selected.id);
                    formData.append('mensaje', contenidoMensaje);
                    formData.append('asunto', asuntoNormalizado);

                    
                    const adjuntosInput = document.getElementById('adjuntos');
                    if (adjuntosInput && adjuntosInput.files && adjuntosInput.files.length > 0) {
                        for (let i = 0; i < adjuntosInput.files.length; i++) {
                            formData.append('adjuntos[]', adjuntosInput.files[i]);
                        }
                    }

                    const response = await fetch('/tickets/enviar-respuesta', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.nuevoMensaje = '';
                        // Limpiar el editor TinyMCE
                        if (this.tinyMCEInstance) {
                            this.tinyMCEInstance.setContent('');
                        }
                        // Limpiar archivos adjuntos
                        this.archivosAdjuntos = [];
                        if (adjuntosInput) {
                            adjuntosInput.value = '';
                        }
                        
                       
                        this.mostrarNotificacion(data.message, 'success');
                        
                
                        await this.cargarMensajes();
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error enviando respuesta:', error);
                    this.mostrarNotificacion('Error enviando respuesta', 'error');
                } finally {
                    this.cargando = false;
                }
            },

            async marcarMensajesComoLeidos() {
                if (!this.selected.id) return;

                try {
                    await fetch('/tickets/marcar-leidos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });
                } catch (error) {
                    console.error('Error marcando mensajes como leídos:', error);
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const container = document.getElementById('chat-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            },

            mostrarNotificacion(mensaje, tipo) {
                // Remover notificaciones anteriores si existen
                const notificacionesAnteriores = document.querySelectorAll('.ticket-notification');
                notificacionesAnteriores.forEach(n => n.remove());
                
                let bgColor = 'bg-red-500';
                if (tipo === 'success') bgColor = 'bg-green-500';
                else if (tipo === 'info') bgColor = 'bg-blue-500';
                
                const notification = document.createElement('div');
                notification.className = `ticket-notification p-4 rounded-lg shadow-2xl flex items-center gap-3 min-w-[300px] max-w-md ${bgColor} text-white`;
                
                // Establecer estilos inline para asegurar que aparezca por encima del modal (z-50)
                // Usar un z-index muy alto y position fixed
                notification.style.position = 'fixed';
                notification.style.top = '1rem';
                notification.style.right = '1rem';
                notification.style.zIndex = '999999'; // Mucho más alto que el modal (z-50)
                notification.style.pointerEvents = 'auto';
                
                // Estilos iniciales para animación
                notification.style.transform = 'translateX(400px)';
                notification.style.opacity = '0';
                notification.style.transition = 'all 0.3s ease-in-out';
                
                // Icono según el tipo
                let icono = '';
                if (tipo === 'success') {
                    icono = '<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                } else if (tipo === 'info') {
                    icono = '<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                } else {
                    icono = '<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                }
                
                notification.innerHTML = `
                    ${icono}
                    <span class="flex-1 font-medium">${mensaje}</span>
                `;
                
                // Agregar directamente al body para evitar problemas de contexto de apilamiento
                // Asegurarse de que esté fuera de cualquier contenedor del modal
                document.body.appendChild(notification);
                
                // Forzar el z-index después de agregar al DOM para asegurar que se aplique
                requestAnimationFrame(() => {
                    notification.style.zIndex = '999999';
                });
                
                // Animación de entrada
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                    notification.style.opacity = '1';
                }, 10);
                
                // Remover después de 4 segundos con animación
                setTimeout(() => {
                    notification.style.transform = 'translateX(400px)';
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }, 4000);
            },

            formatearFecha(fecha) {
                return new Date(fecha).toLocaleString('es-ES');
            },

            async verificarTicketsExcedidos() {
                try {
                    this.cargandoExcedidos = true;
                    const response = await fetch('{{ route("tickets.excedidos") }}', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success && data.tickets && data.tickets.length > 0) {
                        this.ticketsExcedidos = data.tickets;
                        
                        // Mostrar popup si hay tickets excedidos
                        // Si hay nuevos tickets o cambió la cantidad, mostrar/actualizar el popup
                        if (this.ticketsExcedidos.length > 0) {
                            this.mostrarPopupExcedidos = true;
                            // Iniciar timer para cerrar automáticamente
                            this.iniciarTimerPopup();
                        }
                    } else {
                        // Si no hay tickets excedidos, ocultar el popup
                        if (this.ticketsExcedidos.length > 0) {
                        }
                        this.ticketsExcedidos = [];
                        this.mostrarPopupExcedidos = false;
                    }
                } catch (error) {
                } finally {
                    this.cargandoExcedidos = false;
                }
            },

            cerrarPopupExcedidos() {
                // Limpiar el timer si existe
                if (this.timerPopupExcedidos) {
                    clearTimeout(this.timerPopupExcedidos);
                    this.timerPopupExcedidos = null;
                }
                // Limpiar el intervalo del contador si existe
                if (this.intervaloContadorPopup) {
                    clearInterval(this.intervaloContadorPopup);
                    this.intervaloContadorPopup = null;
                }
                this.mostrarPopupExcedidos = false;
            },
            
            iniciarTimerPopup() {
                // Limpiar timer anterior si existe
                if (this.timerPopupExcedidos) {
                    clearTimeout(this.timerPopupExcedidos);
                    this.timerPopupExcedidos = null;
                }
                // Limpiar intervalo del contador anterior si existe
                if (this.intervaloContadorPopup) {
                    clearInterval(this.intervaloContadorPopup);
                    this.intervaloContadorPopup = null;
                }
                
                // Reiniciar contador
                this.tiempoRestantePopup = 10;
                
                // Actualizar contador cada segundo
                this.intervaloContadorPopup = setInterval(() => {
                    this.tiempoRestantePopup--;
                    if (this.tiempoRestantePopup <= 0) {
                        clearInterval(this.intervaloContadorPopup);
                        this.intervaloContadorPopup = null;
                    }
                }, 1000);
                
                // Cerrar automáticamente después de 10 segundos
                this.timerPopupExcedidos = setTimeout(() => {
                    if (this.intervaloContadorPopup) {
                        clearInterval(this.intervaloContadorPopup);
                        this.intervaloContadorPopup = null;
                    }
                    this.cerrarPopupExcedidos();
                }, 10000); // 10 segundos
            },

            abrirTicketDesdePopup(ticketId) {
                // Buscar el elemento del ticket y abrirlo
                const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                if (ticketElement) {
                    this.abrirModalDesdeElemento(ticketElement);
                    // Cerrar el popup (esto también limpiará el timer)
                    this.cerrarPopupExcedidos();
                }
            },

            obtenerIniciales(nombre) {
                if (!nombre) return '??';
                return nombre.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
            },

            formatearMensaje(mensaje) {
                if (!mensaje) return '';
                
                // Convertir saltos de línea a <br>
                let mensajeFormateado = mensaje.replace(/\n/g, '<br>');
                
                // Detectar URLs y convertirlas en enlaces
                mensajeFormateado = mensajeFormateado.replace(
                    /(https?:\/\/[^\s]+)/g, 
                    '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>'
                );
                
                return mensajeFormateado;
            },

            obtenerAdjuntos() {
                if (!this.selected || !this.selected.imagen) return [];
                
                try {
                    // Intentar parsear el JSON
                    const adjuntos = typeof this.selected.imagen === 'string' 
                        ? JSON.parse(this.selected.imagen) 
                        : this.selected.imagen;
                    
                    // Asegurarse de que sea un array
                    return Array.isArray(adjuntos) ? adjuntos : [];
                } catch (e) {
                    // Si no es JSON válido, intentar como string simple
                    if (typeof this.selected.imagen === 'string' && this.selected.imagen.trim() !== '') {
                        return [this.selected.imagen];
                    }
                    return [];
                }
            },

            obtenerNombreArchivo(ruta) {
                if (!ruta) return 'Archivo sin nombre';
                // Extraer el nombre del archivo de la ruta
                const partes = ruta.split('/');
                let nombre = partes[partes.length - 1];
                // Remover el prefijo uniqid_ si existe
                if (nombre.includes('_')) {
                    nombre = nombre.substring(nombre.indexOf('_') + 1);
                }
                return nombre;
            },

            obtenerExtensionArchivo(ruta) {
                if (!ruta) return '';
                const nombre = this.obtenerNombreArchivo(ruta);
                const punto = nombre.lastIndexOf('.');
                if (punto === -1) return 'Sin extensión';
                return nombre.substring(punto + 1).toUpperCase();
            },

            obtenerUrlArchivo(ruta) {
                if (!ruta) return '#';
                // Si la ruta ya es una URL completa, retornarla
                if (ruta.startsWith('http://') || ruta.startsWith('https://')) {
                    return ruta;
                }
                
                // Si la ruta es un objeto con propiedades (como los adjuntos del chat)
                if (typeof ruta === 'object' && ruta !== null) {
                    // Si tiene una propiedad 'url', usarla directamente
                    if (ruta.url) {
                        return ruta.url;
                    }
                    // Si tiene 'storage_path', construir la URL desde ahí
                    if (ruta.storage_path) {
                        ruta = ruta.storage_path;
                    }
                    // Si tiene 'path', intentar extraer la ruta relativa
                    else if (ruta.path) {
                        // Si path es una ruta absoluta del sistema, extraer solo el nombre del archivo
                        const pathParts = ruta.path.split(/[\\/]/);
                        const fileName = pathParts[pathParts.length - 1];
                        // Buscar si hay un nombre de archivo en el objeto
                        ruta = ruta.name || fileName;
                    }
                    // Si tiene 'name', usarlo como nombre de archivo
                    else if (ruta.name) {
                        ruta = ruta.name;
                    }
                }
                
                // Limpiar la ruta: remover prefijos comunes de storage
                let rutaLimpia = ruta.toString();
                rutaLimpia = rutaLimpia.replace(/^storage\/app\/public\//, '');
                rutaLimpia = rutaLimpia.replace(/^storage\//, '');
                rutaLimpia = rutaLimpia.replace(/^public\//, '');
                
                // Si la ruta ya incluye 'tickets/', mantenerla tal cual (puede ser tickets/ o tickets/adjuntos/)
                if (!rutaLimpia.startsWith('tickets/')) {
                    // Si es solo el nombre del archivo, agregar 'tickets/' (archivos antiguos)
                    if (!rutaLimpia.includes('/')) {
                        rutaLimpia = `tickets/${rutaLimpia}`;
                    } else {
                        // Si tiene subcarpetas pero no incluye 'tickets', agregarlo
                        if (!rutaLimpia.includes('tickets/')) {
                            rutaLimpia = `tickets/${rutaLimpia}`;
                        }
                    }
                }
                
                // Generar URL con la ruta completa /storage/app/public/tickets/...
                // Formato: /storage/app/public/tickets/archivo.xlsx
                // Esta es la ruta que funciona según el usuario
                return `/storage/app/public/${rutaLimpia}`;
            },

            aplicarFormato(tipo) {
                if (!this.tinyMCEInstance) {
                    this.inicializarTinyMCE();
                    return;
                }
                
                switch(tipo) {
                    case 'bold':
                        this.tinyMCEInstance.execCommand('mceToggleFormat', false, 'bold');
                        break;
                    case 'italic':
                        this.tinyMCEInstance.execCommand('mceToggleFormat', false, 'italic');
                        break;
                    case 'underline':
                        this.tinyMCEInstance.execCommand('mceToggleFormat', false, 'underline');
                        break;
                }
            },

            getTipoMensaje(remitente) {
                return remitente === 'soporte' ? 'soporte' : 'usuario';
            },

            async sincronizarCorreos() {
                if (!this.selected.id) return;

                this.sincronizando = true;

                try {
                    const response = await fetch('/tickets/sincronizar-correos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                        
                        // Si hay mensajes en la respuesta, actualizarlos directamente
                        if (data.mensajes) {
                            this.mensajes = data.mensajes;
                            this.scrollToBottom();
                        } else {
                        // Recargar mensajes después de la sincronización
                        await this.cargarMensajes();
                        }
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error sincronizando correos:', error);
                    this.mostrarNotificacion('Error sincronizando correos', 'error');
                } finally {
                    this.sincronizando = false;
                }
            },

            async obtenerEstadisticasCorreos() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/estadisticas-correos?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        return data.estadisticas;
                    }
                } catch (error) {
                    console.error('Error obteniendo estadísticas:', error);
                }
                
                return null;
            },

            async diagnosticarCorreos() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/diagnosticar-correos?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        
                        // Mostrar diagnóstico en una ventana emergente
                        let mensaje = 'Diagnóstico de Correos:\n\n';
                        mensaje += `SMTP Host: ${data.diagnostico.smtp.host}\n`;
                        mensaje += `SMTP Port: ${data.diagnostico.smtp.port}\n`;
                        mensaje += `IMAP Host: ${data.diagnostico.imap.host}\n`;
                        mensaje += `IMAP Port: ${data.diagnostico.imap.port}\n`;
                        mensaje += `Conexión IMAP: ${data.diagnostico.imap_connection}\n\n`;
                        
                        if (data.diagnostico.mensajes_bd) {
                            mensaje += `Mensajes en BD:\n`;
                            mensaje += `- Total: ${data.diagnostico.mensajes_bd.total}\n`;
                            mensaje += `- Enviados: ${data.diagnostico.mensajes_bd.enviados}\n`;
                            mensaje += `- Recibidos: ${data.diagnostico.mensajes_bd.recibidos}\n`;
                            mensaje += `- Correos: ${data.diagnostico.mensajes_bd.correos}\n`;
                        }
                        
                        alert(mensaje);
                    } else {
                        this.mostrarNotificacion('Error en diagnóstico: ' + data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error ejecutando diagnóstico', 'error');
                }
            },

            async enviarInstrucciones() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch('/tickets/enviar-instrucciones', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error enviando instrucciones', 'error');
                }
            },

            async agregarRespuestaManual() {
                if (!this.selected.id || !this.respuestaManual.mensaje.trim()) return;

                try {
                    const response = await fetch('/tickets/agregar-respuesta-manual', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id,
                            mensaje: this.respuestaManual.mensaje,
                            nombre_emisor: this.respuestaManual.nombre || this.selected.empleado,
                            correo_emisor: this.respuestaManual.correo || this.selected.correo
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                        
                        // Actualizar mensajes
                        if (data.mensajes) {
                            this.mensajes = data.mensajes;
                            this.scrollToBottom();
                        }
                        
                        // Limpiar formulario
                        this.respuestaManual = {
                            nombre: '',
                            correo: '',
                            mensaje: ''
                        };
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error agregando respuesta manual', 'error');
                }
            },

            async probarConexionWebklex() {
                try {
                    
                    const response = await fetch('/api/test-webklex-connection', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error probando conexión Webklex', 'error');
                }
            },

            async buscarCorreosUsuarios() {
                if (!this.selected.id) {
                    this.mostrarNotificacion('Selecciona un ticket primero', 'error');
                    return;
                }

                this.buscandoCorreos = true;

                try {
                    
                    // Procesar correos entrantes desde IMAP
                    const response = await fetch('/api/process-webklex-responses', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        const mensaje = data.procesados > 0 
                            ? `✅ Se encontraron y procesaron ${data.procesados} correo(s)` + (data.descartados > 0 ? `. Se descartaron ${data.descartados} correo(s).` : '')
                            : data.message || 'Búsqueda completada';
                        
                        this.mostrarNotificacion(mensaje, data.procesados > 0 ? 'success' : 'error');
                        
                        // Recargar mensajes para mostrar los correos encontrados
                        await this.cargarMensajes();
                        
                        // Actualizar estadísticas
                        this.estadisticas = await this.obtenerEstadisticasCorreos();
                        
                    } else {
                        this.mostrarNotificacion(data.message || 'No se encontraron correos nuevos', 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error buscando correos de usuarios', 'error');
                } finally {
                    this.buscandoCorreos = false;
                }
            },

            async guardarCorreosEncontrados() {
                if (!this.selected.id) {
                    this.mostrarNotificacion('Selecciona un ticket primero', 'error');
                    return;
                }

                this.guardandoCorreos = true;

                try {
                    
                    // Sincronizar correos y guardarlos en el historial
                    const response = await fetch('/tickets/sincronizar-correos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(
                            data.message || 'Correos guardados en historial exitosamente',
                            'success'
                        );
                        
                        // Recargar mensajes para mostrar el historial completo
                        await this.cargarMensajes();
                        
                        // Actualizar estadísticas
                        this.estadisticas = await this.obtenerEstadisticasCorreos();
                        
                    } else {
                        this.mostrarNotificacion(data.message || 'Error guardando correos', 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error guardando correos en historial', 'error');
                } finally {
                    this.guardandoCorreos = false;
                }
            },

            // Funciones para métricas
            async cargarMetricas() {
                this.cargandoMetricas = true;
                this.metricasTipos = [];
                try {
                    const response = await fetch('/tickets/tipos-con-metricas');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.tipos && Array.isArray(data.tipos)) {
                        this.metricasTipos = data.tipos.map(tipo => ({
                            TipoID: tipo.TipoID,
                            NombreTipo: tipo.NombreTipo,
                            TiempoEstimadoMinutos: tipo.TiempoEstimadoMinutos || null,
                            cambiado: false
                        }));
                    } else {
                        this.mostrarNotificacion(data.message || 'Error cargando métricas', 'error');
                        this.metricasTipos = [];
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error cargando métricas: ' + error.message, 'error');
                    this.metricasTipos = [];
                } finally {
                    this.cargandoMetricas = false;
                }
            },

            async guardarMetricas() {
                const cambios = this.metricasTipos.filter(t => t.cambiado);
                
                if (cambios.length === 0) {
                    this.mostrarNotificacion('No hay cambios para guardar', 'info');
                    return;
                }

                this.guardandoMetricas = true;
                
                try {
                    const metricas = cambios.map(tipo => ({
                        tipo_id: tipo.TipoID,
                        tiempo_estimado_minutos: tipo.TiempoEstimadoMinutos ? parseInt(tipo.TiempoEstimadoMinutos) : null
                    }));

                    const response = await fetch('/tickets/actualizar-metricas-masivo', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            metricas: metricas
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Marcar todos los cambios como guardados
                        cambios.forEach(tipo => {
                            tipo.cambiado = false;
                        });
                        
                        this.mostrarNotificacion(
                            data.message || `Se actualizaron ${data.actualizados || cambios.length} tipos de tickets`,
                            'success'
                        );
                        
                        // Recargar métricas para asegurar sincronización
                        await this.cargarMetricas();
                    } else {
                        this.mostrarNotificacion(data.message || 'Error guardando métricas', 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error guardando métricas', 'error');
                } finally {
                    this.guardandoMetricas = false;
                }
            },

            formatearTiempo(minutos) {
                if (!minutos || minutos === 0) return '-';
                
                const horas = Math.floor(minutos / 60);
                const mins = minutos % 60;
                
                if (horas > 0 && mins > 0) {
                    return `${horas}h ${mins}m`;
                } else if (horas > 0) {
                    return `${horas}h`;
                } else {
                    return `${mins}m`;
                }
            },
            
            formatearHorasDecimales(horasDecimales) {
                if (!horasDecimales || horasDecimales === 0 || horasDecimales === '') return '-';
                
                const horas = parseFloat(horasDecimales);
                if (isNaN(horas)) return '-';
                
                const horasEnteras = Math.floor(horas);
                const minutos = Math.round((horas - horasEnteras) * 60);
                
                if (horasEnteras > 0 && minutos > 0) {
                    return `${horasEnteras}h ${minutos}m`;
                } else if (horasEnteras > 0) {
                    return `${horasEnteras}h`;
                } else if (minutos > 0) {
                    return `${minutos}m`;
                } else {
                    return '0m';
                }
            }
        }
    }

   
    // Hacer las funciones accesibles globalmente para que puedan ser llamadas desde Alpine.js
    window.loadSubtipos = null;
    
    // Función global para formatear horas decimales a horas y minutos
    window.formatearHorasDecimales = function(horasDecimales) {
        if (!horasDecimales || horasDecimales === 0 || horasDecimales === '') return '-';
        
        const horas = parseFloat(horasDecimales);
        if (isNaN(horas)) return '-';
        
        const horasEnteras = Math.floor(horas);
        const minutos = Math.round((horas - horasEnteras) * 60);
        
        if (horasEnteras > 0 && minutos > 0) {
            return `${horasEnteras}h ${minutos}m`;
        } else if (horasEnteras > 0) {
            return `${horasEnteras}h`;
        } else if (minutos > 0) {
            return `${minutos}m`;
        } else {
            return '0m';
        }
    };
    window.loadTertipos = null;
   
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo-select');
        const subtipoSelect = document.getElementById('subtipo-select');
        const tertipoSelect = document.getElementById('tertipo-select');

        loadTipos();

        tipoSelect.addEventListener('change', function() {
            const tipoId = this.value;
            
            clearSelect(subtipoSelect);
            clearSelect(tertipoSelect);
            subtipoSelect.disabled = true;
            tertipoSelect.disabled = true;

            if (tipoId) {
                loadSubtipos(tipoId);
            }
        });

        subtipoSelect.addEventListener('change', function() {
            const subtipoId = this.value;
            
            clearSelect(tertipoSelect);
            tertipoSelect.disabled = true;

            if (subtipoId) {
                loadTertipos(subtipoId);
            }
        });

        async function loadTipos() {
            try {
                const response = await fetch('/api/tipos');
                const data = await response.json();
                
                if (data.success) {
                    data.tipos.forEach(tipo => {
                        const option = document.createElement('option');
                        option.value = tipo.TipoID;
                        option.textContent = tipo.NombreTipo;
                        tipoSelect.appendChild(option);
                    });
                } else {
                }
            } catch (error) {
            }
        }

        window.loadSubtipos = async function loadSubtipos(tipoId) {
            try {
                // Verificar si el ticket está cerrado consultando Alpine.js
                const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                const estaCerrado = alpineData && (alpineData.selected?.estatus === 'Cerrado' || alpineData.ticketEstatus === 'Cerrado');
                
                subtipoSelect.innerHTML = '<option value="">Seleccione un subtipo</option>';
                subtipoSelect.disabled = true;
                
                tertipoSelect.innerHTML = '<option value="">Seleccione un tertipo</option>';
                tertipoSelect.disabled = true;
                
                if (!tipoId) {
                    return;
                }
                
                const response = await fetch(`/api/subtipos-by-tipo?tipo_id=${tipoId}`);
                const data = await response.json();
                
                if (data.success && data.subtipos.length > 0) {
                    data.subtipos.forEach(subtipo => {
                        const option = document.createElement('option');
                        option.value = subtipo.SubtipoID;
                        option.textContent = subtipo.NombreSubtipo;
                        subtipoSelect.appendChild(option);
                    });
                    // Solo habilitar si el ticket NO está cerrado
                    // Alpine.js manejará el disabled basado en su lógica (:disabled="!ticketTipoID || selected.estatus === 'Cerrado'")
                    if (!estaCerrado) {
                    subtipoSelect.disabled = false;
                    }
                } 
            } catch (error) {
            }
        }

        window.loadTertipos = async function loadTertipos(subtipoId) {
            try {
                tertipoSelect.innerHTML = '<option value="">Seleccione un tertipo</option>';
                tertipoSelect.disabled = true;
                
                if (!subtipoId) {
                    return;
                }
                
                const response = await fetch(`/api/tertipos-by-subtipo?subtipo_id=${subtipoId}`);
                const data = await response.json();
                
                if (data.success && data.tertipos.length > 0) {
                    data.tertipos.forEach(tertipo => {
                        const option = document.createElement('option');
                        option.value = tertipo.TertipoID;
                        option.textContent = tertipo.NombreTertipo;
                        tertipoSelect.appendChild(option);
                    });
                    // Habilitar el campo - Alpine.js lo deshabilitará automáticamente si el ticket está cerrado
                    // mediante su directiva :disabled="!ticketSubtipoID || selected.estatus === 'Cerrado'"
                    tertipoSelect.disabled = false;
                } else {
                }
            } catch (error) {
            }
        }

        function clearSelect(selectElement) {
            while (selectElement.children.length > 1) {
                selectElement.removeChild(selectElement.lastChild);
            }
        }
    });
</script>
</div>