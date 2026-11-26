{{-- Modal global "Detalles de Solicitud" (solo lectura).
     Vive en el layout (sidebar) → disponible en cualquier vista, fuera de los tabs.
     Se abre con window.__abrirModalSolicitud(id) desde notificaciones o desde el botón "Ver".
     Estilos: .sdm-* en public/css/ux-pro-max.css (sin build de Tailwind). --}}
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
        document.documentElement.classList.add('sdm-lock');
        this.$nextTick(() => this.$refs.sdmCerrar?.focus());
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
                this.cerrarModal();
            });
    },
    cerrarModal() {
        this.modalAbierto = false;
        this.solicitudSeleccionada = null;
        document.documentElement.classList.remove('sdm-lock');
    },
    formatMoney(value) {
        const amount = Number(value || 0);
        return '$' + amount.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    estatusClase() {
        return ({
            'En revisión': 'sol-status--revision',
            'Cancelada': 'sol-status--cancelada',
            'Rechazada': 'sol-status--rechazada',
            'Aprobada': 'sol-status--aprobada',
            'Cotizaciones Enviadas': 'sol-status--enviadas',
            'Pendiente': 'sol-status--pendiente',
            'Re-cotizar': 'sol-status--recotizar',
            'Listo': 'sol-status--listo'
        })[this.solicitudSeleccionada?.estatusDisplay || ''] || 'sol-status--default';
    },
    iniciales(nombre) {
        return (nombre || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
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
            class="sdm-overlay"
            @keydown.escape.window="modalAbierto && cerrarModal()"
            @click.self="cerrarModal()"
            x-cloak>
        <div class="sdm-dialog" role="dialog" aria-modal="true" aria-labelledby="sdm-titulo">

            {{-- Header fijo --}}
            <header class="sdm-head">
                <span class="sdm-head__icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
                <div class="sdm-head__texto">
                    <h3 id="sdm-titulo" class="sdm-head__titulo">
                        Solicitud
                        <span x-show="solicitudSeleccionada" x-text="'#' + solicitudSeleccionada?.SolicitudID" class="sdm-head__id"></span>
                    </h3>
                    <div x-show="solicitudSeleccionada" class="sdm-head__meta">
                        <span class="erp-chip" :class="estatusClase()" x-text="solicitudSeleccionada?.estatusDisplay || solicitudSeleccionada?.Estatus || 'Sin estatus'"></span>
                        <span><i class="far fa-calendar" aria-hidden="true"></i> <span x-text="solicitudSeleccionada?.fechaCreacion || 'N/A'"></span></span>
                    </div>
                </div>
                <button type="button" x-ref="sdmCerrar" @click="cerrarModal()" class="sdm-close" aria-label="Cerrar detalle">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </header>

            {{-- Cuerpo: único scroll del modal --}}
            <div class="sdm-body">
                <div x-show="cargando" class="sdm-skeleton" aria-live="polite" aria-busy="true">
                    <span class="sdm-sr">Cargando solicitud…</span>
                    <div class="sdm-skeleton__col">
                        <div class="sdm-skeleton__bar sdm-skeleton__bar--lg"></div>
                        <div class="sdm-skeleton__bar"></div>
                        <div class="sdm-skeleton__bar"></div>
                        <div class="sdm-skeleton__bar sdm-skeleton__bar--sm"></div>
                    </div>
                    <div class="sdm-skeleton__col">
                        <div class="sdm-skeleton__bar sdm-skeleton__bar--lg"></div>
                        <div class="sdm-skeleton__bar"></div>
                        <div class="sdm-skeleton__bar sdm-skeleton__bar--sm"></div>
                    </div>
                </div>

                <div x-show="!cargando && solicitudSeleccionada" class="sdm-grid">

                    {{-- ===== Columna lateral: solicitante ===== --}}
                    <aside class="sdm-side">
                        <section class="sdm-card">
                            <h4 class="sdm-label">Solicitante</h4>
                            <div class="sol-persona sdm-persona">
                                <span class="sol-avatar sdm-avatar" aria-hidden="true" x-text="iniciales(solicitudSeleccionada?.empleado?.NombreEmpleado)"></span>
                                <div class="sol-persona__texto">
                                    <span class="sdm-persona__nombre" x-text="solicitudSeleccionada?.empleado?.NombreEmpleado"></span>
                                    <span class="sdm-persona__correo" :title="solicitudSeleccionada?.empleado?.Correo" x-text="solicitudSeleccionada?.empleado?.Correo"></span>
                                </div>
                            </div>
                            <dl class="sdm-dl">
                                <div><dt><i class="fas fa-sitemap" aria-hidden="true"></i> Gerencia</dt><dd x-text="solicitudSeleccionada?.gerencia?.NombreGerencia || 'N/A'"></dd></div>
                                <div><dt><i class="fas fa-hard-hat" aria-hidden="true"></i> Obra</dt><dd x-text="solicitudSeleccionada?.obra?.NombreObra || 'N/A'"></dd></div>
                                <div><dt><i class="fas fa-id-badge" aria-hidden="true"></i> Puesto</dt><dd x-text="solicitudSeleccionada?.puesto?.NombrePuesto || 'N/A'"></dd></div>
                                <div><dt><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Ubicación</dt><dd x-text="solicitudSeleccionada?.ProyectoNombre || solicitudSeleccionada?.Proyecto || 'N/A'"></dd></div>
                            </dl>
                        </section>

                        <template x-if="solicitudSeleccionada?.motivo_cancelacion">
                            <section class="sdm-alert" role="note">
                                <p class="sdm-alert__titulo">
                                    <i class="fas fa-ban" aria-hidden="true"></i>
                                    <span x-text="solicitudSeleccionada?.estatusDisplay === 'Rechazada' ? 'Solicitud rechazada' : 'Solicitud cancelada'"></span>
                                </p>
                                <p class="sdm-alert__texto" x-text="solicitudSeleccionada?.motivo_cancelacion"></p>
                                <p class="sdm-alert__meta">
                                    <span x-text="'Por: ' + (solicitudSeleccionada?.canceladoPorNombre || 'N/A')"></span>
                                    <span x-text="solicitudSeleccionada?.fecha_cancelacion || ''"></span>
                                </p>
                            </section>
                        </template>
                    </aside>

                    {{-- ===== Columna principal ===== --}}
                    <div class="sdm-main">
                        <section class="sdm-card">
                            <h4 class="sdm-label">Motivo</h4>
                            <p class="sdm-motivo" x-text="solicitudSeleccionada?.Motivo || 'N/A'"></p>
                            <div class="sdm-textos">
                                <div>
                                    <h5 class="sdm-sublabel">Descripción</h5>
                                    <p class="sdm-parrafo" x-text="solicitudSeleccionada?.DescripcionMotivo || 'N/A'"></p>
                                </div>
                                <div>
                                    <h5 class="sdm-sublabel">Requerimientos</h5>
                                    <p class="sdm-parrafo" x-text="solicitudSeleccionada?.Requerimientos || 'N/A'"></p>
                                </div>
                            </div>
                        </section>

                        {{-- Flujo de aprobación: timeline vertical (etapa + quién decidió + comentario en un solo lugar) --}}
                        <section class="sdm-card">
                            <h4 class="sdm-label">Flujo de aprobación</h4>
                            <ol class="sdm-flujo">
                                <template x-for="(paso, index) in solicitudSeleccionada?.pasosAprobacion || []" :key="'paso-'+index">
                                    <li class="sdm-paso" :class="'sdm-paso--' + paso.status">
                                        <span class="sdm-paso__dot" aria-hidden="true">
                                            <i class="fas" :class="{ 'fa-check': paso.status === 'approved', 'fa-times': paso.status === 'rejected', 'fa-clock': paso.status === 'pending' }"></i>
                                        </span>
                                        <div class="sdm-paso__cuerpo">
                                            <div class="sdm-paso__linea">
                                                <span class="sdm-paso__etapa" x-text="paso.stageLabel"></span>
                                                <span class="sdm-paso__estado" x-text="paso.statusLabel"></span>
                                            </div>
                                            <div x-show="paso.approverNombre || paso.decidedByNombre || paso.decidedAt" class="sdm-paso__meta">
                                                <span x-show="paso.approverNombre" :title="paso.approverNombre"><i class="fas fa-user" aria-hidden="true"></i> <span x-text="paso.approverNombre"></span></span>
                                                <span x-show="paso.decidedByNombre && paso.decidedByNombre !== paso.approverNombre" :title="paso.decidedByNombre"><i class="fas fa-user-check" aria-hidden="true"></i> <span x-text="'Decidió: ' + paso.decidedByNombre"></span></span>
                                                <span x-show="paso.decidedAt"><i class="far fa-clock" aria-hidden="true"></i> <span x-text="paso.decidedAt"></span></span>
                                            </div>
                                            <p x-show="paso.comment" class="sdm-paso__comentario"
                                                :class="(paso.comment || '').toLowerCase().includes('ganador') && 'sdm-paso__comentario--ganador'"
                                                x-text="paso.comment"></p>
                                        </div>
                                    </li>
                                </template>
                            </ol>
                        </section>

                    {{-- ==================== COTIZACIONES: Propuesta → Producto → Cotizaciones ==================== --}}
                    <section class="sdm-card" x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0" x-data="{
                        propActiva: 0,
                        solicitudCargada: null,
                        cotActiva: {},
                        lista(propuesta) { return propuesta.productos.flatMap(prod => prod.cotizaciones.map(c => ({ cot: c, producto: prod }))); },
                        sel(propuesta) { return this.cotActiva[propuesta.numeroPropuesta] || 0; },
                        moverCot(propuesta, paso) {
                            const total = this.lista(propuesta).length;
                            this.cotActiva[propuesta.numeroPropuesta] = (this.sel(propuesta) + paso + total) % total;
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
                                        return prod;
                                    });

                                p.ganador = null;
                                p.productos.forEach(prod => {
                                    const g = prod.cotizaciones.find(c => c.esGanador);
                                    if (g && !p.ganador) p.ganador = g;
                                });
                                return p;
                            }).sort((a, b) => a.numeroPropuesta - b.numeroPropuesta);
                        }
                    }"
                    x-effect="if (solicitudSeleccionada?.SolicitudID !== solicitudCargada) { solicitudCargada = solicitudSeleccionada?.SolicitudID; propActiva = 0; cotActiva = {}; }">

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

                                <div class="px-3 py-2.5">
                                    {{-- Producto de la cotización activa: cambia solo al pasar a otro producto --}}
                                    <template x-for="(item, k) in lista(propuesta)" :key="'hd-' + item.cot.CotizacionID">
                                        <div x-show="sel(propuesta) === k" class="flex items-center gap-2 min-w-0">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-indigo-600 text-white text-[10px] font-bold tabular-nums shrink-0" x-text="item.producto.numeroProducto"></span>
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate" :title="item.producto.nombre" x-text="item.producto.nombre"></span>
                                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 shrink-0 tabular-nums">
                                                ×<span x-text="item.producto.cantidad"></span> <span x-text="item.producto.unidad"></span>
                                            </span>
                                        </div>
                                    </template>

                                    {{-- Pestañas: todas las cotizaciones de la propuesta, agrupadas por producto --}}
                                    <div x-show="lista(propuesta).length > 1"
                                        class="mt-2 flex flex-wrap items-center gap-1 p-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/70"
                                        role="tablist" aria-label="Cotizaciones"
                                        @keydown.arrow-right.prevent="moverCot(propuesta, 1)"
                                        @keydown.arrow-left.prevent="moverCot(propuesta, -1)">
                                        <template x-for="(item, k) in lista(propuesta)" :key="'tab-' + item.cot.CotizacionID">
                                            <button type="button" role="tab"
                                                @click="cotActiva[propuesta.numeroPropuesta] = k"
                                                :aria-selected="sel(propuesta) === k"
                                                :tabindex="sel(propuesta) === k ? 0 : -1"
                                                :title="item.producto.nombre + ' · ' + (item.cot.Proveedor || '')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[11px] font-semibold transition-all duration-200 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-offset-slate-200 dark:focus-visible:ring-offset-slate-800 focus-visible:ring-violet-500"
                                                :class="[
                                                    k > 0 && lista(propuesta)[k - 1].producto !== item.producto ? 'ml-2' : '',
                                                    sel(propuesta) === k
                                                        ? (item.cot.esGanador
                                                            ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
                                                            : 'bg-rose-600 text-white shadow-sm shadow-rose-600/30')
                                                        : (item.cot.esGanador
                                                            ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/70'
                                                            : 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/70')
                                                ]">
                                                <span x-show="propuesta.productos.length > 1" class="text-[9px] font-bold opacity-70 tabular-nums" x-text="'P' + item.producto.numeroProducto"></span>
                                                <i x-show="item.cot.esGanador" class="fas fa-trophy text-[9px]"
                                                    :class="sel(propuesta) === k ? 'text-amber-300' : 'text-emerald-500'" aria-hidden="true"></i>
                                                <span class="max-w-[10rem] truncate" x-text="item.cot.Proveedor || ('Opción ' + (k + 1))"></span>
                                            </button>
                                        </template>
                                    </div>

                                    {{-- Card de la cotización activa --}}
                                    <template x-for="(item, k) in lista(propuesta)" :key="'det-' + item.cot.CotizacionID">
                                        <div x-show="sel(propuesta) === k" role="tabpanel"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="mt-2 rounded-lg border-l-[3px] ring-1 overflow-hidden"
                                            :class="item.cot.esGanador
                                                ? 'border-l-emerald-500 ring-emerald-200 dark:ring-emerald-800/60 bg-emerald-50/70 dark:bg-emerald-950/25'
                                                : 'border-l-rose-400 ring-rose-200 dark:ring-rose-900/50 bg-rose-50/50 dark:bg-rose-950/15'">

                                            <div class="px-2.5 py-1.5 flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded shrink-0 text-white"
                                                        :class="item.cot.esGanador ? 'bg-emerald-600' : 'bg-rose-500'">
                                                        <i class="fas text-[8px]" :class="item.cot.esGanador ? 'fa-trophy' : 'fa-times'" aria-hidden="true"></i>
                                                        <span x-text="item.cot.esGanador ? 'Ganador' : (item.cot.Estatus || 'No elegida')"></span>
                                                    </span>
                                                    <span class="text-xs font-bold truncate"
                                                        :class="item.cot.esGanador ? 'text-emerald-800 dark:text-emerald-200' : 'text-rose-800 dark:text-rose-200'"
                                                        x-text="item.cot.Proveedor || 'Proveedor'"></span>
                                                </div>
                                                <span class="text-sm font-extrabold tabular-nums shrink-0"
                                                    :class="item.cot.esGanador ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'"
                                                    x-text="formatMoney(totalCot(item.cot))"></span>
                                            </div>

                                            <div class="grid grid-cols-2 divide-x border-t"
                                                :class="item.cot.esGanador
                                                    ? 'divide-emerald-200/70 border-emerald-200/70 dark:divide-emerald-800/50 dark:border-emerald-800/50'
                                                    : 'divide-rose-200/70 border-rose-200/70 dark:divide-rose-900/50 dark:border-rose-900/50'">
                                                <div class="px-2.5 py-1">
                                                    <p class="text-[9px] uppercase tracking-wide text-slate-500 dark:text-slate-400 leading-none">Unitario</p>
                                                    <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 tabular-nums" x-text="formatMoney(item.cot.Precio)"></p>
                                                </div>
                                                <div class="px-2.5 py-1">
                                                    <p class="text-[9px] uppercase tracking-wide text-slate-500 dark:text-slate-400 leading-none">Envío</p>
                                                    <p class="text-[11px] font-semibold tabular-nums"
                                                        :class="parseFloat(item.cot.CostoEnvio || 0) > 0 ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400 dark:text-slate-500'"
                                                        x-text="formatMoney(item.cot.CostoEnvio)"></p>
                                                </div>
                                            </div>

                                            <div x-show="item.cot.NumeroParte || item.cot.Descripcion"
                                                class="px-2.5 py-1 border-t flex items-center gap-2 min-w-0"
                                                :class="item.cot.esGanador
                                                    ? 'border-emerald-200/70 dark:border-emerald-800/50'
                                                    : 'border-rose-200/70 dark:border-rose-900/50'">
                                                <span x-show="item.cot.NumeroParte" class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 shrink-0 truncate max-w-[8rem]" :title="item.cot.NumeroParte" x-text="item.cot.NumeroParte"></span>
                                                <span x-show="item.cot.Descripcion" class="text-[10px] text-slate-500 dark:text-slate-400 truncate" :title="item.cot.Descripcion" x-text="item.cot.Descripcion"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </section>
                    {{-- ==================== /COTIZACIONES ==================== --}}
                    </div>
                </div>
            </div>

            {{-- Footer fijo --}}
            <footer class="sdm-foot">
                <button type="button" @click="cerrarModal()" class="sdm-btn sdm-btn--ghost">Cerrar</button>
                <a x-show="solicitudSeleccionada?.puedeCotizar"
                    :href="'/solicitudes/' + (solicitudSeleccionada?.SolicitudID || '') + '/cotizar'"
                    class="sdm-btn sdm-btn--primario">
                    <i class="fas fa-calculator" aria-hidden="true"></i>
                    <span x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0 ? 'Editar cotizaciones' : 'Cotizar'"></span>
                </a>
            </footer>
        </div>
    </div>
</div>
