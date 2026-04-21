{{-- Comparativa: presupuesto vs facturado — solo Ahorro / Desviación --}}
<style>
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

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-sm px-4 py-3">
        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
            <span class="inline-flex items-center gap-1.5 font-bold text-indigo-600 dark:text-indigo-400"><span class="inline-block w-2 h-2 rounded-sm bg-indigo-500"></span> Presupuesto</span>
            frente a
            <span class="inline-flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400"><span class="inline-block w-2 h-2 rounded-sm bg-emerald-500"></span> Facturado</span>.
            <span class="text-slate-400 mx-1">·</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-200 border border-teal-200 dark:border-teal-800">Ahorro</span> = gasto <strong>por debajo</strong> del presupuesto.
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-800 ml-1">Desviación</span> = gasto <strong>por encima</strong> del presupuesto.
        </p>
    </div>

    {{-- Filtro extra solo para comparativa (insumo). Gerencia / mes / año están arriba en la página. --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-sm">
        <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-2">
            <i class="fas fa-search text-emerald-500 text-sm"></i>
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Comparativa</span>
            <span class="text-[10px] text-slate-400 dark:text-slate-500">Filtrar por nombre de insumo (opcional)</span>
            <span id="cmpFiltrosActivos" class="hidden ml-auto text-[11px] font-semibold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-full"></span>
        </div>
        <div class="p-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
            <div class="flex-1 min-w-0">
                <label for="cmpInsumo" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                    <i class="fas fa-tag mr-1 text-emerald-400"></i>Insumo
                </label>
                <input id="cmpInsumo" type="text" placeholder="Buscar insumo…"
                    class="w-full h-10 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all placeholder:text-slate-300">
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="button" id="cmpBtnFiltrarInsumo"
                    class="h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md shadow-indigo-500/25 transition-all flex items-center justify-center gap-1.5">
                    <i class="fas fa-search text-xs"></i> Aplicar
                </button>
                <button type="button" id="cmpBtnReset" title="Restablecer filtros (gerencia, mes, año e insumo)"
                    class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-all flex items-center justify-center">
                    <i class="fas fa-undo text-xs"></i>
                </button>
            </div>
        </div>
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

        {{-- KPIs mínimos --}}
        <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 ml-1">
                <i class="fas fa-tachometer-alt mr-1"></i>Resumen
            </p>
            <div id="cmpKpis" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"></div>
        </div>

        {{-- Gráfica: presupuesto vs facturado --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden">
            <div class="px-5 pt-4 pb-0 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">Presupuesto vs facturado</h2>
                    <p id="cmpChartSubtitle" class="text-[11px] text-slate-400 mt-0.5">—</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 max-w-2xl leading-snug">
                        Pasa el cursor por cada insumo para ver presupuesto y facturado a la vez. Con muchos insumos, desplázate con la barra inferior o usa la rueda del ratón para acercar el eje horizontal (luego «Restablecer zoom»).
                    </p>
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
            <div id="cmpChartMontos" class="w-full" style="min-height:400px;"></div>
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
                            <th class="px-4 py-3 text-[11px] font-extrabold text-indigo-400 uppercase tracking-wider text-right">Presupuesto</th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-emerald-500 uppercase tracking-wider text-right">Facturado</th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-center">Ahorro / Desviación</th>
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
<script src="https://code.highcharts.com/modules/exporting.js"></script>
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
    const gNom = (id, nombre) => nombre || GERENCIA_MAP[id] || (id ? 'Gerencia #'+id : 'Sin gerencia asignada');
    const gBadge = (id, nombre) => {
        const n = nombre || GERENCIA_MAP[id] || (id ? 'Gerencia #'+id : null);
        if (n) return `<span class="text-xs text-slate-500 dark:text-slate-400">${n}</span>`;
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700"><i class="fas fa-hourglass-half text-[9px]"></i> Sin asignar</span>`;
    };

    /** Color según facturado vs presupuesto (sin umbrales de %) */
    function colorPorMontos(m) {
        const presu = m.presupuesto_generales;
        const fact = m.total_facturado ?? 0;
        if (presu == null || presu <= 0) return fact > 0 ? '#f43f5e' : '#94a3b8';
        if (fact > presu) return '#f43f5e';
        if (fact < presu) return '#0d9488';
        return '#94a3b8';
    }

    function colorTotales(totPresu, totFact) {
        if (!totPresu || totPresu <= 0) return totFact > 0 ? '#f43f5e' : '#94a3b8';
        if (totFact > totPresu) return '#f43f5e';
        if (totFact < totPresu) return '#0d9488';
        return '#94a3b8';
    }

    function estadoBadgeMontos(m) {
        const presu = m.presupuesto_generales;
        const fact = m.total_facturado ?? 0;
        if (presu == null || presu <= 0) {
            if (fact > 0) {
                return `<span class="inline-flex flex-col items-center gap-0.5 max-w-[11rem] mx-auto">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800"><i class="fas fa-exclamation-triangle"></i> Desviación</span>
                    <span class="text-[11px] font-mono font-extrabold text-rose-600 dark:text-rose-400">${f(fact)}</span>
                    <span class="text-[9px] text-slate-400 dark:text-slate-500 italic leading-tight text-center">Sin presupuesto en Cortes: el facturado cuenta como desviación.</span>
                </span>`;
            }
            return `<span class="text-[10px] text-slate-400 italic">Sin presupuesto</span>`;
        }
        if (fact > presu) {
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800"><i class="fas fa-exclamation-triangle"></i> Desviación</span>`;
        }
        if (fact < presu) {
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800"><i class="fas fa-piggy-bank"></i> Ahorro</span>`;
        }
        return `<span class="text-[10px] text-slate-500">Igual al presupuesto</span>`;
    }

    function insumoConPresupuesto(i) {
        const p = i.metricas.presupuesto_generales;
        return p != null && p > 0;
    }

    /* ══ Un solo aviso: ahorro vs desviación por montos ══ */
    function renderInsights(insumos) {
        const conPresu = insumos.filter(insumoConPresupuesto);
        const desv = conPresu.filter(i => (i.metricas.total_facturado ?? 0) > i.metricas.presupuesto_generales);
        const aho  = conPresu.filter(i => (i.metricas.total_facturado ?? 0) < i.metricas.presupuesto_generales);
        const sinP = insumos.filter(i => !insumoConPresupuesto(i));
        const desvSinPresu = sinP.filter(i => (i.metricas.total_facturado ?? 0) > 0);
        const tieneDesv = desv.length > 0 || desvSinPresu.length > 0;

        let gType, gIcon, gMsg;
        if (tieneDesv && aho.length) {
            gType = 'warn'; gIcon = 'fa-balance-scale';
            const partDesv = [];
            if (desv.length) partDesv.push(`<strong>Sobre presupuesto:</strong> ${desv.map(i => i.nombre).join(', ')}`);
            if (desvSinPresu.length) partDesv.push(`<strong>Sin presupuesto en Cortes</strong> (facturado como desviación): ${desvSinPresu.map(i => i.nombre).join(', ')}`);
            gMsg = `${partDesv.join('. ')}. <strong>Ahorro:</strong> ${aho.map(i => i.nombre).join(', ')}.`;
        } else if (tieneDesv) {
            gType = 'alert'; gIcon = 'fa-exclamation-triangle';
            if (desv.length && desvSinPresu.length) {
                gMsg = `<strong>Desviación</strong> por encima del presupuesto: ${desv.map(i => i.nombre).join(', ')}. <strong>Facturado sin presupuesto en Cortes</strong> (se cuenta como desviación): ${desvSinPresu.map(i => i.nombre).join(', ')}.`;
            } else if (desv.length) {
                gMsg = `<strong>Desviación</strong> (facturado por encima del presupuesto): ${desv.map(i => i.nombre).join(', ')}.`;
            } else {
                gMsg = `<strong>Desviación</strong> — hay facturación sin presupuesto en Cortes: ${desvSinPresu.map(i => i.nombre).join(', ')}. El monto facturado se trata como desviación.`;
            }
        } else if (aho.length) {
            gType = 'saving'; gIcon = 'fa-piggy-bank';
            gMsg = `<strong>Ahorro</strong> (facturado por debajo del presupuesto): ${aho.map(i => i.nombre).join(', ')}.`;
        } else {
            gType = 'ok'; gIcon = 'fa-check-circle';
            gMsg = conPresu.length
                ? 'Todos los insumos con presupuesto están en línea con lo presupuestado.'
                : 'No hay presupuesto en Cortes para comparar en este período.';
        }
        const sinPsinFact = sinP.filter(i => (i.metricas.total_facturado ?? 0) <= 0);
        if (sinPsinFact.length) {
            gMsg += ` <span class="opacity-90">(${sinPsinFact.length} sin presupuesto y sin facturar en el período: ${sinPsinFact.map(i => i.nombre).join(', ')}.)</span>`;
        }
        document.getElementById('cmpInsightGlobal').innerHTML =
            `<div class="insight-bar ${gType}"><i class="fas ${gIcon} mt-0.5 flex-shrink-0"></i><span>${gMsg}</span></div>`;
    }

    /* ══ GRÁFICA (solo presupuesto vs facturado) ══ */
    let chartMontos = null;

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

        insumos.forEach(ins => {
            const m = ins.metricas;
            const fc = colorPorMontos(m);
            presuData.push({ y: m.presupuesto_generales ?? 0, name: ins.nombre });
            factData.push({ y: m.total_facturado ?? 0, name: ins.nombre, color: fc });
        });

        function tooltipSharedMontos() {
            return {
                shared: true,
                useHTML: true,
                followPointer: true,
                stickOnContact: true,
                backgroundColor: ttBg,
                borderColor: ttBdr,
                borderRadius: 12,
                borderWidth: 1,
                shadow: true,
                padding: 0,
                style: { color: ttClr },
                formatter: function () {
                    const pts = this.points;
                    if (!pts || !pts.length) return false;
                    const idx = pts[0].point.index;
                    const ins = insumos[idx];
                    if (!ins) return false;
                    const m = ins.metricas;
                    const presu = m.presupuesto_generales;
                    const fact = m.total_facturado ?? 0;
                    const dc = colorPorMontos(m);
                    const sinPresuCorte = presu == null || presu <= 0;
                    const dif = sinPresuCorte ? fact - 0 : fact - presu;
                    let etiqueta = '';
                    if (sinPresuCorte) {
                        if (fact > 0) {
                            etiqueta = '<div style="margin-top:8px;font-size:11px;color:#f43f5e;font-weight:700">Desviación: sin presupuesto en Cortes; el facturado completo se considera desviación.</div>';
                        } else {
                            etiqueta = '<div style="margin-top:10px;font-size:11px;color:#94a3b8">Sin presupuesto ni facturado en Cortes para comparar.</div>';
                        }
                    } else if (fact > presu) {
                        etiqueta = '<div style="margin-top:8px;font-size:11px;color:#f43f5e;font-weight:700">Desviación — facturado por encima del presupuesto</div>';
                    } else if (fact < presu) {
                        etiqueta = '<div style="margin-top:8px;font-size:11px;color:#0d9488;font-weight:700">Ahorro — facturado por debajo del presupuesto</div>';
                    } else {
                        etiqueta = '<div style="margin-top:8px;font-size:11px;color:#64748b">Igual al presupuesto</div>';
                    }
                    let rows = '';
                    pts.forEach(p => {
                        const isP = p.series.name === 'Presupuesto';
                        const col = isP ? '#818cf8' : dc;
                        const sw = isP ? '#6366f1' : (p.color || dc);
                        rows += `<div style="display:flex;justify-content:space-between;align-items:center;gap:18px;margin-top:6px">
                            <span style="display:inline-flex;align-items:center;gap:6px;color:${col};font-weight:700;font-size:12px">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:${sw};opacity:0.95"></span>${p.series.name}</span>
                            <span style="font-family:ui-monospace,monospace;font-weight:800;font-size:13px;color:${isP ? '#4f46e5' : dc}">${f(p.y)}</span>
                        </div>`;
                    });
                    return `<div style="min-width:268px;padding:12px 14px">
                        <div style="font-weight:800;font-size:13px;line-height:1.25;margin-bottom:2px;color:${ttClr}">${ins.nombre}</div>
                        <div style="color:#94a3b8;font-size:10px;margin-bottom:6px">${gNom(ins.gerencia_id, ins.gerencia)}</div>
                        ${rows}
                        ${(!sinPresuCorte && presu > 0) || (sinPresuCorte && fact > 0) ? `<div style="display:flex;justify-content:space-between;gap:18px;margin-top:10px;padding-top:8px;border-top:1px solid ${ttBdr}">
                            <span style="color:#94a3b8;font-weight:600;font-size:11px">${sinPresuCorte ? 'Desviación (sin presupuesto en Cortes)' : 'Diferencia (fact. − pres.)'}</span>
                            <span style="font-family:ui-monospace,monospace;font-weight:800;font-size:12px;color:${dc}">${dif >= 0 ? '+' : ''}${f(dif)}</span>
                        </div>` : ''}
                        ${etiqueta}
                    </div>`;
                }
            };
        }

        const xAxisBase = {
            categories: cats,
            lineColor: axisClr,
            tickColor: axisClr,
            crosshair: { color: dark ? 'rgba(148,163,184,0.35)' : 'rgba(100,116,139,0.35)', width: 1, dashStyle: 'ShortDot' },
            labels: {
                style: { color: lblClr, fontSize: '11px', fontWeight: '600' },
                rotation: cats.length > 6 ? -35 : 0
            }
        };
        const yFmt = function () {
            const v = Math.abs(this.value);
            if (v >= 1000000) return '$' + (this.value / 1000000).toFixed(1) + 'M';
            if (v >= 1000) return '$' + (this.value / 1000).toFixed(0) + 'k';
            return '$' + this.value;
        };

        const plotMinW = Math.min(4200, Math.max(640, insumos.length * 76));

        if (!window._cmpHcLangSet) {
            Highcharts.setOptions({
                lang: {
                    downloadPNG: 'Descargar PNG',
                    downloadJPEG: 'Descargar JPEG',
                    downloadPDF: 'Descargar PDF',
                    downloadSVG: 'Descargar SVG',
                    printChart: 'Imprimir gráfica',
                    resetZoom: 'Restablecer zoom',
                    contextButtonTitle: 'Menú de la gráfica'
                }
            });
            window._cmpHcLangSet = true;
        }

        chartMontos = Highcharts.chart('cmpChartMontos', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent',
                style: { fontFamily: 'inherit' },
                height: cats.length > 8 ? 420 : 400,
                marginTop: 8,
                marginBottom: 72,
                spacing: [12, 12, 8, 12],
                animation: { duration: 700 },
                zooming: { type: 'x', mouseWheel: { enabled: true } },
                scrollablePlotArea: { minWidth: plotMinW, opacity: 1, applyAnimation: true },
                resetZoomButton: {
                    position: { align: 'right', verticalAlign: 'top', x: -8, y: 8 },
                    theme: { fill: dark ? '#312e81' : '#e0e7ff', stroke: dark ? '#6366f1' : '#6366f1', style: { color: dark ? '#e0e7ff' : '#312e81', fontWeight: '700', fontSize: '11px' } }
                }
            },
            title: { text: null },
            credits: { enabled: false },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'printChart']
                    }
                },
                fallbackToExportServer: false
            },
            legend: {
                enabled: true,
                align: 'center',
                verticalAlign: 'bottom',
                itemMarginTop: 6,
                itemStyle: { color: lblClr, fontWeight: '600', fontSize: '11px' },
                symbolRadius: 3
            },
            tooltip: tooltipSharedMontos(),
            xAxis: xAxisBase,
            yAxis: {
                title: { text: 'Importe (MXN)', style: { color: lblClr, fontSize: '10px', fontWeight: '700' }, margin: 10 },
                gridLineColor: gridClr,
                crosshair: { color: dark ? 'rgba(148,163,184,0.25)' : 'rgba(100,116,139,0.25)', width: 1, dashStyle: 'ShortDot' },
                labels: { style: { color: lblClr, fontSize: '11px' }, formatter: yFmt },
                stackLabels: { enabled: false }
            },
            plotOptions: {
                column: {
                    borderRadius: 6,
                    borderWidth: 0,
                    groupPadding: 0.14,
                    pointPadding: 0.06,
                    maxPointWidth: 56,
                    animation: { duration: 700 },
                    states: {
                        hover: { brightness: dark ? 0.12 : -0.06 },
                        inactive: { opacity: 0.45 }
                    }
                },
                series: {
                    states: { inactive: { opacity: 0.45 } },
                    animation: { duration: 650 }
                }
            },
            series: [
                { name: 'Presupuesto', data: presuData, color: '#6366f1', opacity: 0.9, borderColor: dark ? '#1e1b4b' : '#eef2ff', borderWidth: 1 },
                { name: 'Facturado', data: factData, colorByPoint: true, animation: { duration: 700, defer: 180 }, borderColor: dark ? '#0f172a' : '#f8fafc', borderWidth: 1 }
            ],
            responsive: {
                rules: [{
                    condition: { maxWidth: 520 },
                    chartOptions: {
                        xAxis: { labels: { rotation: -55, style: { fontSize: '10px' } } },
                        yAxis: { title: { text: null } },
                        chart: { height: 300, scrollablePlotArea: { minWidth: Math.min(3600, Math.max(520, insumos.length * 64)) } }
                    }
                }]
            }
        });
    }

    /* ══ KPI: solo presupuesto vs facturado ══ */
    function renderKpis(insumos) {
        const totPresu = insumos.reduce((s, i) => s + (i.metricas.presupuesto_generales || 0), 0);
        const totFact  = insumos.reduce((s, i) => s + (i.metricas.total_facturado || 0), 0);
        const dif      = totFact - totPresu;
        const tienePresu = totPresu > 0;
        const cantAhorrada = tienePresu ? Math.max(0, totPresu - totFact) : null;
        const cantDesviada = tienePresu ? Math.max(0, totFact - totPresu) : (totFact > 0 ? totFact : null);
        let resumen = 'Sin presupuesto total en Cortes para este período.';
        let theme = 'slate';
        if (tienePresu) {
            if (dif > 0) { resumen = 'Desviación: el total facturado supera al presupuesto.'; theme = 'rose'; }
            else if (dif < 0) { resumen = 'Ahorro: el total facturado está por debajo del presupuesto.'; theme = 'teal'; }
            else { resumen = 'El total facturado coincide con el presupuesto.'; theme = 'slate'; }
        } else if (totFact > 0) {
            resumen = 'Sin presupuesto total en Cortes: el facturado se considera desviación.';
            theme = 'rose';
        }

        const themes = {
            indigo:  { wrap:'border-indigo-200  dark:border-indigo-800/50  bg-indigo-50  dark:bg-indigo-950/40',  ico:'bg-indigo-100  dark:bg-indigo-900/50  text-indigo-600  dark:text-indigo-400',  val:'text-indigo-700  dark:text-indigo-200'  },
            teal:    { wrap:'border-teal-200 dark:border-teal-800/50 bg-teal-50 dark:bg-teal-950/40', ico:'bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400', val:'text-teal-800 dark:text-teal-200' },
            rose:    { wrap:'border-rose-200 dark:border-rose-800/50 bg-rose-50 dark:bg-rose-950/40', ico:'bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400', val:'text-rose-800 dark:text-rose-200' },
            slate:   { wrap:'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30', ico:'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400', val:'text-slate-700 dark:text-slate-200' }
        };
        const tIndigo = themes.indigo;
        const tRes = theme === 'rose' ? themes.rose : theme === 'teal' ? themes.teal : themes.slate;
        const tTeal = themes.teal;
        const tRose = themes.rose;

        const subAhorrada = !tienePresu
            ? 'Sin presupuesto en Cortes para calcular.'
            : (cantAhorrada > 0 ? 'Presupuesto total menos facturado (monto a favor).' : 'Sin monto ahorrado: el facturado no está por debajo del presupuesto.');
        const subDesviada = !tienePresu
            ? (totFact > 0 ? 'Sin presupuesto en Cortes: el total facturado cuenta como desviación.' : 'Sin facturado ni presupuesto para calcular desviación.')
            : (cantDesviada > 0 ? 'Facturado total menos presupuesto (monto en contra).' : 'Sin desviación: el facturado no supera al presupuesto.');

        const cards = [
            { label: 'Presupuesto', value: !tienePresu ? '—' : f(totPresu), sub: 'Suma en Cortes del período', t: tIndigo, icon: 'fa-wallet' },
            { label: 'Facturado', value: f(totFact), sub: resumen, t: tRes, icon: 'fa-receipt' },
            { label: 'Cantidad ahorrada', value: !tienePresu ? '—' : f(cantAhorrada), sub: subAhorrada, t: tTeal, icon: 'fa-piggy-bank' },
            { label: 'Cantidad desviada', value: cantDesviada != null && cantDesviada > 0 ? f(cantDesviada) : '—', sub: subDesviada, t: tRose, icon: 'fa-chart-line' }
        ];

        document.getElementById('cmpKpis').innerHTML = cards.map(c => `
            <div class="rounded-2xl border ${c.t.wrap} p-4 shadow-sm flex flex-col">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 ${c.t.ico}">
                        <i class="fas ${c.icon} text-xs"></i>
                    </div>
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-tight">${c.label}</div>
                </div>
                <div class="font-mono font-extrabold text-xl leading-tight ${c.t.val}">${c.value}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">${c.sub}</div>
            </div>`).join('');
    }

    /* ══ TABLA: insumo, gerencia, presupuesto, facturado, ahorro/desviación ══ */
    function renderTable(insumos) {
        const isMobile = () => window.innerWidth < 768;
        let totPresu = 0, totFact = 0;

        document.getElementById('cmpTableBody').innerHTML = insumos.map(ins => {
            const m   = ins.metricas;
            const dc  = colorPorMontos(m);
            const mob = isMobile();

            totPresu += m.presupuesto_generales || 0;
            totFact  += m.total_facturado || 0;

            const presuCell = m.presupuesto_generales
                ? `<span style="font-family:monospace;font-size:13px;color:#6366f1;">${f(m.presupuesto_generales)}</span>`
                : `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;background:#fef9c3;color:#854d0e;border:1px solid #fde68a;">
                       <i class="fas fa-triangle-exclamation" style="font-size:8px;"></i> Sin presupuesto
                   </span>`;

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
                <td style="padding:10px 16px;text-align:center;">${estadoBadgeMontos(m)}</td>
            </tr>`;

            const presuMob = m.presupuesto_generales
                ? `<span style="font-family:monospace;font-weight:600;color:#6366f1;">${f(m.presupuesto_generales)}</span>`
                : `<span style="font-size:10px;color:#b45309;font-weight:700;">Sin presupuesto</span>`;

            const mob_ = `
            <tr class="cmp-row-mob" style="display:${mob?'table-row':'none'};border-bottom:1px solid #f1f5f9;">
                <td colspan="5" style="padding:12px 16px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:${dc};margin-top:2px;"></span>
                            <span style="font-weight:600;font-size:14px;line-height:1.3;">${ins.nombre}</span>
                        </div>
                        <div style="flex-shrink:0;">${estadoBadgeMontos(m)}</div>
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
                        </div>
                    </div>
                </td>
            </tr>`;
            return desk + mob_;
        }).join('');

        const difC = colorTotales(totPresu, totFact);
        const mob  = isMobile();
        const totalesMetricas = { presupuesto_generales: totPresu > 0 ? totPresu : null, total_facturado: totFact };

        document.getElementById('cmpTableFoot').innerHTML = `
            <tr class="cmp-foot-desk border-t-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900" style="display:${mob?'none':'table-row'};">
                <td colspan="2" class="px-4 py-2.5 text-[11px] font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total general</td>
                <td class="px-4 py-2.5 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">${f(totPresu)}</td>
                <td class="px-4 py-2.5 text-right font-mono font-bold" style="color:${difC};">${f(totFact)}</td>
                <td class="px-4 py-2.5 text-center">${estadoBadgeMontos(totalesMetricas)}</td>
            </tr>
            <tr class="cmp-foot-mob border-t-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900" style="display:${mob?'table-row':'none'};">
                <td colspan="5" class="p-3 md:p-4">
                    <div class="text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1.5">Total general</div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                        <div><span class="text-slate-500 dark:text-slate-400">Presupuesto: </span><span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">${f(totPresu)}</span></div>
                        <div><span class="text-slate-500 dark:text-slate-400">Facturado: </span><span class="font-mono font-bold" style="color:${difC};">${f(totFact)}</span></div>
                    </div>
                    <div class="mt-2">${estadoBadgeMontos(totalesMetricas)}</div>
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
        const g = document.getElementById('gerenci_id');
        const m = document.getElementById('mesFilter');
        const a = document.getElementById('añoFilter');
        const parts = [];
        if (g && g.value) parts.push(g.options[g.selectedIndex].text);
        if (m && m.value) parts.push(MESES_FULL[parseInt(m.value, 10)]);
        if (a && a.value) parts.push(a.value);
        return parts.length ? parts.join(' · ') : 'Todos los datos';
    }

    function updateFiltrosActivos() {
        const badge = document.getElementById('cmpFiltrosActivos');
        const g = document.getElementById('gerenci_id');
        const m = document.getElementById('mesFilter');
        const a = document.getElementById('añoFilter');
        const i = document.getElementById('cmpInsumo');
        const n = [
            g && g.value ? g.value : '',
            m && m.value ? m.value : '',
            a && a.value ? a.value : '',
            i ? i.value.trim() : ''
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
        const gEl = document.getElementById('gerenci_id');
        const mEl = document.getElementById('mesFilter');
        const aEl = document.getElementById('añoFilter');
        const iEl = document.getElementById('cmpInsumo');
        const g = gEl ? gEl.value : '';
        const m = mEl ? mEl.value : '';
        const a = aEl ? aEl.value : '';
        const i = iEl ? iEl.value.trim() : '';
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

    let _cmpCargado = false;
    window.reloadComparativaFromGlobal = function () {
        _cmpCargado = true;
        cargarComparativa();
    };

    const cmpBtnFiltrarInsumo = document.getElementById('cmpBtnFiltrarInsumo');
    if (cmpBtnFiltrarInsumo) {
        cmpBtnFiltrarInsumo.addEventListener('click', () => cargarComparativa());
    }

    const cmpBtnReset = document.getElementById('cmpBtnReset');
    if (cmpBtnReset) {
        cmpBtnReset.addEventListener('click', () => {
            const formGlobal = document.getElementById('formFilter');
            const mesDef = formGlobal?.dataset?.mesDefault || '';
            const anioDef = formGlobal?.dataset?.anioDefault || '';
            const gEl = document.getElementById('gerenci_id');
            const mEl = document.getElementById('mesFilter');
            const aEl = document.getElementById('añoFilter');
            if (gEl) gEl.value = '';
            if (mEl) mEl.value = mesDef;
            if (aEl) aEl.value = anioDef;
            const ins = document.getElementById('cmpInsumo');
            if (ins) ins.value = '';
            if (typeof window.syncGerenciaFacturasBanner === 'function') {
                window.syncGerenciaFacturasBanner();
            }
            if (window.jQuery && window.jQuery('#facturasTable').length) {
                window.jQuery('#facturasTable').DataTable().ajax.reload(null, false);
            }
            _cmpCargado = true;
            cargarComparativa();
        });
    }

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