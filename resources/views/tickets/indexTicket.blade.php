<!-- TinyMCE Editor - Rich Text Editor Gratuito y Open Source -->
<style>
    .tox-tinymce {
        border: 1px solid #e5e7eb !important;
        border-radius: 0.5rem !important;
    }
    #editor-mensaje {
        min-height: 300px;
    }
</style>

<div
    x-data="ticketsModal()"
    x-init="init(); vista = localStorage.getItem('ticketsVista') || 'kanban'"
    class="space-y-4">
    
    <!-- Selector de Vista -->
    <div class="flex justify-between items-center gap-2 mb-4">
        @can('tickets.ajustar-metricas')
        <button
            @click="mostrarModalMetricas = true; cargarMetricas()"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-all duration-200 flex items-center gap-2 shadow-sm">
            <i class="fas fa-cog text-sm"></i>
            <span>Ajustar Métricas</span>
        </button>
        @endcan
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600 font-medium">Vista:</span>
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button
                    @click="vista = 'kanban'; localStorage.setItem('ticketsVista', 'kanban')"
                    :class="vista === 'kanban' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-columns text-xs"></i>
                    <span>Kanban</span>
                </button>
                <button
                    @click="vista = 'lista'; localStorage.setItem('ticketsVista', 'lista'); prepararDatosLista()"
                    :class="vista === 'lista' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-list text-xs"></i>
                    <span>Lista</span>
                </button>
                <button
                    @click="vista = 'tabla'; localStorage.setItem('ticketsVista', 'tabla'); prepararDatosTabla()"
                    :class="vista === 'tabla' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-table text-xs"></i>
                    <span>Tabla</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Vista Kanban -->
    <div x-show="vista === 'kanban'" x-transition class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
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
                    data-categoria="{{ $key }}"
                    data-ticket-id="{{ $ticket->TicketID }}"
                    data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                    data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion, ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-prioridad="{{ $ticket->Prioridad }}"
                    data-ticket-empleado="{{ $nombreFormateado }}"
                    data-ticket-anydesk="{{ $ticket->CodeAnyDesk }}"
                    data-ticket-numero="{{ $ticket->Numero }}"
                    data-ticket-correo="{{ $ticket->empleado->Correo }}"
                    data-ticket-fecha="{{ $ticket->created_at->format('d/m/Y H:i:s') }}"
                    data-ticket-imagen="{{ htmlspecialchars($ticket->imagen ?? '', ENT_QUOTES, 'UTF-8') }}"
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
    </div>

    <!-- Vista Lista -->
    <div x-show="vista === 'lista'" x-transition class="space-y-3">
        @foreach (['nuevos' => 'Nuevos', 'proceso' => 'En Progreso', 'resueltos' => 'Resueltos'] as $key => $titulo)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-2 flex justify-between items-center">
                <h3 class="text-white font-semibold text-sm">{{ $titulo }}</h3>
                <span class="text-white text-xs" x-text="`Total: ${ticketsLista['{{ $key }}'] || 0}`"></span>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse ($ticketsStatus[$key] as $ticket)
                @php
                $partes = preg_split('/\s+/', trim($ticket->empleado->NombreEmpleado));
                if (count($partes) >= 3) array_splice($partes, 1, 1);
                $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();
                @endphp
                <div
                    class="p-4 hover:bg-gray-50 transition cursor-pointer"
                    data-ticket-id="{{ $ticket->TicketID }}"
                    data-ticket-asunto="Ticket #{{ $ticket->TicketID }}"
                    data-ticket-descripcion="{{ htmlspecialchars($ticket->Descripcion, ENT_QUOTES, 'UTF-8') }}"
                    data-ticket-prioridad="{{ $ticket->Prioridad }}"
                    data-ticket-empleado="{{ $nombreFormateado }}"
                    data-ticket-anydesk="{{ $ticket->CodeAnyDesk }}"
                    data-ticket-numero="{{ $ticket->Numero }}"
                    data-ticket-correo="{{ $ticket->empleado->Correo }}"
                    data-ticket-fecha="{{ $ticket->created_at->format('d/m/Y H:i:s') }}"
                    data-ticket-imagen="{{ htmlspecialchars($ticket->imagen ?? '', ENT_QUOTES, 'UTF-8') }}"
                    x-show="estaEnPaginaLista('{{ $key }}', {{ $loop->index }})"
                    @click="abrirModalDesdeElemento($el)">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="text-base font-semibold text-gray-800">Ticket #{{ $ticket->TicketID }}</h4>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap {{ $ticket->Prioridad == 'Baja' ? 'bg-green-200 text-green-600' : ($ticket->Prioridad == 'Media' ? 'bg-yellow-200 text-yellow-600' : 'bg-red-200 text-red-600') }}">
                                    {{ $ticket->Prioridad }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                {{ Str::limit($ticket->Descripcion, 150, '...') }}
                            </p>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-user text-gray-400"></i>
                                    <span class="font-semibold text-gray-700">{{ $nombreFormateado }}</span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    <span>{{ $ticket->created_at->format('d/m/Y H:i:s') }}</span>
                                </span>
                                @if($ticket->CodeAnyDesk)
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-desktop text-gray-400"></i>
                                    <span>{{ $ticket->CodeAnyDesk }}</span>
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center">
                    <p class="text-sm text-gray-400">No hay tickets en esta categoría.</p>
                </div>
                @endforelse
            </div>
            <!-- Paginación Lista - Cada sección tiene su propia paginación independiente -->
            <div x-show="ticketsLista && ticketsLista['{{ $key }}'] !== undefined && ticketsLista['{{ $key }}'] > 0" class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span x-text="`Mostrando ${((paginaLista['{{ $key }}'] - 1) * elementosPorPagina) + 1} - ${Math.min(paginaLista['{{ $key }}'] * elementosPorPagina, ticketsLista['{{ $key }}'] || 0)} de ${ticketsLista['{{ $key }}'] || 0} tickets`"></span>
                        <span x-show="obtenerTotalPaginasLista('{{ $key }}') > 1" class="ml-2" x-text="`(Página ${paginaLista['{{ $key }}']} de ${obtenerTotalPaginasLista('{{ $key }}')})`"></span>
                    </div>
                    <div x-show="obtenerTotalPaginasLista('{{ $key }}') > 1" class="flex items-center gap-2">
                        <button
                            @click="cambiarPaginaLista('{{ $key }}', paginaLista['{{ $key }}'] - 1)"
                            :disabled="paginaLista['{{ $key }}'] === 1"
                            :class="paginaLista['{{ $key }}'] === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                            class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition">
                            <i class="fas fa-chevron-left text-xs"></i> Anterior
                        </button>
                        <template x-for="pagina in Array.from({length: Math.min(obtenerTotalPaginasLista('{{ $key }}'), 10)}, (_, i) => {
                            const total = obtenerTotalPaginasLista('{{ $key }}');
                            const current = paginaLista['{{ $key }}'];
                            let start = Math.max(1, current - 4);
                            let end = Math.min(total, start + 9);
                            if (end - start < 9) start = Math.max(1, end - 9);
                            return start + i;
                        }).filter((p, i, arr) => p <= obtenerTotalPaginasLista('{{ $key }}') && (i === 0 || p !== arr[i-1]))" :key="pagina">
                            <button
                                @click="cambiarPaginaLista('{{ $key }}', pagina)"
                                :class="paginaLista['{{ $key }}'] === pagina ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                                class="px-3 py-1.5 text-sm font-medium border border-gray-300 rounded-md transition">
                                <span x-text="pagina"></span>
                            </button>
                        </template>
                        <button
                            @click="cambiarPaginaLista('{{ $key }}', paginaLista['{{ $key }}'] + 1)"
                            :disabled="paginaLista['{{ $key }}'] === obtenerTotalPaginasLista('{{ $key }}')"
                            :class="paginaLista['{{ $key }}'] === obtenerTotalPaginasLista('{{ $key }}') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                            class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition">
                            Siguiente <i class="fas fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Vista Tabla -->
    <div x-show="vista === 'tabla'" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <span x-text="`Mostrando ${(paginaTabla - 1) * elementosPorPagina + 1} - ${Math.min(paginaTabla * elementosPorPagina, ticketsTabla.length)} de ${ticketsTabla.length} tickets`"></span>
            </div>
            <div class="text-sm text-gray-600">
                <span>Elementos por página:</span>
                <select x-model="elementosPorPagina" @change="paginaTabla = 1" class="ml-2 px-2 py-1 border border-gray-300 rounded text-sm">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            @click="cambiarOrden('id')">
                            <div class="flex items-center gap-2">
                                <span>ID</span>
                                <i class="fas fa-sort text-gray-400 text-xs"
                                   :class="ordenColumna === 'id' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600') : ''"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            @click="cambiarOrden('descripcion')">
                            <div class="flex items-center gap-2">
                                <span>Descripción</span>
                                <i class="fas fa-sort text-gray-400 text-xs"
                                   :class="ordenColumna === 'descripcion' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600') : ''"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            @click="cambiarOrden('empleado')">
                            <div class="flex items-center gap-2">
                                <span>Empleado</span>
                                <i class="fas fa-sort text-gray-400 text-xs"
                                   :class="ordenColumna === 'empleado' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600') : ''"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            @click="cambiarOrden('prioridad')">
                            <div class="flex items-center gap-2">
                                <span>Prioridad</span>
                                <i class="fas fa-sort text-gray-400 text-xs"
                                   :class="ordenColumna === 'prioridad' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600') : ''"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            @click="cambiarOrden('estado')">
                            <div class="flex items-center gap-2">
                                <span>Estado</span>
                                <i class="fas fa-sort text-gray-400 text-xs"
                                   :class="ordenColumna === 'estado' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600') : ''"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            @click="cambiarOrden('fecha')">
                            <div class="flex items-center gap-2">
                                <span>Fecha</span>
                                <i class="fas fa-sort text-gray-400 text-xs"
                                   :class="ordenColumna === 'fecha' ? (ordenDireccion === 'asc' ? 'fa-sort-up text-blue-600' : 'fa-sort-down text-blue-600') : ''"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="ticket in obtenerTicketsTablaPagina()" :key="ticket.id">
                        <tr
                            class="hover:bg-gray-50 transition cursor-pointer"
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
                                <div class="text-sm font-semibold text-gray-900" x-text="'#' + ticket.id"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-md truncate" x-text="ticket.descripcion.substring(0, 80) + (ticket.descripcion.length > 80 ? '...' : '')"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="ticket.empleado"></div>
                                <div class="text-xs text-gray-500" x-text="ticket.correo"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                      :class="ticket.prioridad == 'Baja' ? 'bg-green-200 text-green-600' : (ticket.prioridad == 'Media' ? 'bg-yellow-200 text-yellow-600' : 'bg-red-200 text-red-600')"
                                      x-text="ticket.prioridad"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                      :class="ticket.estatus == 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : (ticket.estatus == 'En progreso' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800')"
                                      x-text="ticket.estatus"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="ticket.fecha.split(' ')[0] + ' ' + ticket.fecha.split(' ')[1]"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-eye text-blue-500"></i>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="ticketsTabla.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">
                            No hay tickets disponibles.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Paginación Tabla -->
        <div x-show="obtenerTotalPaginasTabla() > 1" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <span x-text="`Página ${paginaTabla} de ${obtenerTotalPaginasTabla()}`"></span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="cambiarPaginaTabla(paginaTabla - 1)"
                    :disabled="paginaTabla === 1"
                    :class="paginaTabla === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                    class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition">
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
                        :class="paginaTabla === pagina ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        class="px-3 py-1.5 text-sm font-medium border border-gray-300 rounded-md transition">
                        <span x-text="pagina"></span>
                    </button>
                </template>
                <button
                    @click="cambiarPaginaTabla(paginaTabla + 1)"
                    :disabled="paginaTabla === obtenerTotalPaginasTabla()"
                    :class="paginaTabla === obtenerTotalPaginasTabla() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                    class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition">
                    Siguiente <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>

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

                        <!-- Documentos Adjuntos -->
                        <div x-show="obtenerAdjuntos().length > 0" class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">Documentos Adjuntos</h3>
                            <div class="space-y-2">
                                <template x-for="(adjunto, index) in obtenerAdjuntos()" :key="index">
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate" x-text="obtenerNombreArchivo(adjunto)"></p>
                                                <p class="text-xs text-gray-500" x-text="obtenerExtensionArchivo(adjunto)"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <a 
                                                :href="obtenerUrlArchivo(adjunto)" 
                                                target="_blank"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition"
                                                title="Ver archivo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a 
                                                :href="obtenerUrlArchivo(adjunto)" 
                                                download
                                                class="p-1.5 text-green-600 hover:bg-green-50 rounded transition"
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
                                x-model="ticketPrioridad"
                                :disabled="selected.estatus === 'Cerrado'"
                                class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="Baja">Baja</option>
                                <option value="Media">Media</option>
                                <option value="Alta">Alta</option>
                            </select>

                            <label class="text-md font-semibold text-gray-600">Estado</label>
                            <select 
                                x-model="ticketEstatus"
                                :disabled="selected.estatus === 'Cerrado'"
                                class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="Pendiente">Pendiente</option>
                                <option value="En progreso">En progreso</option>
                                <option value="Cerrado">Cerrado</option>
                            </select>
                            
                            <!-- Mensaje informativo cuando está en "En progreso" -->
                            <div x-show="selected.estatus === 'En progreso' && ticketEstatus !== 'Cerrado'" class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-md text-xs text-blue-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                El Responsable no se puede modificar cuando el ticket está en "En progreso"
                            </div>

                            <label class="text-md font-semibold text-gray-600">Responsable <span class="text-red-500">*</span></label>
                            <select 
                                x-model="ticketResponsableTI"
                                :disabled="selected.estatus === 'Cerrado' || (selected.estatus === 'En progreso' && ticketEstatus !== 'Cerrado')"
                                class="w-full mt-1 mb-2 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="">Seleccione</option>
                                @foreach($responsablesTI as $responsable)
                                <option value="{{ $responsable->EmpleadoID }}">{{ $responsable->NombreEmpleado }}</option>
                                @endforeach
                            </select>

                            <label class="text-md font-semibold text-gray-600">Categoria <span class="text-red-500">*</span></label>
                            <select 
                                id="tipo-select"
                                x-model="ticketTipoID"
                                :disabled="selected.estatus === 'Cerrado'"
                                class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="">Seleccione</option>
                            </select>
                            
                            <label class="text-md font-semibold text-gray-600">Grupo <span class="text-red-500">*</span></label>
                            <select 
                                id="subtipo-select"
                                x-model="ticketSubtipoID"
                                class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black disabled:bg-gray-100 disabled:cursor-not-allowed" 
                                :disabled="!ticketTipoID || selected.estatus === 'Cerrado'">
                                <option value="">Seleccione</option>
                            </select>
                            
                            <label class="text-md font-semibold text-gray-600">Subgrupo</label>
                            <select 
                                id="tertipo-select"
                                x-model="ticketTertipoID"
                                class="w-full mt-1 rounded-md text-sm cursor-pointer transition-all duration-200 ease-in-out hover:border-black hover:ring-1 hover:ring-black disabled:bg-gray-100 disabled:cursor-not-allowed" 
                                :disabled="!ticketSubtipoID || selected.estatus === 'Cerrado'">
                                <option value="">Seleccione</option>
                            </select>

                            <!-- Botón Guardar Cambios -->
                            <button
                                @click="guardarCambiosTicket()"
                                :disabled="guardandoTicket"
                                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg transition flex items-center justify-center gap-2">
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
                            
                          
                            <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 transition p-2">
                                <span class="text-xl">x</span>
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
                                        {{-- Botones de Copia y Copia Oculta comentados
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
                                        --}}
                                    </div>
                                    
                                    {{-- Campos de Copia y Copia Oculta comentados
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
                                    --}}
                                    
                                    <!-- Campo Asunto -->
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-700 w-16 flex-shrink-0">Asunto: <span class="text-red-500">*</span></label>
                                        <input 
                                            type="text"
                                            x-model="asuntoCorreo"
                                            required
                                            readonly
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm focus:outline-none cursor-not-allowed"
                                            placeholder="Asunto del correo">
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Barra de herramientas personalizada comentada - Quill.js tiene su propia barra de herramientas integrada
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
                            --}}
                            
                            <!-- Sección de Adjuntos -->
                            <div class="border-b border-gray-200 p-3 bg-white">
                                <div x-show="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado'" class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-xs text-yellow-800 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <span>Este ticket está cerrado. No se pueden agregar nuevos mensajes o adjuntos.</span>
                                    </p>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <label 
                                        for="adjuntos"
                                        :class="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado') ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                                        class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition text-sm font-medium"
                                        :title="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado') ? 'El ticket está cerrado' : 'Adjuntar archivo'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                        <span>Elegir archivos</span>
                                    </label>
                                    <span x-show="archivosAdjuntos.length > 0" class="text-sm text-gray-600 font-medium">
                                        <span x-text="archivosAdjuntos.length"></span> archivo<span x-show="archivosAdjuntos.length !== 1">s</span>
                                    </span>
                                </div>
                                
                                <!-- Lista visual de archivos adjuntos -->
                                <div x-show="archivosAdjuntos.length > 0" class="mt-3 space-y-2">
                                    <template x-for="(archivo, index) in archivosAdjuntos" :key="index">
                                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
                                            <!-- Icono según tipo de archivo -->
                                            <div class="flex-shrink-0">
                                                <!-- Imagen -->
                                                <svg x-show="archivo.type && archivo.type.startsWith('image/')" 
                                                     class="w-6 h-6 text-green-600" 
                                                     fill="none" 
                                                     stroke="currentColor" 
                                                     viewBox="0 0 24 24"
                                                     style="display: none;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <!-- PDF -->
                                                <svg x-show="archivo.type && archivo.type === 'application/pdf'" 
                                                     class="w-6 h-6 text-red-600" 
                                                     fill="none" 
                                                     stroke="currentColor" 
                                                     viewBox="0 0 24 24"
                                                     style="display: none;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <!-- Word/Document -->
                                                <svg x-show="archivo.type && (archivo.type.includes('word') || archivo.type.includes('document') || archivo.name.endsWith('.doc') || archivo.name.endsWith('.docx'))" 
                                                     class="w-6 h-6 text-blue-600" 
                                                     fill="none" 
                                                     stroke="currentColor" 
                                                     viewBox="0 0 24 24"
                                                     style="display: none;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <!-- Genérico -->
                                                <svg x-show="!archivo.type || (!archivo.type.startsWith('image/') && archivo.type !== 'application/pdf' && !archivo.type.includes('word') && !archivo.type.includes('document') && !archivo.name.endsWith('.doc') && !archivo.name.endsWith('.docx'))" 
                                                     class="w-6 h-6 text-gray-600" 
                                                     fill="none" 
                                                     stroke="currentColor" 
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            
                                            <!-- Información del archivo -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate" x-text="archivo.name"></p>
                                                <p class="text-xs text-gray-500" x-text="formatearTamañoArchivo(archivo.size)"></p>
                                            </div>
                                            
                                            <!-- Botón para eliminar -->
                                <button 
                                    type="button"
                                                @click="eliminarArchivo(index)"
                                                :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado'"
                                                class="flex-shrink-0 p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado'"
                                    @change="manejarArchivosSeleccionados($event)">
                            </div>
                            
                            <!-- Área de Composición del Mensaje -->
                            <div class="p-4">
                                <textarea 
                                    id="editor-mensaje"
                                    x-model="nuevoMensaje"
                                    class="w-full"
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
                                        @click="limpiarEditor()"
                                        :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado'"
                                        class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                        Descartar
                                    </button>
                                    <button 
                                        @click="enviarRespuesta()"
                                        :disabled="selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado' || !tieneContenido() || !asuntoCorreo || asuntoCorreo.trim().length === 0"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium py-2 px-6 rounded-lg transition text-sm flex items-center gap-2"
                                        :title="(selected.estatus === 'Cerrado' || ticketEstatus === 'Cerrado') ? 'El ticket está cerrado' : 'El botón se activará cuando haya contenido en el mensaje y un asunto'">
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

                </main>

            </div>
        </div>
    </div>

    <!-- Modal de Métricas -->
    <div
        x-show="mostrarModalMetricas"
        x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="mostrarModalMetricas = false">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col"
            @click.stop>
            <!-- Header del Modal -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 flex justify-between items-center">
                <h2 class="text-white text-xl font-semibold flex items-center gap-2">
                    <i class="fas fa-chart-line"></i>
                    Ajustar Métricas de Tiempo Estimado
                </h2>
                <button
                    @click="mostrarModalMetricas = false"
                    class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="mb-4 text-sm text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Configure el tiempo estimado en minutos para cada tipo de ticket. Este tiempo es utilizado para las alertas de la resolución de tickets.
                </div>

                <!-- Tabla de Métricas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                    Tipo de Ticket
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                    Tiempo Estimado (Minutos)
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                    Equivalente
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-if="cargandoMetricas">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center">
                                        <div class="flex items-center justify-center gap-2 text-gray-500">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            <span>Cargando métricas...</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!cargandoMetricas && metricasTipos && metricasTipos.length > 0">
                                <template x-for="(tipo, index) in metricasTipos" :key="tipo.TipoID">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900" x-text="tipo.NombreTipo"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                :value="tipo.TiempoEstimadoMinutos || ''"
                                                @input="tipo.TiempoEstimadoMinutos = $event.target.value ? parseInt($event.target.value) : null; tipo.cambiado = true"
                                                placeholder="0"
                                                class="w-32 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm text-gray-600" x-text="formatearTiempo(tipo.TiempoEstimadoMinutos)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <template x-if="!cargandoMetricas && (!metricasTipos || metricasTipos.length === 0)">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center gap-2">
                                            <i class="fas fa-info-circle text-2xl"></i>
                                            <span>No hay tipos de tickets disponibles</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    <span x-text="`${metricasTipos.filter(t => t.cambiado).length} cambios pendientes`"></span>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="mostrarModalMetricas = false"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition">
                        Cancelar
                    </button>
                    <button
                        @click="guardarMetricas()"
                        :disabled="guardandoMetricas || metricasTipos.filter(t => t.cambiado).length === 0"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-save" :class="{'fa-spin': guardandoMetricas}"></i>
                        <span x-text="guardandoMetricas ? 'Guardando...' : 'Guardar Cambios'"></span>
                    </button>
                </div>
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
            tinyMCEInstance: null, // Instancia del editor TinyMCE
            archivosAdjuntos: [], // Array para almacenar los archivos seleccionados
            // Variables para detalles del ticket
            ticketPrioridad: '',
            ticketEstatus: '',
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
            elementosPorPagina: 10,
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

            init() {
                // Los datos de ticketsLista ya están inicializados desde el servidor
                // Preparar datos para tabla
                this.prepararDatosTabla();
             
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
                
                // Inicializar TinyMCE Editor
                this.$nextTick(() => {
                    this.inicializarTinyMCE();
                });
                
                // La actualización de mensajes se manejará mediante cron job
                // No se configura recarga automática
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
                    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
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
                this.$nextTick(() => {
                    const todosTickets = [];
                    ['nuevos', 'proceso', 'resueltos'].forEach(categoria => {
                        const elementos = document.querySelectorAll(`[data-categoria="${categoria}"]`);
                        elementos.forEach(el => {
                            todosTickets.push({
                                id: el.dataset.ticketId,
                                asunto: el.dataset.ticketAsunto,
                                descripcion: el.dataset.ticketDescripcion,
                                prioridad: el.dataset.ticketPrioridad,
                                empleado: el.dataset.ticketEmpleado,
                                anydesk: el.dataset.ticketAnydesk || '',
                                numero: el.dataset.ticketNumero || '',
                                correo: el.dataset.ticketCorreo,
                                fecha: el.dataset.ticketFecha,
                                estatus: categoria === 'nuevos' ? 'Pendiente' : (categoria === 'proceso' ? 'En progreso' : 'Cerrado'),
                                elemento: el
                            });
                        });
                    });
                    this.ticketsTabla = todosTickets;
                    this.ordenarTabla();
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
                            this.ticketResponsableTI = data.ticket.ResponsableTI ? String(data.ticket.ResponsableTI) : '';
                            this.ticketTipoID = data.ticket.TipoID ? String(data.ticket.TipoID) : '';
                            this.ticketSubtipoID = data.ticket.SubtipoID ? String(data.ticket.SubtipoID) : '';
                            this.ticketTertipoID = data.ticket.TertipoID ? String(data.ticket.TertipoID) : '';
                            
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
                            
                            // Actualizar los atributos data-* de todos los elementos del ticket en el DOM
                            this.$nextTick(() => {
                                // Buscar todos los elementos con el mismo ticket-id (puede haber múltiples en diferentes vistas)
                                const ticketElements = document.querySelectorAll(`[data-ticket-id="${this.selected.id}"]`);
                                
                                ticketElements.forEach(ticketElement => {
                                    // Actualizar atributos data-* del elemento
                                    ticketElement.setAttribute('data-ticket-prioridad', data.ticket.Prioridad);
                                    
                                    // Si el estatus cambió, actualizar la categoría del elemento (vista kanban)
                                    if (estatusAnterior !== data.ticket.Estatus && nuevaCategoria) {
                                        // Solo actualizar si el elemento tiene data-categoria (vista kanban)
                                        if (ticketElement.hasAttribute('data-categoria')) {
                                            ticketElement.setAttribute('data-categoria', nuevaCategoria);
                                        }
                                    }
                                    
                                    // Actualizar el badge de prioridad visualmente (todas las vistas)
                                    const badgesPrioridad = ticketElement.querySelectorAll('.text-xs.font-semibold.px-2, .text-xs.font-semibold.px-2.py-0\\.5, .text-xs.font-semibold.px-2.py-1');
                                    badgesPrioridad.forEach(badge => {
                                        // Verificar si es un badge de prioridad (no de estatus)
                                        const texto = badge.textContent.trim();
                                        if (texto === 'Baja' || texto === 'Media' || texto === 'Alta' || 
                                            texto === this.selected.prioridad || 
                                            badge.classList.contains('rounded-full')) {
                                            badge.textContent = data.ticket.Prioridad;
                                            // Actualizar clases de color según prioridad
                                            const clasesBase = badge.className.split(' ').filter(c => 
                                                !c.startsWith('bg-') && !c.startsWith('text-')
                                            ).join(' ');
                                            const clasesColor = data.ticket.Prioridad === 'Baja' 
                                                ? 'bg-green-200 text-green-600' 
                                                : data.ticket.Prioridad === 'Media' 
                                                ? 'bg-yellow-200 text-yellow-600' 
                                                : 'bg-red-200 text-red-600';
                                            badge.className = clasesBase + ' ' + clasesColor;
                                        }
                                    });
                                });
                                
                                // Actualizar los datos de la tabla (siempre, para que se refleje en todas las vistas)
                                // Esto actualizará los datos desde el DOM con los nuevos valores
                                this.prepararDatosTabla();
                                
                                // Esperar un momento para que prepararDatosTabla termine y luego actualizar manualmente
                                // el estatus en ticketsTabla para asegurar que se refleje correctamente
                        setTimeout(() => {
                                    if (this.ticketsTabla && this.ticketsTabla.length > 0) {
                                        const ticketEnTabla = this.ticketsTabla.find(t => t.id == this.selected.id);
                                        if (ticketEnTabla && nuevoEstatusTexto) {
                                            ticketEnTabla.prioridad = data.ticket.Prioridad;
                                            ticketEnTabla.estatus = nuevoEstatusTexto;
                                        }
                                    }
                                }, 100);
                                
                                // Actualizar estado del editor
                                this.actualizarEstadoEditor();
                            });
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
                
                // Limpiar y agregar todos los archivos seleccionados
                this.archivosAdjuntos = [];
                files.forEach(file => {
                    this.archivosAdjuntos.push(file);
                });
                
                // Forzar actualización de Alpine.js
                this.$nextTick(() => {
                    console.log('Archivos adjuntos actualizados:', this.archivosAdjuntos.length);
                });
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
                
                if (this.tinyMCEInstance) {
                    try {
                        // Cambiar el modo del editor a readonly si está cerrado
                        if (estaCerrado) {
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
                    textarea.disabled = estaCerrado;
                    if (estaCerrado) {
                        textarea.style.cursor = 'not-allowed';
                        textarea.style.backgroundColor = '#f3f4f6';
                    } else {
                        textarea.style.cursor = 'text';
                        textarea.style.backgroundColor = 'white';
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
                notification.className = `ticket-notification fixed top-4 right-4 z-[9999] p-4 rounded-lg shadow-2xl flex items-center gap-3 min-w-[300px] max-w-md ${bgColor} text-white`;
                
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
                
                document.body.appendChild(notification);
                
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
                // Limpiar la ruta: remover 'storage/app/public/' si existe
                let rutaLimpia = ruta.replace(/^storage\/app\/public\//, '');
                // Remover 'storage/' si ya está al inicio
                rutaLimpia = rutaLimpia.replace(/^storage\//, '');
                // Asegurar que tenga el prefijo 'tickets/' si no lo tiene
                if (!rutaLimpia.startsWith('tickets/')) {
                    rutaLimpia = `tickets/${rutaLimpia}`;
                }
                // Usar asset() de Laravel para construir la URL completa dinámica
                // Formato: http://127.0.0.1:8082/storage/tickets/6942ffdd84d33_Libro1 - copia.xlsx
                // asset('storage/...') genera la URL completa con el dominio y puerto
                const baseUrl = '{{ asset("") }}';
                return `${baseUrl}storage/${rutaLimpia}`;
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
                    console.log('Datos recibidos:', data);
                    
                    if (data.success && data.tipos && Array.isArray(data.tipos)) {
                        this.metricasTipos = data.tipos.map(tipo => ({
                            TipoID: tipo.TipoID,
                            NombreTipo: tipo.NombreTipo,
                            TiempoEstimadoMinutos: tipo.TiempoEstimadoMinutos || null,
                            cambiado: false
                        }));
                        console.log('Métricas cargadas:', this.metricasTipos);
                    } else {
                        console.error('Respuesta inválida:', data);
                        this.mostrarNotificacion(data.message || 'Error cargando métricas', 'error');
                        this.metricasTipos = [];
                    }
                } catch (error) {
                    console.error('Error cargando métricas:', error);
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
                    console.error('Error guardando métricas:', error);
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
            }
        }
    }

   
    // Hacer las funciones accesibles globalmente para que puedan ser llamadas desde Alpine.js
    window.loadSubtipos = null;
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
                    console.error('Error cargando tipos:', data.message);
                }
            } catch (error) {
                console.error('Error en la petición de tipos:', error);
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
                } else {
                    console.log('No hay subtipos disponibles para este tipo');
                }
            } catch (error) {
                console.error('Error en la petición de subtipos:', error);
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