<div x-data="solicitudesData()">

    <div class="rounded-lg shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900">

        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Solicitudes de Equipos TI</h2>
        </div>

        <div @if(!$modalAsignacionAbierto) wire:poll.15s @endif>
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex gap-3 flex-wrap items-end">
                    <div class="flex-1 min-w-[140px] max-w-xs">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Estatus</label>
                        <select wire:model.live="filtroEstatus" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-md bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos</option>
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
                    <div class="flex-1 min-w-[180px] max-w-sm">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="ID, empleado o motivo..."
                                class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-md bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Mostrar</label>
                        <select wire:model.live="perPage" class="px-3 py-2 text-sm border border-slate-300 rounded-md bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    @if($filtroEstatus)
                    <button wire:click="$set('filtroEstatus', '')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline pb-2">
                        Limpiar filtro
                    </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
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
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-gray-50 dark:bg-slate-900">
                        @forelse ($todasSolicitudes as $solicitud)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">#{{ $solicitud->SolicitudID }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $solicitud->nombreFormateado }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($solicitud->empleadoid->Correo ?? 'N/A', 25) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ Str::limit($solicitud->Motivo ?? 'N/A', 30) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $solicitud->colorEstatus }}"
                                    @if($solicitud->estatusDisplay === 'Re-cotizar' && !empty($solicitud->recotizarPropuestasText))
                                    title="Recotizar propuesta(s): {{ trim($solicitud->recotizarPropuestasText, ' ()') }}"
                                    @endif>
                                    {{ $solicitud->estatusDisplay }}{{ $solicitud->recotizarPropuestasText ?? '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($solicitud->pasoSupervisor)
                                    @if($solicitud->pasoSupervisor->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Supervisor: Aprobado"></i>
                                    @elseif($solicitud->pasoSupervisor->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Supervisor: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-yellow-500" title="Supervisor: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Supervisor: Pendiente"></i>
                                    @endif
                                    @if($solicitud->pasoGerencia)
                                    @if($solicitud->pasoGerencia->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Gerente: Aprobado"></i>
                                    @elseif($solicitud->pasoGerencia->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Gerente: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-orange-500" title="Gerente: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Gerente: En espera"></i>
                                    @endif
                                    @if($solicitud->pasoAdministracion)
                                    @if($solicitud->pasoAdministracion->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Administración: Aprobado"></i>
                                    @elseif($solicitud->pasoAdministracion->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Administración: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-purple-500" title="Administración: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Administración: En espera"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($solicitud->totalFacturasNecesarias > 0)
                                @if($solicitud->facturasSubidas >= $solicitud->totalFacturasNecesarias)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <i class="fas fa-check-circle text-[10px]"></i> {{ $solicitud->facturasSubidas }}/{{ $solicitud->totalFacturasNecesarias }} Completas
                                </span>
                                @elseif($solicitud->facturasSubidas > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                    <i class="fas fa-exclamation-triangle text-[10px]"></i> {{ $solicitud->facturasSubidas }}/{{ $solicitud->totalFacturasNecesarias }} Parcial
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                    <i class="fas fa-times-circle text-[10px]"></i> 0/{{ $solicitud->totalFacturasNecesarias }} Pendiente
                                </span>
                                @endif
                                @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $solicitud->created_at->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3 flex-wrap">
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
                                        class="{{ $yaSubio ? 'text-sky-600 dark:text-sky-400 hover:text-sky-800' : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-800' }} text-sm font-medium transition-colors">
                                        <i class="fas {{ $yaSubio ? 'fa-eye' : 'fa-file-invoice' }} mr-1"></i>
                                        {{ $yaSubio ? 'Ver Asignación' : 'Asignación' }}
                                    </button>
                                    @endif
                                    @if(!in_array($solicitud->estatusDisplay, ['Cancelada', 'Rechazada']))
                                    <button type="button" wire:click="abrirModalCancelacion({{ $solicitud->SolicitudID }})"
                                        class="text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 text-sm font-medium transition-colors"
                                        title="Cerrar solicitud">
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
                                <p class="text-base font-medium">No hay solicitudes registradas</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Mostrando
                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $todasSolicitudes->firstItem() ?? 0 }}</span>
                        a
                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $todasSolicitudes->lastItem() ?? 0 }}</span>
                        de
                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $todasSolicitudes->total() }}</span>
                        solicitudes
                    </p>
                    @if($todasSolicitudes->hasPages())
                    <nav class="flex items-center gap-1">
                        @if($todasSolicitudes->onFirstPage())
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed text-xs">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                        @else
                        <button wire:click="previousPage" class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-xs">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        @endif
                        @php
                        $rangoPaginas = $todasSolicitudes->getUrlRange(
                        max(1, $todasSolicitudes->currentPage() - 1),
                        min($todasSolicitudes->lastPage(), $todasSolicitudes->currentPage() + 1)
                        );
                        @endphp
                        @foreach($rangoPaginas as $page => $url)
                        @if($page == $todasSolicitudes->currentPage())
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-blue-600 text-white text-sm font-semibold">{{ $page }}</span>
                        @else
                        <button wire:click="gotoPage({{ $page }})" class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-sm">{{ $page }}</button>
                        @endif
                        @endforeach
                        @if($todasSolicitudes->currentPage() < $todasSolicitudes->lastPage() - 2)
                            <span class="px-1 text-slate-400">...</span>
                            <button wire:click="gotoPage({{ $todasSolicitudes->lastPage() }})" class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-sm">{{ $todasSolicitudes->lastPage() }}</button>
                            @endif
                            @if($todasSolicitudes->hasMorePages())
                            <button wire:click="nextPage" class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-xs">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            @else
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed text-xs">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                            @endif
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="modalAbierto"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm overflow-y-auto z-[9999]"
            @keydown.escape.window="cerrarModal()"
            @click.self="cerrarModal()"
            x-cloak>
            <div class="relative top-16 mx-auto p-5 w-11/12 max-w-3xl shadow-xl rounded-xl bg-gray-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 mb-16">
                <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Detalles de Solicitud
                        <span x-show="solicitudSeleccionada" x-text="'#' + solicitudSeleccionada?.SolicitudID" class="text-slate-400 ml-1 font-normal"></span>
                    </h3>
                    <button @click="cerrarModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors p-1 rounded">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div x-show="cargando" class="text-center py-10">
                    <i class="fas fa-spinner fa-spin text-2xl text-slate-400 mb-2 block"></i>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Cargando...</p>
                </div>
                <div x-show="!cargando && solicitudSeleccionada" class="space-y-6">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-3">Solicitante</h4>
                        <div class="grid grid-cols-2 gap-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Nombre</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.empleado?.NombreEmpleado"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Correo</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.empleado?.Correo"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Gerencia</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.gerencia?.NombreGerencia || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Obra</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.obra?.NombreObra || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Puesto</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.puesto?.NombrePuesto || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Ubicación</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.ProyectoNombre || solicitudSeleccionada?.Proyecto || 'N/A'"></p>
                            </div>
                        </div>
                    </div>
                    <template x-if="solicitudSeleccionada?.motivo_cancelacion">
                        <div class="rounded-lg border border-rose-200 dark:border-rose-700/60 bg-rose-50 dark:bg-rose-900/20 p-4">
                            <p class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wide mb-1">Solicitud Cancelada</p>
                            <p class="text-sm text-rose-700 dark:text-rose-300" x-text="solicitudSeleccionada?.motivo_cancelacion"></p>
                            <div class="mt-2 flex flex-wrap gap-4 text-xs text-rose-500 dark:text-rose-400">
                                <span x-text="'Por: ' + (solicitudSeleccionada?.canceladoPorNombre || 'N/A')"></span>
                                <span x-text="solicitudSeleccionada?.fecha_cancelacion || ''"></span>
                            </div>
                        </div>
                    </template>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-3">Solicitud</h4>
                        <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-3">
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Motivo</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.Motivo || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Descripción</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="solicitudSeleccionada?.DescripcionMotivo || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Requerimientos</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="solicitudSeleccionada?.Requerimientos || 'N/A'"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Estatus</p>
                                    <p class="text-sm font-semibold"
                                        :class="{
                                           'text-rose-600':    (solicitudSeleccionada?.estatusDisplay||'') === 'Cancelada',
                                           'text-amber-600':   (solicitudSeleccionada?.estatusDisplay||'') === 'Pendiente',
                                           'text-red-600':     (solicitudSeleccionada?.estatusDisplay||'') === 'Rechazada',
                                           'text-sky-600':     (solicitudSeleccionada?.estatusDisplay||'') === 'En revisión',
                                           'text-emerald-600': (solicitudSeleccionada?.estatusDisplay||'') === 'Aprobada',
                                           'text-teal-600':    (solicitudSeleccionada?.estatusDisplay||'') === 'Listo',
                                           'text-blue-600':    (solicitudSeleccionada?.estatusDisplay||'') === 'Cotizaciones Enviadas',
                                           'text-slate-900 dark:text-slate-100': !['Cancelada','Pendiente','Rechazada','En revisión','Aprobada','Listo','Cotizaciones Enviadas'].includes(solicitudSeleccionada?.estatusDisplay||'')
                                        }"
                                        x-text="solicitudSeleccionada?.estatusDisplay || solicitudSeleccionada?.Estatus || 'Sin estatus'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Creado</p>
                                    <p class="text-sm text-slate-900 dark:text-slate-100" x-text="solicitudSeleccionada?.fechaCreacion || 'N/A'"></p>
                                </div>
                            </div>
                            <div x-show="solicitudSeleccionada?.puedeCotizar" class="pt-2">
                                <a :href="'/solicitudes/' + (solicitudSeleccionada?.SolicitudID || '') + '/cotizar'"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition no-underline">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0 ? 'Editar cotizaciones' : 'Cotizar'"></span>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- ==================== FLUJO DE APROBACIÓN ==================== --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-3">Flujo de Aprobación</h4>

                        {{-- PARTE 1: Pipeline visual de etapas --}}
                        <div class="flex items-stretch mb-4 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                            <template x-for="(paso, index) in solicitudSeleccionada?.pasosAprobacion || []" :key="'pipe-'+index">
                                <div class="flex-1 relative px-4 py-3 text-center"
                                    :class="{
                                        'bg-emerald-50 dark:bg-emerald-900/20':  paso.status === 'approved',
                                        'bg-red-50 dark:bg-red-900/20':          paso.status === 'rejected',
                                        'bg-amber-50/60 dark:bg-amber-900/10':   paso.status === 'pending',
                                        'border-r border-slate-200 dark:border-slate-700': index < (solicitudSeleccionada?.pasosAprobacion?.length || 1) - 1
                                    }">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <span class="text-xl"
                                            :class="{
                                                'text-emerald-500': paso.status === 'approved',
                                                'text-red-500':     paso.status === 'rejected',
                                                'text-amber-400':   paso.status === 'pending'
                                            }">
                                            <template x-if="paso.status === 'approved'"><i class="fas fa-check-circle"></i></template>
                                            <template x-if="paso.status === 'rejected'"><i class="fas fa-times-circle"></i></template>
                                            <template x-if="paso.status === 'pending'"><i class="far fa-clock"></i></template>
                                        </span>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 leading-tight" x-text="paso.stageLabel"></span>
                                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': paso.status === 'approved',
                                                'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300':                 paso.status === 'rejected',
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300':         paso.status === 'pending'
                                            }"
                                            x-text="paso.statusLabel"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- PARTE 2: Detalle de quién decidió / comentarios --}}
                        <div class="space-y-2">
                            <template x-for="(paso, index) in solicitudSeleccionada?.pasosAprobacion || []" :key="'det-'+index">
                                <div x-show="paso.approverNombre || paso.decidedByNombre || paso.decidedAt || paso.comment"
                                    class="flex items-start gap-3 px-4 py-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                    <div class="mt-0.5 w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-[10px]"
                                        :class="{
                                            'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300': paso.status === 'approved',
                                            'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300':                 paso.status === 'rejected',
                                            'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300':         paso.status === 'pending'
                                        }">
                                        <i class="fas"
                                            :class="{
                                                'fa-check': paso.status === 'approved',
                                                'fa-times': paso.status === 'rejected',
                                                'fa-clock': paso.status === 'pending'
                                            }"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1.5" x-text="paso.stageLabel"></p>
                                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                            <span x-show="paso.approverNombre">
                                                <span class="font-medium text-slate-600 dark:text-slate-300">Aprobador:</span>
                                                <span x-text="' ' + paso.approverNombre"></span>
                                            </span>
                                            <span x-show="paso.decidedByNombre">
                                                <span class="font-medium text-slate-600 dark:text-slate-300">Decidido por:</span>
                                                <span x-text="' ' + paso.decidedByNombre"></span>
                                            </span>
                                            <span x-show="paso.decidedAt">
                                                <span class="font-medium text-slate-600 dark:text-slate-300">Fecha:</span>
                                                <span x-text="' ' + paso.decidedAt"></span>
                                            </span>
                                        </div>
                                        <p x-show="paso.comment"
                                            class="mt-1.5 text-xs italic text-slate-500 dark:text-slate-400 border-l-2 border-slate-300 dark:border-slate-600 pl-2"
                                            x-text="paso.comment"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    {{-- ==================== /FLUJO DE APROBACIÓN ==================== --}}

                    <div x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0" x-data="{
                        selectedIndexes: {},
                        getCotizacionesAgrupadas() {
                            const cots = solicitudSeleccionada?.cotizaciones || [];
                            const activosPorCot = solicitudSeleccionada?.activosPorCotizacion || {};
                            const grupos = {};
                            cots.forEach(c => {
                                const p = c.NumeroPropuesta || 0;
                                if (!grupos[p]) grupos[p] = { numeroPropuesta: p, nombreEquipo: c.NombreEquipo || c.Descripcion || 'Equipo', cotizaciones: [] };
                                const activos = activosPorCot[c.CotizacionID] || [];
                                grupos[p].cotizaciones.push({ ...c, activos, esGanador: c.Estatus === 'Seleccionada' });
                            });
                            return Object.values(grupos).map(g => {
                                g.cotizaciones.sort((a, b) => (a.esGanador === b.esGanador) ? 0 : a.esGanador ? -1 : 1);
                                return g;
                            }).sort((a, b) => a.numeroPropuesta - b.numeroPropuesta);
                        },
                        getIdx(p) { return this.selectedIndexes[p] || 0; }
                    }">
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-3">Cotizaciones</h4>
                        <div class="space-y-4">
                            <template x-for="(grupo, gi) in getCotizacionesAgrupadas()" :key="'g'+grupo.numeroPropuesta">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-violet-700 dark:text-violet-300 uppercase">Producto <span x-text="grupo.numeroPropuesta"></span></span>
                                            <span class="text-sm font-medium text-slate-800 dark:text-slate-100" x-text="grupo.nombreEquipo"></span>
                                        </div>
                                        <span class="text-xs text-slate-500"><span x-text="grupo.cotizaciones.length"></span> cotización(es)</span>
                                    </div>
                                    <template x-if="grupo.cotizaciones.length > 1">
                                        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 flex gap-2">
                                            <template x-for="(cot, idx) in grupo.cotizaciones" :key="cot.CotizacionID">
                                                <button type="button" @click="selectedIndexes[grupo.numeroPropuesta] = idx"
                                                    class="px-3 py-1.5 rounded text-xs font-semibold transition"
                                                    :class="getIdx(grupo.numeroPropuesta) === idx
                                                        ? (cot.esGanador ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white')
                                                        : (cot.esGanador ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/30 dark:text-rose-300')">
                                                    <span x-text="cot.Proveedor || 'Opción ' + (idx + 1)"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-for="(cot, ci) in grupo.cotizaciones" :key="cot.CotizacionID||ci">
                                        <div x-show="getIdx(grupo.numeroPropuesta) === ci" class="p-4">
                                            <div class="rounded-lg border p-4" :class="cot.esGanador ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50/30 dark:bg-emerald-900/10' : 'border-rose-200 dark:border-rose-800 bg-rose-50/20 dark:bg-rose-900/10'">
                                                <div class="flex items-center gap-2 mb-3">
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded" :class="cot.esGanador ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'" x-text="cot.Estatus || 'Pendiente'"></span>
                                                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100" x-text="cot.Descripcion || 'Equipo'"></span>
                                                </div>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                                                    <div>
                                                        <p class="text-xs text-slate-500 mb-0.5">Proveedor</p>
                                                        <p class="font-medium text-slate-900 dark:text-slate-100" x-text="cot.Proveedor || 'N/A'"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-slate-500 mb-0.5">No. Parte</p>
                                                        <p class="font-mono text-slate-900 dark:text-slate-100" x-text="cot.NumeroParte || 'N/A'"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-slate-500 mb-0.5">Precio Unit.</p>
                                                        <p class="font-semibold text-slate-900 dark:text-slate-100" x-text="cot.Precio != null ? ('$' + parseFloat(cot.Precio).toLocaleString('es-MX', {minimumFractionDigits:2})) : 'N/A'"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-slate-500 mb-0.5">Envío</p>
                                                        <p class="text-slate-900 dark:text-slate-100" x-text="'$' + parseFloat(cot.CostoEnvio||0).toLocaleString('es-MX', {minimumFractionDigits:2})"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-slate-500 mb-0.5">Total</p>
                                                        <p class="font-bold" :class="cot.esGanador ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-900 dark:text-slate-100'" x-text="'$' + (parseFloat(cot.Precio||0)+parseFloat(cot.CostoEnvio||0)).toLocaleString('es-MX', {minimumFractionDigits:2})"></p>
                                                    </div>
                                                </div>
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
    </template>

    @if($modalAsignacionAbierto)
    @php $modalYaTieneFacturas = $modalEsSoloLectura; @endphp
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm"
        wire:keydown.escape.window="closeAsignacion" tabindex="-1"
        x-data="{ __ready: true }"
        x-init="window.__insumosCatalogo = @js($insumosDisponibles ?? [])">
        <div class="absolute inset-0" wire:click="closeAsignacion"></div>
        <div class="relative z-10 w-full max-w-7xl mx-2 lg:mx-6 bg-gray-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden"
            wire:ignore.self>

            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4 shrink-0">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                            @if($modalYaTieneFacturas) Ver Asignación @else Asignación de Activos @endif
                        </h3>
                        @if($asignacionSolicitudId)
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Solicitud #{{ $asignacionSolicitudId }}</span>
                        @endif
                        @if($modalYaTieneFacturas)
                        <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Solo lectura</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        @if($modalYaTieneFacturas) Visualización de la asignación registrada. @else Asigna responsables, fechas de entrega y sube el XML de factura. @endif
                    </p>
                </div>
                <button type="button" wire:click="closeAsignacion"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            @php $proveedoresAgrupados = collect($propuestasAsignacion)->groupBy('proveedor')->filter(fn($g) => $g->count() > 1); @endphp
            @if($proveedoresAgrupados->isNotEmpty())
            <div class="px-6 py-2.5 bg-blue-50 dark:bg-blue-950/30 border-b border-blue-100 dark:border-blue-800/30 shrink-0">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                    El XML se comparte entre propuestas del mismo proveedor:
                    @foreach($proveedoresAgrupados->keys() as $proveedor)
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $proveedor }}</span>{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            </div>
            @endif

            @if(!$modalYaTieneFacturas)
            @php
            $proveedoresSinFactura = [];
            $proveedoresVistos = [];
            foreach ($propuestasAsignacion as $pIdx => $pItem) {
            $prov = $pItem['proveedor'] ?? '';
            if (!$prov || in_array($prov, $proveedoresVistos, true)) continue;
            $proveedoresVistos[] = $prov;
            $tieneAlgo = false;
            foreach (($pItem['unidades'] ?? []) as $uIdx => $uItem) {
            if (!empty($uItem['factura_xml_path']) || !empty($facturaXml[$pIdx][$uIdx])) { $tieneAlgo = true; break; }
            }
            if (!$tieneAlgo) $proveedoresSinFactura[] = $prov;
            }
            @endphp
            @endif

            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                @if(empty($propuestasAsignacion))
                <div class="py-10 text-center text-slate-500 dark:text-slate-400 text-sm">No hay datos para asignación.</div>
                @else

                @foreach($propuestasAsignacion as $pIndex => $p)
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900"
                    wire:key="prop-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $p['cotizacionId'] ?? 'x' }}">

                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                        <div>
                            <h4 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $p['nombreEquipo'] ?? 'Sin nombre' }}</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $p['proveedor'] ?? 'Sin proveedor' }} &middot; {{ (int)($p['itemsTotal'] ?? 0) }} unidad(es)</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500 mb-0.5">Total</p>
                            <p class="text-lg font-bold text-slate-900 dark:text-slate-100">${{ number_format((float)($p['precioUnitario'] ?? 0), 2, '.', ',') }}</p>
                        </div>
                    </div>

                    <div class="hidden lg:grid grid-cols-12 gap-4 px-5 py-2 bg-slate-50/50 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <div class="col-span-1">ID</div>
                        <div class="col-span-3">Producto</div>
                        <div class="col-span-2">Factura</div>
                        <div class="col-span-3">Fecha de entrega</div>
                        <div class="col-span-3">Usuario final</div>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($p['unidades'] ?? [] as $uIndex => $u)
                        @php 
                        $yaFinalizado = !empty($u['fecha_fin_configuracion']) || !empty($u['config_lista_ui']);
                        // Si tiene activoId significa que fue guardado en BD (persistAsignacion creó SolicitudActivo)
                        $yaGuardado = !empty($u['activoId']);
                        @endphp
                        <div class="px-5 py-4" wire:key="unit-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                <div class="col-span-1 flex lg:justify-center">
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold shrink-0">
                                        {{ $u['unidadIndex'] ?? ($uIndex + 1) }}
                                    </span>
                                </div>
                                <div class="col-span-3 flex items-center">
                                    <div>
                                        <p class="lg:hidden text-[10px] font-bold uppercase text-slate-400">Producto</p>
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-snug">{{ $p['nombreEquipo'] ?? 'Producto' }}</p>
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <p class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1"> Factura</p>
                                    @php
                                    $xmlSavedPath = $u['factura_xml_path'] ?? '';
                                    $hasNewXml = !empty($facturaXml[$pIndex][$uIndex]);
                                    $esXmlGuardado = !empty($xmlSavedPath);
                                    $parsed = $xmlParseado[$pIndex][$uIndex] ?? null;
                                    $parsedOk = $parsed && empty($parsed['error']) && !empty($parsed['uuid']);
                                    $provActual = $p['proveedor'] ?? '';
                                    $esPrimerProv = true;
                                    for ($i = 0; $i < $pIndex; $i++) {
                                        if (($propuestasAsignacion[$i]['proveedor'] ?? '' )===$provActual) { $esPrimerProv=false; break; }
                                        }
                                        $mostrarInput=($uIndex===0 && $esPrimerProv);
                                        @endphp
                                        @if($mostrarInput)
                                        @if($modalYaTieneFacturas)
                                        @if($esXmlGuardado)
                                        <a href="{{ Storage::url($xmlSavedPath) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-xs font-medium bg-violet-50 border border-violet-200 text-violet-700 hover:bg-violet-100 transition-colors dark:bg-violet-900/20 dark:border-violet-700/50 dark:text-violet-300">
                                        <i class="fas fa-file-code text-violet-500"></i>
                                        <span class="truncate max-w-[7rem]">{{ basename($xmlSavedPath) }}</span>
                                        </a>
                                        @else
                                        <span class="inline-flex items-center gap-1 h-9 px-3 rounded-lg text-xs font-medium bg-slate-100 border border-slate-200 text-slate-500 dark:bg-slate-800/50 dark:border-slate-700 dark:text-slate-400">
                                            <i class="fas fa-globe"></i> Sin XML
                                        </span>
                                        @endif
                                        @else
                                        <div class="flex flex-col gap-1">
                                            @if($esXmlGuardado && !$hasNewXml)
                                            <div class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border-2 border-violet-300 bg-violet-50 text-violet-700 text-xs dark:bg-violet-900/20 dark:border-violet-600 dark:text-violet-300 cursor-not-allowed opacity-70">
                                                <i class="fas fa-lock text-violet-500 text-[9px]"></i>
                                                <span class="font-medium truncate max-w-[7rem]">{{ basename($xmlSavedPath) }}</span>
                                            </div>
                                            @else
                                            <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border-2 border-dashed cursor-pointer transition-all text-xs
                                                {{ $hasNewXml ? 'bg-violet-50 border-violet-400 text-violet-700 hover:bg-violet-100 dark:bg-violet-900/20 dark:border-violet-600 dark:text-violet-300' : 'bg-violet-50/40 border-violet-300 text-violet-600 hover:bg-violet-50 hover:border-violet-400 dark:bg-violet-950/20 dark:border-violet-700 dark:text-violet-400' }}">
                                                <input type="file" class="hidden" accept="text/xml,application/xml,.xml"
                                                    wire:model.live="facturaXml.{{ $pIndex }}.{{ $uIndex }}">
                                                <i class="fas {{ $hasNewXml ? 'fa-check-circle text-violet-500' : 'fa-file-code text-violet-400' }}"></i>
                                                <span class="font-medium truncate max-w-[7rem]">
                                                    {{ $hasNewXml ? ($parsedOk ? 'XML Cargado' : 'XML cargado') : 'Subir XML' }}
                                                </span>
                                            </label>
                                            <div wire:loading wire:target="facturaXml.{{ $pIndex }}.{{ $uIndex }}" class="text-[10px] text-violet-500 flex items-center gap-1">
                                                <i class="fas fa-spinner fa-spin"></i> Validando...
                                            </div>
                                            @error("facturaXml.$pIndex.$uIndex")
                                            <p class="text-[10px] text-red-600 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>
                                        @endif
                                        @else
                                        <div class="flex items-center justify-center h-9 px-3 rounded-lg text-xs text-slate-500 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                            @if($uIndex > 0)
                                            <i class="fas fa-link mr-1.5 opacity-60"></i> Compartido con U1
                                            @else
                                            <i class="fas fa-building mr-1.5 opacity-60 text-blue-500"></i> Mismo proveedor
                                            @endif
                                        </div>
                                        @if(!empty($u['factura_xml_path']))
                                        <a href="{{ Storage::url($u['factura_xml_path']) }}" target="_blank" class="block text-[10px] text-violet-600 dark:text-violet-400 hover:underline mt-1">
                                            <i class="fas fa-file-code mr-1"></i>Ver XML
                                        </a>
                                        @endif
                                        @endif
                                </div>
                                <div class="col-span-3">
                                    <p class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Fecha de entrega</p>
                                    @if($modalYaTieneFacturas || $yaGuardado)
                                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-300">
                                        <i class="fas fa-calendar-alt text-slate-400 text-xs"></i> {{ $u['fecha_entrega'] ?? 'Sin fecha' }}
                                    </div>
                                    @else
                                    <input type="date"
                                        wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.fecha_entrega"
                                        class="h-10 w-full px-3 text-sm border border-slate-200 rounded-lg bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.fecha_entrega")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                    @endif
                                </div>
                                <div class="col-span-3"
                                    x-data="{ open: false }"
                                    @click.outside="open = false">
                                    <label class="lg:hidden text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 block">Usuario final</label>
                                    @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado)
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
                                            wire:model.live.debounce.400ms="usuarioSearch.{{ $pIndex }}.{{ $uIndex }}"
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
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $u['departamento_nombre'] ?? '-' }}</span>
                                    </div>
                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.empleado_id")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                    @enderror
                                    @endif
                                </div>

                            </div>

                            @php
                            $checklistFlat = collect($u['checklist'] ?? [])
                            ->flatMap(fn($items) => is_array($items) ? array_values($items) : [])
                            ->filter(fn($i) => is_array($i) && isset($i['nombre']));
                            $hasChecklistItems = $checklistFlat->isNotEmpty();
                            @endphp
                            @if($hasChecklistItems)
                            @php
                            $checklistFlat2 = collect($u['checklist'] ?? [])->flatMap(fn($items) => is_array($items) ? array_values($items) : [])->filter(fn($i) => is_array($i) && isset($i['nombre']));
                            $totalItemsCount = $checklistFlat2->count();
                            $initialDoneStates = [];
                            $flatIdx = 0;
                            foreach ($u['checklist'] ?? [] as $_ck => $_items) {
                            foreach ($_items as $_item) {
                            $initialDoneStates[$flatIdx] = !empty($_item['realizado']);
                            $flatIdx++;
                            }
                            }
                            @endphp
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800"
                                wire:key="checklist-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}"
                                x-data="{
                                checklistOpen: {{ ($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) ? 'false' : 'true' }},
                                reqConfig: {{ ($u['requiere_config'] ?? false) ? 'true' : 'false' }},
                                doneStates: @js($initialDoneStates),
                                totalItems: {{ $totalItemsCount }},
                                get marcados() { return Object.values(this.doneStates).filter(Boolean).length; },
                                get porcentaje() { return this.totalItems > 0 ? Math.round((this.marcados / this.totalItems) * 100) : 0; },
                                toggleItem(flatIdx) { this.doneStates[flatIdx] = !this.doneStates[flatIdx]; }
                            }">
                                <label class="inline-flex items-center gap-2 {{ (!$modalYaTieneFacturas && !$yaFinalizado && !$yaGuardado) ? 'cursor-pointer' : 'cursor-not-allowed opacity-70' }} mb-3 select-none"
                                    @if(!$modalYaTieneFacturas && !$yaFinalizado && !$yaGuardado)
                                    @click.prevent="
                                    let oldVal = reqConfig;
                                    reqConfig = !reqConfig;
                                    if (oldVal && !reqConfig) {
                                        Object.keys(doneStates).forEach(k => doneStates[k] = false);
                                    }
                                    $wire.toggleRequiereConfig({{ $pIndex }}, {{ $uIndex }})
                                "
                                    @endif>
                                    <input type="checkbox" class="peer sr-only" :checked="reqConfig"
                                        @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) disabled @endif>
                                    <div class="relative w-9 h-5 rounded-full border-2 transition-all"
                                        :class="reqConfig ? 'bg-violet-500 border-violet-500 dark:bg-violet-600 dark:border-violet-600' : 'bg-slate-200 border-slate-300 dark:bg-slate-700 dark:border-slate-600'">
                                        <span class="absolute left-0.5 top-0.5 w-3.5 h-3.5 rounded-full bg-gray-50 shadow transition-transform duration-200" :class="reqConfig ? 'translate-x-4' : ''"></span>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Requiere configuración</span>
                                </label>
                                <div x-show="reqConfig" wire:key="checklist-content-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                                    <button type="button" @click="checklistOpen = !checklistOpen"
                                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Checklist de configuración</span>
                                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': checklistOpen }"></i>
                                    </button>
                                    <div x-show="checklistOpen" x-transition class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @php $flatCounter = 0; @endphp
                                        @foreach($u['checklist'] ?? [] as $catKey => $items)
                                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $catKey }}</p>
                                            </div>
                                            <div class="p-3 space-y-2">
                                                @foreach($items as $idx => $item)
                                                @php $currentFlat = $flatCounter; $flatCounter++; @endphp
                                                <div class="flex items-center gap-3 p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                                    <label class="flex items-center justify-center cursor-pointer"
                                                        @if(!$modalYaTieneFacturas && !$yaFinalizado && !$yaGuardado)
                                                        @click.prevent="toggleItem({{ $currentFlat }}); $wire.marcarChecklist({{ $pIndex }}, {{ $uIndex }}, '{{ $catKey }}', {{ $idx }})"
                                                        @endif>
                                                        <input type="checkbox"
                                                            :checked="doneStates[{{ $currentFlat }}]"
                                                            @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) disabled @endif
                                                            class="peer sr-only">
                                                        <div class="w-5 h-5 rounded border-2 transition-all flex items-center justify-center"
                                                            :class="doneStates[{{ $currentFlat }}] ? 'bg-green-500 border-green-500' : 'border-slate-300 dark:border-slate-600'">
                                                            <i class="fas fa-check text-white text-[8px]" :class="doneStates[{{ $currentFlat }}] ? 'opacity-100' : 'opacity-0'"></i>
                                                        </div>
                                                    </label>
                                                    <span class="text-sm flex-1" :class="doneStates[{{ $currentFlat }}] ? 'line-through text-slate-400' : 'text-slate-800 dark:text-slate-200'">{{ $item['nombre'] ?? '' }}</span>
                                                    <input type="text"
                                                        wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.checklist.{{ $catKey }}.{{ $idx }}.responsable"
                                                        @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) readonly @endif
                                                        placeholder="Responsable"
                                                        class="h-7 w-24 px-2 text-xs border border-slate-200 rounded bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 text-center {{ ($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) ? 'opacity-60 cursor-not-allowed' : '' }}">
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                {{-- Counter section: inside same x-data for Alpine reactivity --}}
                                <template x-if="reqConfig">
                                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3"
                                        wire:key="finalize-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                                        @if(!$yaFinalizado && !$yaGuardado && $totalItemsCount > 0)
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <span class="font-semibold" :class="marcados === totalItems ? 'text-emerald-600' : 'text-slate-600 dark:text-slate-300'" x-text="marcados + '/' + totalItems"></span>
                                            <span>tareas</span>
                                            <div class="w-20 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all"
                                                    :class="marcados === totalItems ? 'bg-emerald-500' : 'bg-violet-500'"
                                                    :style="'width: ' + porcentaje + '%'"></div>
                                            </div>
                                        </div>
                                        @else
                                        <div></div>
                                        @endif
                                        @if(!empty($u['config_lista_ui']))
                                        <div class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800">
                                            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                                            <div>
                                                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Ticket creado y equipo configurado</p>
                                                <p class="text-xs text-emerald-600 dark:text-emerald-400">Ya puedes subir el XML de factura.</p>
                                            </div>
                                        </div>
                                        @elseif($yaFinalizado || $yaGuardado)
                                        <span class="text-xs font-medium px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                            <i class="fas fa-check-double mr-1"></i>
                                            Finalizado el {{ \Carbon\Carbon::parse($u['fecha_fin_configuracion'])->format('d/m/Y H:i') }}
                                        </span>
                                        @else
                                        <button type="button"
                                            wire:loading.attr="disabled"
                                            wire:target="finalizarConfiguracionUnidad"
                                            onclick="
                                        var btn = this;
                                        Swal.fire({
                                            background: '#f9fafb', color: '#1e293b',
                                            title: 'Finalizar instalación',
                                            html: 'Se registrará la fecha y hora actual y se creará un ticket de instalación.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonText: 'Sí, finalizar',
                                            cancelButtonText: 'Cancelar',
                                            confirmButtonColor: '#4f46e5',
                                            cancelButtonColor: '#94a3b8',
                                            reverseButtons: true,
                                        }).then(function(result) {
                                            if (result.isConfirmed) {
                                                var wireEl = btn.closest('[wire\\:id]');
                                                Livewire.find(wireEl.getAttribute('wire:id')).finalizarConfiguracionUnidad({{ $pIndex }}, {{ $uIndex }});
                                            }
                                        });
                                    "
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors disabled:opacity-50">
                                            <span wire:loading.remove wire:target="finalizarConfiguracionUnidad"><i class="fas fa-flag-checkered mr-1"></i> Finalizar Instalación</span>
                                            <span wire:loading wire:target="finalizarConfiguracionUnidad"><i class="fas fa-spinner fa-spin mr-1"></i> Registrando...</span>
                                        </button>
                                        @endif
                                    </div>
                                </template>
                            </div>
                            @endif

                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                @if(!$modalYaTieneFacturas)
                @php
                $todasFacturasParseadas = collect();
                $uuidsVistos = []; // Solo deduplicar por UUID, NO por proveedor
                foreach ($propuestasAsignacion as $pi => $p) {
                foreach (($p['unidades'] ?? []) as $ui => $u) {
                $parsed = $xmlParseado[$pi][$ui] ?? null;
                if (!$parsed || !empty($parsed['error'])) continue;
                // Deduplicar SOLO por UUID (si existe y ya se vio)
                $uuid = trim((string)($parsed['uuid'] ?? ''));
                if ($uuid && in_array($uuid, $uuidsVistos, true)) continue;
                if ($uuid) $uuidsVistos[] = $uuid;
                // Si no tiene UUID, siempre mostrar (no deduplicar por proveedor)
                $todasFacturasParseadas->push([
                'uuid' => $uuid,
                'emisor' => $parsed['emisor'] ?? '',
                'mes' => $parsed['mes'] ?? '',
                'anio' => $parsed['anio'] ?? '',
                'total' => $parsed['subtotal'] ?? $parsed['total'] ?? '0',
                'moneda' => $parsed['moneda'] ?? 'MXN',
                'insumoId' => $parsed['insumoId'] ?? null,
                'nombreEquipo' => $p['nombreEquipo'] ?? 'Sin nombre',
                'proveedor' => $p['proveedor'] ?? 'Sin proveedor',
                'pIndex' => $pi,
                'uIndex' => $ui,
                ]);
                }
                }
                $facturasSinInsumo = $todasFacturasParseadas->filter(fn($f) => empty($f['insumoId']))->count();
                $facturasConInsumo = $todasFacturasParseadas->count() - $facturasSinInsumo;
                @endphp

                @if($todasFacturasParseadas->isNotEmpty())
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden" x-data="{ expandido: true }">
                    <button type="button" @click="expandido = !expandido"
                        class="w-full px-5 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="text-left">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Asignación de Insumos</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($facturasSinInsumo > 0)
                            <span class="text-[11px] font-bold px-2 py-1 rounded bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700/50">{{ $facturasSinInsumo }} sin insumo</span>
                            @endif
                            @if($facturasConInsumo > 0)
                            <span class="text-[11px] font-bold px-2 py-1 rounded bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700/50">{{ $facturasConInsumo }} asignados</span>
                            @endif
                            <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': expandido }"></i>
                        </div>
                    </button>

                    <div x-show="expandido" x-transition>
                        <div class="px-5 py-2.5 bg-sky-50 dark:bg-slate-800 border-b border-sky-100 dark:border-slate-700">
                            <p class="text-xs text-sky-700 dark:text-sky-400">
                                <i class="fas fa-lightbulb text-sky-500 mr-1"></i>
                                Asigna el insumo del catálogo que corresponde a cada factura.
                            </p>
                        </div>

                        <div class="overflow-visible">
                            <table class="w-full text-xs table-fixed">
                                <colgroup>
                                    <col class="w-[30%]">
                                    <col class="w-[28%]">
                                    <col class="w-[18%]">
                                    <col class="w-[24%]">
                                </colgroup>
                                <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="text-left px-4 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Cotización</th>
                                        <th class="text-left px-3 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Emisor</th>
                                        <th class="text-right px-3 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Total</th>
                                        <th class="text-left px-3 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Asignación</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900">
                                    @foreach($todasFacturasParseadas as $fIdx => $facturaData)
                                    @php
                                    $fPIndex = $facturaData['pIndex'];
                                    $fUIndex = $facturaData['uIndex'];
                                    $tieneMatch = !empty($facturaData['insumoId']);
                                    $insumoActual = $tieneMatch ? (int)$facturaData['insumoId'] : null;
                                    $nombreInsumoActual = '';
                                    if ($insumoActual) {
                                    $found = collect($insumosDisponibles)->firstWhere('id', $insumoActual);
                                    $nombreInsumoActual = $found['nombre'] ?? '';
                                    }
                                    @endphp
                                    <tr class="{{ $tieneMatch ? '' : 'bg-amber-50/30 dark:bg-amber-950/10' }} hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                        wire:key="factura-row-{{ $fPIndex }}-{{ $fUIndex }}">
                                        <td class="px-4 py-3">
                                            <span class="text-slate-800 dark:text-slate-200 font-medium leading-tight block truncate">{{ Str::limit($facturaData['nombreEquipo'], 38) }}</span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="text-slate-500 dark:text-slate-400 leading-tight block truncate">{{ Str::limit($facturaData['emisor'], 32) }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono tabular-nums">${{ number_format((float)($facturaData['total'] ?? 0), 2) }}</span>
                                        </td>
                                        <td class="px-3 py-3" wire:key="insumo-td-{{ $fPIndex }}-{{ $fUIndex }}">
                                            <button type="button"
                                                id="insumo-btn-{{ $fPIndex }}-{{ $fUIndex }}"
                                                data-insumo-id="{{ $insumoActual ?? '' }}"
                                                onclick="window.__insumoDD.open({{ $fPIndex }}, {{ $fUIndex }}, this)"
                                                class="w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group
                                                    {{ $tieneMatch
                                                        ? 'bg-emerald-50 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-700/50 dark:hover:bg-emerald-900/40'
                                                        : 'bg-amber-50 border-dashed border-2 border-amber-300 hover:bg-amber-100 dark:bg-amber-900/30 dark:border-amber-700/50 dark:hover:bg-amber-900/50' }}">
                                                @if($tieneMatch)
                                                <i class="fas fa-check-circle text-emerald-500 dark:text-emerald-400 text-[10px] shrink-0"></i>
                                                <span class="text-[11px] font-medium text-emerald-800 dark:text-emerald-200 truncate flex-1">{{ Str::limit($nombreInsumoActual, 22) }}</span>
                                                <i class="fas fa-pencil-alt text-[8px] text-emerald-400 shrink-0 opacity-40 group-hover:opacity-100 transition-opacity"></i>
                                                @else
                                                <i class="fas fa-plus-circle text-amber-500 dark:text-amber-400 text-[10px] shrink-0"></i>
                                                <span class="text-[11px] font-medium text-amber-700 dark:text-amber-300">Asignar insumo</span>
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                @endif

                @endif
            </div>

            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3 bg-gray-50 dark:bg-slate-900 shrink-0">
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    @if($modalYaTieneFacturas) Solo lectura — para modificar contacta al administrador. @else Guarda el avance para conservar los XML y datos ingresados. @endif
                </p>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="closeAsignacion"
                        class="px-4 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
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
                                background: '#f9fafb', color: '#1e293b',
                                title: 'Guardar XML y avance',
                                text: 'Se validará que hayas asignado responsable y fecha de entrega.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Guardar y cerrar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#059669',
                                cancelButtonColor: '#94a3b8',
                                reverseButtons: true,
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    var wireEl = btn.closest('[wire\\:id]');
                                    Livewire.find(wireEl.getAttribute('wire:id')).persistAsignacion(true, true);
                                }
                            });
                        "
                        class="px-4 py-2 text-sm rounded-lg font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-700 transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="persistAsignacion"><i class="fas fa-save mr-1"></i> Guardar</span>
                        <span wire:loading wire:target="persistAsignacion"><i class="fas fa-spinner fa-spin mr-1"></i> Guardando...</span>
                    </button>
                    @endif
                </div>
            </div>

            <div wire:loading wire:target="persistAsignacion"
                class="absolute inset-0 bg-gray-50/70 dark:bg-slate-900/70 flex items-center justify-center z-50 rounded-xl">
                <div class="flex flex-col items-center gap-2">
                    <i class="fas fa-spinner fa-spin text-2xl text-slate-600 dark:text-slate-300"></i>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Guardando...</p>
                </div>
            </div>
        </div>
    </div>
    @endif


    @if($modalCancelacionAbierto)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm"
        wire:keydown.escape.window="cerrarModalCancelacion">
        <div class="absolute inset-0" wire:click="cerrarModalCancelacion"></div>
        <div class="relative z-10 w-full max-w-md mx-4 bg-gray-50 dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                            Cancelar Solicitud @if($solicitudCancelarId)<span class="text-slate-400 font-normal">#{{ $solicitudCancelarId }}</span>@endif
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <button type="button" wire:click="cerrarModalCancelacion"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Motivo de cancelación <span class="text-red-500">*</span>
                </label>
                <textarea wire:model.live="motivoCancelacion" rows="4"
                    placeholder="Describe por que se esta cancelando esta solicitud..."
                    class="w-full px-4 py-3 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none"></textarea>
                @error('motivoCancelacion')
                <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </p>
                @enderror
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                <button type="button" wire:click="cerrarModalCancelacion" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50">
                    Regresar
                </button>
                <button type="button" wire:click="confirmarCancelacion" wire:loading.attr="disabled" wire:target="confirmarCancelacion"
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="confirmarCancelacion"><i class="fas fa-ban mr-1"></i>Confirmar Cancelación</span>
                    <span wire:loading wire:target="confirmarCancelacion"><i class="fas fa-spinner fa-spin mr-1"></i>Procesando...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    /* ── Dropdown de Insumos: vanilla JS, un solo panel flotante compartido ── */
    window.__insumoDD = (function() {
        var panel = null;
        var input = null;
        var listEl = null;
        var currentPIdx = null;
        var currentUIdx = null;
        var currentBtn = null;
        var debounceTimer = null;

        function getWireId() {
            if (!currentBtn) return null;
            var el = currentBtn.closest('[wire\\:id]');
            return el ? el.getAttribute('wire:id') : null;
        }

        function ensurePanel() {
            if (panel) return;
            panel = document.createElement('div');
            panel.id = '__insumo-panel';
            panel.style.cssText = 'display:none;position:fixed;z-index:999999;width:300px;';
            panel.className = 'rounded-xl border border-slate-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 shadow-2xl ring-1 ring-black/5 dark:ring-white/5 overflow-hidden';
            panel.innerHTML =
                '<div class="p-2.5 bg-gray-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700">' +
                '<div class="relative">' +
                '<input type="text" id="__insumo-search" placeholder="Escribe para buscar insumo..." ' +
                'class="w-full h-8 pl-8 pr-3 text-xs rounded-lg border border-slate-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 transition-all">' +
                '<i class="fas fa-search text-[9px] text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>' +
                '</div>' +
                '</div>' +
                '<div id="__insumo-list" class="max-h-64 overflow-y-auto overscroll-contain bg-gray-50 dark:bg-slate-900"></div>' +
                '<div class="px-3 py-2 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between">' +
                '<span id="__insumo-count" class="text-[10px] text-slate-400 dark:text-slate-500"></span>' +
                '<button type="button" onclick="window.__insumoDD.close()" class="text-[10px] text-slate-400 hover:text-slate-600 transition-colors px-2 py-0.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700">' +
                '<i class="fas fa-times mr-1"></i>Cerrar' +
                '</button>' +
                '</div>';
            document.body.appendChild(panel);

            input = document.getElementById('__insumo-search');
            listEl = document.getElementById('__insumo-list');

            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    renderList();
                }, 150);
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') close();
            });
            document.addEventListener('mousedown', function(e) {
                if (panel && panel.style.display !== 'none' && !panel.contains(e.target) && e.target !== currentBtn && currentBtn && !currentBtn.contains(e.target)) {
                    close();
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && panel && panel.style.display !== 'none') close();
            });
        }

        function renderList() {
            var q = input.value.trim().toLowerCase();
            var todos = window.__insumosCatalogo || [];
            var countEl = document.getElementById('__insumo-count');
            var html = '';

            // Botón quitar si ya tiene insumo
            var btn = currentBtn;
            if (btn && btn.dataset.insumoId) {
                html += '<button type="button" onclick="window.__insumoDD.select(null, \'\')" ' +
                    'class="w-full px-3 py-2 text-left text-xs flex items-center gap-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors border-b border-slate-100 dark:border-slate-800">' +
                    '<i class="fas fa-times-circle shrink-0"></i><span>Quitar insumo asignado</span></button>';
            }

            if (!q) {
                html += '<div class="px-4 py-6 text-center flex flex-col items-center gap-2">' +
                    '<i class="fas fa-keyboard text-slate-300 dark:text-slate-600 text-xl"></i>' +
                    '<span class="text-xs text-slate-400">Escribe al menos una letra para buscar</span></div>';
                if (countEl) countEl.textContent = todos.length + ' insumos disponibles';
            } else {
                var filtered = todos.filter(function(i) {
                    return i.nombre.toLowerCase().includes(q);
                }).slice(0, 40);
                if (filtered.length === 0) {
                    html += '<div class="px-4 py-6 text-center flex flex-col items-center gap-2">' +
                        '<i class="fas fa-search text-slate-300 dark:text-slate-600 text-xl"></i>' +
                        '<span class="text-xs text-slate-400">Sin resultados para "' + q.replace(/</g, '&lt;') + '"</span></div>';
                } else {
                    for (var i = 0; i < filtered.length; i++) {
                        var ins = filtered[i];
                        var safeName = (ins.nombre || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n');
                        html += '<button type="button" onclick="window.__insumoDD.select(' + ins.id + ', \'' + safeName + '\')" ' +
                            'class="w-full px-3 py-2.5 text-left text-xs text-slate-700 dark:text-slate-200 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0 flex items-center gap-2 group">' +
                            '<i class="fas fa-tag text-[9px] text-slate-300 dark:text-slate-600 group-hover:text-violet-400 transition-colors shrink-0"></i>' +
                            '<span class="truncate">' + ins.nombre.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></button>';
                    }
                }
                if (countEl) countEl.textContent = filtered.length + ' resultado(s)';
            }
            listEl.innerHTML = html;
        }

        function open(pIdx, uIdx, btnEl) {
            ensurePanel();
            currentPIdx = pIdx;
            currentUIdx = uIdx;
            currentBtn = btnEl;

            var rect = btnEl.getBoundingClientRect();
            var left = Math.min(rect.left, window.innerWidth - 320);
            var w = Math.max(rect.width, 300);
            panel.style.left = left + 'px';
            panel.style.width = w + 'px';
            if (rect.bottom + 340 > window.innerHeight) {
                panel.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
                panel.style.top = 'auto';
            } else {
                panel.style.top = (rect.bottom + 4) + 'px';
                panel.style.bottom = 'auto';
            }

            input.value = '';
            panel.style.display = 'block';
            renderList();
            setTimeout(function() {
                input.focus();
            }, 30);
        }

        function close() {
            if (panel) panel.style.display = 'none';
            currentPIdx = null;
            currentUIdx = null;
            currentBtn = null;
        }

        function select(insumoId, insumoNombre) {
            var wId = getWireId();
            if (wId && currentBtn) {
                if (insumoId) {
                    currentBtn.dataset.insumoId = insumoId;

                    // Actualizar clases al estilo "seleccionado" (verde)
                    currentBtn.className = 'w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group' +
                        ' bg-emerald-50 border-emerald-200 hover:bg-emerald-100' +
                        ' dark:bg-emerald-900/20 dark:border-emerald-700/50 dark:hover:bg-emerald-900/40';

                    // Truncar nombre igual que PHP: Str::limit($nombreInsumoActual, 22)
                    var nombreCorto = insumoNombre.length > 22 ?
                        insumoNombre.substring(0, 22) + '…' :
                        insumoNombre;

                    currentBtn.innerHTML =
                        '<i class="fas fa-check-circle text-emerald-500 dark:text-emerald-400 text-[10px] shrink-0"></i>' +
                        '<span class="text-[11px] font-medium text-emerald-800 dark:text-emerald-200 truncate flex-1">' +
                        nombreCorto.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                        '</span>' +
                        '<i class="fas fa-pencil-alt text-[8px] text-emerald-400 shrink-0 opacity-40 group-hover:opacity-100 transition-opacity"></i>';

                } else {
                    // Quitar insumo → volver al estado "pendiente" (ámbar)
                    currentBtn.dataset.insumoId = '';

                    currentBtn.className = 'w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group' +
                        ' bg-amber-50 border-dashed border-2 border-amber-300 hover:bg-amber-100' +
                        ' dark:bg-amber-900/30 dark:border-amber-700/50 dark:hover:bg-amber-900/50';

                    currentBtn.innerHTML =
                        '<i class="fas fa-plus-circle text-amber-500 dark:text-amber-400 text-[10px] shrink-0"></i>' +
                        '<span class="text-[11px] font-medium text-amber-700 dark:text-amber-300">Asignar insumo</span>';
                }

                try {
                    var lwComponent = Livewire.find(wId);
                    if (lwComponent && typeof lwComponent.actualizarInsumoConcepto === 'function') {
                        lwComponent.actualizarInsumoConcepto(currentPIdx, currentUIdx, insumoId);
                    }
                } catch (e) {
                    console.error('Error calling actualizarInsumoConcepto:', e);
                }
            }
            close();
        }

        return {
            open: open,
            close: close,
            select: select
        };
    })();

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
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.solicitudSeleccionada = data;
                        this.cargando = false;
                    })
                    .catch(() => {
                        this.cargando = false;
                    });
            },
            cerrarModal() {
                this.modalAbierto = false;
                this.solicitudSeleccionada = null;
            }
        }
    }

    document.addEventListener('livewire:load', function() {
        const _swalBase = {
            background: '#f9fafb',
            color: '#1e293b',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#94a3b8'
        };

        window.addEventListener('swal:success', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Cambios guardados correctamente');
            Swal.fire({
                ..._swalBase,
                icon: 'success',
                title: 'Listo',
                text: msg,
                timer: 3500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });

        window.addEventListener('swal:error', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Ocurrio un error inesperado');
            Swal.fire({
                ..._swalBase,
                icon: 'error',
                title: 'Error',
                text: msg
            });
        });

        window.addEventListener('swal:warning', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Atencion requerida');
            Swal.fire({
                ..._swalBase,
                icon: 'warning',
                title: 'Atencion',
                text: msg
            });
        });

        window.addEventListener('swal:info', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : '');
            if (!msg) return;
            Swal.fire({
                ..._swalBase,
                icon: 'info',
                text: msg,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    });
</script>