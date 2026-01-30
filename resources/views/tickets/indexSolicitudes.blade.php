@verbatim
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('solicitudesData', () => ({
            modalAbierto: false,
            solicitudSeleccionada: null,
            cargando: false,
            // Filtros
            filtros: {
                estatus: ''
            },
            abrirModal(id) {
                this.cargando = true;
                this.modalAbierto = true;
                fetch(`/solicitudes/${id}/datos`)
                    .then(response => response.json())
                    .then(data => {
                        this.solicitudSeleccionada = data;
                        this.cargando = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'No se pudo cargar la información de la solicitud', 'error');
                        this.cargando = false;
                        this.modalAbierto = false;
                    });
            },
            cerrarModal() {
                this.modalAbierto = false;
                this.solicitudSeleccionada = null;
            },
            getCotizacionesAgrupadasPorProducto() {
                const cotizaciones = this.solicitudSeleccionada?.cotizaciones || [];
                const byProduct = {};
                cotizaciones.forEach(c => {
                    const num = c.NumeroPropuesta ?? 0;
                    const nombre = (c.NombreEquipo || '').toString().replace(/\|+$/g, '').trim() || 'Producto ' + num;
                    const key = num + '_' + (c.NombreEquipo || '');
                    if (!byProduct[key]) {
                        byProduct[key] = { numeroPropuesta: num, nombreEquipo: nombre, cotizaciones: [] };
                    }
                    byProduct[key].cotizaciones.push(c);
                });
                return Object.values(byProduct).sort((a, b) => a.numeroPropuesta - b.numeroPropuesta);
            }
        }));
    });
</script>
@endverbatim

<div
    x-data="solicitudesData()"
    class="rounded-lg shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
    
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
        <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Solicitudes de Equipos TI</h2>
    </div>

    <div class="p-4 border-b border-slate-200 dark:border-slate-700">
        <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100/50 dark:bg-slate-800/50">
            <i class="fas fa-filter text-slate-400 dark:text-slate-500"></i>
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Filtros</h3>
            <button
                @click="filtros = { estatus: '' }"
                x-show="filtros.estatus"
                class="ml-auto text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline transition-colors duration-200">
                Limpiar filtro
            </button>
        </div>
        <div class="flex gap-3">
            <div class="flex-1 max-w-xs">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Estatus</label>
                <select
                    x-model="filtros.estatus"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos los estatus</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En revisión">En revisión</option>
                    <option value="Cotizaciones Enviadas">Cotizaciones Enviadas</option>
                    <option value="Aprobada">Aprobada</option>
                    <option value="Rechazada">Rechazada</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <livewire:tabla-solicitudes />
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
                                Cotizaciones por producto (<span x-text="solicitudSeleccionada?.cotizaciones?.length || 0"></span>)
                            </h4>
                            <div class="space-y-6">
                                <template x-for="(grupo, gIndex) in getCotizacionesAgrupadasPorProducto()" :key="gIndex">
                                    <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800/50">
                                        <div class="px-4 py-2 bg-violet-100 dark:bg-violet-900/30 border-b border-slate-200 dark:border-slate-600">
                                            <span class="text-xs font-semibold text-violet-700 dark:text-violet-300">Producto <span x-text="grupo.numeroPropuesta"></span></span>
                                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100 ml-2" x-text="grupo.nombreEquipo"></span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 ml-2">(<span x-text="grupo.cotizaciones.length"></span> propuesta(s))</span>
                                        </div>
                                        <div class="p-3 space-y-3">
                                            <template x-for="(cotizacion, pIndex) in grupo.cotizaciones" :key="cotizacion.CotizacionID || pIndex">
                                                <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                                                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200 dark:border-slate-600">
                                                        <span class="text-xs font-semibold text-violet-600 dark:text-violet-400 bg-violet-100 dark:bg-violet-900/40 px-2 py-1 rounded">Propuesta <span x-text="pIndex + 1"></span></span>
                                                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="cotizacion.Proveedor || 'Sin proveedor'"></span>
                                                    </div>
                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                                        <div>
                                                            <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Proveedor</label>
                                                            <p class="text-sm text-slate-900 dark:text-slate-200 font-medium break-words" x-text="cotizacion.Proveedor || '—'"></p>
                                                        </div>
                                                        <div>
                                                            <label class="text-xs font-medium text-slate-500 dark:text-slate-400">NO. PARTE</label>
                                                            <p class="text-sm text-slate-900 dark:text-slate-200 font-medium break-words" x-text="cotizacion.NumeroParte || '—'"></p>
                                                        </div>
                                                        <div>
                                                            <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Precio</label>
                                                            <p class="text-sm text-slate-900 dark:text-slate-200 font-medium" x-text="'$' + (cotizacion.Precio != null ? parseFloat(cotizacion.Precio).toLocaleString('es-MX', {minimumFractionDigits: 2}) : '0.00')"></p>
                                                        </div>
                                                        <div>
                                                            <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Estatus</label>
                                                            <p class="text-sm font-medium"
                                                               :class="{
                                                                   'text-emerald-600 dark:text-emerald-400': cotizacion.Estatus === 'Seleccionada',
                                                                   'text-red-600 dark:text-red-400': cotizacion.Estatus === 'Rechazada',
                                                                   'text-slate-600 dark:text-slate-400': cotizacion.Estatus === 'Pendiente'
                                                               }"
                                                               x-text="cotizacion.Estatus === 'Seleccionada' ? 'Ganador' : (cotizacion.Estatus || '—')"></p>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                                        <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Descripción</label>
                                                        <p class="text-sm text-slate-700 dark:text-slate-300 mt-0.5 min-h-[1.25rem] break-words" x-text="(cotizacion.Descripcion && cotizacion.Descripcion.trim()) ? cotizacion.Descripcion : '—'"></p>
                                                    </div>
                                                </div>
                                            </template>
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
</div>
<!-- Scripts para manejar aprobaciones -->
<script>
    function aprobarSolicitud(solicitudId, nivel) {
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Ingrese un comentario (opcional)',
            input: 'textarea',
            inputPlaceholder: 'Comentario...',
            showCancelButton: true,
            confirmButtonText: 'Aprobar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            preConfirm: (comentario) => {
                return fetch(`/solicitudes/${solicitudId}/aprobar-${nivel}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comentario: comentario || ''
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Error al aprobar');
                        }
                        return data;
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('¡Aprobado!', result.value.message, 'success').then(() => {
                    location.reload();
                });
            }
        }).catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
    }

    function rechazarSolicitud(solicitudId, nivel) {
        Swal.fire({
            title: '¿Rechazar solicitud?',
            text: 'Ingrese el motivo del rechazo',
            input: 'textarea',
            inputPlaceholder: 'Motivo del rechazo...',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe ingresar un motivo';
                }
            },
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            preConfirm: (comentario) => {
                return fetch(`/solicitudes/${solicitudId}/rechazar-${nivel}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comentario: comentario
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Error al rechazar');
                        }
                        return data;
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Rechazada', result.value.message, 'info').then(() => {
                    location.reload();
                });
            }
        }).catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
    }
</script>