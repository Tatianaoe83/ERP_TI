{{-- ═══════════════════════════════════════════════════════
     COMPARATIVA: PRESUPUESTO GENERALES vs FACTURADO
     — Sin cotización ganadora —
═══════════════════════════════════════════════════════ --}}
<style>
/* ── Tooltip glosario ── */
.gls {
    position: relative; display: inline-flex; align-items: center;
    gap: 3px; cursor: help;
    border-bottom: 1.5px dashed #94a3b8; text-decoration: none;
    transition: border-color .15s;
}
.gls:hover { border-color: #6366f1; }
.gls-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 14px; height: 14px; border-radius: 50%;
    background: #e0e7ff; color: #4f46e5;
    font-size: 8px; font-weight: 900; flex-shrink: 0;
    transition: background .15s;
}
.dark .gls-icon { background: #312e81; color: #a5b4fc; }
.gls:hover .gls-icon { background: #6366f1; color: #fff; }
.gls-tip {
    position: absolute; bottom: calc(100% + 8px); left: 50%;
    transform: translateX(-50%) translateY(4px);
    background: #1e293b; color: #e2e8f0; border-radius: 10px;
    padding: 9px 13px; font-size: 12px; font-weight: 500; line-height: 1.5;
    white-space: normal; max-width: 260px; z-index: 50;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    pointer-events: none; opacity: 0;
    transition: opacity .15s, transform .15s;
}
.gls-tip::after {
    content: ''; position: absolute; top: 100%; left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent; border-top-color: #1e293b;
}
.gls:hover .gls-tip { opacity: 1; transform: translateX(-50%) translateY(0); }

/* ── Insights ── */
.insight-bar {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 16px; border-radius: 10px;
    margin: 0 16px 0 16px; font-size: 12px; font-weight: 600; line-height: 1.5;
}
.insight-bar.ok     { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.insight-bar.warn   { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
.insight-bar.alert  { background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; }
.insight-bar.saving { background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; }
.dark .insight-bar.ok     { background:#052e16; border-color:#14532d; color:#86efac; }
.dark .insight-bar.warn   { background:#1c1003; border-color:#713f12; color:#fde68a; }
.dark .insight-bar.alert  { background:#1c0008; border-color:#9f1239; color:#fda4af; }
.dark .insight-bar.saving { background:#042f2e; border-color:#0f766e; color:#5eead4; }

#cmpTableHead { display: table-header-group; }
@media (max-width: 767px) { #cmpTableHead { display: none; } }
</style>

<div id="comparativaRoot" class="space-y-5">

    {{-- ════════════════════════════════════════
         BARRA DE REFERENCIA RÁPIDA
    ════════════════════════════════════════ --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-sm px-5 py-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

            <span class="text-[10px] font-extrabold text-slate-300 dark:text-slate-600 uppercase tracking-widest flex-shrink-0">¿Qué significa cada dato?</span>

            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-indigo-500 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Presupuesto Generales</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip">El monto que se tenía asignado para este insumo. Es el <strong>techo de gasto</strong> esperado para el período.</span>
            </span>

            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Facturado</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip">Lo que el proveedor ya cobró. Si supera el presupuesto, se gastó más de lo planeado.</span>
            </span>

            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-fuchsia-500 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Desviación %</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip"><strong style="color:#f43f5e">Positivo (+)</strong> = se gastó más de lo presupuestado.<br><strong style="color:#10b981">Negativo (−)</strong> = se gastó menos — ¡ahorro!</span>
            </span>

            <span class="hidden lg:block w-px h-5 bg-slate-200 dark:bg-slate-700 flex-shrink-0"></span>

            {{-- Semáforo --}}
            <span class="flex items-center gap-1.5 flex-shrink-0 text-[11px] font-semibold text-slate-500">Estado:</span>
            <span class="gls flex-shrink-0">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                    <i class="fas fa-piggy-bank text-[9px]"></i> Ahorro
                </span>
                <span class="gls-tip" style="left:0;transform:none;">Más del 5% por debajo del presupuesto — gasto eficiente, ¡excelente!</span>
            </span>
            <span class="gls flex-shrink-0">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                    <i class="fas fa-exclamation-triangle text-[9px]"></i> Desviacion
                </span>
                <span class="gls-tip" style="left:0;transform:none;">Excedente mayor al 5% sobre el presupuesto — se gastó más de lo presupuestado.</span>
            </span>

        </div>
    </div>

    {{-- ════════════════════════════════════════
         FILTROS
    ════════════════════════════════════════ --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-sm">
        <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <i class="fas fa-sliders-h text-indigo-400 text-sm"></i>
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Filtros</span>
            <span id="cmpFiltrosActivos" class="hidden ml-auto text-[11px] font-semibold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-full"></span>
        </div>
        <form id="formComparativaFilter" class="p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-start">

                {{-- Gerencia --}}
                <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-building mr-1 text-indigo-400"></i>Gerencia
                    </label>
                    <div class="relative">
                        <select id="cmpGerencia"
                            class="w-full h-10 pl-4 pr-10 appearance-none rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                            <option value="">Todas las gerencias</option>
                            @foreach($gerencia as $id => $nombre)
                                @if($id !== '')
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">Deja en blanco para ver todas las áreas.</p>
                </div>

                {{-- Mes --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-calendar-alt mr-1 text-sky-400"></i>Mes
                    </label>
                    <div class="relative">
                        <select id="cmpMes"
                            class="w-full h-10 pl-4 pr-10 appearance-none rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                            <option value="">Todos los meses</option>
                            <option value="1">Enero</option><option value="2">Febrero</option>
                            <option value="3">Marzo</option><option value="4">Abril</option>
                            <option value="5">Mayo</option><option value="6">Junio</option>
                            <option value="7">Julio</option><option value="8">Agosto</option>
                            <option value="9">Septiembre</option><option value="10">Octubre</option>
                            <option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                {{-- Año --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-calendar mr-1 text-violet-400"></i>Año
                    </label>
                    <div class="relative">
                        <select id="cmpAnio"
                            class="w-full h-10 pl-4 pr-10 appearance-none rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                            <option value="">Todos los años</option>
                            @for($y = date('Y'); $y >= 2022; $y--)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">Por defecto muestra el año actual.</p>
                </div>

                {{-- Insumo --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-tag mr-1 text-emerald-400"></i>Insumo
                    </label>
                    <input id="cmpInsumo" type="text" placeholder="Buscar insumo…"
                        class="w-full h-10 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all placeholder:text-slate-300">
                </div>

                {{-- Botones --}}
                <div class="flex gap-2 pt-5">
                    <button type="submit"
                        class="flex-1 h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md shadow-indigo-500/25 transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-1.5">
                        <i class="fas fa-search text-xs"></i> Filtrar
                    </button>
                    <button type="button" id="cmpBtnReset" title="Quitar todos los filtros"
                        class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-all flex items-center justify-center">
                        <i class="fas fa-undo text-xs"></i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- ════════════════════════════════════════  LOADING ════════════════════════════════════════ --}}
    <div id="cmpLoading" class="hidden py-16 text-center">
        <div class="inline-flex flex-col items-center gap-3">
            <i class="fas fa-circle-notch fa-spin text-indigo-500 text-3xl"></i>
            <p class="text-slate-500 text-sm font-semibold">Consultando datos…</p>
            <p class="text-slate-300 text-xs">Esto tarda unos segundos</p>
        </div>
    </div>

    {{-- ════════════════════════════════════════  EMPTY ════════════════════════════════════════ --}}
    <div id="cmpEmpty" class="hidden py-14 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-800 mb-3">
            <i class="fas fa-search text-xl text-slate-300"></i>
        </div>
        <p class="text-slate-500 text-sm font-semibold">No hay datos para los filtros seleccionados.</p>
        <p class="text-slate-300 text-xs mt-1 mb-4">Prueba con un rango de fechas más amplio o quita algún filtro.</p>
        <button onclick="document.getElementById('cmpBtnReset').click()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-all">
            <i class="fas fa-undo"></i> Quitar filtros
        </button>
    </div>

    {{-- ════════════════════════════════════════
         RESULTADOS
    ════════════════════════════════════════ --}}
    <div id="cmpResults" class="hidden space-y-5">

        {{-- Insight global --}}
        <div id="cmpInsightGlobal"></div>

        {{-- KPIs --}}
        <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 ml-1">
                <i class="fas fa-tachometer-alt mr-1"></i>Resumen del período
            </p>
            <div id="cmpKpis" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"></div>
        </div>

        {{-- ── GRÁFICA 1: PRESUPUESTO vs FACTURADO ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden">
            <div class="px-5 pt-4 pb-0 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">
                        <span class="gls">Presupuesto Generales
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Monto asignado para el período. Es el techo de gasto esperado por insumo.</span>
                        </span>
                        <span class="mx-1 text-slate-300">vs</span>
                        <span class="gls">Facturado
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Lo que el proveedor cobró efectivamente. Barras por debajo del presupuesto indican ahorro.</span>
                        </span>
                    </h2>
                    <p id="cmpChartSubtitle" class="text-[11px] text-slate-400 mt-0.5">—</p>
                </div>
                <div class="flex flex-wrap items-center gap-4 pb-1 text-[11px] font-bold text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-8 h-3 rounded" style="background:#6366f1;opacity:.85"></span>
                        Presupuesto
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-8 h-3 rounded" style="background:#10b981"></span>
                        Facturado
                    </span>
                </div>
            </div>
            <div id="cmpInsightMontos" class="mt-3 mb-1"></div>
            <div id="cmpChartMontos" class="w-full" style="min-height:360px;"></div>
        </div>

        {{-- ── GRÁFICA 2: DESVIACIÓN % ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden">
            <div class="px-5 pt-4 pb-0 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">
                        <span class="gls">Desviación respecto al Presupuesto
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Muestra qué tanto se alejó el gasto real del presupuesto asignado. Positivo = se gastó más. Negativo = se ahorró.</span>
                        </span>
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Barras hacia la derecha (<span style="color:#f43f5e">rojas</span>) = excedente &nbsp;·&nbsp;
                        Barras hacia la izquierda (<span style="color:#10b981">verdes</span>) = ahorro ✓
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-4 pb-1 text-[11px] font-bold text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-8 h-3 rounded" style="background:#f43f5e;opacity:.85"></span>
                        Excedente
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-8 h-3 rounded" style="background:#10b981;opacity:.85"></span>
                        Ahorro
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-8 h-0 border-t-2 border-dashed" style="border-color:#94a3b8"></span>
                        Sin desviación
                    </span>
                </div>
            </div>
            <div id="cmpInsightDesv" class="mt-3 mb-1"></div>
            <div id="cmpChartDesv" class="w-full" style="min-height:260px;"></div>
        </div>

        {{-- ── TABLA DETALLE ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-table text-slate-400 text-xs"></i>
                    <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Detalle por Insumo</span>
                </div>
                <span id="cmpTotalRows" class="text-[11px] font-semibold text-slate-400"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead id="cmpTableHead" class="bg-slate-50 dark:bg-slate-950 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Insumo</th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Gerencia</th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-indigo-400 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Presupuesto
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Monto asignado al insumo para el período seleccionado.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-emerald-500 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Facturado
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Monto real cobrado por el proveedor.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Diferencia
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Cuánto se gastó de más (positivo) o de menos — ahorro (negativo) — respecto al presupuesto.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Desviación %
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Porcentaje de diferencia entre el presupuesto y lo facturado. + = excedente. − = ahorro.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="cmpTableBody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                    <tfoot id="cmpTableFoot" class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 font-bold text-sm"></tfoot>
                </table>
            </div>
        </div>

    </div>{{-- /cmpResults --}}
</div>

@push('facturas_scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
(function () {
    'use strict';

    /* ══ Constantes ══ */
    const FMT        = new Intl.NumberFormat('es-MX', { style:'currency', currency:'MXN', maximumFractionDigits:2 });
    const MESES_FULL = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const isDark     = () => document.documentElement.classList.contains('dark');

    const GERENCIA_MAP = {
        @foreach($gerencia as $id => $nombre)
            @if($id !== ''){{ $id }}: @json($nombre),@endif
        @endforeach
    };

    /* ══ Helpers ══ */
    const f    = v => (v != null && v !== 0 && !isNaN(v)) ? FMT.format(v) : '—';
    const pct  = v => v != null ? `${v > 0 ? '+' : ''}${parseFloat(v).toFixed(1)}%` : '—';
    const gNom = (id, nombre) => nombre || GERENCIA_MAP[id] || (id ? 'Gerencia #'+id : 'Sin gerencia asignada');
    const gBadge = (id, nombre) => {
        const n = nombre || GERENCIA_MAP[id] || (id ? 'Gerencia #'+id : null);
        if (n) return `<span class="text-xs text-slate-500 dark:text-slate-400">${n}</span>`;
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700"><i class="fas fa-hourglass-half text-[9px]"></i> Sin asignar</span>`;
    };

    /* ── Colores por nivel de desviación ──
       Solo positivos (excedente) son malos.
       Negativo = ahorro = verde siempre.      */
    function desvColor(v) {
        if (v == null) return '#94a3b8';
        if (v > 5)    return '#f43f5e';   /* excedente → rojo   */
        if (v < -5)   return '#0d9488';   /* ahorro    → teal   */
        return '#94a3b8';                 /* neutral   → gris   */
    }

    function desvClass(v) {
        if (v == null) return 'text-slate-400';
        if (v > 5)    return 'text-rose-600 dark:text-rose-400 font-bold';
        if (v < -5)   return 'text-teal-600 dark:text-teal-400 font-semibold';
        return 'text-slate-400';
    }

    /* Badge de estado — Solo Ahorro y Desviacion */
    function estadoBadge(desvPct) {
        const v = desvPct ?? 0;
        if (v < -5) return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800"><i class="fas fa-piggy-bank"></i> Ahorro</span>`;
        if (v > 5) return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800"><i class="fas fa-exclamation-triangle"></i> Desviacion</span>`;
        return '';
    }

    /* ══ INSIGHTS ══ */
    function renderInsights(insumos) {
        const totPresu = insumos.reduce((s,i) => s + (i.metricas.presupuesto_generales || 0), 0);
        const totFact  = insumos.reduce((s,i) => s + (i.metricas.total_facturado || 0), 0);

        /* Solo cuentan como problema los que SE PASAN del presupuesto */
        const desviaciones = insumos.filter(i => (i.metricas.desviacion_pct ?? 0) > 5);
        const ahorros  = insumos.filter(i => (i.metricas.desviacion_pct ?? 0) < -5);
        const sinPresu = insumos.filter(i => !i.metricas.presupuesto_generales);

        const difPct = totPresu > 0 ? ((totFact - totPresu) / totPresu) * 100 : null;

        /* ── Insight global ── */
        let gType, gIcon, gMsg;
        if (desviaciones.length > 0) {
            gType = 'alert'; gIcon = 'fa-exclamation-triangle';
            const ahorroExtra = ahorros.length > 0
                ? ` · <span style="color:#0d9488">✓ ${ahorros.length} con ahorro: ${ahorros.map(i=>i.nombre).join(', ')}</span>`
                : '';
            gMsg = `<strong>${desviaciones.length} insumo${desviaciones.length!==1?'s':''} con excedente (&gt;5%):</strong> ${desviaciones.map(i=>i.nombre).join(', ')}. Solicita justificación al área responsable.${ahorroExtra}`;
        } else if (ahorros.length > 0) {
            gType = 'saving'; gIcon = 'fa-piggy-bank';
            gMsg = `<strong>¡Buen trabajo! ${ahorros.length} insumo${ahorros.length!==1?'s tienen':' tiene'} ahorro</strong> (más del 5% por debajo del presupuesto): ${ahorros.map(i=>i.nombre).join(', ')}. Sin excedentes detectados.`;
        } else {
            gType = 'ok'; gIcon = 'fa-check-circle';
            gMsg = difPct != null
                ? `Todo en orden. La desviación global es <strong>${pct(difPct)}</strong> — ${difPct > 0 ? 'ligero excedente' : 'ahorro'} respecto al presupuesto asignado.`
                : 'No se detectaron excedentes. Todos los insumos están dentro del presupuesto.';
        }
        document.getElementById('cmpInsightGlobal').innerHTML =
            `<div class="insight-bar ${gType}"><i class="fas ${gIcon} mt-0.5 flex-shrink-0"></i><span>${gMsg}</span></div>`;

        /* ── Insight gráfica montos ── */
        const maxDesv = insumos.reduce((m,i) => Math.max(m, i.metricas.desviacion_pct ?? 0), 0);
        let mType, mMsg;
        if (maxDesv > 5) {
            mType = 'alert';
            mMsg  = `Las barras <strong>rojas</strong> superan al presupuesto (azul). Revisa los insumos marcados como Desviacion — se gastó más de lo autorizado.`;
        } else {
            mType = 'ok';
            mMsg  = ahorros.length > 0
                ? `¡Excelente control de gastos! ${ahorros.length} insumo${ahorros.length!==1?'s están':' está'} por debajo del presupuesto — se generó ahorro en el período.`
                : `Los montos facturados están dentro o por debajo del presupuesto — buen control de gastos en el período seleccionado.`;
        }
        document.getElementById('cmpInsightMontos').innerHTML =
            `<div class="insight-bar ${mType}"><i class="fas fa-chart-bar mt-0.5 flex-shrink-0"></i><span>${mMsg}</span></div>`;

        /* ── Insight gráfica desviación ── */
        let dType, dMsg;
        if (desviaciones.length > 0) {
            dType = 'alert';
            dMsg  = `Las barras que más sobresalen a la derecha son los insumos con mayor excedente. <strong>Prioriza los marcados en rojo</strong> (&gt;5%). Las barras verdes a la izquierda representan ahorro.`;
        } else if (sinPresu.length > 0) {
            dType = 'warn';
            const nombresDP = sinPresu.map(i => i.nombre).join(', ');
            dMsg  = `<strong>${sinPresu.length} insumo${sinPresu.length!==1?'s sin presupuesto en Cortes':' sin presupuesto en Cortes'}:</strong> ${nombresDP}. Para calcular la desviación, ve al módulo <strong>Cortes</strong> y asigna el presupuesto anual.`;
        } else if (ahorros.length > 0) {
            dType = 'saving';
            dMsg  = `<strong>¡Las barras verdes indican ahorro!</strong> ${ahorros.length} insumo${ahorros.length!==1?'s están':' está'} por debajo del presupuesto. Sin excedentes detectados — excelente gestión.`;
        } else {
            dType = 'ok';
            dMsg  = `Las desviaciones están dentro de rangos controlados. Sin excedentes significativos — buen manejo del presupuesto.`;
        }
        document.getElementById('cmpInsightDesv').innerHTML =
            `<div class="insight-bar ${dType}"><i class="fas fa-chart-bar mt-0.5 flex-shrink-0"></i><span>${dMsg}</span></div>`;
    }

    /* ══ GRÁFICAS ══ */
    let chartMontos = null;
    let chartDesv   = null;

    function applyHCAnimation(H) {
        ['line','spline'].forEach(t => {
            if (H.seriesTypes[t]) {
                H.seriesTypes[t].prototype.animate = function(init) {
                    if (!init && this.graph) {
                        try {
                            const len = this.graph.element.getTotalLength();
                            this.graph.attr({ 'stroke-dasharray': len, 'stroke-dashoffset': len, opacity: 1 });
                            this.graph.animate({ 'stroke-dashoffset': 0 }, H.animObject(this.options.animation));
                        } catch(e) {}
                    }
                };
            }
        });
    }

    function renderChart(insumos) {
        if (chartMontos) { chartMontos.destroy(); chartMontos = null; }
        if (chartDesv)   { chartDesv.destroy();   chartDesv   = null; }
        applyHCAnimation(Highcharts);

        const dark    = isDark();
        const axisClr = dark ? '#334155' : '#e2e8f0';
        const lblClr  = dark ? '#94a3b8' : '#64748b';
        const gridClr = dark ? '#1e293b' : '#f8fafc';
        const ttBg    = dark ? '#1e293b' : '#ffffff';
        const ttBdr   = dark ? '#334155' : '#e2e8f0';
        const ttClr   = dark ? '#e2e8f0' : '#1e293b';

        const cats      = insumos.map(i => i.nombre.length > 22 ? i.nombre.substring(0,22)+'…' : i.nombre);
        const presuData = [];
        const factData  = [];
        const desvData  = [];

        insumos.forEach(ins => {
            const m   = ins.metricas;
            const dp  = m.desviacion_pct;
            /* Color barra facturado: teal si ahorro, rojo si excedente */
            const fc  = dp == null ? '#10b981'
                      : dp < -5   ? '#0d9488'
                      : dp > 5    ? '#f43f5e'
                      : '#10b981';
            const dclr = dp == null ? '#94a3b8'
                       : dp < 0    ? '#0d9488'
                       : dp > 5    ? '#f43f5e'
                       : '#94a3b8';

            presuData.push({ y: m.presupuesto_generales ?? 0, name: ins.nombre });
            factData.push( { y: m.total_facturado ?? 0,       name: ins.nombre, color: fc });
            desvData.push( { y: dp,                           name: ins.nombre, color: dclr });
        });

        /* Tooltip montos */
        function tooltipMontos() {
            return {
                useHTML: true, backgroundColor: ttBg, borderColor: ttBdr,
                borderRadius: 12, borderWidth: 1, shadow: true,
                style: { color: ttClr, fontSize: '12px', padding: '10px' },
                formatter: function () {
                    const idx = this.point.index;
                    const ins = insumos[idx]; if (!ins) return '';
                    const m   = ins.metricas;
                    const dp  = m.desviacion_pct;
                    const dc  = desvColor(dp);
                    const dif = (m.total_facturado ?? 0) - (m.presupuesto_generales ?? 0);
                    const interpret = dp == null ? ''
                        : dp < -5  ? `<div style="margin-top:8px;font-size:11px;color:#0d9488;font-weight:700">✓ Ahorro — se gastó menos de lo presupuestado</div>`
                        : dp > 5   ? `<div style="margin-top:8px;font-size:11px;color:#f43f5e;font-weight:700">⚠ Excedente — se gastó más de lo presupuestado</div>`
                        : '';
                    return `<div style="min-width:240px">
                        <div style="font-weight:800;font-size:13px;margin-bottom:4px;white-space:normal">${ins.nombre}</div>
                        <div style="color:#94a3b8;font-size:10px;margin-bottom:10px">${gNom(ins.gerencia_id, ins.gerencia)}</div>
                        <div style="display:flex;justify-content:space-between;gap:20px;margin-bottom:4px">
                            <span style="color:#818cf8;font-weight:600">Presupuesto asignado</span>
                            <span style="font-family:monospace;font-weight:700">${f(m.presupuesto_generales)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:20px;margin-bottom:4px">
                            <span style="color:${dc};font-weight:600">Lo que se facturó</span>
                            <span style="font-family:monospace;font-weight:700;color:${dc}">${f(m.total_facturado)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:20px;margin-top:6px;padding-top:6px;border-top:1px solid #334155">
                            <span style="color:#94a3b8;font-weight:600">Diferencia</span>
                            <span style="font-family:monospace;font-weight:700;color:${dc}">${dif >= 0 ? '+' : ''}${f(dif)}</span>
                        </div>
                        ${interpret}
                    </div>`;
                }
            };
        }

        /* Tooltip desviación */
        function tooltipDesv() {
            return {
                useHTML: true, backgroundColor: ttBg, borderColor: ttBdr,
                borderRadius: 12, borderWidth: 1, shadow: true,
                style: { color: ttClr, fontSize: '12px', padding: '10px' },
                formatter: function () {
                    const idx = this.point.index;
                    const ins = insumos[idx]; if (!ins) return '';
                    const m   = ins.metricas;
                    const dp  = m.desviacion_pct;
                    const dc  = desvColor(dp);
                    const accion = dp == null ? 'Sin presupuesto asignado'
                        : dp < -5  ? `✓ Ahorro de ${f(Math.abs(m.desviacion_monto ?? 0))} — ¡excelente gestión!`
                        : dp > 5   ? '⚠ Excedente — revisar con el área responsable'
                        : '';
                    return `<div style="min-width:220px">
                        <div style="font-weight:800;font-size:13px;margin-bottom:4px;white-space:normal">${ins.nombre}</div>
                        <div style="color:#94a3b8;font-size:10px;margin-bottom:10px">${gNom(ins.gerencia_id, ins.gerencia)}</div>
                        <div style="display:flex;justify-content:space-between;gap:20px;margin-bottom:8px">
                            <span style="font-weight:700">Desviación</span>
                            <span style="font-family:monospace;font-weight:800;font-size:16px;color:${dc}">${pct(dp)}</span>
                        </div>
                        <div style="font-size:11px;color:${dc};font-weight:600">${accion}</div>
                    </div>`;
                }
            };
        }

        const xAxisBase = {
            categories: cats, lineColor: axisClr, tickColor: axisClr,
            labels: {
                style: { color: lblClr, fontSize: '11px', fontWeight: '600' },
                rotation: cats.length > 6 ? -35 : 0
            }
        };
        const yFmt = function () {
            const v = Math.abs(this.value);
            if (v >= 1000000) return '$'+(this.value/1000000).toFixed(1)+'M';
            if (v >= 1000)    return '$'+(this.value/1000).toFixed(0)+'k';
            return '$'+this.value;
        };

        chartMontos = Highcharts.chart('cmpChartMontos', {
            chart: { type:'column', backgroundColor:'transparent', style:{fontFamily:'inherit'}, height:360, marginTop:10, animation:{duration:700} },
            title: { text:null }, credits: { enabled:false },
            legend: { enabled:true, align:'center', verticalAlign:'bottom', itemStyle:{ color:lblClr, fontWeight:'600', fontSize:'11px' } },
            tooltip: tooltipMontos(),
            xAxis: xAxisBase,
            yAxis: { title:{ text:null }, gridLineColor:gridClr, labels:{ style:{color:lblClr, fontSize:'11px'}, formatter: yFmt } },
            plotOptions: { column: { borderRadius:5, groupPadding:.15, pointPadding:.05, animation:{duration:700} } },
            series: [
                { name:'Presupuesto Generales', data:presuData, color:'#6366f1', opacity:.85 },
                { name:'Facturado',             data:factData,  colorByPoint:true, animation:{duration:700,defer:200} }
            ],
            responsive: { rules:[{ condition:{maxWidth:520}, chartOptions:{ xAxis:{ labels:{ rotation:-55, style:{fontSize:'10px'} } }, chart:{height:280} } }] }
        });

        const chartHeight = Math.max(260, insumos.length * 52 + 80);
        chartDesv = Highcharts.chart('cmpChartDesv', {
            chart: { type:'bar', backgroundColor:'transparent', style:{fontFamily:'inherit'}, height:chartHeight, marginTop:10, animation:{duration:800} },
            title: { text:null }, credits: { enabled:false },
            legend: { enabled:false },
            tooltip: tooltipDesv(),
            xAxis: { categories:cats, lineColor:axisClr, tickColor:axisClr, labels:{ style:{color:lblClr, fontSize:'11px', fontWeight:'600'} } },
            yAxis: {
                title:{ text:null }, gridLineColor:gridClr,
                labels:{ format:'{value:.1f}%', style:{ color:lblClr, fontSize:'11px' } },
                plotLines:[{ value:0, color: dark?'#64748b':'#94a3b8', width:2, zIndex:5, dashStyle:'Dash',
                    label:{ text:'Sin desviación', style:{ color:lblClr, fontSize:'10px', fontWeight:'700' } }
                }]
            },
            plotOptions: { bar: {
                borderRadius:4, groupPadding:.1, pointPadding:.05, animation:{duration:800},
                dataLabels: { enabled:true, useHTML:true,
                    formatter: function() {
                        if (this.y == null) return '';
                        return `<span style="font-family:monospace;font-size:10px;font-weight:700;color:${this.point.color}">${this.y > 0 ? '+' : ''}${this.y.toFixed(1)}%</span>`;
                    }, inside:false
                }
            }},
            series: [{ name:'Desviación vs Presupuesto', data:desvData, colorByPoint:true }],
            responsive: { rules:[{ condition:{maxWidth:520}, chartOptions:{ chart:{ height: Math.max(200, insumos.length*42+60) } } }] }
        });
    }

    /* ══ KPI CARDS ══ */
    function renderKpis(insumos) {
        const totPresu     = insumos.reduce((s,i) => s + (i.metricas.presupuesto_generales || 0), 0);
        const totFact      = insumos.reduce((s,i) => s + (i.metricas.total_facturado || 0), 0);
        const dif          = totFact - totPresu;
        const difPct       = totPresu > 0 ? (dif / totPresu) * 100 : null;
        /* Solo cuenta excedentes reales (positivo > 10%) */
        const conExcedente = insumos.filter(i => (i.metricas.desviacion_pct ?? 0) > 10).length;
        const conAhorro    = insumos.filter(i => (i.metricas.desviacion_pct ?? 0) < -5).length;
        const sinPresu     = insumos.filter(i => !i.metricas.presupuesto_generales).length;

        function accion(nivel, texto) {
            const c = {
                ok:   'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
                save: 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300',
                warn: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                bad:  'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300',
            };
            return `<span class="inline-block mt-2 px-2 py-0.5 rounded-full text-[10px] font-bold ${c[nivel]}">${texto}</span>`;
        }

        const cards = [
            {
                label: 'Presupuesto Total',
                value: f(totPresu) === '—' ? 'Sin datos' : f(totPresu),
                sub: `${insumos.length} insumo${insumos.length!==1?'s':''} en el período` + (sinPresu > 0 ? ` · ${sinPresu} sin presupuesto` : ''),
                action: sinPresu > 0 ? accion('warn','Algunos insumos sin presupuesto') : accion('ok','Presupuesto base del período'),
                icon: 'fa-wallet', theme: 'indigo'
            },
            {
                label: 'Total Facturado',
                value: f(totFact),
                sub: dif === 0 ? 'Igual al presupuesto' : dif > 0
                    ? `<span class="text-rose-500 font-semibold">+${f(dif)} sobre presupuesto</span>`
                    : `<span class="text-teal-600 font-semibold">${f(Math.abs(dif))} de ahorro</span>`,
                action: dif > totPresu * 0.15 ? accion('bad','Revisar gasto con el área')
                    : dif > totPresu * 0.05   ? accion('warn','Monitorear tendencia')
                    : dif < -(totPresu * 0.05) ? accion('save','¡Ahorro generado!')
                    : accion('ok','Sin acción requerida'),
                icon: 'fa-receipt',
                theme: dif > totPresu * 0.05 ? (dif > totPresu * 0.15 ? 'rose' : 'amber') : 'emerald'
            },
            {
                label: 'Diferencia',
                value: dif === 0 ? 'Equilibrado' : (dif > 0 ? '+' : '') + f(dif),
                sub: difPct != null
                    ? (Math.abs(difPct) < 1 ? 'Sin desviación significativa'
                      : difPct < 0 ? `Ahorro de ${pct(Math.abs(difPct))} respecto al presupuesto`
                      : `Excedente de ${pct(difPct)} respecto al presupuesto`)
                    : 'Sin presupuesto para calcular',
                action: difPct == null    ? accion('warn','Sin datos suficientes')
                    : difPct < -5         ? accion('save','Ahorro — ¡buen desempeño!')
                    : Math.abs(difPct) < 5 ? accion('ok','Dentro del rango esperado')
                    : difPct > 0          ? accion('bad','Excedente a justificar')
                    : accion('ok','Sin acción requerida'),
                icon: dif > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down',
                theme: difPct == null ? 'slate' : (difPct > 15 ? 'rose' : difPct > 5 ? 'amber' : 'emerald')
            },
            {
                label: 'Desviación Global',
                value: difPct != null ? pct(difPct) : '—',
                sub: difPct == null ? 'No hay presupuesto registrado'
                    : Math.abs(difPct) < 5 ? 'Rango aceptable (menos del 5%)'
                    : difPct > 0 ? `Se gastó ${pct(difPct)} más de lo planeado`
                    : `Se ahorró ${pct(Math.abs(difPct))} respecto al presupuesto`,
                action: difPct == null       ? accion('warn','Registrar presupuesto')
                    : difPct < -5            ? accion('save','¡Ahorro!')
                    : Math.abs(difPct) > 15  ? accion('bad','Revisión urgente')
                    : Math.abs(difPct) > 5   ? accion('warn','Monitorear')
                    : accion('ok','Sin acción requerida'),
                icon: 'fa-chart-pie',
                theme: difPct == null ? 'slate' : Math.abs(difPct) > 15 ? 'rose' : Math.abs(difPct) > 5 ? 'amber' : 'emerald'
            },
            {
                label: conExcedente > 0 ? 'Insumos con Excedente' : 'Balance de Insumos',
                value: conExcedente === 0
                    ? (conAhorro > 0 ? `${conAhorro} con ahorro` : 'Sin excedentes')
                    : `${conExcedente} con excedente`,
                sub: conExcedente === 0
                    ? (conAhorro > 0
                        ? `${conAhorro} insumo${conAhorro!==1?'s tienen':' tiene'} gasto por debajo del presupuesto`
                        : 'Todos dentro del presupuesto (&lt;10%)')
                    : `${conExcedente} insumo${conExcedente!==1?'s':''} superan el 10% del presupuesto`,
                action: conExcedente === 0
                    ? (conAhorro > 0 ? accion('save',`${conAhorro} insumo${conAhorro!==1?'s':''} con ahorro`) : accion('ok','Sin acción requerida'))
                    : (conExcedente > 3 ? accion('bad','Atención inmediata') : accion('warn','Revisar en la próxima reunión')),
                icon: conExcedente === 0 ? (conAhorro > 0 ? 'fa-piggy-bank' : 'fa-shield-check') : 'fa-exclamation-triangle',
                theme: conExcedente === 0 ? 'emerald' : (conExcedente > 3 ? 'rose' : 'amber')
            }
        ];

        const themes = {
            indigo:  { wrap:'border-indigo-200  dark:border-indigo-800/50  bg-indigo-50  dark:bg-indigo-950/40',  ico:'bg-indigo-100  dark:bg-indigo-900/50  text-indigo-600  dark:text-indigo-400',  val:'text-indigo-700  dark:text-indigo-200'  },
            emerald: { wrap:'border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-950/40', ico:'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400', val:'text-emerald-700 dark:text-emerald-200' },
            rose:    { wrap:'border-rose-200    dark:border-rose-800/50    bg-rose-50    dark:bg-rose-950/40',    ico:'bg-rose-100    dark:bg-rose-900/50    text-rose-600    dark:text-rose-400',    val:'text-rose-700    dark:text-rose-200'    },
            amber:   { wrap:'border-amber-200   dark:border-amber-800/50   bg-amber-50   dark:bg-amber-950/40',   ico:'bg-amber-100   dark:bg-amber-900/50   text-amber-600   dark:text-amber-400',   val:'text-amber-700   dark:text-amber-200'   },
            slate:   { wrap:'border-slate-200   dark:border-slate-700      bg-slate-50   dark:bg-slate-800/30',   ico:'bg-slate-100   dark:bg-slate-800      text-slate-500   dark:text-slate-400',   val:'text-slate-600   dark:text-slate-300'   }
        };

        document.getElementById('cmpKpis').innerHTML = cards.map(c => {
            const t = themes[c.theme];
            return `<div class="rounded-2xl border ${t.wrap} p-4 shadow-sm flex flex-col">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 ${t.ico}">
                        <i class="fas ${c.icon} text-xs"></i>
                    </div>
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-tight">${c.label}</div>
                </div>
                <div class="font-mono font-extrabold text-xl leading-tight ${t.val}">${c.value}</div>
                <div class="text-[11px] text-slate-400 mt-1 leading-snug">${c.sub}</div>
                <div class="mt-auto pt-2">${c.action}</div>
            </div>`;
        }).join('');
    }

    /* ══ TABLA ══ */
    function renderTable(insumos) {
        const isMobile = () => window.innerWidth < 768;
        let totPresu = 0, totFact = 0, totDif = 0;

        document.getElementById('cmpTableBody').innerHTML = insumos.map(ins => {
            const m    = ins.metricas;
            const dp   = m.desviacion_pct;
            const dif  = m.desviacion_monto;
            const dc   = desvColor(dp);
            const barW = Math.min(Math.abs(dp ?? 0), 100);
            const mob  = isMobile();

            totPresu += m.presupuesto_generales || 0;
            totFact  += m.total_facturado       || 0;
            totDif   += dif || 0;

            /* ── Presupuesto cell: pastilla amarilla si no hay datos ── */
            const presuCell = m.presupuesto_generales
                ? `<span style="font-family:monospace;font-size:13px;color:#6366f1;">${f(m.presupuesto_generales)}</span>`
                : `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;background:#fef9c3;color:#854d0e;border:1px solid #fde68a;">
                       <i class="fas fa-triangle-exclamation" style="font-size:8px;"></i> Sin presupuesto
                   </span>`;

            /* ── Desviación cell: "No calculable" si no hay presupuesto ── */
            const desvCell = dp != null
                ? `<div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;">
                       <span style="font-family:monospace;font-size:13px;font-weight:700;color:${dc};">${pct(dp)}</span>
                       <div style="width:56px;height:5px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                           <div style="width:${barW}%;height:100%;background:${dc};border-radius:99px;"></div>
                       </div>
                   </div>`
                : `<span style="font-size:11px;color:#94a3b8;font-style:italic;">No calculable</span>`;

            /* ── Fila desktop ── */
            const desk = `
            <tr class="cmp-row-desk" style="display:${mob?'none':'table-row'};border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 16px;font-weight:600;font-size:13px;max-width:200px;">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:${dc}"></span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${ins.nombre}">${ins.nombre}</span>
                    </span>
                </td>
                <td style="padding:10px 16px;font-size:12px;">${gBadge(ins.gerencia_id, ins.gerencia)}</td>
                <td style="padding:10px 16px;text-align:right;">${presuCell}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:13px;font-weight:700;color:${dc};">${f(m.total_facturado)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:13px;color:${dc};">${dif != null ? (dif >= 0 ? '+' : '') + f(dif) : '—'}</td>
                <td style="padding:10px 16px;text-align:right;">${desvCell}</td>
                <td style="padding:10px 16px;text-align:center;">${estadoBadge(dp)}</td>
            </tr>`;

            /* ── Fila mobile ── */
            const presuMob = m.presupuesto_generales
                ? `<span style="font-family:monospace;font-weight:600;color:#6366f1;">${f(m.presupuesto_generales)}</span>`
                : `<span style="font-size:10px;color:#b45309;font-weight:700;">⚠ Sin asignar</span>`;

            const desvMob = dp != null
                ? `<div style="display:flex;align-items:center;gap:6px;">
                       <span style="font-family:monospace;font-weight:700;color:${dc};">${pct(dp)}</span>
                       <div style="flex:1;max-width:40px;height:4px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                           <div style="width:${barW}%;height:100%;background:${dc};"></div>
                       </div>
                   </div>`
                : `<span style="font-size:10px;color:#94a3b8;font-style:italic;">Sin datos</span>`;

            const mob_ = `
            <tr class="cmp-row-mob" style="display:${mob?'table-row':'none'};border-bottom:1px solid #f1f5f9;">
                <td colspan="7" style="padding:12px 16px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:${dc};margin-top:2px;"></span>
                            <span style="font-weight:600;font-size:14px;line-height:1.3;">${ins.nombre}</span>
                        </div>
                        <div style="flex-shrink:0;">${estadoBadge(dp)}</div>
                    </div>
                    <div style="margin-left:18px;">
                        <div style="margin-bottom:8px;">${gBadge(ins.gerencia_id, ins.gerencia)}</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;font-size:12px;">
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;">Presupuesto</div>
                                ${presuMob}
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;">Facturado</div>
                                <div style="font-family:monospace;font-weight:700;color:${dc};">${f(m.total_facturado)}</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;">Diferencia</div>
                                <div style="font-family:monospace;color:${dc};">${dif != null ? (dif >= 0 ? '+' : '') + f(dif) : '—'}</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;">Desviación %</div>
                                ${desvMob}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>`;
            return desk + mob_;
        }).join('');

        /* ── Footer totales ── */
        const difTP = totPresu > 0 ? ((totFact - totPresu) / totPresu) * 100 : null;
        const difC  = desvColor(difTP);
        const mob   = isMobile();

        document.getElementById('cmpTableFoot').innerHTML = `
            <tr class="cmp-foot-desk" style="display:${mob?'none':'table-row'};background:#18171c;">
                <td colspan="2" style="padding:10px 16px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Total general</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;color:#6366f1;font-weight:700;">${f(totPresu)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:${difC};">${f(totFact)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;color:${difC};">${totDif >= 0 ? '+' : ''}${f(totDif)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:${difC};">${pct(difTP)}</td>
                <td></td>
            </tr>
            <tr class="cmp-foot-mob" style="display:${mob?'table-row':'none'};background:#f8fafc;">
                <td colspan="7" style="padding:12px 16px;">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Total general</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;font-size:12px;">
                        <div><span style="color:#94a3b8;">Presupuesto: </span><span style="font-family:monospace;font-weight:700;color:#6366f1;">${f(totPresu)}</span></div>
                        <div><span style="color:#94a3b8;">Facturado: </span><span style="font-family:monospace;font-weight:700;color:${difC};">${f(totFact)}</span></div>
                        <div><span style="color:#94a3b8;">Diferencia: </span><span style="font-family:monospace;font-weight:700;color:${difC};">${totDif >= 0 ? '+' : ''}${f(totDif)}</span></div>
                        <div><span style="color:#94a3b8;">Desviación: </span><span style="font-family:monospace;font-weight:700;color:${difC};">${pct(difTP)}</span></div>
                    </div>
                </td>
            </tr>`;

        document.getElementById('cmpTotalRows').textContent = `${insumos.length} insumo${insumos.length!==1?'s':''}`;

        /* Responsive resize */
        const applyBp = () => {
            const m = window.innerWidth < 768;
            document.querySelectorAll('.cmp-row-desk,.cmp-foot-desk').forEach(r => r.style.display = m ? 'none' : 'table-row');
            document.querySelectorAll('.cmp-row-mob,.cmp-foot-mob').forEach(r => r.style.display = m ? 'table-row' : 'none');
        };
        if (!window._cmpResizeAttached) { window.addEventListener('resize', applyBp); window._cmpResizeAttached = true; }
    }

    /* ══ SUBTÍTULO y FILTROS ACTIVOS ══ */
    function buildSubtitle() {
        const g = document.getElementById('cmpGerencia');
        const m = document.getElementById('cmpMes');
        const a = document.getElementById('cmpAnio');
        const parts = [];
        if (g.value) parts.push(g.options[g.selectedIndex].text);
        if (m.value) parts.push(MESES_FULL[parseInt(m.value)]);
        if (a.value) parts.push(a.value);
        return parts.length ? parts.join(' · ') : 'Todos los datos';
    }

    function updateFiltrosActivos() {
        const badge = document.getElementById('cmpFiltrosActivos');
        const n = [
            document.getElementById('cmpGerencia').value,
            document.getElementById('cmpMes').value,
            document.getElementById('cmpAnio').value,
            document.getElementById('cmpInsumo').value.trim()
        ].filter(Boolean).length;
        if (n > 0) {
            badge.textContent = `${n} filtro${n!==1?'s':''} activo${n!==1?'s':''}`;
            badge.classList.remove('hidden');
        } else { badge.classList.add('hidden'); }
    }

    /* ══ CARGA AJAX ══ */
    async function cargarComparativa() {
        const results = document.getElementById('cmpResults');
        const empty   = document.getElementById('cmpEmpty');
        const loading = document.getElementById('cmpLoading');

        results.classList.add('hidden');
        empty.classList.add('hidden');
        loading.classList.remove('hidden');
        updateFiltrosActivos();

        const params = new URLSearchParams();
        const g = document.getElementById('cmpGerencia').value;
        const m = document.getElementById('cmpMes').value;
        const a = document.getElementById('cmpAnio').value;
        const i = document.getElementById('cmpInsumo').value.trim();
        if (g) params.append('gerencia_id', g);
        if (m) params.append('mes', m);
        if (a) params.append('anio', a);
        if (i) params.append('insumo', i);

        try {
            const res  = await fetch(`{{ route('facturas.comparativa') }}?${params}`, {
                headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }
            });
            const json = await res.json();
            const data = json.insumos || [];

            loading.classList.add('hidden');

            if (!data.length) {
                empty.classList.remove('hidden');
                return;
            }

            results.classList.remove('hidden');
            document.getElementById('cmpChartSubtitle').textContent = buildSubtitle();

            requestAnimationFrame(() => {
                renderInsights(data);
                renderKpis(data);
                renderChart(data);
                renderTable(data);
            });

        } catch(e) {
            loading.classList.add('hidden');
            document.getElementById('cmpEmpty').innerHTML = `<div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-100 dark:bg-rose-900/30 mb-3">
                    <i class="fas fa-exclamation-triangle text-xl text-rose-400"></i>
                </div>
                <p class="text-slate-500 text-sm font-semibold">Error al cargar los datos.</p>
                <p class="text-slate-300 text-xs mt-1">Revisa tu conexión o contacta al administrador.</p>
            </div>`;
            document.getElementById('cmpEmpty').classList.remove('hidden');
            console.error(e);
        }
    }

    document.getElementById('formComparativaFilter').addEventListener('submit', e => { e.preventDefault(); cargarComparativa(); });
    document.getElementById('cmpBtnReset').addEventListener('click', () => {
        document.getElementById('cmpGerencia').value = '';
        document.getElementById('cmpMes').value      = '';
        document.getElementById('cmpAnio').value     = '{{ date("Y") }}';
        document.getElementById('cmpInsumo').value   = '';
        cargarComparativa();
    });
    ['cmpGerencia','cmpMes','cmpAnio'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => cargarComparativa());
    });

    let _cmpCargado = false;
    window.initComparativa = function () {
        if (!_cmpCargado) {
            _cmpCargado = true;
            cargarComparativa();
        } else {
            if (typeof Highcharts !== 'undefined')
                setTimeout(() => { Highcharts.charts.forEach(c => { if (c) c.reflow(); }); }, 60);
        }
    };

    function autoInit() {
        const contenedor = document.getElementById('content-historial');
        if (contenedor && contenedor.classList.contains('active')) cargarComparativa();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', autoInit);
    else autoInit();

})();
</script>
@endpush