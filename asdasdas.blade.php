<!-- BLOQUE 1: CONVERSACIÓN (PRINCIPAL) -->
<section class="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-6
               bg-gray-50 dark:bg-[#0F1116]"
         id="chat-container">

    <template x-for="mensaje in mensajes" :key="mensaje.id">

        <div class="flex"
             :class="mensaje.remitente === 'soporte'
                     ? 'justify-end'
                     : 'justify-start'">

            <div
                class="max-w-[75%] rounded-2xl px-4 py-3 shadow-sm
                       text-sm leading-relaxed
                       transition-all"

                :class="mensaje.remitente === 'soporte'
                    ? 'bg-blue-600 text-white rounded-br-md'
                    : 'bg-white text-gray-800 rounded-bl-md dark:bg-[#1C1F26] dark:text-gray-100'">

                <!-- Encabezado -->
                <div class="flex items-center justify-between mb-1 text-xs opacity-70">
                    <span x-text="mensaje.remitente === 'soporte' ? 'Soporte' : mensaje.autor"></span>
                    <span x-text="mensaje.fecha"></span>
                </div>

                <!-- Contenido -->
                <div x-html="mensaje.contenido"></div>

            </div>
        </div>

    </template>

</section>
<!-- BLOQUE 2: SEPARADOR + ESTADO -->
<section class="flex items-center justify-between px-6 py-2
               border-t border-gray-200
               bg-white dark:bg-[#0F1116] dark:border-[#2A2F3A]">

    <!-- Estado del ticket -->
    <div class="flex items-center gap-2 text-xs">

        <span class="w-2.5 h-2.5 rounded-full"
              :class="{
                  'bg-yellow-400': selected.estatus === 'Pendiente',
                  'bg-blue-500': selected.estatus === 'En progreso',
                  'bg-green-500': selected.estatus === 'Resuelto',
                  'bg-gray-400': selected.estatus === 'Cerrado'
              }">
        </span>

        <span class="font-medium text-gray-600 dark:text-gray-400">
            Estado:
        </span>

        <span class="text-gray-800 dark:text-gray-200"
              x-text="selected.estatus">
        </span>

    </div>

    <!-- Texto informativo -->
    <p class="text-xs text-gray-500 dark:text-gray-500">
        La conversación es el registro principal del ticket
    </p>

</section>
<main class="flex-1 flex flex-col min-w-0 bg-gray-50 dark:bg-[#0F1116] h-full overflow-hidden relative">

    <div class="flex-shrink-0 flex justify-between items-start p-6 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1A1D24]">
        <div>
            <h1 class="text-2xl font-semibold mb-1 text-gray-900 dark:text-gray-100" x-text="selected.asunto"></h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span x-text="selected.fecha"></span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="cerrarModal" class="transition p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700">
                <span class="text-xl">×</span>
            </button>
        </div>
    </div>

    <div class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-[#0F1116]">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span class="text-gray-600 dark:text-gray-300">Enviados:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_enviados || 0"></span>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span class="text-gray-600 dark:text-gray-300">Respuestas:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="estadisticas?.correos_recibidos || 0"></span>
                </span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Total: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="estadisticas?.total_correos || 0"></span>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 bg-gray-50 dark:bg-[#0F1116] scroll-smooth custom-scrollbar" id="chat-container">
        
        <div x-show="mensajes.length === 0" class="text-center py-8">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                No hay mensajes aún.
            </div>
        </div>

        <template x-for="mensaje in mensajes" :key="mensaje.id">
            <div class="group flex gap-4 w-full" :class="mensaje.remitente === 'soporte' ? 'flex-row-reverse' : 'flex-row'">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-md"
                         :class="mensaje.remitente === 'soporte' ? 'bg-blue-600' : 'bg-green-500'"
                         x-text="obtenerIniciales(mensaje.nombre_remitente)">
                    </div>
                </div>
                <div class="flex flex-col max-w-[85%]" :class="mensaje.remitente === 'soporte' ? 'items-end' : 'items-start'">
                    <div class="flex flex-wrap items-center gap-2 mb-1" :class="mensaje.remitente === 'soporte' ? 'justify-end' : 'justify-start'">
                        <span class="font-bold text-sm text-gray-800 dark:text-gray-200" x-text="mensaje.nombre_remitente"></span>
                        <span class="text-[10px] text-gray-400" x-text="mensaje.created_at"></span>
                    </div>
                    <div class="px-5 py-4 shadow-sm border text-sm w-full"
                         :class="mensaje.remitente === 'soporte' 
                         ? 'bg-blue-50 border-blue-100 rounded-2xl rounded-tr-none dark:bg-blue-900/20 dark:border-blue-800' 
                         : 'bg-white border-gray-200 rounded-2xl rounded-tl-none dark:bg-[#1C1F26] dark:border-gray-700'">
                        
                         <div x-show="mensaje.es_correo" class="mb-2 pb-2 border-b border-gray-200 dark:border-gray-700/50">
                            <span class="text-xs text-gray-500">De: <span x-text="mensaje.correo_remitente"></span></span>
                         </div>

                         <div class="prose prose-sm max-w-none dark:prose-invert" x-html="formatearMensaje(mensaje.mensaje)"></div>
                         
                         <div x-show="mensaje.adjuntos && mensaje.adjuntos.length > 0" class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                             <div class="flex flex-wrap gap-2">
                                <template x-for="adjunto in mensaje.adjuntos">
                                    <span class="text-xs bg-white border px-2 py-1 rounded flex gap-1 items-center dark:bg-gray-800 dark:border-gray-600">
                                        📎 <span x-text="adjunto.name"></span>
                                    </span>
                                </template>
                             </div>
                         </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="flex-shrink-0 p-4 bg-white dark:bg-[#1F2937] border-t border-gray-200 dark:border-gray-700 z-10 overflow-y-auto max-h-[50vh] custom-scrollbar" id="compose-container">

        <div x-show="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') && ticketEstatus !== 'Cerrado' && selected.estatus !== 'Cerrado'"
             class="mb-3 rounded-lg p-3 border border-yellow-200 bg-yellow-50 text-yellow-800 text-sm flex gap-2">
            <span>⚠️</span> <span>El ticket está "Pendiente". Cambia a "En progreso" para editar.</span>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 p-3 space-y-2 mb-3"
             :class="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') ? 'opacity-50 pointer-events-none' : ''">
            <div class="flex gap-2 text-sm border-b border-gray-200 dark:border-gray-700 pb-2">
                <span class="font-semibold w-12 text-gray-500">Para:</span>
                <input class="bg-transparent flex-1 outline-none dark:text-gray-200" :value="selected.correo" readonly>
                <button @click="mostrarBcc = !mostrarBcc" class="text-xs text-blue-500 hover:underline">CC/CCO</button>
            </div>
            <div x-show="mostrarBcc" class="flex gap-2 text-sm border-b border-gray-200 dark:border-gray-700 pb-2">
                <span class="font-semibold w-12 text-gray-500">CCO:</span>
                <input class="bg-transparent flex-1 outline-none dark:text-gray-200" x-model="correoBcc" placeholder="email@ejemplo.com">
            </div>
            <div class="flex gap-2 text-sm">
                <span class="font-semibold w-12 text-gray-500">Asunto:</span>
                <input class="bg-transparent flex-1 outline-none font-medium dark:text-white" x-model="asuntoCorreo" placeholder="Asunto...">
            </div>
        </div>

        <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-[#1F2937]"
             :class="(selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente') ? 'opacity-50 pointer-events-none' : ''">
            
            <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-2 flex gap-2">
                <button type="button" @click="aplicarFormato('bold')" class="p-1 hover:bg-gray-200 rounded dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"><b>B</b></button>
                <button type="button" class="p-1 hover:bg-gray-200 rounded dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"><i>I</i></button>
            </div>

            <textarea x-model="nuevoMensaje"
                      class="w-full h-32 p-3 outline-none resize-y bg-transparent dark:text-gray-200 placeholder-gray-400"
                      placeholder="Escribe tu respuesta..."></textarea>
            
            <div class="bg-gray-50 dark:bg-gray-800 p-2 border-t border-gray-200 dark:border-gray-700">
                <div x-show="archivosAdjuntos.length > 0" class="flex flex-wrap gap-2 mb-2">
                     <template x-for="(archivo, index) in archivosAdjuntos" :key="index">
                        <div class="text-xs bg-white border px-2 py-1 rounded flex gap-2 items-center dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <span x-text="archivo.name"></span>
                            <button @click="eliminarArchivo(index)" class="text-red-500 font-bold">×</button>
                        </div>
                     </template>
                </div>
                <div class="flex justify-between items-center">
                    <button @click="document.getElementById('adjuntos').click()" class="text-sm text-blue-600 hover:underline flex gap-1 items-center">
                        📎 Adjuntar archivos
                    </button>
                    <input type="file" id="adjuntos" multiple class="hidden" @change="manejarArchivosSeleccionados($event)">
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <button @click="limpiarEditor()" class="text-gray-500 hover:text-red-500 text-sm">Descartar</button>
            
            <button @click="enviarRespuesta()" 
                    :disabled="selected.estatus === 'Pendiente' || ticketEstatus === 'Pendiente'"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed shadow-md flex items-center gap-2">
                <span>Enviar Respuesta</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </div>

        <div x-show="mostrarProcesarRespuesta"
             x-transition
             class="mt-4 rounded-xl border border-green-200 bg-white shadow-lg overflow-hidden dark:bg-[#1C1F26] dark:border-green-800">
            
            <div class="bg-green-50 px-4 py-2 border-b border-green-100 flex items-center gap-2 dark:bg-green-900/20 dark:border-green-800">
                <span class="text-green-600">⚡</span>
                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Ingreso Manual de Respuesta</span>
            </div>

            <div class="p-4 space-y-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Usa esto si el correo del usuario no se importó automáticamente.</p>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Nombre</label>
                        <input type="text" x-model="respuestaManual.nombre" class="w-full text-sm border rounded p-2 mt-1 dark:bg-black/20 dark:border-gray-600 dark:text-white" placeholder="Ej. Juan Perez">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Correo</label>
                        <input type="email" x-model="respuestaManual.correo" class="w-full text-sm border rounded p-2 mt-1 dark:bg-black/20 dark:border-gray-600 dark:text-white" placeholder="cliente@email.com">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Mensaje Original</label>
                    <textarea x-model="respuestaManual.mensaje" class="w-full text-sm border rounded p-2 mt-1 h-24 dark:bg-black/20 dark:border-gray-600 dark:text-white" placeholder="Pega aquí el contenido del correo..."></textarea>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="agregarRespuestaManual()" 
                            :disabled="!respuestaManual.mensaje"
                            class="bg-green-600 text-white text-sm px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50 flex gap-2 items-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Importar Respuesta
                    </button>
                </div>
            </div>
        </div>

    </div>

</main>
<!-- BLOQUE 4: ACCIONES -->
<section class="flex items-center justify-end gap-3 px-6 py-3
               border-t border-gray-200
               bg-white dark:bg-[#0F1116] dark:border-[#2A2F3A]">

    <!-- Botón Cancelar -->
    <button
        type="button"
        @click="cancelarEnvio()"
        class="px-4 py-2 rounded-md text-sm font-medium
               text-gray-700 bg-gray-100 hover:bg-gray-200
               transition
               focus:outline-none focus:ring-2 focus:ring-gray-300
               dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        Cancelar
    </button>

    <!-- Botón Enviar -->
    <button
        type="button"
        @click="enviarCorreo()"
        :disabled="!nuevoMensaje.trim()
                   || selected.estatus === 'Cerrado'
                   || ticketEstatus === 'Cerrado'
                   || selected.estatus === 'Pendiente'
                   || ticketEstatus === 'Pendiente'"
        class="px-5 py-2 rounded-md text-sm font-medium
               text-white bg-blue-600 hover:bg-blue-700
               transition
               focus:outline-none focus:ring-2 focus:ring-blue-500
               disabled:opacity-50 disabled:cursor-not-allowed">
        Enviar respuesta
    </button>

</section>
