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

    .swal2-container {
        z-index: 20000 !important;
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
        const vistaGuardada = localStorage.getItem('ticketsVista') || 'kanban';
        vista = vistaGuardada;
        init();
        window.__abrirModalTicket = (datos) => abrirModal(datos);
        $watch('mostrar', value => {
            window.dispatchEvent(new CustomEvent(value ? 'abrir-modal-overlay' : 'cerrar-modal-overlay'));
        });
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
                    @click="vista = 'lista'; localStorage.setItem('ticketsVista', 'lista')"
                    :class="vista === 'lista' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 flex-1 sm:flex-initial justify-center">
                    <i class="fas fa-list text-xs"></i>
                    <span class="hidden sm:inline">Lista</span>
                </button>
                <button
                    @click="vista = 'tabla'; localStorage.setItem('ticketsVista', 'tabla')"
                    :class="vista === 'tabla' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 flex-1 sm:flex-initial justify-center">
                    <i class="fas fa-table text-xs"></i>
                    <span class="hidden sm:inline">Tabla</span>
                </button>
            </div>
        </div>
    </div>

    <!-- KANBAN liveWire -->

    <div class="kanban-root w-full h-full" x-show="vista === 'kanban'" x-transition>
    @livewire('tickets-kanban-updater')
    </div>


    <!-- Vista Lista Livewire -->
    <div class="space-y-4 w-full max-w-full overflow-x-hidden pb-6" x-show="vista === 'lista'" x-transition>
    @livewire('tickets-lista-updater')
    </div>

    <!-- Vista Tabla Livewire -->
    <div x-show="vista === 'tabla'" x-transition class="w-full">
    
    @livewire('tickets-tabla-updater')

    </div>



    @include('partials.modal-ticket')

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

<!-- Lightbox para imágenes del chat -->
<div 
    id="lightbox-overlay"
    onclick="cerrarLightbox()"
    style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(0,0,0,0.92); cursor:zoom-out;"
    class="flex items-center justify-center p-4">
    
    <!-- Botón cerrar -->
    <button 
        onclick="cerrarLightbox()" 
        style="position:absolute; top:1rem; right:1rem; z-index:1000000; color:white; background:rgba(255,255,255,0.15); border:none; border-radius:9999px; width:2.5rem; height:2.5rem; font-size:1.25rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">
        ×
    </button>

    <!-- Botones anterior / siguiente -->
    <button 
        id="lightbox-prev"
        onclick="event.stopPropagation(); navegarLightbox(-1)"
        style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); z-index:1000000; color:white; background:rgba(255,255,255,0.15); border:none; border-radius:9999px; width:2.5rem; height:2.5rem; font-size:1.25rem; cursor:pointer; display:none; align-items:center; justify-content:center;">
        ‹
    </button>
    <button 
        id="lightbox-next"
        onclick="event.stopPropagation(); navegarLightbox(1)"
        style="position:absolute; right:4rem; top:50%; transform:translateY(-50%); z-index:1000000; color:white; background:rgba(255,255,255,0.15); border:none; border-radius:9999px; width:2.5rem; height:2.5rem; font-size:1.25rem; cursor:pointer; display:none; align-items:center; justify-content:center;">
        ›
    </button>

    <!-- Imagen principal -->
    <img 
        id="lightbox-img" 
        src="" 
        alt="Imagen ampliada"
        onclick="event.stopPropagation()"
        style="max-width:90vw; max-height:90vh; object-fit:contain; border-radius:0.5rem; box-shadow:0 25px 60px rgba(0,0,0,0.5); cursor:default;">
    
    <!-- Contador -->
    <div id="lightbox-counter" style="position:absolute; bottom:1rem; left:50%; transform:translateX(-50%); color:rgba(255,255,255,0.7); font-size:0.85rem; background:rgba(0,0,0,0.4); padding:0.25rem 0.75rem; border-radius:9999px;"></div>
</div>

<style>
/* Miniaturas de imágenes en el chat */
.chat-img-thumb {
    display: inline-block;
    cursor: zoom-in;
    margin: 4px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    vertical-align: top;
}
.chat-img-thumb:hover {
    border-color: #3B82F6;
    transform: scale(1.03);
    box-shadow: 0 4px 15px rgba(59,130,246,0.4);
}
.chat-img-thumb img {
    width: 120px;
    height: 90px;
    object-fit: cover;
    display: block;
    border-radius: 6px;
}
.chat-img-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
</style>

<script>
// ── Lightbox global ──────────────────────────────────────────────────────────
    let _lightboxImagenes = [];
    let _lightboxIndice   = 0;

    function abrirLightbox(srcs, indice) {
        _lightboxImagenes = Array.isArray(srcs) ? srcs : [srcs];
        _lightboxIndice   = indice || 0;

        const overlay = document.getElementById('lightbox-overlay');
        const img     = document.getElementById('lightbox-img');
        const prev    = document.getElementById('lightbox-prev');
        const next    = document.getElementById('lightbox-next');
        const counter = document.getElementById('lightbox-counter');

        img.src = _lightboxImagenes[_lightboxIndice];

        // Mostrar/ocultar flechas
        const multiple = _lightboxImagenes.length > 1;
        prev.style.display    = multiple ? 'flex' : 'none';
        next.style.display    = multiple ? 'flex' : 'none';
        counter.style.display = multiple ? 'block' : 'none';

        if (multiple) {
            counter.textContent = `${_lightboxIndice + 1} / ${_lightboxImagenes.length}`;
        }

        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function cerrarLightbox() {
        document.getElementById('lightbox-overlay').style.display = 'none';
        document.getElementById('lightbox-img').src = '';
        document.body.style.overflow = '';
    }

    function navegarLightbox(direccion) {
        _lightboxIndice = (_lightboxIndice + direccion + _lightboxImagenes.length) % _lightboxImagenes.length;
        document.getElementById('lightbox-img').src = _lightboxImagenes[_lightboxIndice];
        document.getElementById('lightbox-counter').textContent = `${_lightboxIndice + 1} / ${_lightboxImagenes.length}`;
    }

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarLightbox();
        if (e.key === 'ArrowRight') navegarLightbox(1);
        if (e.key === 'ArrowLeft')  navegarLightbox(-1);
    });
</script>

@include('partials.tickets-modal-engine')
</div>