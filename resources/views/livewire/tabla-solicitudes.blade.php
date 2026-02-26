<div x-data="solicitudesData()">

    <div class="rounded-lg shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">

        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Solicitudes de Equipos TI</h2>
        </div>

        <div wire:poll.15s>
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex gap-3 flex-wrap">
                    <div class="flex-1 max-w-xs">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Estatus</label>
                        <select wire:model.live="filtroEstatus" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200">
                            <option value="">Todos los estatus</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En revisión">En revisión</option>
                            <option value="Cotizaciones Enviadas">Cotizaciones Enviadas</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Rechazada">Rechazada</option>
                        </select>
                    </div>
                    <div class="flex-1 max-w-sm relative">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Buscar</label>
                        <div class="relative">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Buscar por ID, empleado o motivo..."
                                class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-slate-400"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Mostrar</label>
                        <select wire:model.live="perPage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    @if($filtroEstatus)
                    <button wire:click="$set('filtroEstatus', '')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-6">
                        Limpiar filtro
                    </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-100 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Empleado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Motivo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estatus</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aprobaciones</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Facturas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Creado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-transparent">

                        @forelse ($todasSolicitudes as $solicitud)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b border-slate-100 dark:border-slate-800">

                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">#{{ $solicitud->SolicitudID }}</div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $solicitud->nombreFormateado }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($solicitud->empleadoid->Correo ?? 'N/A', 25) }}</div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-700 dark:text-slate-300">{{ Str::limit($solicitud->Motivo ?? 'N/A', 30) }}</div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $solicitud->colorEstatus }}">
                                    {{ $solicitud->estatusDisplay }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($solicitud->pasoSupervisor)
                                    @if($solicitud->pasoSupervisor->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Vo.bo supervisor: Aprobado"></i>
                                    @elseif($solicitud->pasoSupervisor->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Vo.bo supervisor: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-yellow-500 dark:text-yellow-400" title="Vo.bo supervisor: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Vo.bo supervisor: Pendiente"></i>
                                    @endif

                                    @if($solicitud->pasoGerencia)
                                    @if($solicitud->pasoGerencia->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Gerente (propuestas): Aprobado"></i>
                                    @elseif($solicitud->pasoGerencia->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Gerente (propuestas): Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-orange-500 dark:text-orange-400" title="Gerente (propuestas): Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Gerente (propuestas): Esperando"></i>
                                    @endif

                                    @if($solicitud->pasoAdministracion)
                                    @if($solicitud->pasoAdministracion->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Administración (ganadores): Aprobado"></i>
                                    @elseif($solicitud->pasoAdministracion->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Administración (ganadores): Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-purple-500 dark:text-purple-400" title="Administración (ganadores): Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Administración (ganadores): Esperando"></i>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($solicitud->totalFacturasNecesarias > 0)
                                <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $solicitud->facturasSubidas }}/{{ $solicitud->totalFacturasNecesarias }}</span>
                                @else
                                <span class="text-sm text-slate-400 dark:text-slate-500">0/0</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-slate-700 dark:text-slate-300">{{ $solicitud->created_at->format('d/m/Y') }}</div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button
                                        @click="abrirModal({{ $solicitud->SolicitudID }})"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </button>

                                    @if($solicitud->puedeCotizar)
                                    <a href="{{ route('solicitudes.cotizar', $solicitud->SolicitudID) }}"
                                        class="text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-300 text-sm font-medium transition-colors no-underline">
                                        <i class="fas fa-file-invoice-dollar mr-1"></i> Cotizar
                                    </a>
                                    @endif

                                    @if($solicitud->puedeSubirFactura)
                                    <button
                                        type="button"
                                        wire:click="abrirModalAsignacion({{ $solicitud->SolicitudID }})"
                                        class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 text-sm font-medium transition-colors">
                                        <i class="fas fa-file-invoice mr-1"></i> Asignacion
                                    </button>
                                    @endif

                                    <!-- {{-- Botón Reenviar --}}
                                    <button
                                        type="button"
                                        wire:click="reenviarCorreo({{ $solicitud->SolicitudID }})"
                                        class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 text-sm font-medium transition-colors"
                                        title="Reenviar correos a pendientes">
                                        <i class="fas fa-paper-plane mr-1"></i> Reenviar
                                    </button>

                                    {{-- Botón Cerrar/Cancelar Solicitud --}}
                                    <button
                                        type="button"
                                        wire:click="abrirModalCancelacion({{ $solicitud->SolicitudID }})"
                                        class="text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 text-sm font-medium transition-colors"
                                        title="Cerrar solicitud por parte de TI">
                                        <i class="fas fa-ban mr-1"></i> Cerrar
                                    </button> -->
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <i class="fas fa-inbox text-4xl mb-3 block text-slate-300 dark:text-slate-600"></i>
                                <p class="text-lg font-medium">No hay solicitudes registradas</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-gradient-to-r from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Info de resultados -->
                    <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <i class="fas fa-list-ul text-slate-400"></i>
                        <span>
                            Mostrando 
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $todasSolicitudes->firstItem() ?? 0 }}</span>
                            a 
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $todasSolicitudes->lastItem() ?? 0 }}</span>
                            de 
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $todasSolicitudes->total() }}</span>
                            solicitudes
                        </span>
                    </div>

                    <!-- Navegación de páginas -->
                    @if($todasSolicitudes->hasPages())
                    <nav class="flex items-center gap-1">
                        @if($todasSolicitudes->onFirstPage())
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </span>
                        @else
                            <button 
                                wire:click="previousPage" 
                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                        @endif

                        @foreach($todasSolicitudes->getUrlRange(max(1, $todasSolicitudes->currentPage() - 1), min($todasSolicitudes->lastPage(), $todasSolicitudes->currentPage() + 1)) as $page => $url)
                            @if($page == $todasSolicitudes->currentPage())
                                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white font-semibold shadow-md shadow-blue-500/25 dark:from-blue-600 dark:to-blue-700">
                                    {{ $page }}
                                </span>
                            @else
                                <button 
                                    wire:click="gotoPage({{ $page }})" 
                                    class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 transition-all duration-200 font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach

                        @if($todasSolicitudes->currentPage() < $todasSolicitudes->lastPage() - 2)
                            <span class="inline-flex items-center justify-center w-10 h-10 text-slate-400 dark:text-slate-500">
                                <i class="fas fa-ellipsis-h text-xs"></i>
                            </span>
                            <button 
                                wire:click="gotoPage({{ $todasSolicitudes->lastPage() }})" 
                                class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 transition-all duration-200 font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100">
                                {{ $todasSolicitudes->lastPage() }}
                            </button>
                        @endif

                        @if($todasSolicitudes->hasMorePages())
                            <button 
                                wire:click="nextPage" 
                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        @else
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </span>
                        @endif
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-show="modalAbierto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
            @click.self="cerrarModal()"
            style="display: none;">

            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-xl rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">

                <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                        Detalles de Solicitud
                        <span
                            x-show="solicitudSeleccionada"
                            x-text="'#' + solicitudSeleccionada?.SolicitudID"
                            class="text-slate-500 dark:text-slate-400 ml-2">
                        </span>
                    </h3>
                    <button
                        @click="cerrarModal()"
                        class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mt-4">
                    <div x-show="cargando" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-slate-400 dark:text-slate-600"></i>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Cargando información...</p>
                    </div>

                    <div x-show="!cargando && solicitudSeleccionada" style="display: none;">

                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-user text-blue-500 dark:text-blue-400"></i>
                                Información del Solicitante
                            </h4>
                            <div class="grid grid-cols-2 gap-4 p-4 rounded-lg bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Nombre</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="solicitudSeleccionada?.empleado?.NombreEmpleado"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Correo</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.empleado?.Correo"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Gerencia</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.gerencia?.NombreGerencia || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Obra</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.obra?.NombreObra || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Puesto</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.puesto?.NombrePuesto || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Ubicación</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.ProyectoNombre || solicitudSeleccionada?.Proyecto || 'N/A'"></p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-file-alt text-emerald-500 dark:text-emerald-400"></i>
                                Detalles de la Solicitud
                            </h4>
                            <div class="p-4 rounded-lg space-y-3 bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Motivo</label>
                                    <p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="solicitudSeleccionada?.Motivo || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Descripción del Motivo</label>
                                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="solicitudSeleccionada?.DescripcionMotivo || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Requerimientos</label>
                                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="solicitudSeleccionada?.Requerimientos || 'N/A'"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                                    <div>
                                        <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Estatus</label>
                                        <p class="text-sm font-semibold"
                                            :class="{
                                               'text-amber-600 dark:text-amber-400': (solicitudSeleccionada?.estatusDisplay || '') === 'Pendiente',
                                               'text-red-600 dark:text-red-400': (solicitudSeleccionada?.estatusDisplay || '') === 'Rechazada',
                                               'text-sky-600 dark:text-sky-400': (solicitudSeleccionada?.estatusDisplay || '') === 'En revisión',
                                               'text-emerald-600 dark:text-emerald-400': (solicitudSeleccionada?.estatusDisplay || '') === 'Aprobada',
                                               'text-blue-600 dark:text-blue-400': (solicitudSeleccionada?.estatusDisplay || '') === 'Cotizaciones Enviadas',
                                               'text-slate-900 dark:text-slate-100': !['Pendiente','Rechazada','En revisión','Aprobada','Cotizaciones Enviadas'].includes(solicitudSeleccionada?.estatusDisplay || '')
                                           }"
                                            x-text="solicitudSeleccionada?.estatusDisplay || 'Pendiente'"></p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Fecha de Creación</label>
                                        <p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.fechaCreacion || 'N/A'"></p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2" x-show="solicitudSeleccionada?.puedeCotizar">
                                    <a :href="'/solicitudes/' + (solicitudSeleccionada?.SolicitudID || '') + '/cotizar'"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 dark:bg-violet-700 dark:hover:bg-violet-600 text-white text-sm font-medium rounded-lg transition shadow-sm no-underline">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <span x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0 ? 'Editar cotizaciones' : 'Cotizar'"></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-tasks text-purple-500 dark:text-purple-400"></i>
                                Flujo de Aprobación
                            </h4>
                            <div class="mb-4 p-4 rounded-lg bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 space-y-2">
                                <p><strong>1. Solicitud</strong> → Vo.bo de supervisor</p>
                                <p><strong>2. TI</strong> → Envía cotización → <strong>Gerente</strong> ve propuestas, elige ganador o regresa a TI para cotizar de nuevo (al aprobar pasa a Administración)</p>
                                <p><strong>3. Administración</strong> → Ve los ganadores y aprueba la solicitud</p>
                            </div>
                            <div class="space-y-4">
                                <template x-for="(paso, index) in solicitudSeleccionada?.pasosAprobacion || []" :key="index">
                                    <div class="p-4 rounded-lg border-l-4 bg-slate-50 dark:bg-slate-800 shadow-sm border-t border-r border-b border-slate-200 dark:border-slate-700"
                                        :class="{
                                             'border-l-green-500': paso.status === 'approved',
                                             'border-l-red-500': paso.status === 'rejected',
                                             'border-l-yellow-500': paso.status === 'pending'
                                         }">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <i class="fas"
                                                    :class="{
                                                       'fa-check-circle text-green-500 dark:text-green-400': paso.status === 'approved',
                                                       'fa-times-circle text-red-500 dark:text-red-400': paso.status === 'rejected',
                                                       'fa-circle text-yellow-500 dark:text-yellow-400': paso.status === 'pending'
                                                   }"></i>
                                                <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="paso.stageLabel"></span>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded font-medium"
                                                :class="{
                                                      'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300': paso.status === 'approved',
                                                      'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300': paso.status === 'rejected',
                                                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300': paso.status === 'pending'
                                                  }"
                                                x-text="paso.statusLabel"></span>
                                        </div>
                                        <div class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                                            <p><span class="font-medium text-slate-700 dark:text-slate-300">Aprobador asignado:</span> <span x-text="paso.approverNombre || 'N/A'"></span></p>
                                            <p x-show="paso.decidedByNombre"><span class="font-medium text-slate-700 dark:text-slate-300">Aprobado por:</span> <span x-text="paso.decidedByNombre"></span></p>
                                            <p x-show="paso.decidedAt"><span class="font-medium text-slate-700 dark:text-slate-300">Fecha:</span> <span x-text="paso.decidedAt"></span></p>
                                            <p x-show="paso.comment"><span class="font-medium text-slate-700 dark:text-slate-300">Comentario:</span> <span class="italic" x-text="paso.comment"></span></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mb-6" x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0" x-data="{
                            selectedIndexes: {},
                            getCotizacionesAgrupadasPorPropuesta() {
                                const cots = solicitudSeleccionada?.cotizaciones || [];
                                const activosPorCot = solicitudSeleccionada?.activosPorCotizacion || {};
                                
                                // Agrupar por NumeroPropuesta
                                const grupos = {};
                                cots.forEach(c => {
                                    const propuesta = c.NumeroPropuesta || 0;
                                    if (!grupos[propuesta]) {
                                        grupos[propuesta] = {
                                            numeroPropuesta: propuesta,
                                            nombreEquipo: c.NombreEquipo || 'Equipo',
                                            cotizaciones: []
                                        };
                                    }
                                    
                                    const activos = activosPorCot[c.CotizacionID] || [];
                                    grupos[propuesta].cotizaciones.push({
                                        ...c,
                                        activos: activos,
                                        esGanador: c.Estatus === 'Seleccionada'
                                    });
                                });
                                
                                // Convertir a array y ordenar cotizaciones dentro de cada grupo
                                return Object.values(grupos).map(grupo => {
                                    // Ordenar: ganadoras primero, luego las demás
                                    grupo.cotizaciones.sort((a, b) => {
                                        if (a.esGanador && !b.esGanador) return -1;
                                        if (!a.esGanador && b.esGanador) return 1;
                                        return 0;
                                    });
                                    return grupo;
                                }).sort((a, b) => a.numeroPropuesta - b.numeroPropuesta);
                            },
                            selectCotizacion(propuesta, index) {
                                this.selectedIndexes[propuesta] = index;
                            },
                            getSelectedIndex(propuesta) {
                                return this.selectedIndexes[propuesta] || 0;
                            }
                        }">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-trophy text-amber-500 dark:text-amber-400"></i>
                                Cotizaciones y Asignaciones
                            </h4>
                            <div class="space-y-6">
                                <template x-for="(grupo, gIndex) in getCotizacionesAgrupadasPorPropuesta()" :key="'grupo-' + grupo.numeroPropuesta">
                                    <div class="rounded-lg border-2 border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800/30">
                                        <!-- Header del grupo -->
                                        <div class="px-4 py-3 bg-gradient-to-r from-violet-100 to-violet-50 dark:from-violet-900/30 dark:to-violet-800/20 border-b-2 border-violet-200 dark:border-violet-800">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xs font-bold uppercase tracking-wider text-violet-700 dark:text-violet-300">
                                                        Producto <span x-text="grupo.numeroPropuesta"></span>
                                                    </span>
                                                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="grupo.nombreEquipo"></span>
                                                </div>
                                                <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                                                    <span x-text="grupo.cotizaciones.length"></span> cotización(es)
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Botones de navegación si hay múltiples cotizaciones -->
                                        <template x-if="grupo.cotizaciones.length > 1">
                                            <div class="px-4 py-3 bg-slate-100 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                                    <template x-for="(cot, idx) in grupo.cotizaciones" :key="'btn-' + cot.CotizacionID">
                                                        <button
                                                            type="button"
                                                            @click="selectCotizacion(grupo.numeroPropuesta, idx)"
                                                            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                                                            :class="getSelectedIndex(grupo.numeroPropuesta) === idx 
                                                                ? (cot.esGanador 
                                                                    ? 'bg-emerald-500 text-white shadow-md' 
                                                                    : 'bg-red-500 text-white shadow-md')
                                                                : (cot.esGanador 
                                                                    ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300' 
                                                                    : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300')">
                                                            <i :class="cot.esGanador ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-2"></i>
                                                            <span x-text="cot.Proveedor || 'Opción ' + (idx + 1)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Cotización seleccionada -->
                                        <template x-for="(cotizacion, cIndex) in grupo.cotizaciones" :key="cotizacion.CotizacionID || cIndex">
                                            <div x-show="getSelectedIndex(grupo.numeroPropuesta) === cIndex"
                                                class="p-5 transition-opacity duration-200">
                                                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg border-2 shadow-sm p-4"
                                                    :class="cotizacion.esGanador ? 'border-emerald-400 dark:border-emerald-600' : 'border-red-400 dark:border-red-600'">
                                        
                                        <div class="flex items-center gap-2 mb-3 pb-2 border-b"
                                            :class="cotizacion.esGanador ? 'border-emerald-100 dark:border-emerald-800/30' : 'border-red-100 dark:border-red-800/30'">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded"
                                                :class="cotizacion.esGanador ? 'text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/40' : 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/40'">
                                                <i :class="cotizacion.esGanador ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                                <span x-text="cotizacion.Estatus || 'Pendiente'"></span>
                                            </span>
                                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="cotizacion.NombreEquipo || 'Equipo'"></span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Proveedor</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-semibold" x-text="cotizacion.Proveedor || 'N/A'"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">No. Parte</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-mono" x-text="cotizacion.NumeroParte || 'N/A'"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Cantidad</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-bold" x-text="cotizacion.Cantidad || 1"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Precio Unitario</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-semibold" x-text="cotizacion.Precio != null ? ('$' + parseFloat(cotizacion.Precio).toLocaleString('es-MX', {minimumFractionDigits: 2})) : 'N/A'"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Costo Envío</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-semibold" x-text="cotizacion.CostoEnvio != null ? ('$' + parseFloat(cotizacion.CostoEnvio).toLocaleString('es-MX', {minimumFractionDigits: 2})) : '$0.00'"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Final</label>
                                                <p class="text-sm font-bold"
                                                    :class="cotizacion.esGanador ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-900 dark:text-slate-200'"
                                                    x-text="(() => {
                                                        const precio = parseFloat(cotizacion.Precio || 0);
                                                        const cantidad = parseInt(cotizacion.Cantidad || 1);
                                                        const envio = parseFloat(cotizacion.CostoEnvio || 0);
                                                        const total = (precio * cantidad) + envio;
                                                        return '$' + total.toLocaleString('es-MX', {minimumFractionDigits: 2});
                                                    })()"></p>
                                            </div>
                                        </div>

                                        <!-- Fechas de entrega solo para ganadores -->
                                        <template x-if="cotizacion.esGanador && cotizacion.activos && cotizacion.activos.length > 0">
                                            <div class="mt-4 pt-4 border-t border-emerald-200 dark:border-emerald-800">
                                                <h5 class="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-300 mb-3 flex items-center gap-2">
                                                    <i class="fas fa-calendar-check"></i>
                                                    Asignaciones y Fechas de Entrega
                                                </h5>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <template x-for="(activo, aIdx) in cotizacion.activos" :key="activo.SolicitudActivoID || aIdx">
                                                        <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                                                            <div class="flex items-center justify-between mb-2">
                                                                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                                                    Unidad <span x-text="activo.UnidadIndex + 1"></span>
                                                                </span>
                                                                <span class="text-xs font-bold"
                                                                    :class="activo.FechaEntrega ? 'text-teal-600 dark:text-teal-400' : 'text-amber-600 dark:text-amber-400'">
                                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                                    <span x-text="activo.FechaEntrega || 'Pendiente'"></span>
                                                                </span>
                                                            </div>
                                                            <div class="text-xs text-slate-600 dark:text-slate-400">
                                                                <span class="font-medium">Asignado a:</span>
                                                                <span x-text="activo.EmpleadoAsignado?.NombreEmpleado || 'Sin asignar'"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Descripción -->
                                        <template x-if="cotizacion.Descripcion && cotizacion.Descripcion.trim()">
                                            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                                <div class="flex items-start gap-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                                    <div class="flex-1 min-w-0">
                                                        <h6 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">
                                                            Descripción del Producto
                                                        </h6>
                                                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap" x-text="cotizacion.Descripcion"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </template>

    @if($modalAsignacionAbierto)
    <div
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm"
        wire:click.self="closeAsignacion"
        wire:keydown.escape.window="closeAsignacion">

        <div class="relative w-full max-w-6xl mx-4 bg-slate-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden">

            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            Asignación y Provisión de Activos
                        </h3>

                        @if($asignacionSolicitudId)
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold
                             bg-sky-50 text-sky-700 border border-sky-100
                             dark:bg-sky-900/25 dark:text-sky-200 dark:border-sky-800/60">
                            <span class="text-sky-700/70 dark:text-sky-200/70">Solicitud</span>
                            <span class="font-bold">#{{ $asignacionSolicitudId }}</span>
                        </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Defina responsables técnicos por ítem y complete checklist.
                    </p>
                </div>

                <button type="button"
                    class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:text-slate-500 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition-colors"
                    wire:click="closeAsignacion"
                    aria-label="Cerrar">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            @php
            $proveedoresAgrupados = collect($propuestasAsignacion)->groupBy('proveedor')->filter(fn($g) => $g->count() > 1);
            $tieneProveedoresRepetidos = $proveedoresAgrupados->isNotEmpty();
            @endphp

            @if($tieneProveedoresRepetidos)
            <div class="px-6 py-4 bg-slate-50 border-b border-blue-100/60 dark:border-blue-800/30 dark:bg-slate-800/50 dark:text-slate-200">
                <div class="flex flex-row items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Cualquier propuesta que comparta el mismo proveedor se adjuntará automáticamente la misma factura. Proveedores con múltiples propuestas:
                            </span>
                            @foreach($proveedoresAgrupados->keys() as $proveedor)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-blue-200 dark:border-blue-800/50 shadow-sm">
                                <div class="w-2 h-2 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600"></div>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $proveedor }}</span>
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">
                @if(empty($propuestasAsignacion))
                <div class="py-10 text-center text-slate-500 dark:text-slate-400">
                    No hay datos para asignación.
                </div>
                @else

                @foreach($propuestasAsignacion as $pIndex => $p)
                <div class="group rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-gradient-to-b from-white to-slate-50/50 dark:from-slate-900 dark:to-slate-800/30 shadow-sm hover:shadow-md transition-all duration-300"
                    wire:key="prop-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $p['cotizacionId'] ?? 'x' }}">

                    <!-- Header del producto -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r rounded-t-2xl from-slate-50/80 to-white dark:from-slate-800/50 dark:to-slate-900">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="min-w-0">
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                                        {{ $p['nombreEquipo'] ?? 'Sin nombre' }}
                                    </h4>
                                    <div class="flex items-center gap-3 mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        <span class="inline-flex items-center gap-1.5">
                                            <i class="fas fa-building text-xs"></i>
                                            {{ $p['proveedor'] ?? 'Sin proveedor' }}
                                        </span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <i class="fas fa-boxes text-xs"></i>
                                            {{ (int)($p['itemsTotal'] ?? 0) }} unidades
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30">
                                <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    Precio unitario
                                </span>
                                <span class="text-xl font-bold text-emerald-700 dark:text-emerald-300">
                                    ${{ number_format((float)($p['precioUnitario'] ?? 0), 2, '.', ',') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Encabezado de columnas -->
                    <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-slate-100/60 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800">
                        <div class="col-span-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">#</div>
                        <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Descripción</div>
                        <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Factura</div>
                        <div class="col-span-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Fecha de entrega</div>
                        <div class="col-span-4 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Usuario final</div>
                    </div>

                    <!-- Unidades -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @foreach(($p['unidades'] ?? []) as $uIndex => $u)
                        <div class="px-6 py-5 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors duration-200 relative z-[{{ 100 - $uIndex }}]"
                            wire:key="unit-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}-{{ $u['unidadIndex'] ?? ($uIndex+1) }}">

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

                                <!-- Número de unidad -->
                                <div class="col-span-1 flex lg:justify-center">
                                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold shadow-inner">
                                        {{ $u['unidadIndex'] ?? ($uIndex + 1) }}
                                    </span>
                                </div>

                                <!-- Descripción del item -->
                                <div class="col-span-2">
                                    <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Descripción</label>
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-relaxed">
                                        {{ $p['nombreEquipo'] ?? 'Producto' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        Unidad {{ $u['unidadIndex'] ?? ($uIndex + 1) }}
                                    </div>
                                </div>

                                <!-- Factura -->
                                <div class="col-span-2">
                                    <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Factura</label>
                                    @php
                                    $facturaPath = $u['factura_path'] ?? null;
                                    $hasPath = !empty($facturaPath);
                                    $hasNew = isset($facturas[$pIndex][$uIndex]) && $facturas[$pIndex][$uIndex];
                                    $fileLabel = $hasPath ? basename($facturaPath) : null;

                                    $proveedorUnico = collect($propuestasAsignacion)->pluck('proveedor')->unique();
                                    $tieneProveedorUnico = $proveedorUnico->count() === 1;
                                    @endphp

                                    <div class="flex items-center gap-2">
                                        <label class="group/btn relative inline-flex items-center gap-2.5 h-11 px-4 rounded-xl border-2 border-dashed cursor-pointer transition-all duration-200
                                              {{ ($hasNew || $hasPath)
                                                    ? 'bg-emerald-50 border-emerald-300 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-400 dark:bg-emerald-900/20 dark:border-emerald-700/50 dark:text-emerald-300 dark:hover:bg-emerald-900/30'
                                                    : 'bg-slate-50 border-slate-300 text-slate-600 hover:bg-slate-100 hover:border-slate-400 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 dark:hover:border-slate-500' }}">
                                            <input type="file" class="hidden" accept="application/pdf" wire:model="facturas.{{ $pIndex }}.{{ $uIndex }}">
                                            <i class="fas {{ ($hasNew || $hasPath) ? 'fa-check-circle' : 'fa-cloud-upload-alt' }} text-base transition-transform group-hover/btn:scale-110"></i>
                                            <span class="text-sm font-medium truncate max-w-[7rem]">
                                                {{ ($hasNew || $hasPath) ? ($fileLabel ?: 'Adjunto') : 'Subir PDF' }}
                                            </span>
                                        </label>
                                    </div>

                                    @error("facturas.$pIndex.$uIndex")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                    @enderror

                                    <div wire:loading wire:target="facturas.{{ $pIndex }}.{{ $uIndex }}" class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        {{ $tieneProveedorUnico ? 'Aplicando a todas las unidades...' : 'Subiendo archivo...' }}
                                    </div>
                                </div>

                                <!-- Fecha de entrega -->
                                <div class="col-span-3">
                                    <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Fecha de entrega</label>
                                    <div class="relative">

                                        <input
                                            type="date"
                                            wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.fecha_entrega"
                                            class="h-11 w-full pl-3 pr-4 text-sm border-2 border-slate-200 rounded-xl bg-slate-50 shadow-sm
                                                   focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all
                                                   dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:focus:border-blue-400">
                                    </div>

                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.fecha_entrega")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                    @enderror
                                </div>

                                <!-- Usuario final -->
                                <div class="col-span-4 relative">
                                    <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">
                                        Usuario final
                                    </label>

                                    <div class="relative">
                                        <input
                                            type="text"
                                            wire:model.live.debounce.250ms="usuarioSearch.{{ $pIndex }}.{{ $uIndex }}"
                                            autocomplete="off"
                                            class="h-11 w-full pl-7 pr-4 text-sm border-2 border-slate-200 rounded-xl bg-slate-50 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all relative z-20 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:focus:border-blue-400"
                                            placeholder="Buscar empleado...">

                                        @php
                                        $opts = $usuarioOptions[$pIndex][$uIndex] ?? [];
                                        @endphp

                                        @if(!empty($opts))
                                        <div
                                            class="absolute top-full left-0 right-0 z-[99999] mt-1 max-h-64 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 shadow-2xl overflow-y-auto">
                                            @foreach($opts as $opt)
                                            <button
                                                type="button"
                                                wire:click.prevent="seleccionarEmpleado({{ $pIndex }}, {{ $uIndex }}, {{ (int) $opt['id'] }})"
                                                class="w-full px-3 py-2.5 text-left hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0">
                                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100 leading-tight truncate">
                                                    {{ $opt['name'] }}
                                                </div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">
                                                    {{ $opt['correo'] }}
                                                </div>
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif

                                    </div>

                                    <div class="mt-2.5 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-slate-100 to-slate-50 border border-slate-200 text-xs dark:from-slate-800 dark:to-slate-800/50 dark:border-slate-700">
                                        <i class="fas fa-sitemap text-slate-400 dark:text-slate-500"></i>
                                        <span class="text-slate-500 dark:text-slate-400">Departamento:</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">
                                            {{ $u['departamento_nombre'] ?? '-' }}
                                        </span>
                                    </div>

                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.empleado_id")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                    @enderror
                                </div>

                            </div>

                            @php
                            $hasChecklistItems = !empty($u['checklist'] ?? []);
                            @endphp

                            @if($hasChecklistItems)
                            <div
                                class="group/details mt-5"
                                x-data="{ open: false }"
                                wire:key="checklist-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="w-full flex items-center justify-between gap-4 px-4 py-3 rounded-xl bg-gradient-to-r from-slate-100/80 to-slate-50/50 dark:from-slate-800/60 dark:to-slate-800/30 border border-slate-200/60 dark:border-slate-700/60 hover:from-slate-100 hover:to-slate-50 dark:hover:from-slate-800 dark:hover:to-slate-800/50 cursor-pointer transition-all duration-200">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-md shadow-purple-500/20">
                                            <i class="fas fa-tasks text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200">Checklist de configuración</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                                            <i class="fas fa-chevron-down text-xs text-slate-500 dark:text-slate-400 transition-transform duration-300" :class="{ 'rotate-180': open }"></i>
                                        </span>
                                    </div>
                                </button>

                                <div x-show="open" x-transition class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    @foreach(($u['checklist'] ?? []) as $catKey => $items)
                                    @if(!empty($items))
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                        <div class="px-4 py-3 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 border-b border-slate-100 dark:border-slate-800">
                                            <div class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                                                {{ $catKey }}
                                            </div>
                                        </div>

                                        <div class="p-4 space-y-3">
                                            @foreach($items as $idx => $item)
                                            <div class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                                <label class="relative flex items-center justify-center cursor-pointer mt-0.5">
                                                    <input
                                                        type="checkbox"
                                                        wire:model.live="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.checklist.{{ $catKey }}.{{ $idx }}.realizado"
                                                        class="peer sr-only">

                                                    <div
                                                        class="relative w-5 h-5 rounded-md border-2 border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-800 peer-focus:ring-2 peer-focus:ring-green-500/20 peer-checked:bg-green-500 peer-checked:border-green-500 transition-all duration-200 flex items-center justify-center">

                                                        <svg
                                                            class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                                                            viewBox="0 0 20 20"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="3"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <polyline points="4 11 8 15 16 6"></polyline>
                                                        </svg>
                                                    </div>
                                                </label>

                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm text-slate-800 dark:text-slate-200">
                                                        {{ $item['nombre'] ?? '—' }}
                                                    </div>
                                                </div>

                                                <input
                                                    type="text"
                                                    wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.checklist.{{ $catKey }}.{{ $idx }}.responsable"
                                                    readonly
                                                    class="h-8 w-24 px-2.5 text-xs border border-slate-200 rounded-lg bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 text-center font-medium"
                                                    placeholder="-">
                                            </div>

                                            @error("propuestasAsignacion.$pIndex.unidades.$uIndex.checklist.$catKey.$idx.responsable")
                                            <p class="text-xs text-red-600 dark:text-red-400 px-2">{{ $message }}</p>
                                            @enderror
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif


                        </div>
                        @endforeach
                    </div>

                </div>
                @endforeach

                @endif
            </div>

            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3 bg-slate-50 dark:bg-slate-900">
                <button
                    type="button"
                    wire:click="closeAsignacion"
                    class="px-4 py-2 text-sm rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-600 dark:hover:bg-slate-700"
                    wire:loading.attr="disabled">
                    Cancelar
                </button>

                <button
                    type="button"
                    wire:click="guardarAsignacion"
                    class="px-4 py-2 text-sm rounded-lg bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600"
                    wire:loading.attr="disabled">
                    Guardar avance
                </button>
            </div>

            <div wire:loading wire:target="guardarAsignacion" class="absolute inset-0 bg-slate-500/60 dark:bg-slate-900/60 flex items-center justify-center z-50">
                <div class="flex flex-col items-center gap-3">
                    <i class="fas fa-spinner fa-spin text-3xl text-slate-600 dark:text-slate-300"></i>
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-200">
                        Guardando...
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endif

</div>