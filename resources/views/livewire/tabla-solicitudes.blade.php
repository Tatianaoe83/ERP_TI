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
                            <option value="Re-cotizar">Re-cotizar</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Listo">Listo</option>
                            <option value="Rechazada">Rechazada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="flex-1 max-w-sm relative">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text"
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

                        {{-- COLUMNA: ESTATUS --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $solicitud->colorEstatus }}"
                                @if($solicitud->estatusDisplay === 'Re-cotizar' && !empty($solicitud->recotizarPropuestasText))
                                    title="Recotizar propuesta(s): {{ trim($solicitud->recotizarPropuestasText, ' ()') }}"
                                @endif>
                                {{ $solicitud->estatusDisplay }}{{ $solicitud->recotizarPropuestasText ?? '' }}
                            </span>
                        </td>

                        {{-- COLUMNA: APROBACIONES --}}
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
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Gerente: Aprobado"></i>
                                    @elseif($solicitud->pasoGerencia->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Gerente: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-orange-500 dark:text-orange-400" title="Gerente: Pendiente"></i>
                                    @endif
                                @else
                                <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Gerente: Esperando"></i>
                                @endif

                                @if($solicitud->pasoAdministracion)
                                    @if($solicitud->pasoAdministracion->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Administración: Aprobado"></i>
                                    @elseif($solicitud->pasoAdministracion->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Administración: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-purple-500 dark:text-purple-400" title="Administración: Pendiente"></i>
                                    @endif
                                @else
                                <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Administración: Esperando"></i>
                                @endif
                            </div>
                        </td>

                        {{-- COLUMNA: FACTURAS --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($solicitud->totalFacturasNecesarias > 0)
                                @if($solicitud->facturasSubidas >= $solicitud->totalFacturasNecesarias)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800" title="Todas las facturas subidas">
                                        <i class="fas fa-check-circle"></i> {{ $solicitud->facturasSubidas }}/{{ $solicitud->totalFacturasNecesarias }} Completas
                                    </span>
                                @elseif($solicitud->facturasSubidas > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800" title="Faltan facturas por subir">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $solicitud->facturasSubidas }}/{{ $solicitud->totalFacturasNecesarias }} Parcial
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800" title="Falta subir facturas">
                                        <i class="fas fa-times-circle"></i> Faltan (0/{{ $solicitud->totalFacturasNecesarias }})
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium text-slate-500 bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400" title="No requiere facturas">
                                    <i class="fas fa-minus"></i> N/A
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-slate-700 dark:text-slate-300">{{ $solicitud->created_at->format('d/m/Y') }}</div>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2 flex-wrap">
                                <button @click="abrirModal({{ $solicitud->SolicitudID }})"
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
                                @php $yaSubio = $solicitud->facturasSubidas >= $solicitud->totalFacturasNecesarias && $solicitud->totalFacturasNecesarias > 0; @endphp
                                <button type="button" wire:click="abrirModalAsignacion({{ $solicitud->SolicitudID }})"
                                    class="{{ $yaSubio ? 'text-sky-600 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-300' : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300' }} text-sm font-medium transition-colors">
                                    <i class="fas {{ $yaSubio ? 'fa-eye' : 'fa-file-invoice' }} mr-1"></i>
                                    {{ $yaSubio ? 'Ver Asignación' : 'Asignación' }}
                                </button>
                                @endif
                                @if(!in_array($solicitud->estatusDisplay, ['Cancelada', 'Rechazada']))
                                <button type="button" wire:click="abrirModalCancelacion({{ $solicitud->SolicitudID }})"
                                    class="text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 text-sm font-medium transition-colors"
                                    title="Cerrar solicitud por parte de TI">
                                    <i class="fas fa-ban mr-1"></i> Cerrar
                                </button>
                                @endif
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

            {{-- PAGINACIÓN --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-gradient-to-r from-slate-50 to-gray-50 dark:from-slate-900 dark:to-slate-800">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
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
                    @if($todasSolicitudes->hasPages())
                    <nav class="flex items-center gap-1">
                        @if($todasSolicitudes->onFirstPage())
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </span>
                        @else
                            <button wire:click="previousPage" class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 hover:border-slate-300 transition-all duration-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                        @endif
                        @foreach($todasSolicitudes->getUrlRange(max(1, $todasSolicitudes->currentPage() - 1), min($todasSolicitudes->lastPage(), $todasSolicitudes->currentPage() + 1)) as $page => $url)
                            @if($page == $todasSolicitudes->currentPage())
                                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-gray-50 font-semibold shadow-md shadow-blue-500/25">{{ $page }}</span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 hover:border-slate-300 hover:text-slate-900 transition-all duration-200 font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100">{{ $page }}</button>
                            @endif
                        @endforeach
                        @if($todasSolicitudes->currentPage() < $todasSolicitudes->lastPage() - 2)
                            <span class="inline-flex items-center justify-center w-10 h-10 text-slate-400 dark:text-slate-500"><i class="fas fa-ellipsis-h text-xs"></i></span>
                            <button wire:click="gotoPage({{ $todasSolicitudes->lastPage() }})" class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 transition-all font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">{{ $todasSolicitudes->lastPage() }}</button>
                        @endif
                        @if($todasSolicitudes->hasMorePages())
                            <button wire:click="nextPage" class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 hover:border-slate-300 transition-all duration-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
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

    {{-- MODAL DETALLES --}}
    <template x-teleport="body">
        <div x-show="modalAbierto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
            @keydown.escape.window="cerrarModal()"
            @click.self="cerrarModal()"
            x-cloak>
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-xl rounded-lg bg-gray-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">
                <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                        Detalles de Solicitud
                        <span x-show="solicitudSeleccionada" x-text="'#' + solicitudSeleccionada?.SolicitudID" class="text-slate-500 dark:text-slate-400 ml-2"></span>
                    </h3>
                    <button @click="cerrarModal()" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="mt-4">
                    <div x-show="cargando" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-slate-400 dark:text-slate-600"></i>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Cargando información...</p>
                    </div>
                    <div x-show="!cargando && solicitudSeleccionada">

                        {{-- Info del Solicitante --}}
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-user text-blue-500 dark:text-blue-400"></i> Información del Solicitante
                            </h4>
                            <div class="grid grid-cols-2 gap-4 p-4 rounded-lg bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Nombre</label><p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="solicitudSeleccionada?.empleado?.NombreEmpleado"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Correo</label><p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.empleado?.Correo"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Gerencia</label><p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.gerencia?.NombreGerencia || 'N/A'"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Obra</label><p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.obra?.NombreObra || 'N/A'"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Puesto</label><p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.puesto?.NombrePuesto || 'N/A'"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Ubicación</label><p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.ProyectoNombre || solicitudSeleccionada?.Proyecto || 'N/A'"></p></div>
                            </div>
                        </div>

                        {{-- Banner cancelación --}}
                        <template x-if="solicitudSeleccionada?.motivo_cancelacion">
                            <div class="mb-6 rounded-lg border border-rose-300 dark:border-rose-700/60 bg-rose-50 dark:bg-rose-900/20 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-rose-100 dark:bg-rose-800/40">
                                        <i class="fas fa-ban text-rose-600 dark:text-rose-400 text-base"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wide">Solicitud Cancelada / Cerrada</p>
                                        <p class="mt-1 text-sm text-rose-700 dark:text-rose-300 leading-relaxed" x-text="solicitudSeleccionada?.motivo_cancelacion"></p>
                                        <div class="mt-2 flex flex-wrap items-center gap-3">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-rose-500 dark:text-rose-400"><i class="fas fa-user-slash"></i><span x-text="'Cancelado por: ' + (solicitudSeleccionada?.canceladoPorNombre || solicitudSeleccionada?.cancelado_por || 'N/A')"></span></span>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-rose-500 dark:text-rose-400"><i class="fas fa-calendar-times"></i><span x-text="solicitudSeleccionada?.fecha_cancelacion || 'Fecha no disponible'"></span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Detalles de la Solicitud --}}
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-file-alt text-emerald-500 dark:text-emerald-400"></i> Detalles de la Solicitud
                            </h4>
                            <div class="p-4 rounded-lg space-y-3 bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Motivo</label><p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="solicitudSeleccionada?.Motivo || 'N/A'"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Descripción del Motivo</label><p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="solicitudSeleccionada?.DescripcionMotivo || 'N/A'"></p></div>
                                <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Requerimientos</label><p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="solicitudSeleccionada?.Requerimientos || 'N/A'"></p></div>
                                <div class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                                    <div>
                                        <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Estatus</label>
                                        <p class="text-sm font-semibold"
                                            :class="{
                                               'text-rose-600 dark:text-rose-400':       (solicitudSeleccionada?.estatusDisplay || '') === 'Cancelada',
                                               'text-amber-600 dark:text-amber-400':     (solicitudSeleccionada?.estatusDisplay || '') === 'Pendiente',
                                               'text-red-600 dark:text-red-400':         (solicitudSeleccionada?.estatusDisplay || '') === 'Rechazada',
                                               'text-sky-600 dark:text-sky-400':         (solicitudSeleccionada?.estatusDisplay || '') === 'En revisión',
                                               'text-emerald-600 dark:text-emerald-400': (solicitudSeleccionada?.estatusDisplay || '') === 'Aprobada',
                                               'text-teal-600 dark:text-teal-400':       (solicitudSeleccionada?.estatusDisplay || '') === 'Listo',
                                               'text-blue-600 dark:text-blue-400':       (solicitudSeleccionada?.estatusDisplay || '') === 'Cotizaciones Enviadas',
                                               'text-slate-900 dark:text-slate-100':     !['Cancelada','Pendiente','Rechazada','En revisión','Aprobada','Listo','Cotizaciones Enviadas'].includes(solicitudSeleccionada?.estatusDisplay || '')
                                            }"
                                            x-text="solicitudSeleccionada?.estatusDisplay || solicitudSeleccionada?.Estatus || 'Sin estatus'"></p>
                                    </div>
                                    <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Fecha de Creación</label><p class="text-sm text-slate-900 dark:text-slate-200" x-text="solicitudSeleccionada?.fechaCreacion || 'N/A'"></p></div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2" x-show="solicitudSeleccionada?.puedeCotizar">
                                    <a :href="'/solicitudes/' + (solicitudSeleccionada?.SolicitudID || '') + '/cotizar'"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-gray-50 text-sm font-medium rounded-lg transition shadow-sm no-underline">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <span x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0 ? 'Editar cotizaciones' : 'Cotizar'"></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Flujo de Aprobación --}}
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-tasks text-purple-500 dark:text-purple-400"></i> Flujo de Aprobación
                            </h4>
                            <div class="mb-4 p-4 rounded-lg bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 space-y-2">
                                <p><strong>1. Solicitud</strong> → Vo.bo de supervisor</p>
                                <p><strong>2. TI</strong> → Envía cotización → <strong>Gerente</strong> ve propuestas, elige ganador o regresa a TI para cotizar de nuevo</p>
                                <p><strong>3. Administración</strong> → Ve los ganadores y aprueba la solicitud</p>
                            </div>
                            <div class="space-y-4">
                                <template x-for="(paso, index) in solicitudSeleccionada?.pasosAprobacion || []" :key="index">
                                    <div class="p-4 rounded-lg border-l-4 bg-slate-50 dark:bg-slate-800 shadow-sm border-t border-r border-b border-slate-200 dark:border-slate-700"
                                        :class="{ 'border-l-green-500': paso.status === 'approved', 'border-l-red-500': paso.status === 'rejected', 'border-l-yellow-500': paso.status === 'pending' }">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <i class="fas" :class="{ 'fa-check-circle text-green-500': paso.status === 'approved', 'fa-times-circle text-red-500': paso.status === 'rejected', 'fa-circle text-yellow-500': paso.status === 'pending' }"></i>
                                                <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="paso.stageLabel"></span>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded font-medium"
                                                :class="{ 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300': paso.status === 'approved', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300': paso.status === 'rejected', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300': paso.status === 'pending' }"
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

                        {{-- Cotizaciones --}}
                        <div class="mb-6" x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0" x-data="{
                            selectedIndexes: {},
                            getCotizacionesAgrupadasPorPropuesta() {
                                const cots = solicitudSeleccionada?.cotizaciones || [];
                                const activosPorCot = solicitudSeleccionada?.activosPorCotizacion || {};
                                const grupos = {};
                                cots.forEach(c => {
                                    const propuesta = c.NumeroPropuesta || 0;
                                    if (!grupos[propuesta]) grupos[propuesta] = { numeroPropuesta: propuesta, nombreEquipo: c.NombreEquipo || c.Descripcion || 'Equipo', cotizaciones: [] };
                                    const activos = activosPorCot[c.CotizacionID] || [];
                                    grupos[propuesta].cotizaciones.push({ ...c, activos, esGanador: c.Estatus === 'Seleccionada' });
                                });
                                return Object.values(grupos).map(grupo => {
                                    grupo.cotizaciones.sort((a, b) => { if (a.esGanador && !b.esGanador) return -1; if (!a.esGanador && b.esGanador) return 1; return 0; });
                                    return grupo;
                                }).sort((a, b) => a.numeroPropuesta - b.numeroPropuesta);
                            },
                            selectCotizacion(propuesta, index) { this.selectedIndexes[propuesta] = index; },
                            getSelectedIndex(propuesta) { return this.selectedIndexes[propuesta] || 0; }
                        }">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-trophy text-amber-500 dark:text-amber-400"></i> Cotizaciones y Asignaciones
                            </h4>
                            <div class="space-y-6">
                                <template x-for="(grupo, gIndex) in getCotizacionesAgrupadasPorPropuesta()" :key="'grupo-' + grupo.numeroPropuesta">
                                    <div class="rounded-lg border-2 border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800/30">
                                        <div class="px-4 py-3 bg-gradient-to-r from-violet-100 to-violet-50 dark:from-violet-900/30 dark:to-violet-800/20 border-b-2 border-violet-200 dark:border-violet-800">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xs font-bold uppercase tracking-wider text-violet-700 dark:text-violet-300">Producto <span x-text="grupo.numeroPropuesta"></span></span>
                                                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="grupo.nombreEquipo"></span>
                                                </div>
                                                <span class="text-xs text-slate-600 dark:text-slate-400 font-medium"><span x-text="grupo.cotizaciones.length"></span> cotización(es)</span>
                                            </div>
                                        </div>
                                        <template x-if="grupo.cotizaciones.length > 1">
                                            <div class="px-4 py-3 bg-slate-100 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                                    <template x-for="(cot, idx) in grupo.cotizaciones" :key="'btn-' + cot.CotizacionID">
                                                        <button type="button" @click="selectCotizacion(grupo.numeroPropuesta, idx)"
                                                            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                                                            :class="getSelectedIndex(grupo.numeroPropuesta) === idx ? (cot.esGanador ? 'bg-emerald-500 text-gray-50 shadow-md' : 'bg-red-500 text-gray-50 shadow-md') : (cot.esGanador ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300')">
                                                            <i :class="cot.esGanador ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-2"></i>
                                                            <span x-text="cot.Proveedor || 'Opción ' + (idx + 1)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-for="(cotizacion, cIndex) in grupo.cotizaciones" :key="cotizacion.CotizacionID || cIndex">
                                            <div x-show="getSelectedIndex(grupo.numeroPropuesta) === cIndex" class="p-5">
                                                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg border-2 shadow-sm p-4" :class="cotizacion.esGanador ? 'border-emerald-400 dark:border-emerald-600' : 'border-red-400 dark:border-red-600'">
                                                    <div class="flex items-center gap-2 mb-3 pb-2 border-b" :class="cotizacion.esGanador ? 'border-emerald-100 dark:border-emerald-800/30' : 'border-red-100 dark:border-red-800/30'">
                                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded" :class="cotizacion.esGanador ? 'text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/40' : 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/40'">
                                                            <i :class="cotizacion.esGanador ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                                            <span x-text="cotizacion.Estatus || 'Pendiente'"></span>
                                                        </span>
                                                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="cotizacion.Descripcion || 'Equipo'"></span>
                                                    </div>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                                        <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Proveedor</label><p class="text-sm text-slate-900 dark:text-slate-200 font-semibold" x-text="cotizacion.Proveedor || 'N/A'"></p></div>
                                                        <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">No. Parte</label><p class="text-sm text-slate-900 dark:text-slate-200 font-mono" x-text="cotizacion.NumeroParte || 'N/A'"></p></div>
                                                        <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Precio Unitario</label><p class="text-sm text-slate-900 dark:text-slate-200 font-semibold" x-text="cotizacion.Precio != null ? ('$' + parseFloat(cotizacion.Precio).toLocaleString('es-MX', {minimumFractionDigits: 2})) : 'N/A'"></p></div>
                                                        <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Costo Envío</label><p class="text-sm text-slate-900 dark:text-slate-200 font-semibold" x-text="cotizacion.CostoEnvio != null ? ('$' + parseFloat(cotizacion.CostoEnvio).toLocaleString('es-MX', {minimumFractionDigits: 2})) : '$0.00'"></p></div>
                                                        <div><label class="text-xs font-medium text-slate-500 dark:text-slate-400">Total</label><p class="text-sm font-bold" :class="cotizacion.esGanador ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-900 dark:text-slate-200'" x-text="(() => { const precio = parseFloat(cotizacion.Precio || 0); const envio = parseFloat(cotizacion.CostoEnvio || 0); return '$' + (precio + envio).toLocaleString('es-MX', {minimumFractionDigits: 2}); })()"></p></div>
                                                    </div>
                                                    <template x-if="cotizacion.esGanador && cotizacion.activos && cotizacion.activos.length > 0">
                                                        <div class="mt-4 pt-4 border-t border-emerald-200 dark:border-emerald-800">
                                                            <h5 class="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-300 mb-3 flex items-center gap-2"><i class="fas fa-calendar-check"></i> Asignaciones y Fechas de Entrega</h5>
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                                <template x-for="(activo, aIdx) in cotizacion.activos" :key="activo.SolicitudActivoID || aIdx">
                                                                    <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                                                                        <div class="flex items-center justify-between mb-2">
                                                                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Unidad <span x-text="activo.UnidadIndex"></span></span>
                                                                            <span class="text-xs font-bold" :class="activo.FechaEntrega ? 'text-teal-600 dark:text-teal-400' : 'text-amber-600 dark:text-amber-400'"><i class="fas fa-calendar-alt mr-1"></i><span x-text="activo.FechaEntrega || 'Pendiente'"></span></span>
                                                                        </div>
                                                                        <div class="text-xs text-slate-600 dark:text-slate-400"><span class="font-medium">Asignado a:</span> <span x-text="activo.EmpleadoAsignado?.NombreEmpleado || 'Sin asignar'"></span></div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="cotizacion.Descripcion && cotizacion.Descripcion.trim()">
                                                        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                                            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 dark:bg-slate-800/50">
                                                                <div class="flex-1 min-w-0">
                                                                    <h6 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">Descripción del Producto</h6>
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

                    </div>{{-- /!cargando --}}
                </div>
            </div>
        </div>
    </template>

    {{-- MODAL ASIGNACIÓN (Livewire) --}}
    @if($modalAsignacionAbierto)
    @php
        $modalYaTieneFacturas = $modalEsSoloLectura;
    @endphp
    <div
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm"
        wire:keydown.escape.window="closeAsignacion"
        tabindex="-1">

        <div class="absolute inset-0" wire:click="closeAsignacion"></div>

        <div class="relative z-10 w-full max-w-6xl mx-4 bg-gray-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden">

            {{-- CABECERA --}}
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            @if($modalYaTieneFacturas)
                                <i class="fas fa-eye text-sky-500"></i> Ver Asignación y Provisión
                            @else
                                <i class="fas fa-file-invoice text-emerald-500"></i> Asignación y Provisión de Activos
                            @endif
                        </h3>
                        @if($asignacionSolicitudId)
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-100 dark:bg-sky-900/25 dark:text-sky-200 dark:border-sky-800/60">
                            <span class="text-sky-700/70 dark:text-sky-200/70">Solicitud</span><span class="font-bold">#{{ $asignacionSolicitudId }}</span>
                        </span>
                        @endif
                        @if($modalYaTieneFacturas)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-700/40">
                            <i class="fas fa-lock text-[10px]"></i> Solo lectura
                        </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        @if($modalYaTieneFacturas)
                            Visualización de la asignación registrada.
                        @else
                            Defina responsables técnicos por ítem, suba el XML de factura y complete el checklist.
                        @endif
                    </p>
                </div>
                <button type="button"
                    wire:click="closeAsignacion"
                    class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:text-slate-500 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            {{-- Banner: proveedores con factura compartida --}}
            @php
            $proveedoresAgrupados = collect($propuestasAsignacion)->groupBy('proveedor')->filter(fn($g) => $g->count() > 1);
            @endphp
            @if($proveedoresAgrupados->isNotEmpty())
            <div class="px-6 py-3 bg-blue-50 dark:bg-blue-950/30 border-b border-blue-100 dark:border-blue-800/30">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                        Proveedores con múltiples propuestas — el XML se comparte automáticamente:
                    </span>
                    @foreach($proveedoresAgrupados->keys() as $proveedor)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-slate-800 border border-blue-200 dark:border-blue-800/50 shadow-sm">
                        <div class="w-2 h-2 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600"></div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $proveedor }}</span>
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Banner facturas (XML) pendientes --}}
            @if(!$modalYaTieneFacturas)
            @php
                $proveedoresSinFactura = [];
                $proveedoresVistos     = [];
                foreach ($propuestasAsignacion as $pIdx => $pItem) {
                    $prov = $pItem['proveedor'] ?? '';
                    if (!$prov || in_array($prov, $proveedoresVistos, true)) continue;
                    $proveedoresVistos[] = $prov;
                    $tieneAlgo = false;
                    foreach (($pItem['unidades'] ?? []) as $uIdx => $uItem) {
                        if (!empty($uItem['factura_xml_path']) || !empty($facturaXml[$pIdx][$uIdx])) {
                            $tieneAlgo = true;
                            break;
                        }
                    }
                    if (!$tieneAlgo) $proveedoresSinFactura[] = $prov;
                }
            @endphp
            @if(!empty($proveedoresSinFactura))
            <div class="px-6 py-3 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-800/40 flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-amber-500 shrink-0"></i>
                <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">
                    XML pendiente de subir para:
                    <span class="font-bold">{{ implode(', ', $proveedoresSinFactura) }}</span>.<br>
                    <span class="font-normal opacity-90 mt-1 inline-block">
                        <i class="fas fa-info-circle"></i>
                        Sube el <b>XML</b> del CFDI (se valida automáticamente). Para proveedores extranjeros como Starlink/AWS esta sección no aplica.
                    </span>
                </p>
            </div>
            @endif
            @endif

            {{-- CUERPO SCROLLABLE --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6 bg-gray-50 dark:bg-slate-900">
                @if(empty($propuestasAsignacion))
                <div class="py-10 text-center text-slate-500 dark:text-slate-400">No hay datos para asignación.</div>
                @else

                @foreach($propuestasAsignacion as $pIndex => $p)
                <div class="group rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-gradient-to-b from-gray-50 to-slate-50/50 dark:from-slate-900 dark:to-slate-800/30 shadow-sm"
                    wire:key="prop-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $p['cotizacionId'] ?? 'x' }}">

                    {{-- Cabecera de propuesta --}}
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r rounded-t-2xl from-slate-50/80 to-gray-50 dark:from-slate-800/50 dark:to-slate-900">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 truncate">{{ $p['nombreEquipo'] ?? 'Sin nombre' }}</h4>
                                <div class="flex items-center gap-3 mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5"><i class="fas fa-building text-xs"></i>{{ $p['proveedor'] ?? 'Sin proveedor' }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                    <span class="inline-flex items-center gap-1.5"><i class="fas fa-boxes text-xs"></i>{{ (int)($p['itemsTotal'] ?? 0) }} unidades</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30">
                                <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Precio unitario</span>
                                <span class="text-xl font-bold text-emerald-700 dark:text-emerald-300">${{ number_format((float)($p['precioUnitario'] ?? 0), 2, '.', ',') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Cabecera columnas --}}
                    <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-slate-100/60 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800">
                        <div class="col-span-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">#</div>
                        <div class="col-span-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Descripción</div>
                        <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">XML Factura</div>
                        <div class="col-span-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Fecha de entrega</div>
                        <div class="col-span-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Usuario final</div>
                    </div>

                    {{-- Unidades --}}
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @foreach(($p['unidades'] ?? []) as $uIndex => $u)
                    <div class="px-6 py-5" wire:key="unit-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}-{{ $u['unidadIndex'] ?? ($uIndex+1) }}">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

                            {{-- # --}}
                            <div class="col-span-1 flex lg:justify-center">
                                <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold shadow-inner">
                                    {{ $u['unidadIndex'] ?? ($uIndex + 1) }}
                                </span>
                            </div>

                            {{-- Descripción --}}
                            <div class="col-span-3">
                                <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Descripción</label>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-relaxed">{{ $p['nombreEquipo'] ?? 'Producto' }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Unidad {{ $u['unidadIndex'] ?? ($uIndex + 1) }}</div>
                            </div>

                            {{-- ══ COLUMNA: XML ÚNICAMENTE ══ --}}
                            <div class="col-span-2">
                                <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">XML Factura</label>

                                @php
                                    $xmlSavedPath  = $u['factura_xml_path'] ?? '';
                                    $hasNewXml     = !empty($facturaXml[$pIndex][$uIndex]);
                                    $esXmlGuardado = !empty($xmlSavedPath);
                                    $parsed        = $xmlParseado[$pIndex][$uIndex] ?? null;
                                    $parsedOk      = $parsed && empty($parsed['error']) && !empty($parsed['conceptos']);

                                    $provActual   = $p['proveedor'] ?? '';
                                    $esPrimerProv = true;
                                    for ($i = 0; $i < $pIndex; $i++) {
                                        if (($propuestasAsignacion[$i]['proveedor'] ?? '') === $provActual) {
                                            $esPrimerProv = false;
                                            break;
                                        }
                                    }
                                    $mostrarInput = ($uIndex === 0 && $esPrimerProv);
                                @endphp

                                @if($mostrarInput)
                                    @if($modalYaTieneFacturas)
                                    {{-- Solo lectura --}}
                                    <div class="flex flex-col gap-2">
                                        @if($esXmlGuardado)
                                        <a href="{{ Storage::url($xmlSavedPath) }}" target="_blank"
                                            class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-xs font-medium bg-violet-50 border border-violet-200 text-violet-700 hover:bg-violet-100 transition-colors dark:bg-violet-900/20 dark:border-violet-700/50 dark:text-violet-300">
                                            <i class="fas fa-file-code text-violet-500"></i>
                                            <span class="truncate max-w-[8rem]">{{ basename($xmlSavedPath) }}</span>
                                            <i class="fas fa-external-link-alt text-[10px] ml-auto opacity-60"></i>
                                        </a>
                                        @else
                                        <span class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-xs font-medium bg-slate-100 border border-slate-200 text-slate-500 dark:bg-slate-800/50 dark:border-slate-700 dark:text-slate-400" title="Proveedor extranjero sin XML">
                                            <i class="fas fa-globe"></i> Sin XML
                                        </span>
                                        @endif
                                    </div>

                                    @else
                                    {{-- ══ Modo edición: solo XML ══ --}}
                                    <div class="flex flex-col gap-1.5">
                                        <label class="relative inline-flex items-center gap-2 h-9 px-3 rounded-lg border-2 border-dashed cursor-pointer transition-all duration-200 text-xs
                                            {{ ($hasNewXml || $esXmlGuardado)
                                                ? 'bg-violet-50 border-violet-400 text-violet-700 hover:bg-violet-100 dark:bg-violet-900/20 dark:border-violet-600 dark:text-violet-300'
                                                : 'bg-violet-50/40 border-violet-300 text-violet-600 hover:bg-violet-50 hover:border-violet-400 dark:bg-violet-950/20 dark:border-violet-700 dark:text-violet-400' }}">
                                            <input type="file" class="hidden" accept="text/xml,application/xml,.xml"
                                                wire:model.live="facturaXml.{{ $pIndex }}.{{ $uIndex }}">
                                            <i class="fas {{ ($hasNewXml || $esXmlGuardado) ? 'fa-check-circle text-violet-500' : 'fa-file-code text-violet-400' }}"></i>
                                            <span class="font-semibold truncate max-w-[8rem]">
                                                @if($hasNewXml)
                                                    {{ $parsedOk ? 'XML ✓ (' . count($parsed['conceptos']) . ' conceptos)' : 'XML cargado' }}
                                                @elseif($esXmlGuardado)
                                                    {{ basename($xmlSavedPath) }}
                                                @else
                                                    Subir XML (CFDI)
                                                @endif
                                            </span>
                                        </label>

                                        <div wire:loading wire:target="facturaXml.{{ $pIndex }}.{{ $uIndex }}"
                                            class="text-[10px] text-violet-500 flex items-center gap-1">
                                            <i class="fas fa-spinner fa-spin"></i> Validando CFDI...
                                        </div>

                                        @error("facturaXml.$pIndex.$uIndex")
                                        <p class="text-[10px] text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                        </p>
                                        @enderror

                                        @if($esXmlGuardado && !$hasNewXml)
                                        <a href="{{ Storage::url($xmlSavedPath) }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-[10px] text-violet-600 dark:text-violet-400 hover:underline">
                                            <i class="fas fa-file-code"></i> Ver XML guardado
                                        </a>
                                        @endif
                                    </div>
                                    @endif

                                @else
                                {{-- Indicador "Compartida" --}}
                                <div class="flex flex-col gap-2">
                                    <div class="min-h-[44px] flex items-center justify-center text-xs font-medium
                                        text-slate-500 bg-slate-50 dark:bg-slate-800/50 rounded-lg
                                        border border-slate-200 dark:border-slate-700 p-2 text-center">
                                        @if($uIndex > 0)
                                            <span><i class="fas fa-link mr-1.5 opacity-60"></i> Compartida con U1</span>
                                        @else
                                            <span><i class="fas fa-building mr-1.5 opacity-60 text-blue-500"></i> Compartida — mismo proveedor</span>
                                        @endif
                                    </div>
                                    @if(!empty($u['factura_xml_path']))
                                    <a href="{{ Storage::url($u['factura_xml_path']) }}" target="_blank"
                                        class="inline-flex items-center justify-center gap-1 text-[10px] text-violet-600 dark:text-violet-400 hover:underline">
                                        <i class="fas fa-file-code"></i> Ver XML
                                    </a>
                                    @elseif($hasNewXml)
                                    <span class="text-[10px] text-violet-500 text-center"><i class="fas fa-check mr-1"></i> XML listo</span>
                                    @endif
                                </div>
                                @endif
                            </div>

                            {{-- Fecha de entrega --}}
                            <div class="col-span-3">
                                <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Fecha de entrega</label>
                                @if($modalYaTieneFacturas)
                                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-300">
                                        <i class="fas fa-calendar-alt text-slate-400 text-xs"></i> {{ $u['fecha_entrega'] ?? '—' }}
                                    </div>
                                @else
                                    <input type="date"
                                        wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.fecha_entrega"
                                        class="h-11 w-full pl-3 pr-4 text-sm border-2 border-slate-200 rounded-xl bg-gray-50 dark:bg-slate-800 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all dark:border-slate-600 dark:text-slate-200">
                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.fecha_entrega")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                    @enderror
                                @endif
                            </div>

                            {{-- Usuario final --}}
                            <div class="col-span-3"
                                x-data="{ open: false }"
                                @click.outside="open = false">
                                <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Usuario final</label>
                                @if($modalYaTieneFacturas)
                                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-300">
                                        <i class="fas fa-user text-slate-400 text-xs"></i> {{ $u['empleado_nombre'] ?? '—' }}
                                    </div>
                                    <div class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                                        <i class="fas fa-sitemap text-slate-400 text-[10px]"></i>
                                        <span>{{ $u['departamento_nombre'] ?? '-' }}</span>
                                    </div>
                                @else
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live.debounce.250ms="usuarioSearch.{{ $pIndex }}.{{ $uIndex }}"
                                            @focus="open = true"
                                            autocomplete="off"
                                            class="h-11 w-full pl-7 pr-4 text-sm border-2 border-slate-200 rounded-xl bg-gray-50 dark:bg-slate-800 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all relative z-20 dark:border-slate-600 dark:text-slate-200"
                                            placeholder="Buscar empleado...">
                                        @php $opts = $usuarioOptions[$pIndex][$uIndex] ?? []; @endphp
                                        @if(!empty($opts))
                                        <div x-show="open" class="absolute top-full left-0 right-0 z-[99999] mt-1 max-h-64 rounded-lg border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 shadow-2xl overflow-y-auto">
                                            @foreach($opts as $opt)
                                            <button type="button"
                                                wire:click.prevent="seleccionarEmpleado({{ $pIndex }}, {{ $uIndex }}, {{ (int) $opt['id'] }})"
                                                @click="open = false"
                                                class="w-full px-3 py-2.5 text-left hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0">
                                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100 leading-tight truncate">{{ $opt['name'] }}</div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">{{ $opt['correo'] }}</div>
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    <div class="mt-2.5 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-slate-100 to-slate-50 border border-slate-200 text-xs dark:from-slate-800 dark:to-slate-800/50 dark:border-slate-700">
                                        <i class="fas fa-sitemap text-slate-400 dark:text-slate-500"></i>
                                        <span class="text-slate-500 dark:text-slate-400">Departamento:</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $u['departamento_nombre'] ?? '-' }}</span>
                                    </div>
                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.empleado_id")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                    @enderror
                                @endif
                            </div>

                        </div>{{-- /grid --}}

                        {{-- CHECKLIST --}}
                        @php
                            $checklistFlat    = collect($u['checklist'] ?? [])
                                ->flatMap(fn($items) => is_array($items) ? array_values($items) : [])
                                ->filter(fn($i) => is_array($i) && isset($i['nombre']));
                            $hasChecklistItems = $checklistFlat->isNotEmpty();
                        @endphp
                        @if($hasChecklistItems)
                        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800"
                            wire:key="checklist-wrap-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                            <label class="inline-flex items-center gap-3 {{ !$modalYaTieneFacturas ? 'cursor-pointer' : '' }} mb-3 select-none">
                                <input type="checkbox"
                                    wire:model.live="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.requiere_config"
                                    class="peer sr-only"
                                    @if($modalYaTieneFacturas) disabled @endif>
                                <div class="relative w-10 h-5 rounded-full border-2 transition-all duration-200
                                    bg-slate-200 border-slate-300
                                    peer-checked:bg-violet-500 peer-checked:border-violet-500
                                    dark:bg-slate-700 dark:border-slate-600
                                    dark:peer-checked:bg-violet-600 dark:peer-checked:border-violet-600
                                    flex items-center">
                                    <span class="absolute left-0.5 top-0.5 w-3.5 h-3.5 rounded-full bg-gray-50 shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                </div>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Requiere configuración</span>
                            </label>

                            @if($u['requiere_config'] ?? false)
                            <div x-data="{ open: {{ ($modalYaTieneFacturas || !empty($u['config_lista_ui'])) ? 'false' : 'true' }} }"
                                wire:key="checklist-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}-{{ empty($u['config_lista_ui']) ? 'open' : 'closed' }}">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-4 px-4 py-3 rounded-xl
                                    bg-gradient-to-r from-slate-100/80 to-slate-50/50
                                    dark:from-slate-800/60 dark:to-slate-800/30
                                    border border-slate-200/60 dark:border-slate-700/60
                                    hover:from-slate-100 hover:to-slate-50 dark:hover:from-slate-800
                                    transition-all duration-200 {{ !$modalYaTieneFacturas ? 'cursor-pointer' : '' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500 to-violet-600 text-gray-50 shadow-md shadow-purple-500/20">
                                            <i class="fas fa-tasks text-sm"></i>
                                        </div>
                                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">Checklist de configuración</span>
                                        @if($modalYaTieneFacturas)
                                        <span class="text-[10px] bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded text-slate-500 ml-2">Solo lectura</span>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                                        <i class="fas fa-chevron-down text-xs text-slate-500 dark:text-slate-400 transition-transform duration-300" :class="{ 'rotate-180': open }"></i>
                                    </span>
                                </button>
                                <div x-show="open" x-transition class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    @foreach(($u['checklist'] ?? []) as $catKey => $items)
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 overflow-hidden shadow-sm">
                                        <div class="px-4 py-3 bg-gradient-to-r from-slate-50 to-gray-50 dark:from-slate-800 dark:to-slate-900 border-b border-slate-100 dark:border-slate-800">
                                            <div class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-violet-500"></span>{{ $catKey }}
                                            </div>
                                        </div>
                                        <div class="p-4 space-y-3">
                                            @foreach($items as $idx => $item)
                                            <div class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                                <label class="relative flex items-center justify-center cursor-pointer mt-0.5">
                                                    <input type="checkbox"
                                                        wire:click="marcarChecklist({{ $pIndex }}, {{ $uIndex }}, '{{ $catKey }}', {{ $idx }})"
                                                        {{ !empty($item['realizado']) ? 'checked' : '' }}
                                                        @if($modalYaTieneFacturas) disabled @endif
                                                        class="peer sr-only">
                                                    <div class="relative w-5 h-5 rounded-md border-2 border-slate-300 bg-slate-50
                                                        dark:border-slate-600 dark:bg-slate-800
                                                        peer-checked:bg-green-500 peer-checked:border-green-500
                                                        transition-all duration-200 flex items-center justify-center
                                                        peer-focus:ring-2 peer-focus:ring-green-500/20">
                                                        <svg class="w-3.5 h-3.5 text-gray-50 opacity-0 peer-checked:opacity-100 transition-opacity"
                                                            viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                                            stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="4 11 8 15 16 6"></polyline>
                                                        </svg>
                                                    </div>
                                                </label>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm {{ !empty($item['realizado']) ? 'text-slate-400 line-through' : 'text-slate-800 dark:text-slate-200' }}">
                                                        {{ $item['nombre'] ?? '—' }}
                                                    </div>
                                                </div>
                                                <input type="text"
                                                    wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.checklist.{{ $catKey }}.{{ $idx }}.responsable"
                                                    @if($modalYaTieneFacturas) readonly @endif
                                                    class="h-8 w-24 px-2.5 text-xs border border-slate-200 rounded-lg bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 text-center font-medium {{ $modalYaTieneFacturas ? 'opacity-70 cursor-not-allowed' : '' }}"
                                                    placeholder="-">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- ══ BOTÓN FINALIZAR: SweetAlert único, sin distinción por checklist ══ --}}
                        @if($u['requiere_config'] ?? false)
                        @php
                            $checklistFlat2 = collect($u['checklist'] ?? [])
                                ->flatMap(fn($items) => is_array($items) ? array_values($items) : [])
                                ->filter(fn($i) => is_array($i) && isset($i['nombre']));
                            $totalItems     = $checklistFlat2->count();
                            $itemsMarcados  = $checklistFlat2->filter(fn($i) => !empty($i['realizado']))->count();
                            $yaFinalizado   = !empty($u['fecha_fin_configuracion']);
                        @endphp
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3"
                            wire:key="finalize-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">

                            {{-- Barra de progreso --}}
                            @if(!$yaFinalizado && $totalItems > 0)
                            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-semibold {{ $itemsMarcados === $totalItems ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' }}">
                                    {{ $itemsMarcados }}/{{ $totalItems }}
                                </span>
                                <span>tareas</span>
                                <div class="w-24 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $itemsMarcados === $totalItems ? 'bg-emerald-500' : 'bg-violet-500' }}"
                                        style="width: {{ $totalItems > 0 ? round(($itemsMarcados / $totalItems) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            @else
                            <div></div>
                            @endif

                            {{-- Estado / Botón --}}
                            @if(!empty($u['config_lista_ui']))
                                <div class="flex-1 flex items-center justify-between p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 shadow-sm">
                                    <div class="flex items-center gap-3 text-emerald-800 dark:text-emerald-300">
                                        <i class="fas fa-check-circle text-2xl text-emerald-500 dark:text-emerald-400"></i>
                                        <div>
                                            <p class="font-bold text-sm">¡Ticket creado y equipo configurado!</p>
                                            <p class="text-xs opacity-90 mt-0.5">Ya puedes continuar y subir el XML de factura de este equipo.</p>
                                        </div>
                                    </div>
                                </div>

                            @elseif($yaFinalizado)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 shadow-sm">
                                    <i class="fas fa-check-double"></i>
                                    Configuración finalizada el {{ \Carbon\Carbon::parse($u['fecha_fin_configuracion'])->format('d/m/Y H:i') }}
                                </span>

                            @else
                                {{-- ══ Botón Finalizar Instalación ══ --}}
                                <div wire:key="btn-fin-wrap-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                                    <button type="button"
                                        wire:key="btn-fin-activo-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}"
                                        wire:loading.attr="disabled"
                                        wire:target="finalizarConfiguracionUnidad"
                                        onclick="
                                            var btn = this;
                                            Swal.fire({
                                                background: '#f9fafb',
                                                color: '#1e293b',
                                                title: '¿Finalizar instalación?',
                                                html: 'Se registrará la <b>fecha y hora actual</b> y se creará un <b>ticket de instalación</b> automáticamente.',
                                                icon: 'question',
                                                showCancelButton: true,
                                                confirmButtonText: '<i class=\'fas fa-flag-checkered mr-1\'></i> Sí, finalizar',
                                                cancelButtonText: 'Cancelar',
                                                confirmButtonColor: '#4f46e5',
                                                cancelButtonColor: '#94a3b8',
                                                reverseButtons: true,
                                            }).then(function(result) {
                                                if (result.isConfirmed) {
                                                    var wireEl = btn.closest('[wire\\:id]');
                                                    Livewire.find(wireEl.getAttribute('wire:id'))
                                                        .finalizarConfiguracionUnidad({{ $pIndex }}, {{ $uIndex }});
                                                }
                                            });
                                        "
                                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-50 rounded-xl transition-all shadow-sm bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none">
                                        <span wire:loading.remove wire:target="finalizarConfiguracionUnidad">
                                            <i class="fas fa-flag-checkered"></i> Finalizar Instalación
                                        </span>
                                        <span wire:loading wire:target="finalizarConfiguracionUnidad">
                                            <i class="fas fa-spinner fa-spin mr-1"></i> Registrando...
                                        </span>
                                    </button>
                                </div>
                            @endif

                        </div>
                        @endif

                    </div>{{-- /unit py-5 --}}
                    @endforeach
                    </div>{{-- /divide-y --}}
                </div>{{-- /propuesta card --}}
                @endforeach

                {{-- RESUMEN XML PARSEADO --}}
                @if(!$modalYaTieneFacturas)
                @php
                    $todasFacturasParseadas = collect();
                    foreach ($propuestasAsignacion as $pi => $p) {
                        foreach (($p['unidades'] ?? []) as $ui => $u) {
                            $parsed = $xmlParseado[$pi][$ui] ?? null;
                                if ($parsed && empty($parsed['error']) && (!empty($parsed['conceptos']) || !empty($parsed['total']))) {
                                $uuid = $parsed['uuid'] ?? '';
                                if ($uuid && $todasFacturasParseadas->contains('uuid', $uuid)) continue;
                                $todasFacturasParseadas->push([
                                    'uuid'      => $uuid,
                                    'emisor'    => $parsed['emisor']    ?? '',
                                    'mes'       => $parsed['mes']       ?? '',
                                    'anio'      => $parsed['anio']      ?? '',
                                    'total'     => $parsed['total']     ?? '0',
                                    'moneda'    => $parsed['moneda']    ?? 'MXN',
                                    'conceptos' => $parsed['conceptos'] ?? [],
                                    'es_pdf'    => !empty($parsed['es_pdf']),
                                ]);
                            }
                        }
                    }
                @endphp
                @if($todasFacturasParseadas->isNotEmpty())
                <div class="rounded-2xl border border-violet-200 dark:border-violet-700/40 bg-violet-50/60 dark:bg-violet-950/20 overflow-hidden">
                    <div class="px-5 py-3 bg-violet-100/80 dark:bg-violet-900/30 border-b border-violet-200 dark:border-violet-700/40 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-violet-600 dark:text-violet-400"></i>
                        <span class="text-sm font-semibold text-violet-800 dark:text-violet-300">
                            Insumos detectados en el XML
                            <span class="ml-1.5 text-xs font-normal text-violet-500">
                                ({{ $todasFacturasParseadas->sum(fn($f) => count($f['conceptos'])) }} conceptos
                                · {{ $todasFacturasParseadas->count() }} {{ $todasFacturasParseadas->count() === 1 ? 'factura' : 'facturas' }})
                            </span>
                        </span>
                    </div>
                    <div class="divide-y divide-violet-100 dark:divide-violet-800/30">
                        @foreach($todasFacturasParseadas as $facturaData)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap gap-x-5 gap-y-1 mb-3">
                                @if($facturaData['emisor'])
                                <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                    <i class="fas fa-building text-violet-400"></i>
                                    <span class="font-medium">{{ $facturaData['emisor'] }}</span>
                                </div>
                                @endif
                                @if($facturaData['uuid'])
                                <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                                    <i class="fas fa-fingerprint text-violet-400"></i>
                                    <span>{{ Str::upper($facturaData['uuid']) }}</span>
                                </div>
                                @endif
                                @if($facturaData['mes'] && $facturaData['anio'])
                                <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                    <i class="fas fa-calendar-alt text-violet-400"></i>
                                    <span>{{ $facturaData['mes'] }}/{{ $facturaData['anio'] }}</span>
                                </div>
                                @endif
                                @if($facturaData['total'])
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    <i class="fas fa-dollar-sign text-violet-400"></i>
                                    <span>Total: ${{ number_format((float)$facturaData['total'], 2) }} {{ $facturaData['moneda'] }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="rounded-xl overflow-hidden border border-violet-200 dark:border-violet-700/30">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="bg-violet-100/70 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">
                                            <th class="text-left px-3 py-2 font-semibold">Descripción</th>
                                            <th class="text-right px-3 py-2 font-semibold w-16">Cant.</th>
                                            <th class="text-right px-3 py-2 font-semibold w-28">Costo unit.</th>
                                            <th class="text-right px-3 py-2 font-semibold w-28">Importe</th>
                                            <th class="text-center px-3 py-2 font-semibold w-32">Catálogo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-violet-100 dark:divide-violet-800/20">
                                        @foreach($facturaData['conceptos'] as $concepto)
                                        <tr class="bg-gray-50/70 dark:bg-slate-800/40 hover:bg-violet-50/60 transition-colors">
                                            <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200">{{ $concepto['nombre'] }}</td>
                                            <td class="px-3 py-2.5 text-right text-slate-500 dark:text-slate-400">{{ $concepto['cantidad'] }}</td>
                                            <td class="px-3 py-2.5 text-right text-slate-600 dark:text-slate-300 font-mono">${{ number_format((float)($concepto['costo'] ?? 0), 2) }}</td>
                                            <td class="px-3 py-2.5 text-right font-semibold text-slate-700 dark:text-slate-200 font-mono">${{ number_format((float)($concepto['importe'] ?? 0), 2) }}</td>
                                            <td class="px-3 py-2.5 text-center">
                                                @if($concepto['insumoId'])
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200">
                                                    <i class="fas fa-check-circle"></i> Encontrado
                                                </span>
                                                @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200">
                                                    <i class="fas fa-question-circle"></i> Sin match
                                                </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

                @endif{{-- /empty propuestasAsignacion --}}
            </div>{{-- /scroll body --}}

            {{-- PIE DEL MODAL --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3 bg-gray-50 dark:bg-slate-900">
                <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                    @if($modalYaTieneFacturas)
                        <i class="fas fa-lock text-sky-400"></i>
                        <span>Modo solo lectura — para modificar contacta al administrador.</span>
                    @else
                        <i class="fas fa-info-circle text-slate-300"></i>
                        <span>Guarda el avance para conservar los XML y los datos ingresados.</span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <button type="button"
                        wire:click="closeAsignacion"
                        class="px-4 py-2 text-sm rounded-lg border border-slate-300 bg-gray-50 hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-600 dark:hover:bg-slate-700 transition-colors"
                        wire:loading.attr="disabled">
                        Cerrar
                    </button>

                    @if(!$modalYaTieneFacturas)
                        <button type="button"
                            wire:loading.attr="disabled"
                            wire:target="persistAsignacion"
                            onclick="
                                var btn = this;
                                Swal.fire({
                                    background: '#f9fafb',
                                    color: '#1e293b',
                                    title: '¿Guardar XML y avance?',
                                    text: 'Se guardarán los archivos XML y toda la información ingresada hasta ahora.',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: '<i class=\'fas fa-save mr-1\'></i> Guardar',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#0f172a',
                                    cancelButtonColor: '#94a3b8',
                                    reverseButtons: true,
                                }).then(function(result) {
                                    if (result.isConfirmed) {
                                        var wireEl = btn.closest('[wire\\:id]');
                                        Livewire.find(wireEl.getAttribute('wire:id')).persistAsignacion(0, 0);
                                    }
                                });
                            "
                            class="px-4 py-2 text-sm rounded-lg font-medium text-gray-50 bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors shadow-sm
                                   disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="persistAsignacion">
                                <i class="fas fa-save mr-1"></i> Guardar XML y Avance
                            </span>
                            <span wire:loading wire:target="persistAsignacion">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Guardando...
                            </span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Overlay de carga --}}
            <div wire:loading wire:target="persistAsignacion"
                class="absolute inset-0 bg-slate-500/60 dark:bg-slate-900/60 flex items-center justify-center z-50 rounded-xl">
                <div class="flex flex-col items-center gap-3">
                    <i class="fas fa-spinner fa-spin text-3xl text-slate-600 dark:text-slate-300"></i>
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-200">Guardando...</div>
                </div>
            </div>
        </div>{{-- /modal card --}}
    </div>{{-- /modal wrapper --}}
    @endif

    @if($confirmarCierreModalAbierto)
    <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
        <div class="w-full max-w-md mx-4 bg-gray-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-5 flex items-start gap-4">
                <div class="flex-shrink-0 w-11 h-11 flex items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                    <i class="fas fa-exclamation-triangle text-amber-600 dark:text-amber-400 text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">¿Cerrar sin guardar?</h3>
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Hay proveedores sin XML cargado. Si cierras ahora, los archivos seleccionados en esta sesión
                        <strong class="text-slate-700 dark:text-slate-200">se perderán</strong>.
                        Los datos ya guardados anteriormente se conservan.
                    </p>
                </div>
            </div>
            <div class="px-6 pb-5 flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" wire:click="$set('confirmarCierreModalAbierto', false)"
                    class="px-4 py-2 text-sm rounded-lg border border-slate-300 bg-gray-50 text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Volver y subir XML
                </button>
                <button type="button" wire:click="forzarCloseAsignacion"
                    class="px-4 py-2 text-sm rounded-lg font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors">
                    <i class="fas fa-times mr-1"></i> Cerrar de todas formas
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL CANCELACIÓN --}}
    @if($modalCancelacionAbierto)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm"
        wire:keydown.escape.window="cerrarModalCancelacion">
        <div class="absolute inset-0" wire:click="cerrarModalCancelacion"></div>
        <div class="relative z-10 w-full max-w-lg mx-4 bg-gray-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-start justify-between gap-4 bg-gray-50 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                            Cancelar Solicitud @if($solicitudCancelarId)<span class="text-slate-500 dark:text-slate-400 font-normal ml-1">#{{ $solicitudCancelarId }}</span>@endif
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <button type="button" wire:click="cerrarModalCancelacion"
                    class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:text-slate-500 dark:hover:text-slate-300 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-5 bg-gray-50 dark:bg-slate-900">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Motivo de cancelación <span class="text-red-500">*</span>
                </label>
                <textarea wire:model.live="motivoCancelacion" rows="4"
                    placeholder="Describe por qué se está cancelando esta solicitud..."
                    class="w-full px-4 py-3 text-sm rounded-lg border border-slate-300 bg-gray-50 text-slate-900 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100 dark:placeholder-slate-400"></textarea>
                @error('motivoCancelacion')
                <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400 flex items-center gap-1.5">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </p>
                @enderror
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3 bg-gray-50 dark:bg-slate-900">
                <button type="button" wire:click="cerrarModalCancelacion" wire:loading.attr="disabled"
                    class="px-4 py-2.5 text-sm font-medium rounded-lg border border-slate-300 bg-gray-50 text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                    Regresar
                </button>
                <button type="button" wire:click="confirmarCancelacion" wire:loading.attr="disabled" wire:target="confirmarCancelacion"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-red-600 text-gray-50 hover:bg-red-700 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="confirmarCancelacion"><i class="fas fa-ban"></i> Confirmar Cancelación</span>
                    <span wire:loading wire:target="confirmarCancelacion"><i class="fas fa-spinner fa-spin"></i> Procesando...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
function solicitudesData() {
    return {
        modalAbierto: false,
        cargando: false,
        solicitudSeleccionada: null,
        abrirModal(id) {
            this.modalAbierto = true;
            this.cargando = true;
            this.solicitudSeleccionada = null;
            fetch(`/solicitudes/${id}/datos`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { this.solicitudSeleccionada = data; this.cargando = false; })
            .catch(() => { this.cargando = false; });
        },
        cerrarModal() {
            this.modalAbierto = false;
            this.solicitudSeleccionada = null;
        }
    }
}

document.addEventListener('livewire:load', function () {

    const _swalBase = {
        background:         '#f9fafb',
        color:              '#1e293b',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor:  '#94a3b8',
    };

    window.addEventListener('swal:success', function (e) {
        var data = e.detail;
        var msg  = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Cambios hechos correctamente');
        Swal.fire({
            ..._swalBase,
            icon:              'success',
            title:             '¡Listo!',
            text:              msg,
            timer:             3500,
            showConfirmButton: false,
            toast:             true,
            position:          'top-end',
        });
    });

    window.addEventListener('swal:error', function (e) {
        var data = e.detail;
        var msg  = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Error desconocido');
        Swal.fire({
            ..._swalBase,
            icon:  'error',
            title: 'Error',
            text:  msg,
        });
    });

    window.addEventListener('swal:warning', function (e) {
        var data = e.detail;
        var msg  = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Advertencia');
        Swal.fire({
            ..._swalBase,
            icon:  'warning',
            title: 'Atención',
            text:  msg,
        });
    });

    window.addEventListener('swal:info', function (e) {
        var data = e.detail;
        var msg  = (typeof data === 'string') ? data : (data && data.message ? data.message : '');
        if (!msg) return;
        Swal.fire({
            ..._swalBase,
            icon:              'info',
            text:              msg,
            timer:             3000,
            showConfirmButton: false,
            toast:             true,
            position:          'top-end',
        });
    });

    window.addEventListener('swal:success', () => window.dispatchEvent(new Event('swal-fin-done')));
    window.addEventListener('swal:error',   () => window.dispatchEvent(new Event('swal-fin-done')));
    window.addEventListener('swal:warning', () => window.dispatchEvent(new Event('swal-fin-done')));

});
</script>