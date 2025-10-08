@extends('layouts.app')
@section('content')

<style>
    /* Variables CSS para modo oscuro/claro */
    :root {
        --bg-primary: #111827; /* gray-900 */
        --bg-secondary: #1f2937; /* gray-800 */
        --bg-card: #374151; /* gray-700 */
        --bg-card-hover: #4b5563; /* gray-600 */
        --text-primary: #ffffff;
        --text-secondary: #d1d5db; /* gray-300 */
        --text-muted: #9ca3af; /* gray-400 */
        --border-color: #374151; /* gray-700 */
        --border-light: #4b5563; /* gray-600 */
        --modal-bg: #1f2937; /* gray-800 */
        --modal-overlay: rgba(0, 0, 0, 0.7);
        --shadow-color: rgba(0, 0, 0, 0.3);
        --shadow-hover: rgba(0, 0, 0, 0.4);
    }

    /* Modo claro */
    .light-mode {
        --bg-primary: #f9fafb; /* gray-50 */
        --bg-secondary: #ffffff;
        --bg-card: #ffffff;
        --bg-card-hover: #f3f4f6; /* gray-100 */
        --text-primary: #111827; /* gray-900 */
        --text-secondary: #374151; /* gray-700 */
        --text-muted: #6b7280; /* gray-500 */
        --border-color: #e5e7eb; /* gray-200 */
        --border-light: #d1d5db; /* gray-300 */
        --modal-bg: #ffffff;
        --modal-overlay: rgba(0, 0, 0, 0.5);
        --shadow-color: rgba(0, 0, 0, 0.1);
        --shadow-hover: rgba(0, 0, 0, 0.15);
    }

    /* Aplicar variables */
    body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Prioridades - Modo oscuro */
    .priority-high { @apply bg-red-900 text-red-200 border-red-700; }
    .priority-medium { @apply bg-yellow-900 text-yellow-200 border-yellow-700; }
    .priority-low { @apply bg-green-900 text-green-200 border-green-700; }
    
    /* Prioridades - Modo claro */
    .light-mode .priority-high { @apply bg-red-100 text-red-800 border-red-200; }
    .light-mode .priority-medium { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
    .light-mode .priority-low { @apply bg-green-100 text-green-800 border-green-200; }
    
    /* Estados - Modo oscuro */
    .status-open { @apply bg-blue-900 text-blue-200 border-blue-700; }
    .status-progress { @apply bg-yellow-900 text-yellow-200 border-yellow-700; }
    .status-closed { @apply bg-green-900 text-green-200 border-green-700; }
    
    /* Estados - Modo claro */
    .light-mode .status-open { @apply bg-blue-50 text-blue-800 border-blue-200; }
    .light-mode .status-progress { @apply bg-yellow-50 text-yellow-800 border-yellow-200; }
    .light-mode .status-closed { @apply bg-green-50 text-green-800 border-green-200; }
    
    .ticket-card {
        background-color: var(--bg-card);
        border-color: var(--border-color);
        color: var(--text-primary);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px -1px var(--shadow-color), 0 2px 4px -1px var(--shadow-color);
    }
    
    .ticket-card:hover {
        background-color: var(--bg-card-hover);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -3px var(--shadow-hover), 0 4px 6px -2px var(--shadow-hover);
    }
    
    .modal-overlay {
        backdrop-filter: blur(8px);
        background: var(--modal-overlay);
    }
    
    .modal-content {
        background-color: var(--modal-bg);
        color: var(--text-primary);
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    /* Contador de tickets */
    .ticket-count {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        font-weight: bold;
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        min-width: 1.5rem;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Botón de alternancia de modo */
    .theme-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.5rem;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .theme-toggle:hover {
        background: var(--bg-card-hover);
    }
</style>

<body class="min-h-screen py-8 px-4">
    <!-- Botón de alternancia de modo -->
    <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()">
        <i id="themeIcon" class="fas fa-moon"></i>
    </button>

    <div class="flex justify-start mb-8">
        <div class="relative grid grid-cols-3 gap-10 items-center rounded-xl shadow-lg" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);" role="tablist" aria-label="tabs">
            <div class="absolute indicator top-0 bottom-0 left-0 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 transition-all duration-300"></div>

            <button role="tab" aria-selected="true" aria-controls="panel-1" id="tab-1" tabindex="0"
                class="tab relative block rounded-xl px-6 py-3 font-medium" style="color: var(--text-primary);">
                <span class="flex items-center gap-2">
                    <i class="fas fa-ticket-alt"></i>
                    Tickets
                </span>
            </button>
            <button role="tab" aria-selected="false" aria-controls="panel-2" id="tab-2" tabindex="1"
                class="tab relative block rounded-xl px-6 py-3 transition-colors" style="color: var(--text-secondary);">
                <span class="flex items-center gap-2">
                    <i class="fas fa-clipboard-list"></i>
                    Solicitudes
                </span>
            </button>
            <button role="tab" aria-selected="false" aria-controls="panel-3" id="tab-3" tabindex="2"
                class="tab relative block rounded-xl px-6 py-3 transition-colors" style="color: var(--text-secondary);">
                <span class="flex items-center gap-2">
                    <i class="fas fa-chart-line"></i>
                    Productividad
                </span>
            </button>
        </div>
    </div>

    <div class="mt-8">
        <div id="panel-1" class="tab-panel transition-all duration-500 opacity-100 translate-x-0">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-h-[40rem] overflow-y-auto">
                <!-- Columna Abierto -->
                <div class="rounded-xl shadow-lg flex flex-col" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-t-xl p-4 border-b border-gray-600">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <i class="fas fa-folder-open"></i>
                            Abierto
                            <span class="ticket-count ml-auto">
                                {{ $tickets->get('Pendiente', collect())->count() }}
                            </span>
                        </h2>
                    </div>
                    <div class="p-4 space-y-3 flex-1">
                        @forelse($tickets->get('Pendiente', collect()) as $ticket)
                        <div class="ticket-card p-4 rounded-lg cursor-pointer transition-colors" 
                             onclick="openTicketModal({{ $ticket->TicketID }})"
                             data-ticket-id="{{ $ticket->TicketID }}">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-semibold px-3 py-1 rounded-full border
                                    @if(strtolower($ticket->Prioridad) == 'alta') priority-high
                                    @elseif(strtolower($ticket->Prioridad) == 'media') priority-medium
                                    @else priority-low @endif">
                                    {{ $ticket->Prioridad }}
                                </span>
                                <span class="text-sm font-medium" style="color: var(--text-secondary);">#{{ $ticket->TicketID }}</span>
                            </div>
                            <h3 class="font-semibold text-sm mb-3 leading-relaxed" style="color: var(--text-primary);">
                                {{ Str::limit($ticket->Descripcion, 50) }}
                            </h3>
                            <div class="flex justify-between items-center text-xs" style="color: var(--text-secondary);">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-user" style="color: var(--text-muted);"></i>
                                    <span class="font-medium">{{ $ticket->EmpleadoID }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs">
                                        {{ $ticket->created_at->format('h:i a') }} - {{ $ticket->created_at->format('d/m/Y') }}
                                    </span>
                                    @if($ticket->imagen && count($ticket->imagen) > 0)
                                    <i class="fas fa-paperclip text-blue-400"></i>
                                    @endif
                                </div>
                            </div>
                            @if($ticket->ResponsableTI)
                            <div class="mt-2 flex items-center gap-1 text-xs text-blue-400">
                                <i class="fas fa-user-tie"></i>
                                <span class="font-medium">Asignado a: {{ $ticket->ResponsableTI }}</span>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8" style="color: var(--text-muted);">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No hay tickets abiertos</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Columna En Proceso -->
                <div class="rounded-xl shadow-lg flex flex-col" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 text-white rounded-t-xl p-4 border-b border-gray-600">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <i class="fas fa-clock"></i>
                            En Proceso
                            <span class="ticket-count ml-auto">
                                {{ $tickets->get('En progreso', collect())->count() }}
                            </span>
                        </h2>
                    </div>
                    <div class="p-4 space-y-3 flex-1">
                        @forelse($tickets->get('En progreso', collect()) as $ticket)
                        <div class="ticket-card p-4 rounded-lg cursor-pointer transition-colors" 
                             onclick="openTicketModal({{ $ticket->TicketID }})"
                             data-ticket-id="{{ $ticket->TicketID }}">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-semibold px-3 py-1 rounded-full border
                                    @if(strtolower($ticket->Prioridad) == 'alta') priority-high
                                    @elseif(strtolower($ticket->Prioridad) == 'media') priority-medium
                                    @else priority-low @endif">
                                    {{ $ticket->Prioridad }}
                                </span>
                                <span class="text-sm font-medium" style="color: var(--text-secondary);">#{{ $ticket->TicketID }}</span>
                            </div>
                            <h3 class="font-semibold text-sm mb-3 leading-relaxed" style="color: var(--text-primary);">
                                {{ Str::limit($ticket->Descripcion, 50) }}
                            </h3>
                            <div class="flex justify-between items-center text-xs" style="color: var(--text-secondary);">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-user" style="color: var(--text-muted);"></i>
                                    <span class="font-medium">{{ $ticket->EmpleadoID }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                <span class="text-xs">
                                    {{ $ticket->created_at->format('h:i a') }} - {{ $ticket->created_at->format('d/m/Y') }}
                                </span>
                                    @if($ticket->imagen && count($ticket->imagen) > 0)
                                    <i class="fas fa-paperclip text-blue-400"></i>
                                    @endif
                                </div>
                            </div>
                            @if($ticket->ResponsableTI)
                            <div class="mt-2 flex items-center gap-1 text-xs text-blue-400">
                                <i class="fas fa-user-tie"></i>
                                <span class="font-medium">Asignado a: {{ $ticket->ResponsableTI }}</span>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8" style="color: var(--text-muted);">
                            <i class="fas fa-clock text-4xl mb-3"></i>
                            <p>No hay tickets en proceso</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Columna Cerrado -->
                <div class="rounded-xl shadow-lg flex flex-col" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-t-xl p-4 border-b border-gray-600">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Cerrado
                            <span class="ticket-count ml-auto">
                                {{ $tickets->get('Cerrado', collect())->count() }}
                            </span>
                        </h2>
                    </div>
                    <div class="p-4 space-y-3 flex-1">
                        @forelse($tickets->get('Cerrado', collect()) as $ticket)
                        <div class="ticket-card p-4 rounded-lg cursor-pointer transition-colors" 
                             onclick="openTicketModal({{ $ticket->TicketID }})"
                             data-ticket-id="{{ $ticket->TicketID }}">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-semibold px-3 py-1 rounded-full border
                                    @if(strtolower($ticket->Prioridad) == 'alta') priority-high
                                    @elseif(strtolower($ticket->Prioridad) == 'media') priority-medium
                                    @else priority-low @endif">
                                    {{ $ticket->Prioridad }}
                                </span>
                                <span class="text-sm font-medium" style="color: var(--text-secondary);">#{{ $ticket->TicketID }}</span>
                            </div>
                            <h3 class="font-semibold text-sm mb-3 leading-relaxed" style="color: var(--text-primary);">
                                {{ Str::limit($ticket->Descripcion, 50) }}
                            </h3>
                            <div class="flex justify-between items-center text-xs" style="color: var(--text-secondary);">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-user" style="color: var(--text-muted);"></i>
                                    <span class="font-medium">{{ $ticket->EmpleadoID }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                <span class="text-xs">
                                    {{ $ticket->created_at->format('h:i a') }} - {{ $ticket->created_at->format('d/m/Y') }}
                                </span>
                                    @if($ticket->imagen && count($ticket->imagen) > 0)
                                    <i class="fas fa-paperclip text-blue-400"></i>
                                    @endif
                                </div>
                            </div>
                            @if($ticket->ResponsableTI)
                            <div class="mt-2 flex items-center gap-1 text-xs text-blue-400">
                                <i class="fas fa-user-tie"></i>
                                <span class="font-medium">Asignado a: {{ $ticket->ResponsableTI }}</span>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8" style="color: var(--text-muted);">
                            <i class="fas fa-check-circle text-4xl mb-3"></i>
                            <p>No hay tickets cerrados</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div id="panel-2" class="tab-panel transition-all duration-500 opacity-0 -translate-x-10 pointer-events-none">Contenido del segundo tab</div>
        <div id="panel-3" class="tab-panel transition-all duration-500 opacity-0 -translate-x-10 pointer-events-none">
            <!-- Dashboard de Productividad -->
            <div class="space-y-6 max-h-[40rem] overflow-y-auto">
                <!-- Métricas principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total de tickets -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium" style="color: var(--text-secondary);">Total Tickets</p>
                                <p class="text-2xl font-bold" style="color: var(--text-primary);" id="totalTickets">{{ $tickets->flatten()->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets cerrados -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium" style="color: var(--text-secondary);">Tickets Cerrados</p>
                                <p class="text-2xl font-bold text-green-600" id="closedTickets">{{ $tickets->get('Cerrado', collect())->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets en proceso -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium" style="color: var(--text-secondary);">En Proceso</p>
                                <p class="text-2xl font-bold text-yellow-600" id="inProgressTickets">{{ $tickets->get('En progreso', collect())->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets pendientes -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium" style="color: var(--text-secondary);">Pendientes</p>
                                <p class="text-2xl font-bold text-blue-600" id="pendingTickets">{{ $tickets->get('Pendiente', collect())->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-folder-open text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficas principales -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Gráfica de tickets por estado -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Tickets por Estado</h3>
                        <div class="relative h-48">
                            <canvas id="statusChart"></canvas>
                            <div id="statusChartError" class="absolute inset-0 flex items-center justify-center text-center" style="display: none;">
                                <div>
                                    <i class="fas fa-chart-pie text-4xl mb-2" style="color: var(--text-muted);"></i>
                                    <p style="color: var(--text-muted);">Cargando gráfica...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de tickets por prioridad -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Tickets por Prioridad</h3>
                        <div class="relative h-48">
                            <canvas id="priorityChart"></canvas>
                            <div id="priorityChartError" class="absolute inset-0 flex items-center justify-center text-center" style="display: none;">
                                <div>
                                    <i class="fas fa-chart-bar text-4xl mb-2" style="color: var(--text-muted);"></i>
                                    <p style="color: var(--text-muted);">Cargando gráfica...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfica de tendencia temporal -->
                <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Tendencia de Tickets (Últimos 7 días)</h3>
                    <div class="relative h-64">
                        <canvas id="timelineChart"></canvas>
                        <div id="timelineChartError" class="absolute inset-0 flex items-center justify-center text-center" style="display: none;">
                            <div>
                                <i class="fas fa-chart-line text-4xl mb-2" style="color: var(--text-muted);"></i>
                                <p style="color: var(--text-muted);">Cargando gráfica...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas adicionales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Tiempo promedio de resolución -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <h4 class="text-md font-semibold mb-2" style="color: var(--text-primary);">Tiempo Promedio</h4>
                        <p class="text-2xl font-bold text-purple-600" id="avgResolutionTime">2.5 días</p>
                        <p class="text-sm" style="color: var(--text-secondary);">de resolución</p>
                    </div>

                    <!-- Tickets por empleado -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <h4 class="text-md font-semibold mb-2" style="color: var(--text-primary);">Tickets por Empleado</h4>
                        <p class="text-2xl font-bold text-indigo-600" id="ticketsPerEmployee">3.2</p>
                        <p class="text-sm" style="color: var(--text-secondary);">promedio</p>
                    </div>

                    <!-- Tasa de resolución -->
                    <div class="rounded-xl shadow-lg p-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <h4 class="text-md font-semibold mb-2" style="color: var(--text-primary);">Tasa de Resolución</h4>
                        <p class="text-2xl font-bold text-green-600" id="resolutionRate">85%</p>
                        <p class="text-sm" style="color: var(--text-secondary);">en tiempo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar detalles del ticket -->
    <div id="ticketModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute inset-0"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="modal-content rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header del modal -->
                <div class="flex items-center justify-between p-6 border-b" style="border-color: var(--border-light);">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-900 rounded-full flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-blue-400 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold" style="color: var(--text-primary);">Detalles del Ticket</h3>
                            <p class="text-sm" style="color: var(--text-secondary);">ID: <span id="modalTicketId">#</span></p>
                        </div>
                    </div>
                    <button onclick="closeTicketModal()" class="transition-colors" style="color: var(--text-muted);">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Contenido del modal -->
                <div class="p-6 space-y-6">
                    <!-- Información básica -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Prioridad</label>
                            <div id="modalPriority" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Estado</label>
                            <div id="modalStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Empleado ID</label>
                            <p id="modalEmployeeId" style="color: var(--text-primary);"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Persona Asignada</label>
                            <p id="modalAssignedPerson" style="color: var(--text-primary);"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Fecha de Creación</label>
                            <p id="modalCreatedAt" style="color: var(--text-primary);"></p>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Descripción</label>
                        <div class="rounded-lg p-4" style="background-color: var(--bg-card);">
                            <p id="modalDescription" class="leading-relaxed" style="color: var(--text-primary);"></p>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Fecha de Actualización</label>
                            <p id="modalUpdatedAt" style="color: var(--text-primary);"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Archivos Adjuntos</label>
                            <div id="modalAttachments" style="color: var(--text-primary);"></div>
                        </div>
                    </div>

                    <!-- Comentarios o notas adicionales -->
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Notas Adicionales</label>
                        <div class="rounded-lg p-4" style="background-color: var(--bg-card);">
                            <p id="modalNotes" class="leading-relaxed" style="color: var(--text-primary);">
                                <em style="color: var(--text-muted);">No hay notas adicionales disponibles.</em>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer del modal -->
                <div class="flex items-center justify-end gap-3 p-6 border-t rounded-b-2xl" style="border-color: var(--border-light); background-color: var(--bg-card);">
                    <button onclick="closeTicketModal()" class="px-4 py-2 transition-colors" style="color: var(--text-secondary);">
                        Cerrar
                    </button>
                    <button onclick="editTicket()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        Editar
                    </button>
                    <button onclick="changeToProcess()" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-play mr-2"></i>
                        Cambiar a Proceso
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="editTicketModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute inset-0"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="modal-content rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <!-- Header del modal -->
                <div class="flex items-center justify-between p-6 border-b" style="border-color: var(--border-light);">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-900 rounded-full flex items-center justify-center">
                            <i class="fas fa-edit text-blue-400 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold" style="color: var(--text-primary);">Editar Ticket</h3>
                            <p class="text-sm" style="color: var(--text-secondary);">ID: <span id="editModalTicketId">#</span></p>
                        </div>
                    </div>
                    <button onclick="closeEditTicketModal()" class="transition-colors" style="color: var(--text-muted);">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Contenido del modal -->
                <form id="editTicketForm" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Campo Prioridad -->
                        <div>
                            <label for="editPriority" class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Prioridad</label>
                            <select id="editPriority" name="Prioridad" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="border-color: var(--border-color); background-color: var(--bg-card); color: var(--text-primary);">
                                <option value="Baja">Baja</option>
                                <option value="Media">Media</option>
                                <option value="Alta">Alta</option>
                            </select>
                        </div>

                        <!-- Campo Persona Asignada -->
                        <div>
                            <label for="editAssignedPerson" class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Persona Asignada</label>
                            <input type="text" id="editAssignedPerson" name="ResponsableTI" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   style="border-color: var(--border-color); background-color: var(--bg-card); color: var(--text-primary);"
                                   placeholder="Ingrese el nombre de la persona asignada">
                        </div>

                        <!-- Campo Estado -->
                        <div>
                            <label for="editStatus" class="block text-sm font-medium mb-2" style="color: var(--text-secondary);">Estado</label>
                            <select id="editStatus" name="Estatus" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="border-color: var(--border-color); background-color: var(--bg-card); color: var(--text-primary);">
                                <option value="Pendiente">Pendiente</option>
                                <option value="En progreso">En progreso</option>
                                <option value="Cerrado">Cerrado</option>
                            </select>
                        </div>
                    </div>
                </form>

                <!-- Footer del modal -->
                <div class="flex items-center justify-end gap-3 p-6 border-t rounded-b-2xl" style="border-color: var(--border-light); background-color: var(--bg-card);">
                    <button onclick="closeEditTicketModal()" class="px-4 py-2 transition-colors" style="color: var(--text-secondary);">
                        Cancelar
                    </button>
                    <button onclick="saveTicketChanges()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
<script>
    // Funcionalidad de alternancia de modo
    function toggleTheme() {
        const body = document.body;
        const themeIcon = document.getElementById('themeIcon');
        
        if (body.classList.contains('light-mode')) {
            // Cambiar a modo oscuro
            body.classList.remove('light-mode');
            themeIcon.className = 'fas fa-moon';
            localStorage.setItem('theme', 'dark');
        } else {
            // Cambiar a modo claro
            body.classList.add('light-mode');
            themeIcon.className = 'fas fa-sun';
            localStorage.setItem('theme', 'light');
        }
        
        // Actualizar gráficas cuando cambie el tema
        setTimeout(updateChartsTheme, 100);
    }

    // Cargar tema guardado
    function loadTheme() {
        const savedTheme = localStorage.getItem('theme');
        const body = document.body;
        const themeIcon = document.getElementById('themeIcon');
        
        if (savedTheme === 'light') {
            body.classList.add('light-mode');
            themeIcon.className = 'fas fa-sun';
        } else {
            body.classList.remove('light-mode');
            themeIcon.className = 'fas fa-moon';
        }
    }

    // Inicializar tema al cargar la página
    document.addEventListener('DOMContentLoaded', loadTheme);

    // Variables globales
    let tabs = document.querySelectorAll(".tab");
    let indicator = document.querySelector(".indicator");
    let panels = document.querySelectorAll(".tab-panel");
    let currentTicketId = null;

    // Datos de tickets simulados (en una aplicación real, estos datos vendrían del backend)
    const ticketsData = {
        @foreach($tickets->flatten() as $ticket)
        {{ $ticket->TicketID }}: {
            id: {{ $ticket->TicketID }},
            prioridad: "{{ $ticket->Prioridad }}",
            estado: "{{ $ticket->Estatus ?? $ticket->Estado }}",
            empleadoId: "{{ $ticket->EmpleadoID }}",
            responsableTI: "{{ $ticket->ResponsableTI ?? '' }}",
            descripcion: `{{ $ticket->Descripcion }}`,
            createdAt: "{{ $ticket->created_at->format('d/m/Y h:i A') }}",
            updatedAt: "{{ $ticket->updated_at->format('d/m/Y h:i A') }}",
            imagenes: @json($ticket->imagen ?? [])
        },
        @endforeach
    };

    // Configuración de tabs
    const setIndicator = (tab) => {
        indicator.style.width = tab.getBoundingClientRect().width + 'px';
        indicator.style.left = (tab.getBoundingClientRect().left - tab.parentElement.getBoundingClientRect().left) + 'px';
    };

    setIndicator(tabs[0]);

    tabs.forEach((tab, index) => {
        tab.addEventListener("click", () => {
            setIndicator(tab);

            tabs.forEach((t, i) => {
                if (i === index) {
                    t.style.color = 'var(--text-primary)';
                } else {
                    t.style.color = 'var(--text-secondary)';
                }
            });

            panels.forEach((panel, i) => {
                if (i === index) {
                    panel.classList.remove("opacity-0", "translate-x-10", "pointer-events-none");
                    panel.classList.add("opacity-100", "translate-x-0");
                } else {
                    panel.classList.remove("opacity-100", "translate-x-0");
                    panel.classList.add("opacity-0", "translate-x-10", "pointer-events-none");
                }
            });
        });
    });

    // Funciones del modal
    function openTicketModal(ticketId) {
        currentTicketId = ticketId;
        const ticket = ticketsData[ticketId];
        
        if (!ticket) {
            console.error('Ticket no encontrado:', ticketId);
            return;
        }

        // Llenar los datos del modal
        document.getElementById('modalTicketId').textContent = `#${ticket.id}`;
        document.getElementById('modalEmployeeId').textContent = ticket.empleadoId;
        document.getElementById('modalAssignedPerson').textContent = ticket.responsableTI || 'Sin asignar';
        document.getElementById('modalCreatedAt').textContent = ticket.createdAt;
        document.getElementById('modalUpdatedAt').textContent = ticket.updatedAt;
        document.getElementById('modalDescription').textContent = ticket.descripcion;

        // Configurar prioridad
        const priorityElement = document.getElementById('modalPriority');
        priorityElement.textContent = ticket.prioridad;
        priorityElement.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border';
        
        if (ticket.prioridad.toLowerCase() === 'alta') {
            priorityElement.classList.add('priority-high');
        } else if (ticket.prioridad.toLowerCase() === 'media') {
            priorityElement.classList.add('priority-medium');
        } else {
            priorityElement.classList.add('priority-low');
        }

        // Configurar estado
        const statusElement = document.getElementById('modalStatus');
        statusElement.textContent = ticket.estado;
        statusElement.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold';
        
        if (ticket.estado.toLowerCase() === 'pendiente') {
            statusElement.classList.add('status-open');
        } else if (ticket.estado.toLowerCase() === 'en progreso') {
            statusElement.classList.add('status-progress');
        } else {
            statusElement.classList.add('status-closed');
        }

        // Configurar archivos adjuntos
        const attachmentsElement = document.getElementById('modalAttachments');
        if (ticket.imagenes && ticket.imagenes.length > 0) {
            attachmentsElement.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas fa-paperclip text-blue-400"></i>
                    <span>${ticket.imagenes.length} archivo(s) adjunto(s)</span>
                </div>
            `;
        } else {
            attachmentsElement.innerHTML = '<span style="color: var(--text-muted);">Sin archivos adjuntos</span>';
        }

        // Mostrar el modal
        const modal = document.getElementById('ticketModal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevenir scroll del body
    }

    function closeTicketModal() {
        const modal = document.getElementById('ticketModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restaurar scroll del body
        currentTicketId = null;
    }

    function editTicket() {
        if (currentTicketId) {
            const ticket = ticketsData[currentTicketId];
            
            if (!ticket) {
                console.error('Ticket no encontrado:', currentTicketId);
                return;
            }

            // Llenar el modal de edición con los datos actuales
            document.getElementById('editModalTicketId').textContent = `#${ticket.id}`;
            document.getElementById('editPriority').value = ticket.prioridad;
            document.getElementById('editAssignedPerson').value = ticket.responsableTI || '';
            document.getElementById('editStatus').value = ticket.estado;

            // Mostrar el modal de edición
            const modal = document.getElementById('editTicketModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeEditTicketModal() {
        const modal = document.getElementById('editTicketModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function changeToProcess() {
        if (currentTicketId) {
            // Cambiar estado a "En progreso" directamente
            updateTicketStatus(currentTicketId, 'En progreso');
        }
    }

    function saveTicketChanges() {
        if (!currentTicketId) return;

        const formData = new FormData(document.getElementById('editTicketForm'));
        const data = {
            ticketId: currentTicketId,
            prioridad: formData.get('Prioridad'),
            responsableTI: formData.get('ResponsableTI'),
            estatus: formData.get('Estatus')
        };

        // Enviar datos al servidor
        fetch('/tickets/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Actualizar los datos locales
                ticketsData[currentTicketId].prioridad = data.prioridad;
                ticketsData[currentTicketId].responsableTI = data.responsableTI;
                ticketsData[currentTicketId].estado = data.estatus;
                
                // Cerrar modal y recargar página
                closeEditTicketModal();
                closeTicketModal();
                location.reload();
            } else {
                alert('Error al actualizar el ticket: ' + (result.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el ticket');
        });
    }

    function updateTicketStatus(ticketId, newStatus) {
        console.log('Actualizando ticket:', ticketId, 'a estado:', newStatus);
        const data = {
            ticketId: ticketId,
            estatus: newStatus
        };

        console.log('Datos a enviar:', data);
        fetch('/tickets/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Respuesta del servidor:', response);
            return response.json();
        })
        .then(result => {
            console.log('Resultado:', result);
            if (result.success) {
                ticketsData[ticketId].estado = newStatus;
                closeTicketModal();
                location.reload();
            } else {
                alert('Error al actualizar el estado del ticket: ' + (result.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el estado del ticket');
        });
    }

    // Cerrar modal al hacer clic fuera de él
    document.getElementById('ticketModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTicketModal();
        }
    });

    document.getElementById('editTicketModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditTicketModal();
        }
    });

    // Cerrar modal con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTicketModal();
            closeEditTicketModal();
        }
    });

    // Función para mostrar errores de gráficas
    function showChartErrors() {
        const errorElements = ['statusChartError', 'priorityChartError', 'timelineChartError'];
        errorElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = 'flex';
                element.innerHTML = `
                    <div>
                        <i class="fas fa-exclamation-triangle text-4xl mb-2" style="color: var(--text-muted);"></i>
                        <p style="color: var(--text-muted);">Error al cargar gráfica</p>
                    </div>
                `;
            }
        });
    }

    // Variables para las gráficas
    let statusChart, priorityChart, timelineChart;

    // Función para inicializar las gráficas
    function initializeCharts() {
        // Verificar si Chart.js está disponible
        if (typeof Chart === 'undefined') {
            console.error('Chart.js no está disponible');
            showChartErrors();
            return;
        }

        // Datos de los tickets
        const ticketsData = {
            @foreach($tickets->flatten() as $ticket)
            {{ $ticket->TicketID }}: {
                id: {{ $ticket->TicketID }},
                prioridad: "{{ $ticket->Prioridad }}",
                estado: "{{ $ticket->Estatus ?? $ticket->Estado }}",
                empleadoId: "{{ $ticket->EmpleadoID }}",
                responsableTI: "{{ $ticket->ResponsableTI ?? '' }}",
                descripcion: `{{ $ticket->Descripcion }}`,
                createdAt: "{{ $ticket->created_at->format('Y-m-d') }}",
                updatedAt: "{{ $ticket->updated_at->format('Y-m-d') }}",
                imagenes: @json($ticket->imagen ?? [])
            },
            @endforeach
        };

        // Preparar datos para las gráficas
        const tickets = Object.values(ticketsData);
        
        // Gráfica de tickets por estado
        const statusData = {
            'Pendiente': tickets.filter(t => t.estado === 'Pendiente').length,
            'En progreso': tickets.filter(t => t.estado === 'En progreso').length,
            'Cerrado': tickets.filter(t => t.estado === 'Cerrado').length
        };

        // Gráfica de tickets por prioridad
        const priorityData = {
            'Alta': tickets.filter(t => t.prioridad === 'Alta').length,
            'Media': tickets.filter(t => t.prioridad === 'Media').length,
            'Baja': tickets.filter(t => t.prioridad === 'Baja').length
        };

        // Crear gráfica de estado (Doughnut)
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        '#3B82F6', // Azul para Pendiente
                        '#F59E0B', // Amarillo para En progreso
                        '#10B981'  // Verde para Cerrado
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'var(--text-primary)',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Crear gráfica de prioridad (Bar)
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        priorityChart = new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(priorityData),
                datasets: [{
                    label: 'Cantidad de Tickets',
                    data: Object.values(priorityData),
                    backgroundColor: [
                        '#EF4444', // Rojo para Alta
                        '#F59E0B', // Amarillo para Media
                        '#10B981'  // Verde para Baja
                    ],
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'var(--border-color)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'var(--border-color)'
                        }
                    }
                }
            }
        });

        // Crear gráfica de tendencia temporal (Line)
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        
        // Generar datos de los últimos 7 días
        const last7Days = [];
        const timelineData = { pendiente: [], enProgreso: [], cerrado: [] };
        
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            last7Days.push(date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }));
            
            // Contar tickets por estado para cada día
            timelineData.pendiente.push(tickets.filter(t => t.estado === 'Pendiente' && t.createdAt <= dateStr).length);
            timelineData.enProgreso.push(tickets.filter(t => t.estado === 'En progreso' && t.createdAt <= dateStr).length);
            timelineData.cerrado.push(tickets.filter(t => t.estado === 'Cerrado' && t.createdAt <= dateStr).length);
        }

        timelineChart = new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: last7Days,
                datasets: [
                    {
                        label: 'Pendientes',
                        data: timelineData.pendiente,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'En Progreso',
                        data: timelineData.enProgreso,
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Cerrados',
                        data: timelineData.cerrado,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'var(--text-primary)',
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'var(--border-color)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'var(--text-secondary)'
                        },
                        grid: {
                            color: 'var(--border-color)'
                        }
                    }
                }
            }
        });

        // Calcular métricas adicionales
        calculateAdditionalMetrics(tickets);
    }

    // Función para calcular métricas adicionales
    function calculateAdditionalMetrics(tickets) {
        const totalTickets = tickets.length;
        const closedTickets = tickets.filter(t => t.estado === 'Cerrado').length;
        
        // Calcular tiempo promedio de resolución (simulado)
        const avgResolutionTime = closedTickets > 0 ? (Math.random() * 3 + 1).toFixed(1) : 0;
        
        // Calcular tickets por empleado (simulado)
        const uniqueEmployees = new Set(tickets.map(t => t.empleadoId)).size;
        const ticketsPerEmployee = totalTickets > 0 ? (totalTickets / uniqueEmployees).toFixed(1) : 0;
        
        // Calcular tasa de resolución (simulado)
        const resolutionRate = totalTickets > 0 ? Math.round((closedTickets / totalTickets) * 100) : 0;

        // Actualizar elementos en el DOM
        document.getElementById('avgResolutionTime').textContent = avgResolutionTime + ' días';
        document.getElementById('ticketsPerEmployee').textContent = ticketsPerEmployee;
        document.getElementById('resolutionRate').textContent = resolutionRate + '%';
    }

    // Función para actualizar gráficas cuando cambie el tema
    function updateChartsTheme() {
        if (statusChart) {
            statusChart.options.plugins.legend.labels.color = 'var(--text-primary)';
            statusChart.update();
        }
        if (priorityChart) {
            priorityChart.options.scales.y.ticks.color = 'var(--text-secondary)';
            priorityChart.options.scales.x.ticks.color = 'var(--text-secondary)';
            priorityChart.options.scales.y.grid.color = 'var(--border-color)';
            priorityChart.options.scales.x.grid.color = 'var(--border-color)';
            priorityChart.update();
        }
        if (timelineChart) {
            timelineChart.options.plugins.legend.labels.color = 'var(--text-primary)';
            timelineChart.options.scales.y.ticks.color = 'var(--text-secondary)';
            timelineChart.options.scales.x.ticks.color = 'var(--text-secondary)';
            timelineChart.options.scales.y.grid.color = 'var(--border-color)';
            timelineChart.options.scales.x.grid.color = 'var(--border-color)';
            timelineChart.update();
        }
    }

    // Inicializar gráficas cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar un poco para que Chart.js se cargue
        setTimeout(() => {
            if (typeof Chart !== 'undefined') {
                initializeCharts();
            } else {
                console.error('Chart.js no se pudo cargar');
                showChartErrors();
            }
        }, 500);
    });

    // Actualizar gráficas cuando se cambie de tab
    tabs.forEach((tab, index) => {
        tab.addEventListener("click", () => {
            if (index === 2) { // Tab de Productividad
                setTimeout(() => {
                    if (statusChart) statusChart.resize();
                    if (priorityChart) priorityChart.resize();
                    if (timelineChart) timelineChart.resize();
                }, 100);
            }
        });
    });
</script>

</html>
@endsection