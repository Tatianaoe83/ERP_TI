<div x-data="solicitudesData()">
    
    <div class="rounded-lg shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
        
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Solicitudes de Equipos TI</h2>
        </div>

        <div wire:poll.15s>
            
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex gap-3">
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cotiz.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
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
                                                <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Supervisor: Aprobado"></i>
                                            @elseif($solicitud->pasoSupervisor->status === 'rejected')
                                                <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Supervisor: Rechazado"></i>
                                            @else
                                                <i class="far fa-circle text-yellow-500 dark:text-yellow-400" title="Supervisor: Pendiente"></i>
                                            @endif
                                        @else
                                            <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Supervisor: Pendiente"></i>
                                        @endif

                                        @if($solicitud->pasoGerencia)
                                            @if($solicitud->pasoGerencia->status === 'approved')
                                                <i class="fas fa-check-circle text-green-500 dark:text-green-400" title="Gerencia: Aprobado"></i>
                                            @elseif($solicitud->pasoGerencia->status === 'rejected')
                                                <i class="fas fa-times-circle text-red-500 dark:text-red-400" title="Gerencia: Rechazado"></i>
                                            @else
                                                <i class="far fa-circle text-orange-500 dark:text-orange-400" title="Gerencia: Pendiente"></i>
                                            @endif
                                        @else
                                            <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Gerencia: Esperando"></i>
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

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($solicitud->cotizaciones && $solicitud->cotizaciones->count() > 0)
                                        <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $solicitud->cotizaciones->count() }}/3</span>
                                    @else
                                        <span class="text-sm text-slate-400 dark:text-slate-500">0/3</span>
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
                                            <button
                                                @click="abrirModalCotizacion({{ $solicitud->SolicitudID }})"
                                                class="text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-300 text-sm font-medium transition-colors">
                                                <i class="fas fa-file-invoice-dollar mr-1"></i> Cotizar
                                            </button>
                                        @endif

                                        @if($solicitud->puedeSubirFactura)
                                            <span class="text-emerald-600 dark:text-emerald-400 text-sm font-medium cursor-default">
                                                <i class="fas fa-file-invoice mr-1"></i> Factura
                                            </span>
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
                                
                                <div class="mt-4 flex flex-wrap gap-2" x-show="solicitudSeleccionada?.puedeCotizar && solicitudSeleccionada?.estatusDisplay !== 'Aprobada' && solicitudSeleccionada?.estatusDisplay !== 'Cotizaciones Enviadas'">
                                    <button @click="abrirModalCotizacion(solicitudSeleccionada?.SolicitudID)"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 dark:bg-violet-700 dark:hover:bg-violet-600 text-white text-sm font-medium rounded-lg transition shadow-sm">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <span x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0 ? 'Editar cotizaciones' : 'Cotizar'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-tasks text-purple-500 dark:text-purple-400"></i>
                                Flujo de Aprobación
                            </h4>
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

                        <div class="mb-6" x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0">
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                                <i class="fas fa-file-invoice-dollar text-violet-500 dark:text-violet-400"></i>
                                Cotizaciones (<span x-text="solicitudSeleccionada?.cotizaciones?.length || 0"></span>)
                            </h4>
                            <div class="space-y-3">
                                <template x-for="(cotizacion, index) in solicitudSeleccionada?.cotizaciones || []" :key="index">
                                    <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Proveedor</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="cotizacion.Proveedor"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">NO. PARTE</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="cotizacion.NumeroParte || 'N/A'"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Precio</label>
                                                <p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="'$' + parseFloat(cotizacion.Precio).toLocaleString('es-MX', {minimumFractionDigits: 2})"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Estatus</label>
                                                <p class="text-sm font-medium"
                                                   :class="{
                                                       'text-emerald-600 dark:text-emerald-400': cotizacion.Estatus === 'Seleccionada',
                                                       'text-red-600 dark:text-red-400': cotizacion.Estatus === 'Rechazada',
                                                       'text-slate-600 dark:text-slate-400': cotizacion.Estatus === 'Pendiente'
                                                   }"
                                                   x-text="cotizacion.Estatus === 'Seleccionada' ? 'Ganador' : cotizacion.Estatus"></p>
                                            </div>
                                            <div class="col-span-4 border-t border-slate-200 dark:border-slate-700 mt-2 pt-2">
                                                <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Descripción</label>
                                                <p class="text-sm text-slate-700 dark:text-slate-300" x-text="cotizacion.Descripcion"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-show="modalCotizacionAbierto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
            @click.self="cerrarModalCotizacion()"
            style="display: none;">
            
            <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-7xl shadow-2xl rounded-lg max-h-[90vh] overflow-y-auto bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">
                
                <div class="flex justify-between items-center pb-3 border-b sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                        Cotización - Solicitud #<span x-text="solicitudCotizacionId"></span>
                        <span class="text-xs font-normal text-slate-500 dark:text-slate-400 ml-2">PRECIO IVA INCLUIDO</span>
                    </h3>
                    <button @click="cerrarModalCotizacion()" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mt-4">
                    <div x-show="cargandoCotizaciones" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-slate-400 dark:text-slate-600"></i>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Cargando cotizaciones...</p>
                    </div>

                    <div x-show="!cargandoCotizaciones" style="display: none;">
                        
                        <div x-show="tieneCotizacionesEnviadas || solicitudSeleccionada?.estatusDisplay === 'Cotizaciones Enviadas'" 
                             class="mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-info-circle text-blue-500 dark:text-blue-400"></i>
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>Cotizaciones enviadas:</strong> Ya se han enviado cotizaciones al gerente para su revisión.
                                    Puedes agregar o editar nuevas cotizaciones si es necesario.
                                </p>
                            </div>
                        </div>

                        <div class="mb-4 flex justify-between items-center flex-wrap gap-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-semibold text-slate-700 dark:text-slate-300">Proveedores:</h4>
                                <template x-for="(prov, index) in proveedores" :key="index">
                                    <span class="px-3 py-1 rounded text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200 border border-blue-200 dark:border-blue-800"
                                          x-text="prov"></span>
                                </template>
                            </div>
                            <button
                                @click="agregarProveedor()"
                                class="px-3 py-1 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white text-sm rounded transition shadow-sm">
                                <i class="fas fa-plus mr-1"></i> Agregar Proveedor
                            </button>
                        </div>

                        <div class="overflow-x-auto border rounded-lg border-slate-200 dark:border-slate-700">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                                <thead class="bg-slate-100 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-400 uppercase">CANT.</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-400 uppercase">NO. PARTE</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-400 uppercase">DESCRIPCIÓN</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-400 uppercase">Unidad</th>
                                        <template x-for="(proveedor, provIndex) in proveedores" :key="provIndex">
                                            <th class="px-3 py-2 text-center text-xs font-medium text-slate-600 dark:text-slate-400 uppercase relative min-w-[120px]">
                                                <div class="flex flex-col items-center">
                                                    <span x-text="proveedor" class="text-slate-800 dark:text-slate-200 font-semibold"></span>
                                                    <button
                                                        @click.stop="eliminarProveedor(provIndex)"
                                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs mt-1 transition-colors"
                                                        x-show="proveedores.length > 1"
                                                        title="Eliminar proveedor">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </th>
                                        </template>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-600 dark:text-slate-400 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-transparent">
                                    <template x-for="(producto, prodIndex) in productos" :key="prodIndex">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                            <td class="px-3 py-2 align-top">
                                                <input
                                                    type="number"
                                                    x-model="producto.cantidad"
                                                    min="1"
                                                    class="w-16 px-2 py-1 border rounded text-center text-sm bg-gray-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="1">
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input
                                                    type="text"
                                                    x-model="producto.numeroParte"
                                                    class="w-full px-2 py-1 border rounded text-sm bg-gray-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Número de parte">
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <textarea
                                                    x-model="producto.descripcion"
                                                    rows="2"
                                                    class="w-full px-2 py-1 border rounded text-sm bg-gray-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                                    placeholder="Descripción del producto"></textarea>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input
                                                    type="text"
                                                    x-model="producto.unidad"
                                                    class="w-20 px-2 py-1 border rounded text-center text-sm bg-gray-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="PIEZA">
                                            </td>
                                            <template x-for="(proveedor, provIndex) in proveedores" :key="provIndex">
                                                <td class="px-3 py-2 align-top">
                                                    <div class="space-y-1">
                                                        <div class="relative">
                                                            <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-slate-400 text-sm">$</span>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                :value="producto.precios[proveedor] || ''"
                                                                @input="producto.precios[proveedor] = $event.target.value"
                                                                @blur="if(producto.precios[proveedor]) { const val = parseFloat(producto.precios[proveedor]); producto.precios[proveedor] = isNaN(val) ? '' : val.toFixed(2); }"
                                                                class="w-full pl-6 pr-2 py-1 border rounded text-sm text-right bg-gray-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                                :class="{
                                                                    'border-red-300 bg-red-50 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 font-semibold': obtenerPrecioMinimo(producto) && parseFloat(producto.precios[proveedor] || 0) === obtenerPrecioMinimo(producto) && obtenerPrecioMinimo(producto) > 0
                                                                }"
                                                                placeholder="0.00">
                                                        </div>
                                                    </div>
                                                </td>
                                            </template>
                                            <td class="px-3 py-2 text-center align-top">
                                                <button
                                                    @click="eliminarProducto(prodIndex)"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-500 dark:hover:text-red-400 transition-colors pt-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    
                                    <tr x-show="productos.length === 0">
                                        <td :colspan="proveedores.length + 5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                            <i class="fas fa-inbox text-4xl mb-3 block text-slate-300 dark:text-slate-600"></i>
                                            <p class="text-base">No hay productos agregados. Haz clic en "Agregar Producto" para comenzar.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex justify-between items-center flex-wrap gap-4">
                            <button
                                @click="agregarProducto()"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition shadow-sm">
                                <i class="fas fa-plus mr-2"></i> Agregar Producto
                            </button>
                            
                            <div class="flex gap-2">
                                <button
                                    @click="cerrarModalCotizacion()"
                                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg transition">
                                    Cancelar
                                </button>
                                <button
                                    @click="guardarCotizaciones()"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-lg transition shadow-sm">
                                    <i class="fas fa-save mr-2"></i> Guardar Cotizaciones
                                </button>
                                <button
                                    x-show="tieneCotizacionesGuardadas"
                                    @click="enviarCotizacionesAlGerente()"
                                    class="px-4 py-2 bg-violet-600 hover:bg-violet-700 dark:bg-violet-700 dark:hover:bg-violet-600 text-white rounded-lg transition shadow-sm">
                                    <i class="fas fa-envelope mr-2"></i> Enviar al Gerente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>