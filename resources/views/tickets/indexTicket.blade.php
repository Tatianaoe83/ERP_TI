<div
    x-data="ticketsModal()"
    x-init="init()"
    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
    @foreach (['nuevos' => 'Nuevos', 'proceso' => 'En Progreso', 'resueltos' => 'Resueltos'] as $key => $titulo)
    <div class="p-4 text-center shadow-lg rounded-md bg-white border border-gray-100">
        <div class="border-b font-semibold text-gray-700 mb-2">{{ $titulo }}</div>

        <div class="relative w-full h-[505px]">
            <div class="absolute inset-0 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                @forelse ($ticketsStatus[$key] as $ticket)
                @php
                $partes = preg_split('/\s+/', trim($ticket->empleado->NombreEmpleado));
                if (count($partes) >= 3) array_splice($partes, 1, 1);
                $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();
                @endphp

                <div
                    class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition p-4 text-left cursor-pointer"
                    data-ticket-id="{{ $ticket->TicketID }}"
                    data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                    data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion, ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-prioridad="{{ $ticket->Prioridad }}"
                    data-ticket-empleado="{{ $nombreFormateado }}"
                    data-ticket-anydesk="{{ $ticket->CodeAnyDesk }}"
                    data-ticket-numero="{{ $ticket->Numero }}"
                    data-ticket-correo="{{ $ticket->empleado->Correo }}"
                    data-ticket-fecha="{{ $ticket->created_at->format('d/m/Y H:i:s') }}"
                    @click="abrirModalDesdeElemento($el)">
                    <div class="flex justify-between items-start">
                        <h3 class="text-sm font-semibold text-gray-800 truncate">
                            Ticket #{{ $ticket->TicketID }} 
                        </h3>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap {{ $ticket->Prioridad == 'Baja' ? 'bg-green-200 text-green-600' : ($ticket->Prioridad == 'Media' ? 'bg-yellow-200 text-yellow-600' : 'bg-red-200 text-red-600') }}">
                            {{ $ticket->Prioridad  }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">
                        {{ Str::limit($ticket->Descripcion, 100, '...') }}
                    </p>

                    <div class="flex justify-between items-center mt-3 text-xs text-gray-500">
                        <span class="font-semibold text-gray-700">
                            {{ $nombreFormateado }}
                        </span>
                        <span>{{ $ticket->created_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400 mt-10">No hay tickets en esta categoría.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endforeach

    <div
        x-show="mostrar && selected.id"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed inset-0 flex items-center justify-center bg-gray-900/40 backdrop-blur-md z-50"
        @click.self="cerrarModal"
        x-cloak>
        <div
            class="bg-white w-11/12 md:w-4/5 lg:w-[1100px] xl:w-[1200px] rounded-2xl overflow-hidden shadow-2xl border border-gray-200 transition-all duration-300"
            @click.stop>
            <div class="grid grid-cols-1 md:grid-cols-[35%_65%] h-[90vh] bg-white rounded-2xl overflow-hidden">

                <aside class="bg-gray-50 border-r border-gray-200 p-6 flex flex-col overflow-y-auto">
                    <h2 class="text-gray-800 text-sm font-semibold mb-4 uppercase">
                        Propiedades del Ticket
                    </h2>

                    <div class="space-y-5 text-sm text-gray-700 flex-1">

                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Descripcion de ticket</h3>
                            <div class="font-medium text-gray-800 whitespace-pre-wrap ticket-description" x-text="selected.descripcion"></div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Información de Contacto</h3>
                            <p class="font-medium text-gray-800" x-text="selected.empleado"></p>
                            <p class="text-gray-500 text-sm" x-text="selected.correo"></p>
                            <p class="text-gray-500 text-sm" x-text="selected.numero"></p>
                            <p class="text-gray-500 text-sm" x-text="selected.anydesk"></p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex flex-col gap-3">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Detalles del Ticket</h3>

                            <label class="text-md font-semibold text-gray-600">Prioridad</label>
                            <select
                                class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Baja</option>
                                <option>Media</option>
                                <option>Alta</option>
                            </select>

                            <label class="text-md font-semibold text-gray-600">Estado</label>
                            <select class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Pendiente</option>
                                <option>En progreso</option>
                                <option>Cerrado</option>
                            </select>

                            <label class="text-md font-semibold text-gray-600">Responsable <span class="text-red-500">*</span></label>
                            <select class="w-full mt-1 mb-2 0 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option required value="">Seleccione</option>
                                @foreach($responsablesTI as $responsable)
                                <option value="{{ $responsable->EmpleadoID }}">{{ $responsable->NombreEmpleado }}</option>
                                @endforeach
                            </select>

                            <label class="text-md font-semibold text-gray-600">Categoria <span class="text-red-500">*</span></label>
                            <select id="tipo-select" class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option required value="">Seleccione</option>
                            </select>
                            
                            <label class="text-md font-semibold text-gray-600">Grupo <span class="text-red-500">*</span></label>
                            <select id="subtipo-select" class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black" disabled>
                                <option required value="">Seleccione</option>
                            </select>
                            
                            <label class="text-md font-semibold text-gray-600">Subgrupo</label>
                            <select id="tertipo-select" class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black" disabled>
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                </aside>

                <main class="flex flex-col overflow-hidden">
                    <!-- Header del Ticket -->
                    <div class="flex justify-between items-start p-6 border-b border-gray-200">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-800 mb-1" x-text="selected.asunto"></h1>
                            <p class="text-sm text-gray-500">
                                <span x-text="selected.fecha"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                           
                            <!--<button 
                                @click="diagnosticarCorreos()"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Diagnosticar Sistema</span>
                            </button>  -->
                            <!--<button 
                                @click="enviarInstrucciones()"
                                class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>Enviar Instrucciones</span>
                            </button>   -->
                            <button 
                                @click="procesarRespuestasAutomaticas()"
                                :disabled="procesandoAutomatico"
                                class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span x-text="procesandoAutomatico ? 'Procesando...' : 'Procesar Automático'"></span>
                            </button>
                            
                            <button 
                                @click="buscarCorreosUsuarios()"
                                :disabled="buscandoCorreos || !selected.id"
                                class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <span x-text="buscandoCorreos ? 'Buscando...' : 'Buscar Correos de Usuarios'"></span>
                            </button>
                            
                            <button 
                                @click="guardarCorreosEncontrados()"
                                :disabled="guardandoCorreos || !selected.id"
                                class="bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                <span x-text="guardandoCorreos ? 'Guardando...' : 'Guardar Correos en Historial'"></span>
                            </button>
                           
                            <button 
                                @click="probarConexionWebklex()"
                                class="bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Probar Conexión</span>
                            </button>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                Responder A Todos
                            </button>
                            <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 transition p-2">
                                <span class="text-xl">Cerrar</span>
                            </button>
                        </div>
                    </div>

                  
                    <!-- Estadísticas de Correos -->
                    <div class="border-b border-gray-200 p-4 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 text-sm">
                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    <span class="text-gray-600">Correos Enviados:</span>
                                    <span class="font-semibold" x-text="estadisticas?.correos_enviados || 0"></span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-gray-600">Respuestas:</span>
                                    <span class="font-semibold" x-text="estadisticas?.correos_recibidos || 0"></span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                    <span class="text-gray-600">No Leídos:</span>
                                    <span class="font-semibold" x-text="estadisticas?.correos_no_leidos || 0"></span>
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                Total: <span class="font-semibold" x-text="estadisticas?.total_correos || 0"></span> correos
                            </div>
                        </div>
                    </div>

                    <!-- Área de Conversaciones -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6" id="chat-container">
                        <!-- Mensajes dinámicos del chat -->
                        <template x-for="mensaje in mensajes" :key="mensaje.id">
                            <div class="flex gap-4" :class="mensaje.remitente === 'soporte' ? 'justify-end' : 'justify-start'">
                                <div class="flex-shrink-0" :class="mensaje.remitente === 'soporte' ? 'order-2' : 'order-1'">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold text-sm"
                                         :class="mensaje.remitente === 'soporte' ? 'bg-blue-500' : 'bg-green-500'"
                                         x-text="obtenerIniciales(mensaje.nombre_remitente)">
                                    </div>
                                </div>
                                <div class="flex-1" :class="mensaje.remitente === 'soporte' ? 'order-1' : 'order-2'">
                                <div class="flex items-center gap-2 mb-2">
                                        <span class="font-semibold text-gray-800" x-text="mensaje.nombre_remitente"></span>
                                        <span class="text-sm text-gray-500" x-text="mensaje.created_at"></span>
                                        <span x-show="mensaje.es_correo && mensaje.remitente === 'soporte'" class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded flex items-center gap-1">
                                            📤 Correo Enviado
                                        </span>
                                        <span x-show="mensaje.es_correo && mensaje.remitente === 'usuario'" class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded flex items-center gap-1">
                                            📥 Respuesta Recibida
                                        </span>
                                        <span x-show="!mensaje.es_correo" class="text-xs text-gray-600 bg-gray-50 px-2 py-1 rounded flex items-center gap-1">
                                            💬 Nota Interna
                                        </span>
                                        <span x-show="mensaje.thread_id" class="text-xs text-purple-600 bg-purple-50 px-2 py-1 rounded flex items-center gap-1">
                                            🔗 En Hilo
                                        </span>
                                        <span x-show="!mensaje.leido" class="text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded flex items-center gap-1">
                                            ⚠ No Leído
                                        </span>
                                    </div>
                                    <div class="rounded-lg p-4 border"
                                         :class="mensaje.remitente === 'soporte' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'">
                                        <div x-show="mensaje.es_correo" class="text-sm text-gray-600 mb-2">
                                            <div x-show="mensaje.correo_remitente">
                                                <span class="font-medium">Desde:</span> <span x-text="mensaje.correo_remitente"></span>
                                            </div>
                                            <div x-show="mensaje.message_id" class="text-xs text-gray-500 mt-1">
                                                <span class="font-medium">Message-ID:</span> <span x-text="mensaje.message_id"></span>
                                            </div>
                                            <div x-show="mensaje.thread_id" class="text-xs text-gray-500 mt-1">
                                                <span class="font-medium">Thread-ID:</span> <span x-text="mensaje.thread_id"></span>
                                            </div>
                                        </div>
                                        <div class="text-gray-800 mt-3" x-html="formatearMensaje(mensaje.mensaje)"></div>
                                        <div x-show="mensaje.adjuntos && mensaje.adjuntos.length > 0" class="mt-3 pt-3 border-t border-gray-200">
                                            <div class="text-xs text-gray-500 mb-2">Adjuntos:</div>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="adjunto in mensaje.adjuntos" :key="adjunto.name">
                                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded flex items-center gap-1">
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
                            <div class="text-gray-400 text-sm">
                                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                No hay mensajes aún. Envía una respuesta para iniciar la conversación.
                            </div>
                        </div>

                        <!-- Área para escribir nueva respuesta - Estilo Cliente de Correo -->
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                            <!-- Encabezado de Composición -->
                            <div class="border-b border-gray-200 p-4 bg-gray-50">
                                <div class="space-y-3">
                                    <!-- Campo Para -->
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-700 w-16 flex-shrink-0">Para:</label>
                                        <input 
                                            type="email"
                                            :value="selected.correo || ''"
                                            readonly
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <div class="flex items-center gap-2">
                                            <button 
                                                type="button"
                                                @click="mostrarCc = !mostrarCc"
                                                class="text-xs text-gray-600 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100 transition">
                                                Copia
                                            </button>
                                            <button 
                                                type="button"
                                                @click="mostrarBcc = !mostrarBcc"
                                                class="text-xs text-gray-600 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100 transition">
                                                Copia Oculta
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Campo Copia (oculto por defecto) -->
                                    <div x-show="mostrarCc" x-transition class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-700 w-16 flex-shrink-0">Copia:</label>
                                        <input 
                                            type="email"
                                            x-model="correoCc"
                                            placeholder="correo@ejemplo.com"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    
                                    <!-- Campo Copia Oculta (oculto por defecto) -->
                                    <div x-show="mostrarBcc" x-transition class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-700 w-16 flex-shrink-0">Copia Oculta:</label>
                                        <input 
                                            type="email"
                                            x-model="correoBcc"
                                            placeholder="correo@ejemplo.com"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    
                                    <!-- Campo Asunto -->
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-700 w-16 flex-shrink-0">Asunto: <span class="text-red-500">*</span></label>
                                        <input 
                                            type="text"
                                            x-model="asuntoCorreo"
                                            required
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Asunto del correo">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barra de Herramientas de Formato -->
                            <div class="border-b border-gray-200 p-2 bg-white flex items-center gap-2 flex-wrap">
                                <!-- Botones de formato básico -->
                                <div class="flex items-center gap-1 border-r border-gray-200 pr-2">
                                    <button 
                                        type="button"
                                        @click="aplicarFormato('bold')"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Negrita">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"></path>
                                        </svg>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="aplicarFormato('italic')"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Cursiva">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="aplicarFormato('underline')"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Subrayado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Selector de fuente y tamaño -->
                                <div class="flex items-center gap-1 border-r border-gray-200 pr-2">
                                    <select class="text-xs border border-gray-300 rounded px-2 py-1 bg-white text-gray-700 focus:outline-none">
                                        <option>Calibri</option>
                                        <option>Arial</option>
                                        <option>Times New Roman</option>
                                        <option>Courier New</option>
                                    </select>
                                    <select class="text-xs border border-gray-300 rounded px-2 py-1 bg-white text-gray-700 focus:outline-none">
                                        <option>11</option>
                                        <option>10</option>
                                        <option>12</option>
                                        <option>14</option>
                                        <option>16</option>
                                        <option>18</option>
                                    </select>
                                </div>
                                
                                <!-- Colores y alineación -->
                                <div class="flex items-center gap-1 border-r border-gray-200 pr-2">
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Color de texto">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
                                        </svg>
                                    </button>
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Resaltar">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M6 14l3 3v5h6v-5l3-3V9H6v5zm5-12h2v3h-2V2zM3.5 5.88L4.88 4.5 7.05 6.67 5.67 8.05 3.5 5.88zm13.45.79l2.58-2.59L21.5 5.88l-2.58 2.59-1.97-1.97zM11 16h2v2h-2v-2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Alineación -->
                                <div class="flex items-center gap-1 border-r border-gray-200 pr-2">
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Alinear izquierda">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"></path>
                                        </svg>
                                    </button>
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Centrar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                    </button>
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Alinear derecha">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Listas -->
                                <div class="flex items-center gap-1 border-r border-gray-200 pr-2">
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Lista con viñetas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                    </button>
                                    <button 
                                        type="button"
                                        class="p-1.5 hover:bg-gray-100 rounded transition"
                                        title="Lista numerada">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Adjuntar archivo -->
                                <div class="flex items-center gap-1">
                                    <label 
                                        for="adjuntos"
                                        class="p-1.5 hover:bg-gray-100 rounded transition cursor-pointer"
                                        title="Adjuntar archivo">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                    </label>
                                    <input 
                                        type="file" 
                                        id="adjuntos" 
                                        name="adjuntos[]" 
                                        multiple 
                                        accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif"
                                        class="hidden">
                                </div>
                                
                                <!-- Más opciones -->
                                <button 
                                    type="button"
                                    class="p-1.5 hover:bg-gray-100 rounded transition ml-auto"
                                    title="Más opciones">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Área de Composición del Mensaje -->
                            <div class="p-4">
                                <textarea 
                                    id="editor-mensaje"
                                    x-model="nuevoMensaje"
                                    class="w-full min-h-[200px] p-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm font-sans"
                                    placeholder="Escribe tu mensaje aquí..."></textarea>
                                
                                <!-- Información del ticket (mostrada como correo citado) -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="text-xs text-gray-500 space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-600">De:</span>
                                            <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded">Soporte TI</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-600">Fecha:</span>
                                            <span x-text="new Date().toLocaleString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-600">Para:</span>
                                            <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded" x-text="selected.correo || 'No disponible'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-600">Asunto:</span>
                                            <span x-text="'Ticket #' + (selected.id || '')"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botón de envío -->
                                <div class="flex justify-end items-center gap-3 mt-4 pt-4 border-t border-gray-200">
                                    <button 
                                        type="button"
                                        @click="nuevoMensaje = ''"
                                        class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100 transition text-sm">
                                        Descartar
                                    </button>
                                    <button 
                                        @click="enviarRespuesta()"
                                        :disabled="!nuevoMensaje.trim() || !asuntoCorreo.trim()"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium py-2 px-6 rounded-lg transition text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        Enviar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Área para Procesar Respuesta de Correo -->
                    <div x-show="mostrarProcesarRespuesta" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                        
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-sm font-medium text-green-700">📧 Procesar Respuesta de Correo:</span>
                            <span class="text-xs text-green-600">(Procesamiento manual cuando Webklex no funciona)</span>
                        </div>
                        
                        <div class="bg-green-100 border border-green-300 rounded-lg p-3 mb-3">
                            <div class="flex items-start gap-2">
                                <div class="text-green-600 mt-0.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="text-sm text-green-800">
                                    <p class="font-medium mb-1">¿Cómo procesar respuestas de correo?</p>
                                    <ol class="text-xs space-y-1 list-decimal list-inside">
                                        <li><strong>Automático:</strong> Usa "Procesar Automático" para Webklex IMAP</li>
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
                                <label class="block text-xs font-medium text-green-600 mb-1">Nombre del usuario:</label>
                                <input 
                                    x-model="respuestaManual.nombre"
                                    type="text" 
                                    class="w-full p-2 border border-green-300 rounded text-sm"
                                    placeholder="Nombre del usuario">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-green-600 mb-1">Correo del usuario:</label>
                                <input 
                                    x-model="respuestaManual.correo"
                                    type="email" 
                                    class="w-full p-2 border border-green-300 rounded text-sm"
                                    placeholder="correo@usuario.com">
                            </div>
                        </div>
                        
                        <textarea 
                            x-model="respuestaManual.mensaje"
                            class="w-full h-20 p-3 border border-green-300 rounded-lg resize-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
                            placeholder="Copia y pega aquí la respuesta que recibiste por correo..."></textarea>
                        
                        <div class="flex justify-end mt-3">
                            <button 
                                @click="agregarRespuestaManual()"
                                :disabled="!respuestaManual.mensaje.trim()"
                                class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                                Procesar Respuesta de Correo
                            </button>
                        </div>
                    </div>

                    <!-- Barra de Acciones Inferior -->
                    <div class="border-t border-gray-200 p-4 bg-gray-50">
                        <div class="flex justify-between items-center">
                           
                            <button class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-5 rounded-lg flex items-center gap-2 transition">
                                Cerrar Ticket
                            </button>
                        </div>
                    </div>
                </main>

            </div>
        </div>
    </div>
</div>

<script>
    function ticketsModal() {
        return {
            mostrar: false,
            selected: {},
            mensajes: [],
            nuevoMensaje: '',
            cargando: false,
            sincronizando: false,
            procesandoAutomatico: false,
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

            init() {
             
                this.mostrar = false;
                this.selected = {};
                this.mensajes = [];
                this.nuevoMensaje = '';
                this.asuntoCorreo = '';
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                
                // Configurar actualización automática cada 30 segundos
                this.configurarActualizacionAutomatica();
            },

            configurarActualizacionAutomatica() {
                setInterval(() => {
                    if (this.mostrar && this.selected.id) {
                        this.cargarMensajes();
                    }
                }, 30000); // 30 segundos
            },

            abrirModal(datos) {
                this.selected = datos;
                this.mostrar = true;
                this.asuntoCorreo = `Re: Ticket #${datos.id}`;
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                this.cargarMensajes();
            },

            abrirModalDesdeElemento(elemento) {
                const datos = {
                    id: elemento.dataset.ticketId,
                    asunto: elemento.dataset.ticketAsunto,
                    descripcion: elemento.dataset.ticketDescripcion,
                    prioridad: elemento.dataset.ticketPrioridad,
                    empleado: elemento.dataset.ticketEmpleado,
                    anydesk: elemento.dataset.ticketAnydesk,
                    numero: elemento.dataset.ticketNumero,
                    correo: elemento.dataset.ticketCorreo,
                    fecha: elemento.dataset.ticketFecha
                };
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
                setTimeout(() => this.selected = {}, 200);
            },

            async cargarMensajes() {
                if (!this.selected.id) return;

                try {
                    console.log('🔄 Cargando mensajes para ticket:', this.selected.id);
                    const response = await fetch(`/tickets/chat-messages?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    console.log('📊 Respuesta de la API:', data);
                    
                    if (data.success) {
                        console.log('✅ Mensajes cargados:', data.messages.length);
                        this.mensajes = data.messages;
                        this.marcarMensajesComoLeidos();
                        this.scrollToBottom();
                    
                        // Actualizar estadísticas después de cargar mensajes
                    this.estadisticas = await this.obtenerEstadisticasCorreos();
                    } else {
                        console.error('❌ Error en la API:', data.message);
                    }
                } catch (error) {
                    console.error('❌ Error cargando mensajes:', error);
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
                if (!this.nuevoMensaje.trim()) return;
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
                    formData.append('mensaje', this.nuevoMensaje);
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
                
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
                    tipo === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                notification.textContent = mensaje;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            },

            formatearFecha(fecha) {
                return new Date(fecha).toLocaleString('es-ES');
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

            aplicarFormato(tipo) {
                const textarea = document.getElementById('editor-mensaje');
                if (!textarea) return;
                
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const texto = textarea.value;
                const textoSeleccionado = texto.substring(start, end);
                
                let textoFormateado = '';
                let nuevoInicio = start;
                let nuevoFin = end;
                
                switch(tipo) {
                    case 'bold':
                        textoFormateado = textoSeleccionado ? `**${textoSeleccionado}**` : '****';
                        nuevoInicio = start + 2;
                        nuevoFin = end + 2;
                        break;
                    case 'italic':
                        textoFormateado = textoSeleccionado ? `*${textoSeleccionado}*` : '**';
                        nuevoInicio = start + 1;
                        nuevoFin = end + 1;
                        break;
                    case 'underline':
                        textoFormateado = textoSeleccionado ? `<u>${textoSeleccionado}</u>` : '<u></u>';
                        nuevoInicio = start + 3;
                        nuevoFin = end + 3;
                        break;
                }
                
                const nuevoTexto = texto.substring(0, start) + textoFormateado + texto.substring(end);
                this.nuevoMensaje = nuevoTexto;
                
                // Restaurar el foco y la selección
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(nuevoInicio, nuevoFin);
                });
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
                        console.log('Diagnóstico de correos:', data.diagnostico);
                        
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
                    console.error('Error en diagnóstico:', error);
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
                    console.error('Error enviando instrucciones:', error);
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
                    console.error('Error agregando respuesta manual:', error);
                    this.mostrarNotificacion('Error agregando respuesta manual', 'error');
                }
            },

            async procesarRespuestasAutomaticas() {
                if (!this.asuntoCorreo.trim()) {
                    this.mostrarNotificacion('El asunto es requerido para procesar automáticamente', 'error');
                    return;
                }

                this.procesandoAutomatico = true;

                try {
                    console.log('🔄 Iniciando procesamiento automático de respuestas...');
                    
                    // Normalizar el asunto para mantener la nomenclatura con el ID
                    const asuntoNormalizado = this.normalizarAsunto(this.asuntoCorreo);
                    
                    const response = await fetch('/api/process-webklex-responses', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id,
                            asunto: asuntoNormalizado
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                        
                        // Mostrar estadísticas si están disponibles
                        if (data.estadisticas) {
                            console.log('📊 Estadísticas del procesamiento:', data.estadisticas);
                        }
                        
                        // Recargar mensajes para mostrar las nuevas respuestas
                        await this.cargarMensajes();
                        
                        // Actualizar estadísticas
                        this.estadisticas = await this.obtenerEstadisticasCorreos();
                        
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error procesando respuestas automáticas:', error);
                    this.mostrarNotificacion('Error procesando respuestas automáticas', 'error');
                } finally {
                    this.procesandoAutomatico = false;
                }
            },

            async probarConexionWebklex() {
                try {
                    console.log('🔌 Probando conexión Webklex IMAP...');
                    
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
                        console.log('✅ Conexión Webklex exitosa:', data);
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                        console.error('❌ Error de conexión Webklex:', data);
                    }
                } catch (error) {
                    console.error('Error probando conexión Webklex:', error);
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
                    console.log('🔍 Buscando correos de usuarios para ticket:', this.selected.id);
                    
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
                        
                        console.log('✅ Correos buscados y procesados exitosamente', {
                            procesados: data.procesados,
                            descartados: data.descartados,
                            correos_usuarios: data.correos_usuarios
                        });
                    } else {
                        this.mostrarNotificacion(data.message || 'No se encontraron correos nuevos', 'error');
                    }
                } catch (error) {
                    console.error('Error buscando correos de usuarios:', error);
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
                    console.log('💾 Guardando correos en historial para ticket:', this.selected.id);
                    
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
                        
                        console.log('✅ Correos guardados en historial exitosamente');
                    } else {
                        this.mostrarNotificacion(data.message || 'Error guardando correos', 'error');
                    }
                } catch (error) {
                    console.error('Error guardando correos en historial:', error);
                    this.mostrarNotificacion('Error guardando correos en historial', 'error');
                } finally {
                    this.guardandoCorreos = false;
                }
            }
        }
    }

   
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
                    console.error('Error cargando tipos:', data.message);
                }
            } catch (error) {
                console.error('Error en la petición de tipos:', error);
            }
        }

        async function loadSubtipos(tipoId) {
            try {
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
                    subtipoSelect.disabled = false;
                } else {
                    console.log('No hay subtipos disponibles para este tipo');
                }
            } catch (error) {
                console.error('Error en la petición de subtipos:', error);
            }
        }

        async function loadTertipos(subtipoId) {
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
                    tertipoSelect.disabled = false;
                } else {
                    console.log('No hay tertipos disponibles para este subtipo');
                }
            } catch (error) {
                console.error('Error en la petición de tertipos:', error);
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