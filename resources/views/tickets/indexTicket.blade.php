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
                    @click="abrirModal({
                            id: '{{ $ticket->TicketID }}',
                            asunto: '{{ $ticket->Asunto ?? 'Sin asunto' }}',
                            descripcion: `{{ $ticket->Descripcion }}`,
                            prioridad: '{{ $ticket->Prioridad }}',
                            empleado: '{{ $nombreFormateado }}',
                            anydesk: '{{ $ticket->CodeAnyDesk }}',
                            numero: '{{ $ticket->Numero }}',
                            correo: '{{ $ticket->empleado->Correo }}',
                            fecha: 'Hace 2 horas'
                        })">
                    <div class="flex justify-between items-start">
                        <h3 class="text-sm font-semibold text-gray-800 truncate">
                            #{{ $ticket->TicketID }} - {{ $ticket->Asunto ?? 'Problema con la impresora' }}
                        </h3>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600 whitespace-nowrap">
                            {{ $ticket->Prioridad }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">
                        {{ $ticket->Descripcion }}
                    </p>

                    <div class="flex justify-between items-center mt-3 text-xs text-gray-500">
                        <span class="font-semibold text-gray-700">
                            {{ $nombreFormateado }}
                        </span>
                        <span>Hace 2 horas</span>
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
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Información de Contacto</h3>
                            <p class="font-medium text-gray-800" x-text="selected.empleado"></p>
                            <p class="text-gray-500 text-sm" x-text="selected.correo"></p>
                            <p class="text-gray-500 text-sm" x-text="selected.numero"></p>
                            <p class="text-gray-500 text-sm" x-text="selected.anydesk"></p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex flex-col gap-3">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Detalles del Ticket</h3>

                            <label class="text-md font-semibold text-gray-600 text-black">Prioridad</label>
                            <select
                                class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Baja</option>
                                <option>Media</option>
                                <option>Alta</option>
                            </select>

                            <label class="text-md font-semibold text-gray-600 text-black">Estado</label>
                            <select class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Pendiente</option>
                                <option>En progreso</option>
                                <option>Cerrado</option>
                            </select>

                            <label class="text-md font-semibold text-gray-600 text-black">Responsable</label>
                            <select class="w-full mt-1 mb-2 0 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option selected disabled>Selecciona</option>
                                @foreach($responsablesTI as $responsable)
                                <option value="{{ $responsable->EmpleadoID }}">{{ $responsable->NombreEmpleado }}</option>
                                @endforeach
                            </select>

                            <label class="text-md font-semibold text-gray-600 text-black">Tipo</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                            <label class="text-md font-semibold text-gray-600 text-black">Tipo1</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                            <label class="text-md font-semibold text-gray-600 text-black">Tipo2</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                            <label class="text-md font-semibold text-gray-600 text-black">Tipo3</label>
                            <select class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black">
                                <option>Problema</option>
                                <option>Solicitud</option>
                            </select>
                        </div>
                    </div>
                </aside>

                <main class="p-8 flex flex-col overflow-y-auto">
                    <div class="flex justify-between items-start mb-6 border-b border-gray-200 pb-4">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-800 mb-1" x-text="selected.asunto"></h1>
                            <p class="text-sm text-gray-500">
                                Ticket #<span x-text="selected.id"></span> — <span x-text="selected.fecha"></span>
                            </p>
                        </div>

                        <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 transition">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>

                    <div class="flex-1 text-gray-700 text-sm leading-relaxed">
                        <p x-text="selected.descripcion"></p>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-5 rounded-lg flex items-center gap-2 transition">
                            Cerrar Ticket
                        </button>
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
            abrirModal(datos) {
                this.selected = datos;
                this.mostrar = true;
            },
            cerrarModal() {
                this.mostrar = false;
                setTimeout(() => this.selected = {}, 200);
            }
        }
    }
</script>
</div>