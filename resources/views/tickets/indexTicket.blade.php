<div
    x-data="ticketsModal()"
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
        x-show="mostrar"
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
                            <div class="font-medium text-gray-800 whitespace-pre-wrap" x-text="selected.descripcion"></div>
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

                            <label class="text-md font-semibold text-gray-600">Responsable</label>
                            <select class="w-full mt-1 mb-2 0 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option selected disabled>Selecciona</option>
                                @foreach($responsablesTI as $responsable)
                                <option value="{{ $responsable->EmpleadoID }}">{{ $responsable->NombreEmpleado }}</option>
                                @endforeach
                            </select>

                            <label class="text-md font-semibold text-gray-600">Tipo</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                            <label class="text-md font-semibold text-gray-600">Tipo1</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                            <label class="text-md font-semibold text-gray-600">Tipo2</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                            <label class="text-md font-semibold text-gray-600">Tipo3</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
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
                            <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition">
                                Responder A Todos
                            </button>
                            <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 transition p-2">
                                <span class="text-xl">Cerrar</span>
                            </button>
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
                                        <span x-show="mensaje.es_correo" class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                            📧 Correo
                                        </span>
                                        <span x-show="!mensaje.es_correo" class="text-xs text-gray-600 bg-gray-50 px-2 py-1 rounded">
                                            💬 Nota Interna
                                        </span>
                                    </div>
                                    <div class="rounded-lg p-4 border"
                                         :class="mensaje.remitente === 'soporte' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'">
                                        <div x-show="mensaje.es_correo" class="text-sm text-gray-600 mb-2">
                                            <div x-show="mensaje.correo_remitente">
                                                <span class="font-medium">Desde:</span> <span x-text="mensaje.correo_remitente"></span>
                                            </div>
                                        </div>
                                        <div class="text-gray-800 mt-3" x-text="mensaje.mensaje"></div>
                                        <div x-show="mensaje.adjuntos && mensaje.adjuntos.length > 0" class="mt-3 pt-3 border-t border-gray-200">
                                            <div class="text-xs text-gray-500 mb-2">Adjuntos:</div>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="adjunto in mensaje.adjuntos" :key="adjunto.name">
                                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded" x-text="adjunto.name"></span>
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

                        <!-- Área para escribir nueva respuesta -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-sm font-medium text-gray-700">Responder por correo:</span>
                            </div>
                            <textarea 
                                x-model="nuevoMensaje"
                                class="w-full h-24 p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Escribe tu respuesta aquí..."></textarea>
                            <div class="flex justify-between items-center mt-3">
                              
                                <button 
                                    @click="enviarRespuesta()"
                                    :disabled="!nuevoMensaje.trim()"
                                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition">
                                    Enviar por Correo
                                </button>
                            </div>
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

            abrirModal(datos) {
                this.selected = datos;
                this.mostrar = true;
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
                setTimeout(() => this.selected = {}, 200);
            },

            async cargarMensajes() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/chat-messages?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.mensajes = data.messages;
                        this.marcarMensajesComoLeidos();
                        this.scrollToBottom();
                    }
                } catch (error) {
                    console.error('Error cargando mensajes:', error);
                }
            },

            async enviarRespuesta() {
                if (!this.nuevoMensaje.trim()) return;

                this.cargando = true;

                try {
                    const formData = new FormData();
                    formData.append('ticket_id', this.selected.id);
                    formData.append('mensaje', this.nuevoMensaje);

                    
                    const adjuntosInput = document.getElementById('adjuntos');
                    if (adjuntosInput.files.length > 0) {
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
                        adjuntosInput.value = '';
                        
                       
                        this.mostrarNotificacion(data.message, 'success');
                        
                        // Recargar mensajes
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

            getTipoMensaje(remitente) {
                return remitente === 'soporte' ? 'soporte' : 'usuario';
            }
        }
    }
</script>
</div>