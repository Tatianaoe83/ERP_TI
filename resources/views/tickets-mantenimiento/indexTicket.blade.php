@php
    $responsables = \App\Models\TicketMantenimiento::obtenerResponsables();
    $responsableId = array_key_first($responsables) ?? '';
    $responsableNombre = $responsables[$responsableId] ?? 'Sin responsable';
@endphp
<style>
    .tox-tinymce { border-radius: 0.5rem !important; border: 1px solid #e5e7eb !important; background-color: #ffffff !important; }
    #editor-mensaje { min-height: 300px; }
    .mantenimiento-container ::-webkit-scrollbar { width: 8px; height: 8px; }
    .mantenimiento-container ::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 4px; }
    .mantenimiento-container ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .dark .tox-tinymce { border: 1px solid #2A2F3A !important; background-color: #0F1115 !important; }
    .dark .tox .tox-edit-area__iframe { background-color: #0F1115 !important; }
    .dark .tox .tox-editor-header { background-color: #1C1F26 !important; border-bottom: 1px solid #2A2F3A !important; }
    .dark .tox .tox-toolbar, .dark .tox .tox-toolbar__primary { background-color: #1C1F26 !important; }
    .dark .tox .tox-tbtn { color: #9CA3AF !important; }
    .dark .mantenimiento-container ::-webkit-scrollbar-track { background: #1C1F26; }
    .dark .mantenimiento-container ::-webkit-scrollbar-thumb { background: #2A2F3A; }
    .dark select { background-color: #374151 !important; color: #ffffff !important; border-color: #4b5563 !important; }
    .swal2-container { z-index: 20000 !important; }
</style>

<div
    x-data="mantenimientoModal()"
    x-init="init()"
    class="mantenimiento-container space-y-4 w-full max-w-full overflow-x-hidden min-h-screen p-6">

    <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2 mb-4">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
            <i class="fas fa-tools mr-2 text-blue-500"></i>Mantenimientos de Compras
        </h2>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-center sm:justify-end">
            <span class="text-xs sm:text-sm text-gray-500 font-medium hidden sm:inline">Vista:</span>
            <div class="flex items-center gap-1 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg p-1">
                <button @click="vista = 'kanban'; localStorage.setItem('mantenimientoVista', 'kanban')"
                    :class="vista === 'kanban' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-columns text-xs"></i><span class="hidden sm:inline">Kanban</span>
                </button>
                <button @click="vista = 'lista'; localStorage.setItem('mantenimientoVista', 'lista')"
                    :class="vista === 'lista' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-list text-xs"></i><span class="hidden sm:inline">Lista</span>
                </button>
                <button @click="vista = 'tabla'; localStorage.setItem('mantenimientoVista', 'tabla')"
                    :class="vista === 'tabla' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-table text-xs"></i><span class="hidden sm:inline">Tabla</span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="vista === 'kanban'" x-transition>@livewire('mantenimiento-tickets-kanban-updater')</div>
    <div x-show="vista === 'lista'" x-transition>@livewire('mantenimiento-tickets-lista-updater')</div>
    <div x-show="vista === 'tabla'" x-transition>@livewire('mantenimiento-tickets-tabla-updater')</div>

    {{-- Modal (misma estructura que tickets de soporte) --}}
    <div
        x-show="mostrar && selected.id"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-3 md:p-0 bg-gray-900/60 backdrop-blur-sm mt-0"
        @click.self="cerrarModal"
        x-cloak>
        <div
            class="flex flex-col w-[95%] md:w-[90%] lg:w-[40%] xl:w-[86%] rounded-2xl overflow-hidden shadow-2xl transition-all duration-300 bg-gray-50 dark:bg-[#1A1D24] border border-transparent dark:border-gray-700 max-h-[calc(100vh-1.5rem)] md:max-h-[95vh] mt-0 min-h-0"
            @click.stop>

            <div class="flex justify-between items-center p-3 md:p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#1A1D24] flex-shrink-0">
                <div class="pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-base md:text-lg font-semibold text-gray-900 dark:text-gray-100 break-words" x-text="selected.asunto"></h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="selected.fecha"></p>
                </div>
                <button @click="cerrarModal" class="transition p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700" aria-label="Cerrar">
                    <span class="text-xl leading-none">×</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[35%_65%] min-h-0 flex-1 rounded-2xl overflow-hidden">
                <aside class="p-4 md:p-6 flex flex-col overflow-y-auto min-h-0 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#0F1116]">
                    <h3 class="text-sm font-semibold mb-4 uppercase text-gray-900 dark:text-gray-100">Propiedades del Mantenimiento</h3>
                    <div class="space-y-5 text-sm flex-1">

                        <div class="rounded-lg p-4 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-xs font-semibold uppercase mb-2 text-gray-500 dark:text-gray-400">Descripción de la solicitud</h3>
                            <div class="font-medium whitespace-pre-wrap text-gray-900 dark:text-gray-100" x-text="selected.descripcion"></div>
                        </div>

                        <div x-show="obtenerAdjuntos().length > 0" class="rounded-lg p-4 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-xs font-semibold uppercase mb-3 text-gray-500 dark:text-gray-400">Documentos Adjuntos</h3>
                            <div class="space-y-2">
                                <template x-for="(adjunto, index) in obtenerAdjuntos()" :key="index">
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate text-gray-700 dark:text-gray-200" x-text="obtenerNombreArchivo(adjunto)"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="obtenerExtensionArchivo(adjunto)"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <a :href="obtenerUrlArchivo(adjunto)" target="_blank" class="p-1.5 rounded text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" title="Ver archivo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            <a :href="obtenerUrlArchivo(adjunto)" download class="p-1.5 rounded hover:bg-green-50 dark:hover:bg-green-900/30" style="color:#22C55E;" title="Descargar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-xs font-semibold uppercase mb-3 text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <i class="fas fa-address-card"></i> Información de Contacto
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-user text-gray-400 w-4 mt-0.5"></i>
                                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100 break-words" x-text="selected.solicitante || 'Sin nombre'"></p>
                                </div>
                                <div class="flex items-start gap-3" x-show="selected.correo">
                                    <i class="fas fa-envelope text-gray-400 w-4 mt-0.5"></i>
                                    <a :href="'mailto:' + selected.correo" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 break-all" x-text="selected.correo"></a>
                                </div>
                                <div class="flex items-center gap-3" x-show="selected.area">
                                    <i class="fas fa-building text-gray-400 w-4"></i>
                                    <div class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-bold text-xs text-gray-500 uppercase mr-1">Área:</span>
                                        <span x-text="selected.area"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg p-4 flex flex-col gap-3 border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-xs font-semibold uppercase mb-2 text-gray-500 dark:text-gray-400">Detalles del Mantenimiento</h3>

                            <div x-show="ticketSla" class="rounded-lg p-3 border text-xs"
                                :class="{
                                    'bg-blue-50 border-blue-100 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300': ticketSla?.estado_sla === 'en_tiempo',
                                    'bg-yellow-50 border-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300': ticketSla?.estado_sla === 'en_riesgo',
                                    'bg-red-50 border-red-100 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300': ['vencido','incumplido'].includes(ticketSla?.estado_sla),
                                    'bg-green-50 border-green-100 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300': ticketSla?.estado_sla === 'cumplido',
                                }">
                                <p class="font-semibold uppercase tracking-wide mb-1"><i class="fas fa-stopwatch mr-1"></i> SLA por prioridad</p>
                                <p x-text="ticketSla?.texto_transcurrido"></p>
                                <p class="font-semibold mt-1" x-text="ticketSla?.texto_restante"></p>
                                <div class="mt-2 h-1.5 rounded-full bg-black/5 dark:bg-white/10 overflow-hidden">
                                    <div class="h-full rounded-full bg-current opacity-70" :style="'width:' + Math.min(100, ticketSla?.porcentaje_uso || 0) + '%'"></div>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Prioridad <span class="text-red-500" x-show="ticketEstatus === 'En proceso' || estatusOriginal === 'En proceso'">*</span></label>
                                <select x-model="ticketPrioridad" :disabled="esFinalizado"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 disabled:opacity-50">
                                    <option value="">Sin prioridad</option>
                                    @foreach(\App\Models\TicketMantenimiento::PRIORIDADES as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                                </select>
                                <p x-show="estaPendiente && !ticketPrioridad" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Asigne prioridad al cambiar el estado a "En proceso" para iniciar el SLA.
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Estado</label>
                                <select x-model="ticketEstatus" @change="actualizarEstadoEditor()" :disabled="esFinalizado"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 disabled:opacity-50">
                                    <template x-for="est in estatusDisponibles" :key="est">
                                        <option :value="est" x-text="est"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Responsable</label>
                                <input type="text" readonly value="{{ $responsableNombre }}"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm border-gray-300 bg-gray-100 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Categoría <span class="text-red-500">*</span></label>
                                <select x-model="ticketCategoria" :disabled="esFinalizado"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm border-gray-300 bg-gray-50 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 disabled:opacity-50">
                                    <option value="">Seleccione</option>
                                    @foreach(\App\Models\TicketMantenimiento::CATEGORIAS as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                                </select>
                            </div>

                            <button @click="guardarTicket()" :disabled="guardando || esFinalizado"
                                class="mt-4 w-full py-2.5 px-4 rounded-lg font-medium shadow-sm transition-all flex items-center justify-center gap-2 text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="!guardando" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg x-show="guardando" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="guardando ? 'Guardando...' : 'Guardar Cambios'"></span>
                            </button>
                        </div>
                    </div>
                </aside>

                <main class="flex flex-col overflow-hidden min-h-0 dark:bg-[#1A1D24]">
                    <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 pb-4 md:pb-6 bg-gray-50 dark:bg-[#0F1116]" id="chat-container">
                        <h3 class="text-sm font-semibold mb-4 uppercase text-gray-900 dark:text-gray-100">Area de Conversaciones</h3>

                        <div class="p-4 transition-opacity duration-200" :class="bloqueadoEnvio ? 'opacity-50 pointer-events-none' : ''">
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">Para:</label>
                                    <input type="email" :value="selected.correo || ''" readonly
                                        class="flex-1 px-3 py-2 rounded-md text-sm border border-gray-300 text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium w-16 flex-shrink-0 text-gray-500 dark:text-gray-400">Asunto: <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="asuntoCorreo" readonly
                                        class="flex-1 px-3 py-2 rounded-md text-sm border cursor-not-allowed bg-gray-100 border-gray-300 text-gray-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-4">
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                        <span class="flex items-center gap-1 whitespace-nowrap">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                            <span class="text-gray-600 dark:text-gray-300">Correos Enviados:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_enviados || 0"></span>
                                        </span>
                                        <span class="flex items-center gap-1 whitespace-nowrap">
                                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                            <span class="text-gray-600 dark:text-gray-300">Respuestas:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_recibidos || 0"></span>
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        Total: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="estadisticas?.total_correos || 0"></span> correos
                                    </div>
                                </div>
                            </div>
                        </div>

                        <template x-for="mensaje in mensajes" :key="mensaje.id">
                            <div class="flex gap-4 mb-4" :class="mensaje.remitente === 'soporte' ? 'justify-end' : 'justify-start'">
                                <div class="flex-shrink-0" :class="mensaje.remitente === 'soporte' ? 'order-2' : 'order-1'">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm"
                                        :class="mensaje.remitente === 'soporte' ? 'bg-blue-600' : 'bg-green-500'"
                                        x-text="obtenerIniciales(mensaje.nombre_remitente)"></div>
                                </div>
                                <div class="flex-1 max-w-[85%]" :class="mensaje.remitente === 'soporte' ? 'order-1 items-end flex flex-col' : 'order-2 items-start flex flex-col'">
                                    <div class="flex flex-wrap items-center gap-2 mb-2" :class="mensaje.remitente === 'soporte' ? 'justify-end' : 'justify-start'">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="mensaje.nombre_remitente"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="mensaje.created_at"></span>
                                        <span x-show="mensaje.es_correo && mensaje.remitente === 'soporte'" class="text-xs px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">📤 Enviado</span>
                                        <span x-show="mensaje.es_correo && mensaje.remitente === 'usuario'" class="text-xs px-2 py-0.5 rounded border border-green-200 bg-green-50 text-green-600 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">📥 Recibido</span>
                                    </div>
                                    <div class="rounded-lg p-4 border shadow-sm w-full text-left"
                                        :class="mensaje.remitente === 'soporte' ? 'bg-blue-50 border-blue-100 dark:bg-blue-900/10 dark:border-blue-800' : 'border-gray-200 dark:bg-[#1C1F26] dark:border-[#2A2F3A]'">
                                        <div x-show="mensaje.es_correo" class="text-xs mb-3 pb-2 border-b border-gray-200 dark:border-gray-700/50 space-y-1">
                                            <div x-show="mensaje.correo_remitente" class="text-gray-600 dark:text-gray-400">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">Desde:</span> <span x-text="mensaje.correo_remitente"></span>
                                            </div>
                                        </div>
                                        <div class="prose prose-sm max-w-none text-gray-800 dark:text-gray-200" x-html="formatearMensaje(mensaje.mensaje)"></div>
                                        <div x-show="mensaje.adjuntos && mensaje.adjuntos.length > 0" class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <div class="text-xs mb-2 font-semibold text-gray-500 dark:text-gray-400">Adjuntos:</div>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="adjunto in mensaje.adjuntos" :key="adjunto.name">
                                                    <a :href="obtenerUrlArchivo(adjunto)" target="_blank"
                                                        class="text-xs px-3 py-1.5 rounded flex items-center gap-2 border border-gray-300 text-blue-600 hover:bg-blue-50 dark:bg-[#2A2F3A] dark:border-gray-600 dark:text-blue-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                        <span x-text="adjunto.name" class="font-medium hover:underline"></span>
                                                    </a>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="mensajes.length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <div class="text-sm text-gray-500 dark:text-gray-400">No hay mensajes aún. Envía una respuesta para iniciar la conversación.</div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:bg-[#1F2937] dark:border-[#2A2F3A]">
                            <div x-show="estaPendiente && !esFinalizado" class="p-4 border-b bg-yellow-50 border-yellow-200 dark:bg-yellow-500/15 dark:border-yellow-500/30">
                                <p class="text-sm flex items-center gap-2 text-yellow-800 dark:text-yellow-400">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>La solicitud está en estado "Pendiente". Para enviar mensajes, cambia el estado a "En proceso" en los detalles.</span>
                                </p>
                            </div>

                            <div class="p-3 transition-opacity duration-200" :class="bloqueadoEnvio ? 'opacity-50 pointer-events-none' : ''">
                                <div x-show="esFinalizado" class="mb-2 p-2 rounded-lg border bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-700/50 dark:text-yellow-200">
                                    <p class="text-xs flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span>Esta solicitud está finalizada. No se pueden agregar nuevos mensajes o adjuntos.</span>
                                    </p>
                                </div>

                                <div class="p-4 dark:bg-[#0F1115]">
                                    <textarea id="editor-mensaje" x-model="nuevoMensaje" :disabled="bloqueadoEnvio"
                                        class="w-full rounded-md border shadow-sm p-3 min-h-[300px] resize-y bg-gray-50 border-gray-300 text-gray-900 dark:bg-[#0F1115] dark:border-[#2A2F3A] dark:text-gray-100 disabled:opacity-50"
                                        placeholder="Escribe tu mensaje aquí..."></textarea>

                                    <div id="drag-drop-area"
                                        :class="bloqueadoEnvio ? 'cursor-not-allowed' : 'cursor-pointer'"
                                        class="border-2 border-dashed rounded-lg p-4 transition-all duration-200 mb-2 bg-blue-50 border-blue-200 dark:bg-blue-900/10 dark:border-blue-800"
                                        @dragover.prevent="handleDragOver($event)"
                                        @dragleave.prevent="handleDragLeave($event)"
                                        @drop.prevent="handleDrop($event)"
                                        @click="!bloqueadoEnvio && document.getElementById('adjuntos').click()">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center">
                                            <svg class="w-8 h-8 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <div>
                                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Arrastra archivos aquí o </span>
                                                <label for="adjuntos" class="text-sm font-medium underline cursor-pointer text-blue-600 dark:text-blue-400" @click.stop>haz clic para seleccionar</label>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, TXT, JPG, PNG, GIF, XLS, XLSX (máx. 10MB)</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end mb-2">
                                        <span x-show="archivosAdjuntos.length > 0" class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            <span x-text="archivosAdjuntos.length"></span> archivo(s) seleccionado(s)
                                        </span>
                                    </div>
                                </div>

                                <div x-show="archivosAdjuntos.length > 0" class="mt-3 space-y-2 px-4">
                                    <template x-for="(archivo, index) in archivosAdjuntos" :key="index">
                                        <div class="flex items-center gap-3 p-2 rounded-lg border bg-gray-50 border-gray-200 dark:bg-[#1F2937] dark:border-[#2A2F3A]">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate text-gray-700 dark:text-gray-200" x-text="archivo.name"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatearTamañoArchivo(archivo.size)"></p>
                                            </div>
                                            <button type="button" @click="eliminarArchivo(index)" :disabled="bloqueadoEnvio"
                                                class="p-1.5 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-50">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <input type="file" id="adjuntos" name="adjuntos[]" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.xlsx,.xls" class="hidden"
                                    :disabled="bloqueadoEnvio" @change="manejarArchivosSeleccionados($event)">

                                <div class="flex justify-end items-center gap-3 mt-4 pt-4 px-4 border-t border-gray-200 dark:border-gray-700/50">
                                    <button type="button" @click="limpiarEditor()" :disabled="bloqueadoEnvio"
                                        class="px-4 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 disabled:opacity-50">
                                        Descartar
                                    </button>
                                    <button @click="enviarRespuesta()"
                                        :disabled="bloqueadoEnvio || !tieneContenido() || !asuntoCorreo || cargando"
                                        class="font-medium py-2 px-6 rounded-lg transition text-sm flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-[#1C1F26] dark:disabled:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                        <span x-text="cargando ? 'Enviando...' : 'Enviar'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>

<script>
function mantenimientoModal() {
    return {
        vista: localStorage.getItem('mantenimientoVista') || 'kanban',
        mostrar: false,
        selected: {},
        mensajes: [],
        estadisticas: null,
        asuntoCorreo: '',
        nuevoMensaje: '',
        archivosAdjuntos: [],
        cargando: false,
        guardando: false,
        ultimoMensajeId: 0,
        verificacionInterval: null,
        tinyMCEInstance: null,
        ticketPrioridad: '',
        ticketEstatus: 'Pendiente',
        estatusOriginal: 'Pendiente',
        transicionesEstatus: @json(\App\Models\TicketMantenimiento::TRANSICIONES),
        responsableFijo: '{{ $responsableId }}',
        ticketResponsable: '{{ $responsableId }}',
        ticketCategoria: '',
        ticketSla: null,

        get esFinalizado() {
            return ['Atendido', 'Cancelado'].includes(this.estatusOriginal);
        },
        get estaPendiente() {
            return this.estatusOriginal === 'Pendiente';
        },
        get bloqueadoEnvio() {
            return this.estaPendiente || this.esFinalizado;
        },
        get estatusDisponibles() {
            if (['Atendido', 'Cancelado'].includes(this.estatusOriginal)) {
                return [this.estatusOriginal];
            }
            const transiciones = this.transicionesEstatus[this.estatusOriginal] || [];
            return [this.estatusOriginal, ...transiciones];
        },

        init() {
            Livewire.on('mantenimiento-actualizados-kanban', (d) => this.procesarActualizacion(d));
            Livewire.on('mantenimiento-actualizados-lista', (d) => this.procesarActualizacion(d));
            Livewire.on('mantenimiento-actualizados-tabla', (d) => this.procesarActualizacion(d));
        },

        procesarActualizacion(datos) {
            if (!datos?.ticketsStatus) return;
            const counts = {
                pendiente: datos.ticketsStatus.pendiente?.length || 0,
                en_proceso: datos.ticketsStatus.en_proceso?.length || 0,
                pausado: datos.ticketsStatus.pausado?.length || 0,
                atendido: datos.ticketsStatus.atendido?.length || 0,
                cancelado: datos.ticketsStatus.cancelado?.length || 0,
            };
            Object.keys(counts).forEach(cat => {
                document.querySelectorAll(`[data-categoria-header="${cat}"]`).forEach(el => { el.textContent = counts[cat]; });
            });
        },

        abrirModalDesdeElemento(el) {
            const id = el.dataset.ticketId;
            this.selected = {
                id,
                asunto: `Mantenimiento #${id}`,
                descripcion: el.dataset.ticketDescripcion || '',
                solicitante: el.dataset.ticketSolicitante || '',
                correo: el.dataset.ticketCorreo || '',
                area: el.dataset.ticketArea || '',
                fecha: el.dataset.ticketFecha || '',
                estatus: el.dataset.ticketEstatus || 'Pendiente',
                imagen: el.dataset.ticketImagen || '',
            };
            this.ticketPrioridad = el.dataset.ticketPrioridad || '';
            this.ticketEstatus = el.dataset.ticketEstatus || 'Pendiente';
            this.estatusOriginal = el.dataset.ticketEstatus || 'Pendiente';
            this.ticketResponsable = this.responsableFijo;
            this.ticketCategoria = el.dataset.ticketCategoria || '';
            try {
                this.ticketSla = el.dataset.ticketSla ? JSON.parse(el.dataset.ticketSla) : null;
            } catch (e) {
                this.ticketSla = null;
            }
            this.asuntoCorreo = `Re: Mantenimiento #${id}`;
            this.nuevoMensaje = '';
            this.archivosAdjuntos = [];
            this.mensajes = [];
            this.estadisticas = null;
            this.mostrar = true;

            this.cargarMensajes();
            this.iniciarVerificacionMensajes();
            this.$nextTick(() => {
                if (!this.tinyMCEInstance) this.inicializarTinyMCE();
                else this.actualizarEstadoEditor();
            });
        },

        cerrarModal() {
            this.detenerVerificacionMensajes();
            if (this.tinyMCEInstance) {
                try { tinymce.remove('editor-mensaje'); } catch (e) {}
                this.tinyMCEInstance = null;
            }
            this.mostrar = false;
            this.selected = {};
            this.ticketSla = null;
            this.mensajes = [];
            this.asuntoCorreo = '';
            this.nuevoMensaje = '';
            this.archivosAdjuntos = [];
        },

        async cargarMensajes() {
            if (!this.selected.id) return;
            try {
                const response = await fetch(`/tickets-mantenimiento/chat-messages?mantenimiento_id=${this.selected.id}`);
                const data = await response.json();
                if (data.success) {
                    this.mensajes = data.messages;
                    this.ultimoMensajeId = this.mensajes.length ? Math.max(...this.mensajes.map(m => m.id)) : 0;
                    await this.marcarMensajesComoLeidos();
                    this.estadisticas = await this.obtenerEstadisticasCorreos();
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) { console.error('Error cargando mensajes:', e); }
        },

        iniciarVerificacionMensajes() {
            this.detenerVerificacionMensajes();
            this.verificacionInterval = setInterval(() => this.verificarMensajesNuevos(), 15000);
        },
        detenerVerificacionMensajes() {
            if (this.verificacionInterval) { clearInterval(this.verificacionInterval); this.verificacionInterval = null; }
            this.ultimoMensajeId = 0;
        },
        async verificarMensajesNuevos() {
            if (!this.selected.id || !this.mostrar) return;
            try {
                const r = await fetch(`/tickets-mantenimiento/verificar-mensajes-nuevos?mantenimiento_id=${this.selected.id}&ultimo_mensaje_id=${this.ultimoMensajeId}`);
                const data = await r.json();
                if (data.success && data.tiene_nuevos) await this.cargarMensajes();
            } catch (e) {}
        },
        async marcarMensajesComoLeidos() {
            if (!this.selected.id) return;
            try {
                await fetch('/tickets-mantenimiento/marcar-leidos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ mantenimiento_id: this.selected.id }),
                });
            } catch (e) {}
        },
        async obtenerEstadisticasCorreos() {
            if (!this.selected.id) return null;
            try {
                const r = await fetch(`/tickets-mantenimiento/estadisticas-correos?mantenimiento_id=${this.selected.id}`);
                const data = await r.json();
                return data.success ? data.estadisticas : null;
            } catch (e) { return null; }
        },

        cargarTinyMCE() {
            if (window.tinymce) return Promise.resolve();
            if (window.__tinyMCELoadPromise) return window.__tinyMCELoadPromise;
            window.__tinyMCELoadPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
                script.defer = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
            return window.__tinyMCELoadPromise;
        },

        async inicializarTinyMCE() {
            if (!document.getElementById('editor-mensaje') || this.tinyMCEInstance) return;
            if (typeof tinymce === 'undefined') { try { await this.cargarTinyMCE(); } catch (e) { return; } }
            if (typeof tinymce === 'undefined') return;
            if (tinymce.get('editor-mensaje')) tinymce.remove('editor-mensaje');

            const isDarkMode = document.documentElement.classList.contains('dark');
            tinymce.init({
                selector: '#editor-mensaje',
                height: 300,
                menubar: false,
                plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'],
                toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | removeformat | link image | code | help',
                content_style: isDarkMode
                    ? 'body { font-family: Arial, sans-serif; font-size: 14px; background-color: #1f2937 !important; color: #ffffff !important; } body * { color: #ffffff !important; }'
                    : 'body { font-family: Arial, sans-serif; font-size: 14px; }',
                language: 'es',
                placeholder: 'Escribe tu mensaje aquí...',
                automatic_uploads: true,
                paste_data_images: true,
                images_upload_handler: (blobInfo) => new Promise((resolve) => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) { resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64()); return; }
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename() || 'imagen.png');
                    fetch('/tickets/subir-imagen-tinymce', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
                        body: formData,
                    }).then(r => r.json().then(d => ({ status: r.status, data: d })))
                      .then(({ status, data }) => resolve(status === 200 && data.location ? data.location : 'data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64()))
                      .catch(() => resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64()));
                }),
                setup: (editor) => {
                    this.tinyMCEInstance = editor;
                    ['input', 'change', 'keyup', 'NodeChange'].forEach(ev => editor.on(ev, () => { this.nuevoMensaje = editor.getContent(); }));
                    editor.on('init', () => this.actualizarEstadoEditor());
                },
            });
        },

        actualizarEstadoEditor() {
            const bloqueado = this.bloqueadoEnvio;
            if (this.tinyMCEInstance) {
                try { this.tinyMCEInstance.mode.set(bloqueado ? 'readonly' : 'design'); } catch (e) {}
            }
            const textarea = document.getElementById('editor-mensaje');
            if (textarea) textarea.disabled = bloqueado;
        },

        tieneContenido() {
            if (this.tinyMCEInstance) {
                try {
                    const texto = this.tinyMCEInstance.getContent().replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
                    if (texto.length > 0) return true;
                } catch (e) {}
            }
            return (this.nuevoMensaje || '').replace(/<[^>]*>/g, '').trim().length > 0;
        },

        limpiarEditor() {
            this.nuevoMensaje = '';
            if (this.tinyMCEInstance) this.tinyMCEInstance.setContent('');
            this.archivosAdjuntos = [];
            const input = document.getElementById('adjuntos');
            if (input) input.value = '';
        },

        manejarArchivosSeleccionados(event) {
            this.procesarArchivos(Array.from(event.target.files || []));
        },

        procesarArchivos(files) {
            const tipos = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png', '.gif', '.xlsx', '.xls'];
            const max = 10 * 1024 * 1024;
            files.forEach(file => {
                const ext = '.' + file.name.split('.').pop().toLowerCase();
                if (!tipos.includes(ext)) { alert(`El archivo "${file.name}" no es un tipo permitido`); return; }
                if (file.size > max) { alert(`El archivo "${file.name}" excede 10MB`); return; }
                this.archivosAdjuntos.push(file);
            });
            const input = document.getElementById('adjuntos');
            if (input) {
                const dt = new DataTransfer();
                this.archivosAdjuntos.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }
        },

        handleDragOver(e) {
            if (this.bloqueadoEnvio) return;
            e.preventDefault();
            const area = document.getElementById('drag-drop-area');
            if (area) { area.style.backgroundColor = 'rgba(59,130,246,0.15)'; area.style.borderColor = '#3B82F6'; area.style.borderStyle = 'solid'; }
        },
        handleDragLeave(e) {
            if (this.bloqueadoEnvio) return;
            e.preventDefault();
            const area = document.getElementById('drag-drop-area');
            if (area && !area.contains(e.relatedTarget)) {
                area.style.backgroundColor = '';
                area.style.borderColor = '';
                area.style.borderStyle = 'dashed';
            }
        },
        handleDrop(e) {
            if (this.bloqueadoEnvio) return;
            e.preventDefault();
            const area = document.getElementById('drag-drop-area');
            if (area) { area.style.backgroundColor = ''; area.style.borderColor = ''; area.style.borderStyle = 'dashed'; }
            this.procesarArchivos(Array.from(e.dataTransfer.files || []));
        },
        eliminarArchivo(index) {
            this.archivosAdjuntos.splice(index, 1);
            const input = document.getElementById('adjuntos');
            if (input) {
                const dt = new DataTransfer();
                this.archivosAdjuntos.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }
        },
        formatearTamañoArchivo(bytes) {
            if (!bytes) return '0 Bytes';
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + ['Bytes', 'KB', 'MB', 'GB'][i];
        },

        async enviarRespuesta() {
            if (this.cargando || this.bloqueadoEnvio) return;
            let contenido = this.tinyMCEInstance ? this.tinyMCEInstance.getContent() : this.nuevoMensaje;
            if (!contenido || contenido === '<p><br></p>' || !contenido.replace(/<[^>]*>/g, '').trim()) {
                Swal.fire({ icon: 'warning', title: 'Mensaje vacío', text: 'Escribe un mensaje antes de enviar.' });
                return;
            }

            this.cargando = true;
            try {
                const formData = new FormData();
                formData.append('mantenimiento_id', this.selected.id);
                formData.append('mensaje', contenido);
                formData.append('asunto', this.asuntoCorreo);
                this.archivosAdjuntos.forEach(f => formData.append('adjuntos[]', f));

                const response = await fetch('/tickets-mantenimiento/enviar-respuesta', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (data.success) {
                    this.limpiarEditor();
                    await this.cargarMensajes();
                    Swal.fire({ icon: 'success', title: 'Enviado', text: data.message, timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo enviar' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al enviar' });
            } finally {
                this.cargando = false;
            }
        },

        async guardarTicket() {
            if (!this.selected.id) return;
            this.guardando = true;
            try {
                const response = await fetch('/tickets-mantenimiento/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ticketId: this.selected.id, prioridad: this.ticketPrioridad, estatus: this.ticketEstatus, responsable: this.ticketResponsable, categoria: this.ticketCategoria }),
                });
                const data = await response.json();
                if (data.success) {
                    this.selected.estatus = this.ticketEstatus;
                    this.estatusOriginal = this.ticketEstatus;
                    this.actualizarEstadoEditor();
                    Swal.fire({ icon: 'success', title: 'Guardado', text: data.message, timer: 2000, showConfirmButton: false });
                    Livewire.emit('mantenimiento-estatus-actualizado');
                } else {
                    this.ticketEstatus = this.estatusOriginal;
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al guardar' });
            } finally {
                this.guardando = false;
            }
        },

        scrollToBottom() {
            const c = document.getElementById('chat-container');
            if (c) c.scrollTop = c.scrollHeight;
        },
        obtenerIniciales(n) { return !n ? '??' : n.split(' ').map(x => x[0]).join('').toUpperCase().slice(0, 2); },
        formatearMensaje(m) {
            if (!m) return '';
            if (!/<[a-z][\s\S]*>/i.test(m)) return m.replace(/\n/g, '<br>').replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>');
            return m;
        },
        obtenerAdjuntos() {
            if (!this.selected?.imagen) return [];
            try {
                const a = typeof this.selected.imagen === 'string' ? JSON.parse(this.selected.imagen) : this.selected.imagen;
                return Array.isArray(a) ? a : (this.selected.imagen ? [this.selected.imagen] : []);
            } catch (e) { return this.selected.imagen ? [this.selected.imagen] : []; }
        },
        obtenerNombreArchivo(r) {
            if (!r) return 'Archivo';
            const s = typeof r === 'object' ? (r.name || r.path || r.url || '') : r;
            const n = String(s).split('/').pop();
            return n.includes('_') ? n.substring(n.indexOf('_') + 1) : n;
        },
        obtenerExtensionArchivo(r) {
            const n = this.obtenerNombreArchivo(r);
            const p = n.lastIndexOf('.');
            return p === -1 ? 'Sin extensión' : n.substring(p + 1).toUpperCase();
        },
        obtenerUrlArchivo(r) {
            if (!r) return '#';
            if (typeof r === 'string' && (r.startsWith('http://') || r.startsWith('https://'))) return r;
            if (typeof r === 'object' && r !== null) {
                if (r.url) return r.url;
                if (r.storage_path) return '/storage/' + r.storage_path.replace(/^\/+/, '');
                if (r.path) return r.path.startsWith('/') ? r.path : '/storage/' + r.path.replace(/^\/+/, '');
            }
            return typeof r === 'string' ? (r.startsWith('/storage/') ? r : '/storage/' + r.replace(/^\/+/, '')) : '#';
        },
    };
}
</script>
