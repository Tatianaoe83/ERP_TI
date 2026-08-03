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

                    {{-- ==================== COTIZACIONES: Propuesta → Producto → Cotizaciones ==================== --}}
                    <div x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0" x-data="{
                        propActiva: 0,
                        solicitudCargada: null,
                        seleccion: {},
                        clave(propuesta, producto) { return propuesta.numeroPropuesta + '-' + producto.numeroProducto; },
                        idx(propuesta, producto) {
                            const k = this.clave(propuesta, producto);
                            return this.seleccion[k] === undefined ? producto.indiceGanador : this.seleccion[k];
                        },
                        elegir(propuesta, producto, i) { this.seleccion[this.clave(propuesta, producto)] = i; },
                        mover(propuesta, producto, paso) {
                            const total = producto.cotizaciones.length;
                            this.elegir(propuesta, producto, (this.idx(propuesta, producto) + paso + total) % total);
                        },
                        irA(i) {
                            const total = this.getPropuestas().length;
                            this.propActiva = (i + total) % total;
                        },
                        cantidadDe(cot) { return Math.max(1, parseInt(cot.Cantidad || 1)); },
                        totalCot(cot) {
                            return (parseFloat(cot.Precio || 0) * this.cantidadDe(cot)) + parseFloat(cot.CostoEnvio || 0);
                        },
                        limpiarNombre(texto, numProducto) {
                            let n = (texto || '').replace(/\|+$/, '');
                            n = n.replace(/\s*\d+\s*$/, '').trim();
                            return n !== '' ? n : ('Producto ' + numProducto);
                        },
                        getPropuestas() {
                            const cots = solicitudSeleccionada?.cotizaciones || [];
                            const propuestas = {};

                            cots.forEach(c => {
                                const np = parseInt(c.NumeroPropuesta || 1) || 1;
                                const npr = parseInt(c.NumeroProducto || 1) || 1;

                                if (!propuestas[np]) propuestas[np] = { numeroPropuesta: np, productos: {} };

                                if (!propuestas[np].productos[npr]) {
                                    propuestas[np].productos[npr] = {
                                        numeroProducto: npr,
                                        nombre: this.limpiarNombre(c.NombreEquipo || c.Descripcion, npr),
                                        cantidad: this.cantidadDe(c),
                                        unidad: (c.Unidad || 'PIEZA').trim() || 'PIEZA',
                                        cotizaciones: []
                                    };
                                }

                                propuestas[np].productos[npr].cotizaciones.push({ ...c, esGanador: c.Estatus === 'Seleccionada' });
                            });

                            return Object.values(propuestas).map(p => {
                                p.productos = Object.values(p.productos)
                                    .sort((a, b) => a.numeroProducto - b.numeroProducto)
                                    .map(prod => {
                                        prod.cotizaciones.sort((a, b) => (a.esGanador === b.esGanador) ? 0 : (a.esGanador ? -1 : 1));
                                        prod.indiceGanador = Math.max(0, prod.cotizaciones.findIndex(c => c.esGanador));
                                        return prod;
                                    });

                                p.ganador = null;
                                p.productos.forEach(prod => {
                                    const g = prod.cotizaciones.find(c => c.esGanador);
                                    if (g && !p.ganador) p.ganador = g;
                                });
                                p.totalCotizaciones = p.productos.reduce((s, prod) => s + prod.cotizaciones.length, 0);
                                return p;
                            }).sort((a, b) => a.numeroPropuesta - b.numeroPropuesta);
                        }
                    }"
                    x-effect="if (solicitudSeleccionada?.SolicitudID !== solicitudCargada) { solicitudCargada = solicitudSeleccionada?.SolicitudID; propActiva = 0; seleccion = {}; }">

                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide">Cotizaciones</h4>
                            <span class="text-[11px] font-medium text-indigo-500 dark:text-indigo-400" x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) + ' recibidas'"></span>
                        </div>

                        {{-- Paginador de propuestas --}}
                        <div x-show="getPropuestas().length > 1" class="flex items-center gap-2 mb-2">
                            <button type="button" @click="irA(propActiva - 1)" aria-label="Propuesta anterior"
                                class="w-7 h-7 shrink-0 rounded-lg bg-violet-100 text-violet-700 hover:bg-violet-200 dark:bg-violet-900/40 dark:text-violet-300 dark:hover:bg-violet-900/70 transition-all duration-200 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500">
                                <i class="fas fa-chevron-left text-[10px]" aria-hidden="true"></i>
                            </button>

                            <div class="flex-1 flex items-center gap-1 overflow-x-auto py-0.5" role="tablist" aria-label="Propuestas">
                                <template x-for="(prop, pi) in getPropuestas()" :key="'pg-' + prop.numeroPropuesta">
                                    <button type="button" role="tab" @click="propActiva = pi"
                                        :aria-selected="propActiva === pi" :tabindex="propActiva === pi ? 0 : -1"
                                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-200 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                                        :class="propActiva === pi
                                            ? 'bg-violet-600 text-white shadow-sm shadow-violet-600/30'
                                            : 'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-slate-800 dark:text-violet-300 dark:hover:bg-slate-700'">
                                        <span x-text="'Propuesta ' + prop.numeroPropuesta"></span>
                                        <i class="fas text-[9px]"
                                            :class="prop.ganador
                                                ? (propActiva === pi ? 'fa-trophy text-amber-300' : 'fa-trophy text-emerald-500')
                                                : (propActiva === pi ? 'fa-clock text-violet-200' : 'fa-clock text-amber-500')"
                                            :title="prop.ganador ? 'Ganador elegido' : 'Sin ganador'"></i>
                                    </button>
                                </template>
                            </div>

                            <button type="button" @click="irA(propActiva + 1)" aria-label="Propuesta siguiente"
                                class="w-7 h-7 shrink-0 rounded-lg bg-violet-100 text-violet-700 hover:bg-violet-200 dark:bg-violet-900/40 dark:text-violet-300 dark:hover:bg-violet-900/70 transition-all duration-200 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500">
                                <i class="fas fa-chevron-right text-[10px]" aria-hidden="true"></i>
                            </button>
                        </div>

                        {{-- Propuesta activa --}}
                        <template x-for="(propuesta, pi) in getPropuestas()" :key="'prop-' + propuesta.numeroPropuesta">
                            <div x-show="propActiva === pi || getPropuestas().length === 1" role="tabpanel"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-x-2"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-700/80 shadow-sm overflow-hidden bg-slate-50 dark:bg-slate-900/60">

                                {{-- Productos de la propuesta --}}
                                <div class="divide-y divide-slate-200 dark:divide-slate-700/80">
                                    <template x-for="producto in propuesta.productos" :key="'prod-' + propuesta.numeroPropuesta + '-' + producto.numeroProducto">
                                        <div class="px-3 py-2.5">

                                            {{-- Producto --}}
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-indigo-600 text-white text-[10px] font-bold tabular-nums shrink-0" x-text="producto.numeroProducto"></span>
                                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate" :title="producto.nombre" x-text="producto.nombre"></span>
                                                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 shrink-0 tabular-nums">
                                                    ×<span x-text="producto.cantidad"></span> <span x-text="producto.unidad"></span>
                                                </span>
                                            </div>

                                            {{-- Tabs de proveedores --}}
                                            <div x-show="producto.cotizaciones.length > 1"
                                                class="mt-2 flex flex-wrap gap-1 p-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/70"
                                                role="tablist"
                                                :aria-label="'Cotizaciones de ' + producto.nombre"
                                                @keydown.arrow-right.prevent="mover(propuesta, producto, 1)"
                                                @keydown.arrow-left.prevent="mover(propuesta, producto, -1)">
                                                <template x-for="(cot, i) in producto.cotizaciones" :key="'tab-' + cot.CotizacionID">
                                                    <button type="button" role="tab"
                                                        @click="elegir(propuesta, producto, i)"
                                                        :aria-selected="idx(propuesta, producto) === i"
                                                        :tabindex="idx(propuesta, producto) === i ? 0 : -1"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[11px] font-semibold transition-all duration-200 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-offset-slate-200 dark:focus-visible:ring-offset-slate-800 focus-visible:ring-violet-500"
                                                        :class="idx(propuesta, producto) === i
                                                            ? (cot.esGanador
                                                                ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
                                                                : 'bg-rose-600 text-white shadow-sm shadow-rose-600/30')
                                                            : (cot.esGanador
                                                                ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/70'
                                                                : 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/70')">
                                                        <i x-show="cot.esGanador" class="fas fa-trophy text-[9px]"
                                                            :class="idx(propuesta, producto) === i ? 'text-amber-300' : 'text-emerald-500'" aria-hidden="true"></i>
                                                        <span class="max-w-[10rem] truncate" x-text="cot.Proveedor || ('Opción ' + (i + 1))"></span>
                                                    </button>
                                                </template>
                                            </div>

                                            {{-- Card de la cotización seleccionada --}}
                                            <template x-for="(cot, i) in producto.cotizaciones" :key="'det-' + cot.CotizacionID">
                                                <div x-show="idx(propuesta, producto) === i" role="tabpanel"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 translate-y-1"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    class="mt-2 rounded-lg border-l-[3px] ring-1 overflow-hidden"
                                                    :class="cot.esGanador
                                                        ? 'border-l-emerald-500 ring-emerald-200 dark:ring-emerald-800/60 bg-emerald-50/70 dark:bg-emerald-950/25'
                                                        : 'border-l-rose-400 ring-rose-200 dark:ring-rose-900/50 bg-rose-50/50 dark:bg-rose-950/15'">

                                                    <div class="px-2.5 py-1.5 flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-1.5 min-w-0">
                                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded shrink-0 text-white"
                                                                :class="cot.esGanador ? 'bg-emerald-600' : 'bg-rose-500'">
                                                                <i class="fas text-[8px]" :class="cot.esGanador ? 'fa-trophy' : 'fa-times'" aria-hidden="true"></i>
                                                                <span x-text="cot.esGanador ? 'Ganador' : (cot.Estatus || 'No elegida')"></span>
                                                            </span>
                                                            <span class="text-xs font-bold truncate"
                                                                :class="cot.esGanador ? 'text-emerald-800 dark:text-emerald-200' : 'text-rose-800 dark:text-rose-200'"
                                                                x-text="cot.Proveedor || 'Proveedor'"></span>
                                                        </div>
                                                        <span class="text-sm font-extrabold tabular-nums shrink-0"
                                                            :class="cot.esGanador ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'"
                                                            x-text="formatMoney(totalCot(cot))"></span>
                                                    </div>

                                                    <div class="grid grid-cols-2 divide-x border-t"
                                                        :class="cot.esGanador
                                                            ? 'divide-emerald-200/70 border-emerald-200/70 dark:divide-emerald-800/50 dark:border-emerald-800/50'
                                                            : 'divide-rose-200/70 border-rose-200/70 dark:divide-rose-900/50 dark:border-rose-900/50'">
                                                        <div class="px-2.5 py-1">
                                                            <p class="text-[9px] uppercase tracking-wide text-slate-500 dark:text-slate-400 leading-none">Unitario</p>
                                                            <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 tabular-nums" x-text="formatMoney(cot.Precio)"></p>
                                                        </div>
                                                        <div class="px-2.5 py-1">
                                                            <p class="text-[9px] uppercase tracking-wide text-slate-500 dark:text-slate-400 leading-none">Envío</p>
                                                            <p class="text-[11px] font-semibold tabular-nums"
                                                                :class="parseFloat(cot.CostoEnvio || 0) > 0 ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400 dark:text-slate-500'"
                                                                x-text="formatMoney(cot.CostoEnvio)"></p>
                                                        </div>
                                                    </div>

                                                    <div x-show="cot.NumeroParte || cot.Descripcion"
                                                        class="px-2.5 py-1 border-t flex items-center gap-2 min-w-0"
                                                        :class="cot.esGanador
                                                            ? 'border-emerald-200/70 dark:border-emerald-800/50'
                                                            : 'border-rose-200/70 dark:border-rose-900/50'">
                                                        <span x-show="cot.NumeroParte" class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 shrink-0 truncate max-w-[8rem]" :title="cot.NumeroParte" x-text="cot.NumeroParte"></span>
                                                        <span x-show="cot.Descripcion" class="text-[10px] text-slate-500 dark:text-slate-400 truncate" :title="cot.Descripcion" x-text="cot.Descripcion"></span>
                                                    </div>
                                                </div>
                                            </template>

                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    {{-- ==================== /COTIZACIONES ==================== --}}
                </div>
            </div>
    </div>
</div>
