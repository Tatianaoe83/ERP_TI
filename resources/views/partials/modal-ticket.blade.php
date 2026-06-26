    <!-- modal -->
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
            class="flex flex-col w-[95%] md:w-[90%] lg:w-[40%] xl:w-[86%] rounded-2xl overflow-hidden shadow-2xl transition-all duration-300
           bg-gray-50 dark:bg-[#1A1D24] 
           border border-transparent dark:border-gray-700
           max-h-[calc(100vh-1.5rem)] md:max-h-[95vh] mt-0 min-h-0"
            @click.stop>

            <!-- Header fijo: Propiedades del Ticket + botón cerrar -->
            <div class="flex justify-between items-center p-3 md:p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#1A1D24] flex-shrink-0">
                <!-- Título del ticket (en scroll) -->
                <div class="pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-base md:text-lg font-semibold text-gray-900 dark:text-gray-100 break-words"
                        x-text="selected.asunto"></h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="selected.fecha"></p>
                </div>
                <button @click="cerrarModal"
                    class="transition p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 touch-manipulation"
                    aria-label="Cerrar">
                    <span class="text-xl leading-none">×</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[35%_65%] min-h-0 flex-1 rounded-2xl overflow-hidden">
                <aside class="p-4 md:p-6 flex flex-col overflow-y-auto min-h-0 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#0F1116]">
                    <h3 class="text-sm font-semibold mb-4 uppercase text-gray-900 dark:text-gray-100">
                        Propiedades del Ticket
                    </h3>
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
         <div class="flex items-center gap-3" x-show="selected.puesto">
            <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Puesto:</span>
                <span x-text="selected.puesto" class="font-mono"></span>
            </div>
        </div>
        <div class="flex items-center gap-3" x-show="selected.departamento">
            <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center">
                <i class="fas fa-building"></i>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Departamento:</span>
                <span x-text="selected.departamento" class="font-mono"></span>
            </div>
        </div>
         <div class="flex items-center gap-3" x-show="selected.gerencia">
            <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Gerencia:</span>
                <span x-text="selected.gerencia" class="font-mono"></span>
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
                   border-gray-300 bg-gray-50 text-gray-900 
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
                                    @change="verificarCambioEstatus($event.target.value)" 
                                    :disabled="selected.estatus === 'Cerrado'"
                                    class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
                   border-gray-300 bg-gray-50 text-gray-900 
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
                   border-gray-300 bg-gray-50 text-gray-900 
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
                   border-gray-300 bg-gray-50 text-gray-900 
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
                   border-gray-300 bg-gray-50 text-gray-900 
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
                   border-gray-300 bg-gray-50 text-gray-900 
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
                   border-gray-300 bg-gray-50 text-gray-900 
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

<main class="flex flex-col overflow-hidden min-h-0 dark:bg-[#1A1D24]">

                    <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 pb-4 md:pb-6 bg-gray-50 dark:bg-[#0F1116]" id="chat-container">

                        <h3 class="text-sm font-semibold mb-4 uppercase text-gray-900 dark:text-gray-100">
                            Area de Conversaciones
                        </h3>

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
                    <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 pb-4 md:pb-6 bg-gray-50 dark:bg-[#0F1116]" id="chat-container"> <!-- Mensajes dinámicos del chat -->


                        <!-- Barra de estadísticas y Ticket Resuelto (scroll con mensajes) -->
                        <div class="space-y-4 mb-6">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-4">
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                        <span class="flex items-center gap-1 whitespace-nowrap">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></span>
                                            <span class="text-gray-600 dark:text-gray-300">Correos Enviados:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_enviados || 0"></span>
                                        </span>
                                        <span class="flex items-center gap-1 whitespace-nowrap">
                                            <span class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0"></span>
                                            <span class="text-gray-600 dark:text-gray-300">Respuestas:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_recibidos || 0"></span>
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        Total: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="estadisticas?.total_correos || 0"></span> correos
                                    </div>
                                </div>
                            </div>

                            <div x-show="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado'"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="rounded-lg border overflow-hidden shadow-sm bg-green-50 dark:bg-[#1C1F26] border-green-200 dark:border-green-800">
                                <div class="px-4 py-3 bg-green-100 dark:bg-green-900/40 border-b border-green-200 dark:border-green-800 flex items-center gap-2">
                                    <div class="p-1 bg-green-200 dark:bg-green-800 rounded-full text-green-700 dark:text-green-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <h3 class="font-bold text-green-800 dark:text-green-200 text-sm">Ticket Resuelto</h3>
                                </div>
                                <div class="p-4">
                                    <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                        Detalle de la solución:
                                    </div>
                                    <div class="prose prose-sm max-w-none text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed"
                                         x-text="selected.resolucion">
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                                    <a :href="obtenerUrlArchivo(adjunto)" 
                                                    target="_blank" 
                                                    class="text-xs px-3 py-1.5 rounded flex items-center gap-2 transition-colors bg-GRAY-50 border border-gray-300 text-blue-600 hover:bg-blue-50 dark:bg-[#2A2F3A] dark:border-gray-600 dark:text-blue-400 dark:hover:bg-gray-700 cursor-pointer shadow-sm">
                                                        
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                        </svg>
                                                        <span x-text="adjunto.name" class="font-medium hover:underline"></span>
                                                    </a>
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
                   bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400
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
                                            <p class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, TXT, JPG, PNG, GIF, XLS, XLSX (máx. 10MB por archivo)</p>
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
                    bg-gray-50 border-gray-200 hover:bg-gray-50
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
                                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.xlsx,.xls"
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
                                        :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || (selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') || !tieneContenido() || !asuntoCorreo || asuntoCorreo.trim().length === 0 || cargando"
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
                       bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400
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
                       bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                       dark:bg-[#1F2937] dark:border-[#2A2F3A] dark:text-gray-100 dark:placeholder-gray-500"
                                            placeholder="correo@usuario.com">
                                    </div>
                                </div>

                                <textarea
                                    x-model="respuestaManual.mensaje"
                                    class="w-full h-20 p-3 rounded-lg resize-none text-sm border shadow-sm transition-colors
               bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400
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
