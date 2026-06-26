{{-- Modal global "Detalles de Solicitud" (solo lectura).
     Vive en el layout (sidebar) → disponible en cualquier vista, fuera de los tabs.
     Se abre con window.__abrirModalSolicitud(id) desde notificaciones o desde el botón "Ver". --}}
<div x-data="{
    modalAbierto: false,
    cargando: false,
    solicitudSeleccionada: null,
    init() {
        window.__abrirModalSolicitud = (id) => this.abrirModal(id);
    },
    abrirModal(id) {
        this.modalAbierto = true;
        this.cargando = true;
        this.solicitudSeleccionada = null;
        fetch('/solicitudes/' + id + '/datos', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => {
                const contentType = r.headers.get('content-type') || '';
                if (!r.ok || !contentType.includes('application/json')) {
                    throw new Error('No se pudo cargar la solicitud.');
                }
                return r.json();
            })
            .then(data => {
                this.solicitudSeleccionada = data;
                this.cargando = false;
            })
            .catch(() => {
                this.cargando = false;
                this.modalAbierto = false;
            });
    },
    cerrarModal() {
        this.modalAbierto = false;
        this.solicitudSeleccionada = null;
    },
    getCotizacionesGanadoras() {
        const cotizaciones = this.solicitudSeleccionada?.cotizaciones || [];
        return cotizaciones
            .filter(cot => cot && cot.Estatus === 'Seleccionada')
            .sort((a, b) => (a.NumeroPropuesta || 0) - (b.NumeroPropuesta || 0));
    },
    formatMoney(value) {
        const amount = Number(value || 0);
        return '$' + amount.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}">
    <div id="modal-solicitud-panel"
            x-show="modalAbierto"
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
                                    <div class="flex-1 min-w-0 space-y-2">
                                        <p class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 leading-snug" x-text="paso.stageLabel"></p>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                            <div x-show="paso.approverNombre" class="min-w-0">
                                                <span class="block font-medium text-slate-500 dark:text-slate-400">Aprobador</span>
                                                <span class="block truncate text-slate-700 dark:text-slate-200" :title="paso.approverNombre" x-text="paso.approverNombre"></span>
                                            </div>
                                            <div x-show="paso.decidedByNombre" class="min-w-0">
                                                <span class="block font-medium text-slate-500 dark:text-slate-400">Decidido por</span>
                                                <span class="block truncate text-slate-700 dark:text-slate-200" :title="paso.decidedByNombre" x-text="paso.decidedByNombre"></span>
                                            </div>
                                            <div x-show="paso.decidedAt" class="min-w-0">
                                                <span class="block font-medium text-slate-500 dark:text-slate-400">Fecha</span>
                                                <span class="block text-slate-700 dark:text-slate-200" x-text="paso.decidedAt"></span>
                                            </div>
                                        </div>
                                        <p x-show="paso.comment"
                                            class="inline-flex items-center gap-1.5 max-w-full rounded-md px-2.5 py-1 text-xs font-medium"
                                            :class="(paso.comment || '').toLowerCase().includes('ganador')
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
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
                        <div x-show="getCotizacionesGanadoras().length > 0"
                            class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/10 p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 flex items-center justify-center">
                                        <i class="fas fa-trophy text-xs"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Ganadores elegidos</p>
                                        <p class="text-xs text-emerald-700/70 dark:text-emerald-300/70">Resumen rápido para no buscarlos entre todas las cotizaciones.</p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 shrink-0">
                                    <span x-text="getCotizacionesGanadoras().length"></span> ganador(es)
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template x-for="cot in getCotizacionesGanadoras()" :key="'winner-'+cot.CotizacionID">
                                    <div class="rounded-md bg-white/80 dark:bg-slate-800/80 border border-emerald-100 dark:border-emerald-800/70 px-3 py-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-bold uppercase text-violet-700 dark:text-violet-300">Producto <span x-text="cot.NumeroPropuesta || '-'"></span></p>
                                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate" :title="cot.Proveedor || 'Proveedor'" x-text="cot.Proveedor || 'Proveedor'"></p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" :title="cot.Descripcion || cot.NombreEquipo || ''" x-text="cot.Descripcion || cot.NombreEquipo || ''"></p>
                                            </div>
                                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 shrink-0" x-text="formatMoney((parseFloat(cot.Precio || 0) + parseFloat(cot.CostoEnvio || 0)))"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <template x-for="(grupo, gi) in getCotizacionesAgrupadas()" :key="'g'+grupo.numeroPropuesta">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="text-xs font-bold text-violet-700 dark:text-violet-300 uppercase">Producto <span x-text="grupo.numeroPropuesta"></span></span>
                                            <span class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate" x-text="grupo.nombreEquipo"></span>
                                        </div>
                                        <span class="text-xs text-slate-500 shrink-0"><span x-text="grupo.cotizaciones.length"></span> cotización(es)</span>
                                    </div>
                                    <template x-if="grupo.cotizaciones.length > 1">
                                        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-2">
                                            <template x-for="(cot, idx) in grupo.cotizaciones" :key="cot.CotizacionID">
                                                <button type="button" @click="selectedIndexes[grupo.numeroPropuesta] = idx"
                                                    class="shrink-0 px-3 py-1.5 rounded text-xs font-semibold transition"
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
</div>
