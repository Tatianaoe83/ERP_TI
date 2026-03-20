
{{-- ═══════════════════════════════════════════════════════
     ESTILOS GLOBALES DEL PARTIAL
═══════════════════════════════════════════════════════ --}}
<style>
/* ── Tooltip glosario ── */
.gls {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    cursor: help;
    border-bottom: 1.5px dashed #94a3b8;
    text-decoration: none;
    transition: border-color .15s;
}
.gls:hover { border-color: #6366f1; }
.gls-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px; height: 14px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #4f46e5;
    font-size: 8px;
    font-weight: 900;
    flex-shrink: 0;
    transition: background .15s;
}
.dark .gls-icon { background:#312e81; color:#a5b4fc; }
.gls:hover .gls-icon { background:#6366f1; color:#fff; }

.gls-tip {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #e2e8f0;
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.5;
    white-space: nowrap;
    max-width: 260px;
    white-space: normal;
    z-index: 50;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    pointer-events: none;
    opacity: 0;
    transition: opacity .15s, transform .15s;
    transform: translateX(-50%) translateY(4px);
}
.gls-tip::after {
    content:'';
    position: absolute;
    top: 100%; left:50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #1e293b;
}
.gls:hover .gls-tip {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* ── Tabla responsiva — breakpoint real sin depender de purge de Tailwind ── */
#cmpTableHead { display: table-header-group; }
@media (max-width: 767px) {
    #cmpTableHead { display: none; }
}
.insight-bar {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 16px;
    border-radius: 10px;
    margin: 0 16px 0 16px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
}
.insight-bar.ok    { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.insight-bar.warn  { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
.insight-bar.alert { background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; }
.dark .insight-bar.ok    { background:#052e16; border-color:#14532d; color:#86efac; }
.dark .insight-bar.warn  { background:#1c1003; border-color:#713f12; color:#fde68a; }
.dark .insight-bar.alert { background:#1c0008; border-color:#9f1239; color:#fda4af; }
</style>

<div id="comparativaRoot" class="space-y-5">

    {{-- ══════════════════════════════════════════
         BARRA DE REFERENCIA RÁPIDA
         4 chips de color + semáforo — siempre visible, sin texto explicativo
    ══════════════════════════════════════════ --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-sm px-5 py-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

            {{-- Etiqueta izquierda --}}
            <span class="text-[10px] font-extrabold text-slate-300 dark:text-slate-600 uppercase tracking-widest flex-shrink-0">Referencia rápida</span>

            {{-- ── Los 4 términos clave ── --}}
            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-indigo-500 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Cotización ganadora</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip">Precio acordado con el proveedor <strong>antes</strong> de iniciar la obra. Es el límite de gasto aprobado.</span>
            </span>

            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Facturado</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip">Lo que el proveedor ya cobró efectivamente. Si supera la cotización, hay un excedente de gasto.</span>
            </span>

            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-400 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Corte de campo</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip">Avance físico medido en obra. Si es menor que lo facturado, el proveedor cobró más de lo ejecutado.</span>
            </span>

            <span class="gls flex-shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-fuchsia-500 mr-1.5"></span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Desviación %</span>
                <span class="gls-icon">?</span>
                <span class="gls-tip"><strong style="color:#f43f5e">Positivo (+)</strong> = se gastó más de lo acordado.<br><strong style="color:#10b981">Negativo (−)</strong> = se gastó menos de lo acordado.</span>
            </span>

            {{-- Divisor visual --}}
            <span class="hidden lg:block w-px h-5 bg-slate-200 dark:bg-slate-700 flex-shrink-0"></span>

            {{-- ── Semáforo de estados ── --}}
            <span class="flex items-center gap-1.5 flex-shrink-0">
                <span class="gls">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <i class="fas fa-check text-[9px]"></i> OK
                    </span>
                    <span class="gls-tip" style="left:0;transform:none;">Desviación menor al 5% — sin acción requerida.</span>
                </span>
                <span class="gls">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        <i class="fas fa-exclamation-circle text-[9px]"></i> Alerta
                    </span>
                    <span class="gls-tip" style="left:0;transform:none;">Desviación entre 5% y 15% — monitorear y pedir explicación.</span>
                </span>
                <span class="gls">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                        <i class="fas fa-exclamation-triangle text-[9px]"></i> Crítico
                    </span>
                    <span class="gls-tip" style="left:0;transform:none;">Desviación mayor al 15% — revisar contrato de inmediato.</span>
                </span>
            </span>

        </div>
    </div>

    {{-- ══════════════════════════════════════════
         FILTROS
    ══════════════════════════════════════════ --}}
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
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">
                        <i class="fas fa-info-circle mr-0.5 text-slate-300"></i>
                        Filtra insumos de una sola área. Deja en blanco para ver todo.
                    </p>
                </div>

                {{-- Mes --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-calendar-alt mr-1 text-sky-400"></i>Mes
                    </label>
                    <div class="relative">
                        <select id="cmpMes"
                            class="w-full h-10 pl-4 pr-10 appearance-none rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                            <option value="">Todos</option>
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">
                        <i class="fas fa-info-circle mr-0.5 text-slate-300"></i>
                        Mes de la factura o del corte.
                    </p>
                </div>

                {{-- Año --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-calendar mr-1 text-violet-400"></i>Año
                    </label>
                    <div class="relative">
                        <select id="cmpAnio"
                            class="w-full h-10 pl-4 pr-10 appearance-none rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                            <option value="">Todos</option>
                            @for($y = date('Y'); $y >= 2022; $y--)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">
                        <i class="fas fa-info-circle mr-0.5 text-slate-300"></i>
                        Por defecto muestra el año actual.
                    </p>
                </div>

                {{-- Insumo --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-0.5">
                        <i class="fas fa-tag mr-1 text-emerald-400"></i>Insumo
                    </label>
                    <input id="cmpInsumo" type="text" placeholder="Ej: concreto, acero…"
                        class="w-full h-10 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all placeholder:text-slate-300">
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">
                        <i class="fas fa-info-circle mr-0.5 text-slate-300"></i>
                        Escribe parte del nombre para buscar.
                    </p>
                </div>

                {{-- Botones --}}
                <div class="flex gap-2 pt-5">
                    <button type="submit"
                        class="flex-1 h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md shadow-indigo-500/25 transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-1.5">
                        <i class="fas fa-search text-xs"></i> Filtrar
                    </button>
                    <button type="button" id="cmpBtnReset" title="Quitar todos los filtros y volver al estado inicial"
                        class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-all flex items-center justify-center">
                        <i class="fas fa-undo text-xs"></i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════
         ESTADO: CARGANDO
    ══════════════════════════════════════════ --}}
    <div id="cmpLoading" class="hidden py-16 text-center">
        <div class="inline-flex flex-col items-center gap-3">
            <i class="fas fa-circle-notch fa-spin text-indigo-500 text-3xl"></i>
            <p class="text-slate-500 text-sm font-semibold">Consultando datos…</p>
            <p class="text-slate-300 text-xs">Esto tarda unos segundos</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         ESTADO: SIN DATOS
    ══════════════════════════════════════════ --}}
    <div id="cmpEmpty" class="hidden py-14 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-800 mb-3">
            <i class="fas fa-search text-xl text-slate-300"></i>
        </div>
        <p class="text-slate-500 text-sm font-semibold">No se encontraron insumos con estos filtros.</p>
        <p class="text-slate-300 text-xs mt-1 mb-4">Intenta seleccionar un rango de fechas más amplio o quitar algún filtro.</p>
        <button onclick="document.getElementById('cmpBtnReset').click()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-all">
            <i class="fas fa-undo"></i> Quitar filtros
        </button>
    </div>

    {{-- ══════════════════════════════════════════
         RESULTADOS
    ══════════════════════════════════════════ --}}
    <div id="cmpResults" class="hidden space-y-5">

        {{-- ── INSIGHT GLOBAL (se rellena por JS) ── --}}
        <div id="cmpInsightGlobal"></div>

        {{-- ── KPI CARDS GLOBALES ── --}}
        <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 ml-1">
                <i class="fas fa-tachometer-alt mr-1"></i>Resumen general
            </p>
            <div id="cmpKpis" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"></div>
        </div>

        {{-- ── GRÁFICA 1: MONTOS ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden">
            <div class="px-5 pt-4 pb-0 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">
                        <span class="gls">
                            Cotización Ganadora
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Precio aprobado antes de iniciar la obra. Es el techo esperado de gasto.</span>
                        </span>
                        ·
                        <span class="gls">
                            Facturado
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Lo que el proveedor ya cobró. Compararlo con la cotización muestra si se salió del presupuesto.</span>
                        </span>
                        ·
                        <span class="gls">
                            Corte
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Avance físico medido en campo. Si es menor que lo facturado, puede haber una discrepancia a investigar.</span>
                        </span>
                    </h2>
                    <p id="cmpChartSubtitle" class="text-[11px] text-slate-400 mt-0.5">—</p>
                </div>
                <div class="flex flex-wrap items-center gap-4 pb-1 text-[11px] font-bold text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="inline-block w-8 h-3 rounded" style="background:#6366f1;opacity:.9"></span>Cotiz. Ganadora</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-8 h-3 rounded" style="background:#10b981"></span>Facturado</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-8 h-0 border-t-2 border-dashed" style="border-color:#f59e0b"></span>Corte</span>
                </div>
            </div>
            {{-- Interpretación de la gráfica --}}
            <div id="cmpInsightMontos" class="mt-3 mb-1"></div>
            <div id="cmpChartMontos" class="w-full" style="min-height:380px;"></div>
        </div>

        {{-- ── GRÁFICA 2: DESVIACIÓN ── --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shadow-xl overflow-hidden">
            <div class="px-5 pt-4 pb-0 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">
                        <span class="gls">
                            Desviación
                            <span class="gls-icon">?</span>
                            <span class="gls-tip">Diferencia en porcentaje. Rojo = se gastó más de lo acordado. Verde = se gastó menos.</span>
                        </span>
                        — Cotización vs Facturado · Corte vs Facturado
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Barras rojas = excedente de gasto &nbsp;·&nbsp; Barras verdes = ahorro
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-4 pb-1 text-[11px] font-bold text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="inline-block w-8 h-3 rounded" style="background:#e879f9;opacity:.9"></span>Desv. Cot→Fact</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-8 h-3 rounded" style="background:#f59e0b;opacity:.85"></span>Desv. Corte→Fact</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-8 h-0 border-t-2 border-dashed" style="border-color:#94a3b8"></span>Cero (sin desviación)</span>
                </div>
            </div>
            <div id="cmpInsightDesv" class="mt-3 mb-1"></div>
            <div id="cmpChartDesv" class="w-full" style="min-height:280px;"></div>
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
            <div class="overflow-x-auto md:overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead id="cmpTableHead" class="bg-slate-50 dark:bg-slate-950 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Insumo</th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Gerencia</th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-indigo-400 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Cotiz. Ganadora
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Precio acordado antes de iniciar el trabajo.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-emerald-500 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Facturado
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Monto real cobrado por el proveedor.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-amber-500 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Corte
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">Avance medido físicamente en campo.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Desv. Cot→Fact
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">% de diferencia entre lo cotizado y lo facturado. Positivo = se gastó más de lo acordado.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-right">
                                <span class="gls">
                                    Desv. Corte→Fact
                                    <span class="gls-icon">?</span>
                                    <span class="gls-tip">% de diferencia entre el corte de campo y lo facturado. Si es positivo, se facturó más de lo medido.</span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="cmpTableBody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                    <tfoot id="cmpTableFoot" class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 font-bold text-sm"></tfoot>
                </table>
            </div>
            {{-- Leyenda de estados debajo de la tabla --}}
            <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-4 text-[11px] text-slate-400">
                <span class="font-bold text-slate-500 mr-1">Significado del estado:</span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"><i class="fas fa-check text-[9px]"></i> OK</span>
                    Desviación menor al 5% — sin acción requerida
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"><i class="fas fa-exclamation-circle text-[9px]"></i> Alerta</span>
                    Entre 5% y 15% — monitorear
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800"><i class="fas fa-exclamation-triangle text-[9px]"></i> Crítico</span>
                    Mayor al 15% — requiere revisión inmediata
                </span>
            </div>
        </div>

    </div>{{-- /cmpResults --}}

</div>{{-- /comparativaRoot --}}

@push('facturas_scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>

<script>
(function () {
    'use strict';

    /* ── Constantes ── */
    const FMT        = new Intl.NumberFormat('es-MX', { style:'currency', currency:'MXN', maximumFractionDigits:2 });
    const MESES_FULL = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const isDark     = () => document.documentElement.classList.contains('dark');

    /* ── Mapa gerencias ── */
    const GERENCIA_MAP = {
        @foreach($gerencia as $id => $nombre)
            @if($id !== ''){{ $id }}: @json($nombre),@endif
        @endforeach
    };

    /* ── Helpers ── */
    const f    = v => (v != null && v !== 0) ? FMT.format(v) : '—';
    const pct  = v => v != null ? `${v > 0 ? '+' : ''}${parseFloat(v).toFixed(2)}%` : '—';
    // gNom: para tooltips (texto plano)
    const gNom     = (id, nombre) => nombre || GERENCIA_MAP[id] || (id ? 'Gerencia #'+id : 'Sin gerencia asignada');
    // gBadge: para celdas HTML — muestra chip "Pendiente" cuando no hay gerencia
    const gBadge   = (id, nombre) => {
        const n = nombre || GERENCIA_MAP[id] || (id ? 'Gerencia #'+id : null);
        if (n) return `<span class="text-xs text-slate-500 dark:text-slate-400">${n}</span>`;
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700 whitespace-nowrap"><i class="fas fa-hourglass-half text-[9px]"></i> Sin asignar</span>`;
    };

    function desvClass(v) {
        if (v == null)  return 'text-slate-400';
        if (v >  10)    return 'text-rose-600   dark:text-rose-400   font-bold';
        if (v >   0)    return 'text-amber-600  dark:text-amber-400  font-semibold';
        if (v <  -5)    return 'text-emerald-600 dark:text-emerald-400';
        return 'text-slate-500 dark:text-slate-400';
    }
    function estadoBadge(a, b) {
        const m = Math.max(Math.abs(a ?? 0), Math.abs(b ?? 0));
        if (m > 15) return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800"><i class="fas fa-exclamation-triangle"></i> Crítico</span>`;
        if (m >  5) return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"><i class="fas fa-exclamation-circle"></i> Alerta</span>`;
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"><i class="fas fa-check"></i> OK</span>`;
    }

    /* ══════════════════════════════════════════════════
       ANIMACIÓN HIGHCHARTS
    ══════════════════════════════════════════════════ */
    function applyHCAnimation(H) {
        const animPath = (el, anim) => {
            if (!el?.element) return;
            try {
                const len = el.element.getTotalLength();
                el.attr({ 'stroke-dasharray': len, 'stroke-dashoffset': len, opacity: 1 });
                el.animate({ 'stroke-dashoffset': 0 }, anim);
            } catch(e) {}
        };
        ['line','spline'].forEach(t => {
            if (H.seriesTypes[t]) {
                H.seriesTypes[t].prototype.animate = function(init) {
                    if (!init && this.graph) animPath(this.graph, H.animObject(this.options.animation));
                };
            }
        });
    }

    /* ══════════════════════════════════════════════════
       INSIGHT BARS — interpretación automática de datos
    ══════════════════════════════════════════════════ */
    function renderInsights(insumos) {
        const totCot  = insumos.reduce((s,i) => s + (i.metricas.cotizacion_seleccionada || 0), 0);
        const totFact = insumos.reduce((s,i) => s + (i.metricas.total_facturado || 0), 0);
        const criticos = insumos.filter(i => Math.abs(i.metricas.desviacion_cot_fact_pct ?? 0) > 15);
        const alertas  = insumos.filter(i => {
            const v = Math.abs(i.metricas.desviacion_cot_fact_pct ?? 0);
            return v > 5 && v <= 15;
        });
        const difPct   = totCot > 0 ? ((totFact - totCot) / totCot) * 100 : null;
        const excedente = totFact > totCot;

        // ── Insight global ──
        let gType, gIcon, gMsg;
        if (criticos.length > 0) {
            gType = 'alert'; gIcon = 'fa-exclamation-triangle';
            gMsg  = `<strong>${criticos.length} insumo${criticos.length!==1?'s':''} con desviación crítica (&gt;15%):</strong> ${criticos.map(i=>i.nombre).join(', ')}. Se recomienda revisar sus contratos y solicitar justificación al proveedor.`;
        } else if (alertas.length > 0) {
            gType = 'warn'; gIcon = 'fa-exclamation-circle';
            gMsg  = `<strong>${alertas.length} insumo${alertas.length!==1?'s':''} en alerta (5–15% de desviación):</strong> ${alertas.map(i=>i.nombre).join(', ')}. Monitorea su evolución en las próximas semanas.`;
        } else {
            gType = 'ok'; gIcon = 'fa-check-circle';
            gMsg  = difPct != null
                ? `Todos los insumos están dentro del rango aceptable. La desviación global es de <strong>${pct(difPct)}</strong> — ${excedente ? 'ligero excedente' : 'ahorro'} respecto a la cotización.`
                : 'Todos los insumos están dentro del rango aceptable. Sin alertas activas.';
        }
        document.getElementById('cmpInsightGlobal').innerHTML =
            `<div class="insight-bar ${gType}"><i class="fas ${gIcon} mt-0.5 flex-shrink-0"></i><span>${gMsg}</span></div>`;

        // ── Insight gráfica montos ──
        const maxDesv = insumos.reduce((m,i) => Math.max(m, i.metricas.desviacion_cot_fact_pct ?? 0), 0);
        let mType, mMsg;
        if (maxDesv > 10) {
            mType = 'alert';
            mMsg  = `Las barras verdes que superan las azules indican insumos donde se <strong>gastó más de lo cotizado</strong>. Revisa los que están marcados en rojo.`;
        } else if (maxDesv > 0) {
            mType = 'warn';
            mMsg  = `En general los montos facturados son cercanos a la cotización. Algunos insumos muestran una leve diferencia — mantén el monitoreo.`;
        } else {
            mType = 'ok';
            mMsg  = `Los montos facturados están por debajo o al nivel de la cotización — buen control de costos en el período seleccionado.`;
        }
        document.getElementById('cmpInsightMontos').innerHTML =
            `<div class="insight-bar ${mType}"><i class="fas fa-chart-bar mt-0.5 flex-shrink-0"></i><span>${mMsg}</span></div>`;

        // ── Insight gráfica desviación ──
        const hayCorte = insumos.some(i => i.metricas.total_cortes);
        let dType, dMsg;
        if (criticos.length > 0) {
            dType = 'alert';
            dMsg  = `Las barras que más sobresalen hacia la derecha son los insumos con mayor excedente. <strong>Prioriza los que superan el 15%</strong> (marcados en rojo).`;
        } else if (!hayCorte) {
            dType = 'warn';
            dMsg  = `No hay datos de corte de campo para este período. La comparación Corte→Facturado no está disponible — solo se muestra la desviación Cotización→Facturado.`;
        } else {
            dType = 'ok';
            dMsg  = `Las desviaciones están dentro de rangos controlados. Si una barra Corte→Fact es positiva, el proveedor facturó más de lo medido en campo.`;
        }
        document.getElementById('cmpInsightDesv').innerHTML =
            `<div class="insight-bar ${dType}"><i class="fas fa-chart-bar mt-0.5 flex-shrink-0"></i><span>${dMsg}</span></div>`;
    }

    /* ══════════════════════════════════════════════════
       GRÁFICAS
    ══════════════════════════════════════════════════ */
    let chartMontos = null;
    let chartDesv   = null;

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

        const cats         = insumos.map(i => i.nombre.length > 22 ? i.nombre.substring(0,22)+'…' : i.nombre);
        const cotData      = [], factData = [], corteData = [];
        const desvCFData   = [], desvCortFData = [];

        insumos.forEach(ins => {
            const m   = ins.metricas;
            const desv = m.desviacion_cot_fact_pct;
            const fc  = desv == null ? '#10b981' : desv > 10 ? '#f43f5e' : desv > 0 ? '#f59e0b' : '#10b981';

            cotData.push(  { y: m.cotizacion_seleccionada ?? 0, name: ins.nombre });
            factData.push( { y: m.total_facturado ?? 0, name: ins.nombre, color: fc });
            corteData.push({ y: m.total_cortes || null, name: ins.nombre });

            const dcf = m.desviacion_cot_fact_pct;
            const dco = m.desviacion_corte_fact_pct;
            desvCFData.push({ y: dcf, name: ins.nombre, color: dcf == null ? '#94a3b8' : dcf > 0 ? '#f43f5e' : '#10b981' });
            desvCortFData.push({ y: dco, name: ins.nombre, color: dco == null ? '#94a3b8' : dco > 0 ? '#f97316' : '#06b6d4' });
        });

        function makeTooltip(type) {
            return {
                useHTML: true,
                backgroundColor: ttBg, borderColor: ttBdr, borderRadius: 12,
                borderWidth: 1, shadow: true,
                style: { color: ttClr, fontSize: '12px', padding: '10px' },
                formatter: function () {
                    const idx = this.point.index;
                    const ins = insumos[idx];
                    if (!ins) return '';
                    const m  = ins.metricas;
                    const dp = m.desviacion_cot_fact_pct;
                    const dc = m.desviacion_corte_fact_pct;
                    const dpc = dp == null ? ttClr : dp > 10 ? '#f43f5e' : dp > 0 ? '#f59e0b' : '#10b981';
                    if (type === 'montos') {
                        const interpret = dp == null ? '' : dp > 10
                            ? `<div style="margin-top:8px;font-size:11px;color:#f43f5e;font-weight:700">⚠ Se facturó más de lo cotizado — revisar</div>`
                            : dp > 0
                            ? `<div style="margin-top:8px;font-size:11px;color:#f59e0b">↑ Leve excedente — monitorear</div>`
                            : `<div style="margin-top:8px;font-size:11px;color:#10b981">✓ Dentro o por debajo de cotización</div>`;
                        return `<div style="min-width:220px">
                            <div style="font-weight:800;font-size:13px;margin-bottom:4px;white-space:normal">${ins.nombre}</div>
                            <div style="color:#94a3b8;font-size:10px;margin-bottom:10px">${gNom(ins.gerencia_id, ins.gerencia)}</div>
                            <div style="display:flex;justify-content:space-between;gap:20px;margin-bottom:4px">
                                <span style="color:#818cf8;font-weight:600">Cotización acordada</span>
                                <span style="font-family:monospace;font-weight:700">${f(m.cotizacion_seleccionada)}</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;gap:20px;margin-bottom:4px">
                                <span style="color:${dpc};font-weight:600">Lo que se facturó</span>
                                <span style="font-family:monospace;font-weight:700;color:${dpc}">${f(m.total_facturado)}</span>
                            </div>
                            ${m.total_cortes ? `<div style="display:flex;justify-content:space-between;gap:20px">
                                <span style="color:#fbbf24;font-weight:600">Corte de campo</span>
                                <span style="font-family:monospace;font-weight:700">${f(m.total_cortes)}</span>
                            </div>` : '<div style="font-size:10px;color:#94a3b8;margin-top:4px">Sin datos de corte de campo</div>'}
                            ${interpret}
                        </div>`;
                    } else {
                        const dint = dp == null ? '' : dp > 10
                            ? `<div style="margin-top:8px;font-size:11px;color:#f43f5e;font-weight:700">⚠ Requiere revisión del contrato</div>`
                            : dp > 0
                            ? `<div style="margin-top:8px;font-size:11px;color:#f59e0b">Monitorear en próximas semanas</div>`
                            : `<div style="margin-top:8px;font-size:11px;color:#10b981">✓ Sin acción requerida</div>`;
                        return `<div style="min-width:220px">
                            <div style="font-weight:800;font-size:13px;margin-bottom:4px;white-space:normal">${ins.nombre}</div>
                            <div style="color:#94a3b8;font-size:10px;margin-bottom:10px">${gNom(ins.gerencia_id, ins.gerencia)}</div>
                            <div style="display:flex;justify-content:space-between;gap:20px;margin-bottom:4px">
                                <span style="color:${dpc};font-weight:700">Cotiz. vs Facturado</span>
                                <span style="color:${dpc};font-family:monospace;font-weight:800">${pct(dp)}</span>
                            </div>
                            ${dc != null ? `<div style="display:flex;justify-content:space-between;gap:20px;margin-top:4px">
                                <span style="color:#fb923c;font-weight:600">Corte vs Facturado</span>
                                <span style="font-family:monospace;font-weight:700">${pct(dc)}</span>
                            </div>` : '<div style="font-size:10px;color:#94a3b8;margin-top:4px">Sin datos de corte de campo</div>'}
                            ${dint}
                        </div>`;
                    }
                }
            };
        }

        const xAxisBase = {
            categories: cats,
            lineColor: axisClr, tickColor: axisClr,
            labels: {
                style: { color: lblClr, fontSize: '11px', fontWeight: '600' },
                rotation: cats.length > 6 ? -35 : 0
            }
        };

        chartMontos = Highcharts.chart('cmpChartMontos', {
            chart: { type:'column', backgroundColor:'transparent', style:{fontFamily:'inherit'}, height:380, marginTop:15, animation:{duration:700} },
            title: { text:null }, credits: { enabled:false },
            legend: { enabled:true, align:'center', verticalAlign:'bottom', itemStyle:{ color:lblClr, fontWeight:'600', fontSize:'11px' } },
            tooltip: makeTooltip('montos'),
            xAxis: xAxisBase,
            yAxis: {
                title: { text: null }, gridLineColor: gridClr,
                labels: {
                    style: { color:lblClr, fontSize:'11px' },
                    formatter: function () {
                        const v = Math.abs(this.value);
                        if (v >= 1000000) return '$'+(this.value/1000000).toFixed(1)+'M';
                        if (v >= 1000)    return '$'+(this.value/1000).toFixed(0)+'k';
                        return '$'+this.value;
                    }
                }
            },
            plotOptions: {
                column: { borderRadius:5, groupPadding:.15, pointPadding:.05, animation:{duration:700} },
                spline:  { lineWidth:2.5, marker:{ enabled:true, radius:6, symbol:'diamond' }, animation:{duration:900,defer:400} }
            },
            series: [
                { type:'column', name:'Cotización acordada', data:cotData,  color:'#6366f1', opacity:.85 },
                { type:'column', name:'Facturado',           data:factData, colorByPoint:true, animation:{duration:700,defer:200} },
                { type:'spline', name:'Corte de campo',      data:corteData, color:'#f59e0b', connectNulls:true }
            ],
            responsive: { rules:[{ condition:{maxWidth:520}, chartOptions:{ xAxis:{ labels:{ rotation:-55, style:{fontSize:'10px'} } }, chart:{height:300} } }] }
        });

        chartDesv = Highcharts.chart('cmpChartDesv', {
            chart: { type:'bar', backgroundColor:'transparent', style:{fontFamily:'inherit'}, height:Math.max(280, insumos.length*60+80), marginTop:15, animation:{duration:800} },
            title: { text:null }, credits: { enabled:false },
            legend: { enabled:true, align:'center', verticalAlign:'bottom', itemStyle:{ color:lblClr, fontWeight:'600', fontSize:'11px' } },
            tooltip: makeTooltip('desv'),
            xAxis: { categories:cats, lineColor:axisClr, tickColor:axisClr, labels:{ style:{color:lblClr, fontSize:'11px', fontWeight:'600'} } },
            yAxis: {
                title: { text:null }, gridLineColor:gridClr,
                labels: { format:'{value:.1f}%', style:{ color:lblClr, fontSize:'11px' } },
                plotLines: [{ value:0, color: dark?'#64748b':'#94a3b8', width:2, zIndex:5, dashStyle:'Dash',
                    label:{ text:'Sin desviación', style:{ color:lblClr, fontSize:'10px', fontWeight:'700' } }
                }]
            },
            plotOptions: { bar: {
                borderRadius:4, groupPadding:.15, pointPadding:.05, animation:{duration:800},
                dataLabels: { enabled:true, useHTML:true,
                    formatter: function() {
                        if (this.y == null) return '';
                        return `<span style="font-family:monospace;font-size:10px;font-weight:700;color:${this.point.color}">${this.y>0?'+':''}${this.y.toFixed(1)}%</span>`;
                    }, inside:false
                }
            }},
            series: [
                { name:'Cotiz. → Facturado',   data:desvCFData,   colorByPoint:true },
                { name:'Corte → Facturado',    data:desvCortFData, colorByPoint:true, animation:{duration:800,defer:300} }
            ],
            responsive: { rules:[{ condition:{maxWidth:520}, chartOptions:{ chart:{height:Math.max(220, insumos.length*50+60)} } }] }
        });
    }

    /* ══════════════════════════════════════════════════
       KPI CARDS — con etiqueta de acción
    ══════════════════════════════════════════════════ */
    function renderKpis(insumos) {
        const totCot   = insumos.reduce((s,i) => s + (i.metricas.cotizacion_seleccionada || 0), 0);
        const totFact  = insumos.reduce((s,i) => s + (i.metricas.total_facturado || 0), 0);
        const totCorte = insumos.reduce((s,i) => s + (i.metricas.total_cortes || 0), 0);
        const dif      = totFact - totCot;
        const difPct   = totCot > 0 ? (dif / totCot) * 100 : null;
        const alertas  = insumos.filter(i => Math.abs(i.metricas.desviacion_cot_fact_pct ?? 0) > 10).length;

        // Función helper para la etiqueta de acción
        function accion(nivel, texto) {
            const clases = {
                ok:   'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
                warn: 'bg-amber-100  dark:bg-amber-900/30  text-amber-700  dark:text-amber-300',
                bad:  'bg-rose-100   dark:bg-rose-900/30   text-rose-700   dark:text-rose-300',
            };
            return `<span class="inline-block mt-2 px-2 py-0.5 rounded-full text-[10px] font-bold ${clases[nivel]}">${texto}</span>`;
        }

        const cards = [
            {
                label: 'Total Cotizado',
                value: f(totCot),
                sub: `${insumos.length} insumo${insumos.length!==1?'s':''} en el período`,
                action: accion('ok', 'Presupuesto base de comparación'),
                icon: 'fa-file-signature', theme: 'indigo'
            },
            {
                label: 'Total Facturado',
                value: f(totFact),
                sub: dif >= 0
                    ? `<span class="text-rose-500 font-semibold">+${f(dif)} sobre lo cotizado</span>`
                    : `<span class="text-emerald-500 font-semibold">${f(Math.abs(dif))} por debajo de lo cotizado</span>`,
                action: dif > totCot * 0.1
                    ? accion('bad',  'Revisar contratos con proveedor')
                    : dif > 0
                    ? accion('warn', 'Monitorear tendencia')
                    : accion('ok',   'Sin acción requerida'),
                icon: 'fa-receipt', theme: dif > 0 ? 'rose' : 'emerald'
            },
            {
                label: 'Total Cortes de Campo',
                value: totCorte > 0 ? f(totCorte) : '—',
                sub: totCorte > 0 ? `${f(Math.abs(totFact - totCorte))} de diferencia vs facturado` : 'No hay datos de corte para este período',
                action: totCorte > 0
                    ? (totFact > totCorte * 1.1 ? accion('warn', 'Factura supera el corte — verificar') : accion('ok', 'Corte y factura alineados'))
                    : accion('warn', 'Solicitar corte de campo'),
                icon: 'fa-scissors', theme: 'amber'
            },
            {
                label: 'Desviación Global',
                value: difPct != null ? pct(difPct) : '—',
                sub: difPct == null ? 'Sin cotizaciones en el período'
                   : Math.abs(difPct) < 5 ? 'Dentro del rango aceptable (&lt;5%)'
                   : difPct > 0 ? `Excedente de ${f(dif)}`
                   : `Ahorro de ${f(Math.abs(dif))}`,
                action: difPct == null ? accion('warn', 'Sin datos suficientes')
                    : Math.abs(difPct) > 15 ? accion('bad',  'Revisión urgente de contratos')
                    : Math.abs(difPct) > 5  ? accion('warn', 'Monitorear semana a semana')
                    : accion('ok', 'Sin acción requerida'),
                icon: 'fa-chart-line', theme: difPct == null ? 'slate' : difPct > 10 ? 'rose' : difPct > 0 ? 'amber' : 'emerald'
            },
            {
                label: 'Insumos con Alerta',
                value: alertas === 0 ? 'Ninguno' : `${alertas} insumo${alertas!==1?'s':''}`,
                sub: alertas === 0 ? 'Todos los insumos con desviación &lt;10%' : `Con desviación mayor al 10%`,
                action: alertas === 0
                    ? accion('ok',  'Sin acción requerida')
                    : alertas > 3
                    ? accion('bad',  'Atención inmediata')
                    : accion('warn', 'Revisar en la próxima reunión'),
                icon: alertas === 0 ? 'fa-shield-check' : 'fa-exclamation-triangle',
                theme: alertas === 0 ? 'emerald' : 'rose'
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
                <div class="text-[11px] text-slate-400 mt-1">${c.sub}</div>
                <div class="mt-auto pt-2">${c.action}</div>
            </div>`;
        }).join('');
    }

    /* ══════════════════════════════════════════════════
       TABLA — responsiva
       IMPORTANTE: El responsive usa style inline (display:none/table-row)
       en lugar de clases Tailwind (md:hidden / hidden md:table-row)
       porque esas clases dinámicas son purgadas por Tailwind en producción.
       El breakpoint se aplica via JS al cargar y al resize.
    ══════════════════════════════════════════════════ */
    function desvColor(v) {
        if (v == null)  return '#94a3b8';
        if (v >  10)    return '#f43f5e';
        if (v >   0)    return '#f59e0b';
        if (v <   0)    return '#10b981';
        return '#94a3b8';
    }

    function renderTable(insumos) {
        let totCot = 0, totFact = 0, totCorte = 0;
        const isMobile = () => window.innerWidth < 768;

        document.getElementById('cmpTableBody').innerHTML = insumos.map((ins, idx) => {
            const m   = ins.metricas;
            const dcf = m.desviacion_cot_fact_pct;
            const dco = m.desviacion_corte_fact_pct;
            totCot   += m.cotizacion_seleccionada || 0;
            totFact  += m.total_facturado         || 0;
            totCorte += m.total_cortes            || 0;

            const barW  = Math.min(Math.abs(dcf ?? 0), 100);
            const barC  = desvColor(dcf);
            const dcfC  = desvColor(dcf);
            const dcoC  = desvColor(dco);
            const mob   = isMobile();

            // ── Fila desktop ──────────────────────────────────────
            const desktop = `
            <tr class="cmp-row-desk" style="display:${mob?'none':'table-row'};border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 16px;font-weight:600;font-size:13px;max-width:200px;">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:${barC}"></span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${ins.nombre}">${ins.nombre}</span>
                    </span>
                </td>
                <td style="padding:10px 16px;font-size:12px;">${gBadge(ins.gerencia_id, ins.gerencia)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:13px;color:#6366f1;">${f(m.cotizacion_seleccionada)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:13px;font-weight:700;color:${dcfC};">${f(m.total_facturado)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:13px;color:#f59e0b;">${f(m.total_cortes)}</td>
                <td style="padding:10px 16px;text-align:right;">
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;">
                        <span style="font-family:monospace;font-size:13px;font-weight:700;color:${dcfC};">${pct(dcf)}</span>
                        <div style="width:60px;height:5px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                            <div style="width:${barW}%;height:100%;background:${barC};border-radius:99px;"></div>
                        </div>
                    </div>
                </td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-size:13px;font-weight:600;color:${dcoC};">${pct(dco)}</td>
                <td style="padding:10px 16px;text-align:center;">${estadoBadge(dcf, dco)}</td>
            </tr>`;

            // ── Fila mobile (tarjeta) ──────────────────────────────
            const mobile = `
            <tr class="cmp-row-mob" style="display:${mob?'table-row':'none'};border-bottom:1px solid #f1f5f9;">
                <td colspan="8" style="padding:12px 16px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:${barC};margin-top:2px;"></span>
                            <span style="font-weight:600;font-size:14px;line-height:1.3;">${ins.nombre}</span>
                        </div>
                        <div style="flex-shrink:0;">${estadoBadge(dcf, dco)}</div>
                    </div>
                    <div style="margin-left:18px;">
                        <div style="margin-bottom:8px;">${gBadge(ins.gerencia_id, ins.gerencia)}</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;font-size:12px;">
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:2px;">Cotización</div>
                                <div style="font-family:monospace;font-weight:600;color:#6366f1;">${f(m.cotizacion_seleccionada)}</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:2px;">Facturado</div>
                                <div style="font-family:monospace;font-weight:700;color:${dcfC};">${f(m.total_facturado)}</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:2px;">Corte de campo</div>
                                <div style="font-family:monospace;color:#f59e0b;">${f(m.total_cortes)}</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:2px;">Desv. Cot→Fact</div>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-family:monospace;font-weight:700;color:${dcfC};">${pct(dcf)}</span>
                                    <div style="flex:1;max-width:48px;height:4px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                                        <div style="width:${barW}%;height:100%;background:${barC};"></div>
                                    </div>
                                </div>
                            </div>
                            ${dco != null ? `<div style="grid-column:1/-1;">
                                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:2px;">Desv. Corte→Fact</div>
                                <div style="font-family:monospace;font-weight:600;color:${dcoC};">${pct(dco)}</div>
                            </div>` : ''}
                        </div>
                    </div>
                </td>
            </tr>`;

            return desktop + mobile;
        }).join('');

        // ── Footer ────────────────────────────────────────────────
        const difT  = totFact - totCot;
        const difTP = totCot > 0 ? (difT / totCot) * 100 : null;
        const difC  = desvColor(difTP);
        const mob   = isMobile();

        document.getElementById('cmpTableFoot').innerHTML = `
            <tr class="cmp-foot-desk" style="display:${mob?'none':'table-row'};background:#f8fafc;">
                <td colspan="2" style="padding:10px 16px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Total general</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;color:#6366f1;font-weight:700;">${f(totCot)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:${difC};">${f(totFact)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;color:#f59e0b;">${f(totCorte || null)}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:${difC};">${pct(difTP)}</td>
                <td colspan="2"></td>
            </tr>
            <tr class="cmp-foot-mob" style="display:${mob?'table-row':'none'};background:#f8fafc;">
                <td colspan="8" style="padding:12px 16px;">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px;">Total general</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;font-size:12px;">
                        <div><span style="color:#94a3b8;">Cotizado: </span><span style="font-family:monospace;font-weight:700;color:#6366f1;">${f(totCot)}</span></div>
                        <div><span style="color:#94a3b8;">Facturado: </span><span style="font-family:monospace;font-weight:700;color:${difC};">${f(totFact)}</span></div>
                        <div><span style="color:#94a3b8;">Cortes: </span><span style="font-family:monospace;color:#f59e0b;">${f(totCorte || null)}</span></div>
                        <div><span style="color:#94a3b8;">Desviación: </span><span style="font-family:monospace;font-weight:700;color:${difC};">${pct(difTP)}</span></div>
                    </div>
                </td>
            </tr>`;

        document.getElementById('cmpTotalRows').textContent =
            `${insumos.length} insumo${insumos.length!==1?'s':''}`;

        // Reaplica visibilidad al hacer resize
        const applyBreakpoint = () => {
            const m = window.innerWidth < 768;
            document.querySelectorAll('.cmp-row-desk,.cmp-foot-desk').forEach(r => r.style.display = m ? 'none' : 'table-row');
            document.querySelectorAll('.cmp-row-mob,.cmp-foot-mob').forEach(r => r.style.display = m ? 'table-row' : 'none');
        };
        if (!window._cmpResizeAttached) {
            window.addEventListener('resize', applyBreakpoint);
            window._cmpResizeAttached = true;
        }
    }

    /* ══════════════════════════════════════════════════
       SUBTÍTULO y FILTROS ACTIVOS
    ══════════════════════════════════════════════════ */
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
        const vals  = [
            document.getElementById('cmpGerencia').value,
            document.getElementById('cmpMes').value,
            document.getElementById('cmpAnio').value,
            document.getElementById('cmpInsumo').value.trim()
        ].filter(Boolean).length;
        if (vals > 0) {
            badge.textContent = `${vals} filtro${vals!==1?'s':''} activo${vals!==1?'s':''}`;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    /* ══════════════════════════════════════════════════
       CARGA AJAX
    ══════════════════════════════════════════════════ */
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
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
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

    /* ══════════════════════════════════════════════════
       EVENTOS
    ══════════════════════════════════════════════════ */
    document.getElementById('formComparativaFilter').addEventListener('submit', e => {
        e.preventDefault();
        cargarComparativa();
    });

    document.getElementById('cmpBtnReset').addEventListener('click', () => {
        document.getElementById('cmpGerencia').value = '';
        document.getElementById('cmpMes').value      = '';
        document.getElementById('cmpAnio').value     = '{{ date("Y") }}';
        document.getElementById('cmpInsumo').value   = '';
        cargarComparativa();
    });

    ['cmpGerencia', 'cmpMes', 'cmpAnio'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => cargarComparativa());
    });

    let _cmpCargado = false;

    window.initComparativa = function () {
        if (!_cmpCargado) {
            _cmpCargado = true;
            cargarComparativa();
        } else {
            // Ya tiene datos — solo reflow de gráficas por si el contenedor cambió de tamaño
            if (typeof Highcharts !== 'undefined') {
                setTimeout(() => {
                    Highcharts.charts.forEach(c => { if (c) c.reflow(); });
                }, 60);
            }
        }
    };

    // Carga automática: si el tab de comparativa ya es el activo al cargar la página,
    // disparar inmediatamente. Si no, initComparativa() lo hace al cambiar de tab.
    function autoInit() {
        const contenedor = document.getElementById('content-historial');
        if (contenedor && contenedor.classList.contains('active')) {
            cargarComparativa();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

})();
</script>
@endpush