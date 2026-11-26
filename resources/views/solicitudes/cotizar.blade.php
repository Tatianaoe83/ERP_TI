@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }

    /* ── Cotizar: tokens ── */
    .cz {
        --cz-surface: #fff;
        --cz-sunken: #f4f7fb;
        --cz-line: #e2e8f0;
        --cz-ink: #0a1628;
        --cz-muted: #5b6b82;
        --cz-faint: #94a3b8;
        --cz-primary: #0052cc;
        --cz-primary-soft: rgba(0, 82, 204, 0.08);
        --cz-best: #00875a;
        --cz-best-soft: rgba(0, 135, 90, 0.08);
        --cz-danger: #de350b;
    }
    .dark .cz {
        --cz-surface: #121d30;
        --cz-sunken: #0c1526;
        --cz-line: rgba(255, 255, 255, 0.09);
        --cz-ink: #f1f5f9;
        --cz-muted: #a3b3c9;
        --cz-faint: #64748b;
        --cz-primary: #4c9aff;
        --cz-primary-soft: rgba(76, 154, 255, 0.12);
        --cz-best: #57d9a3;
        --cz-best-soft: rgba(87, 217, 163, 0.1);
        --cz-danger: #ff8f73;
    }
    .cz-num { font-family: var(--erp-font-display, "Space Grotesk", sans-serif); font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }

    .cz-layout { display: grid; gap: 1.25rem; align-items: start; }
    @media (min-width: 1280px) {
        .cz-layout { grid-template-columns: 300px minmax(0, 1fr); }
        .cz-aside { position: sticky; top: 1rem; }
    }

    /* ── Panel lateral ── */
    .cz-aside { display: flex; flex-direction: column; gap: 1rem; }
    .cz-panel { background: var(--cz-surface); border: 1px solid var(--cz-line); border-radius: 1rem; }
    .cz-panel__head { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; padding: 1rem 1.1rem .75rem; }
    .cz-panel__title { margin: 0; font-size: .95rem; font-weight: 700; color: var(--cz-ink); }
    .cz-panel__meta { font-size: .78rem; color: var(--cz-muted); }

    .cz-total { margin: 0 1.1rem; padding: .9rem 1rem; border-radius: .8rem; background: var(--cz-sunken); }
    .cz-total__label { margin: 0; font-size: .78rem; color: var(--cz-muted); }
    .cz-total__value { margin: .1rem 0 0; font-size: 1.6rem; font-weight: 700; line-height: 1.15; color: var(--cz-ink); }
    .cz-total__sub { margin: .25rem 0 0; font-size: .75rem; color: var(--cz-muted); }

    .cz-sum { list-style: none; margin: 0; padding: .5rem 1.1rem 1rem; }
    .cz-sum__prop { padding: .7rem 0; border-bottom: 1px dashed var(--cz-line); }
    .cz-sum__prop:last-child { border-bottom: 0; padding-bottom: 0; }
    .cz-sum__row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .cz-sum__name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .85rem; font-weight: 600; color: var(--cz-ink); }
    .cz-sum__tag { flex-shrink: 0; font-size: .7rem; font-weight: 600; color: var(--cz-primary); background: var(--cz-primary-soft); padding: .1rem .5rem; border-radius: 99px; }
    .cz-sum__prod { display: flex; justify-content: space-between; gap: .5rem; margin-top: .35rem; font-size: .78rem; color: var(--cz-muted); }
    .cz-sum__prod span:first-child { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cz-sum__best { display: flex; justify-content: space-between; gap: .5rem; margin-top: .15rem; font-size: .75rem; color: var(--cz-best); }
    .cz-sum__best span:first-child { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cz-sum__hint { margin: .3rem 0 0; font-size: .72rem; color: var(--cz-faint); }

    .cz-info { padding: 0 1.1rem 1.1rem; display: flex; flex-direction: column; gap: .85rem; }
    .cz-info__label { margin: 0 0 .25rem; font-size: .75rem; font-weight: 600; color: var(--cz-muted); }
    .cz-info__text { margin: 0; font-size: .82rem; line-height: 1.55; color: var(--cz-ink); white-space: pre-wrap; }

    .cz-help { border-top: 1px solid var(--cz-line); }
    .cz-help summary { display: flex; align-items: center; justify-content: space-between; padding: .8rem 1.1rem; cursor: pointer; list-style: none; font-size: .82rem; font-weight: 600; color: var(--cz-muted); }
    .cz-help summary::-webkit-details-marker { display: none; }
    .cz-help summary i { transition: transform .2s; }
    .cz-help[open] summary i { transform: rotate(180deg); }
    .cz-steps { margin: 0; padding: 0 1.1rem 1rem 2.4rem; font-size: .8rem; line-height: 1.7; color: var(--cz-muted); }

    /* ── Avisos ── */
    .cz-note { display: flex; gap: .85rem; padding: .9rem 1rem; border-radius: .9rem; border: 1px solid; }
    .cz-note__icon { flex-shrink: 0; width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; border-radius: .6rem; font-size: .85rem; }
    .cz-note__title { margin: 0; font-size: .875rem; font-weight: 700; }
    .cz-note__text { margin: .15rem 0 0; font-size: .82rem; line-height: 1.5; }
    .cz-note--warn { background: #fffae6; border-color: #ffe380; color: #7a4d00; }
    .cz-note--warn .cz-note__icon { background: #ffab00; color: #172b4d; }
    .cz-note--info { background: #deebff; border-color: #b3d4ff; color: #0747a6; }
    .cz-note--info .cz-note__icon { background: #0052cc; color: #fff; }
    .dark .cz-note--warn { background: rgba(255, 171, 0, 0.1); border-color: rgba(255, 171, 0, 0.3); color: #ffd666; }
    .dark .cz-note--info { background: rgba(76, 154, 255, 0.1); border-color: rgba(76, 154, 255, 0.3); color: #a8ccff; }

    /* ── Propuesta ── */
    .cz-main { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }
    .cz-prop { background: var(--cz-surface); border: 1px solid var(--cz-line); border-radius: 1rem; overflow: hidden; }
    .cz-prop__head { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; padding: .9rem 1.1rem; border-bottom: 1px solid var(--cz-line); }
    .cz-prop__badge { flex-shrink: 0; width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center; border-radius: .7rem; background: var(--cz-ink); color: var(--cz-surface); font-size: .85rem; font-weight: 700; }
    .cz-prop__name {
        flex: 1 1 14rem; min-width: 0; padding: .35rem .55rem; margin-left: -.55rem;
        font-size: 1.05rem; font-weight: 700; color: var(--cz-ink);
        background: transparent; border: 1px solid transparent; border-radius: .55rem; transition: border-color .15s, background-color .15s;
    }
    .cz-prop__name:hover { border-color: var(--cz-line); }
    .cz-prop__name:focus { outline: none; border-color: var(--cz-primary); background: var(--cz-sunken); }
    .cz-prop__stats { font-size: .78rem; color: var(--cz-muted); white-space: nowrap; }

    .cz-qty { display: inline-flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--cz-muted); }
    .cz-qty__box { display: inline-flex; align-items: center; border: 1px solid var(--cz-line); border-radius: .6rem; overflow: hidden; background: var(--cz-sunken); }
    .cz-qty__btn { width: 2rem; height: 2.25rem; border: 0; background: transparent; color: var(--cz-muted); cursor: pointer; transition: background-color .15s, color .15s; }
    .cz-qty__btn:hover { background: var(--cz-primary-soft); color: var(--cz-primary); }
    .cz-qty__input { width: 3rem; height: 2.25rem; border: 0; background: transparent; text-align: center; font-weight: 700; color: var(--cz-ink); -moz-appearance: textfield; }
    .cz-qty__input::-webkit-outer-spin-button, .cz-qty__input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .cz-qty__input:focus { outline: none; }
    .cz-qty__box:focus-within { border-color: var(--cz-primary); }

    .cz-icon-btn { width: 2.25rem; height: 2.25rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: .6rem; background: transparent; color: var(--cz-faint); cursor: pointer; transition: background-color .15s, color .15s; }
    .cz-icon-btn:hover { background: rgba(222, 53, 11, 0.08); color: var(--cz-danger); }
    .cz-icon-btn:focus-visible, .cz-btn:focus-visible, .cz-add:focus-visible, .cz-qty__btn:focus-visible, .cz-prod__toggle:focus-visible { outline: 2px solid var(--cz-primary); outline-offset: 2px; }

    .cz-prop__body { padding: 1rem 1.1rem 1.1rem; display: flex; flex-direction: column; gap: .75rem; background: var(--cz-sunken); }

    /* ── Producto ── */
    .cz-prod { background: var(--cz-surface); border: 1px solid var(--cz-line); border-radius: .85rem; overflow: hidden; }
    .cz-prod__head { display: flex; align-items: center; gap: .5rem; padding: .35rem .5rem .35rem .35rem; }
    .cz-prod__toggle { flex: 1; min-width: 0; display: flex; align-items: center; gap: .65rem; padding: .45rem .5rem; border: 0; border-radius: .6rem; background: transparent; text-align: left; cursor: pointer; color: var(--cz-ink); }
    .cz-prod__toggle:hover { background: var(--cz-sunken); }
    .cz-prod__chev { width: 1rem; color: var(--cz-faint); font-size: .75rem; transition: transform .2s; }
    .cz-prod__chev.is-open { transform: rotate(90deg); }
    .cz-prod__name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .92rem; font-weight: 600; }
    .cz-chip { flex-shrink: 0; font-size: .7rem; font-weight: 600; padding: .12rem .5rem; border-radius: 99px; background: var(--cz-sunken); color: var(--cz-muted); border: 1px solid var(--cz-line); }
    .cz-prod__best { margin-left: auto; flex-shrink: 0; font-size: .8rem; color: var(--cz-muted); white-space: nowrap; }
    .cz-prod__best strong { color: var(--cz-best); font-weight: 700; }

    .cz-prod__body { padding: .25rem 1rem 1rem; border-top: 1px solid var(--cz-line); }
    .cz-fields { display: grid; gap: .75rem; margin: .85rem 0 1rem; }
    @media (min-width: 768px) { .cz-fields { grid-template-columns: minmax(0, 1fr) 11rem; } }
    .cz-label { display: block; margin-bottom: .3rem; font-size: .75rem; font-weight: 600; color: var(--cz-muted); }
    .cz-input {
        width: 100%; height: 2.5rem; padding: 0 .75rem; font-size: .875rem; color: var(--cz-ink);
        background: var(--cz-sunken); border: 1px solid var(--cz-line); border-radius: .6rem;
        transition: border-color .15s, box-shadow .15s, background-color .15s;
    }
    .cz-input::placeholder { color: var(--cz-faint); }
    .cz-input:focus { outline: none; border-color: var(--cz-primary); box-shadow: 0 0 0 3px var(--cz-primary-soft); background: var(--cz-surface); }
    select.cz-input { padding-right: 2rem; }
    .dark select.cz-input option { background: #121d30; color: #f1f5f9; }

    /* ── Tabla de cotizaciones ── */
    .cz-table-wrap { overflow-x: auto; border: 1px solid var(--cz-line); border-radius: .75rem; }
    .cz-table { width: 100%; min-width: 760px; border-collapse: collapse; font-size: .85rem; }
    .cz-table th { padding: .6rem .6rem; text-align: left; font-size: .72rem; font-weight: 600; color: var(--cz-muted); background: var(--cz-sunken); border-bottom: 1px solid var(--cz-line); white-space: nowrap; }
    .cz-table th.is-num, .cz-table td.is-num { text-align: right; }
    .cz-table td { padding: .4rem .4rem; border-bottom: 1px solid var(--cz-line); vertical-align: middle; }
    .cz-table tbody tr:last-child td { border-bottom: 0; }
    .cz-table tr.is-best td { background: var(--cz-best-soft); }
    .cz-table tr.is-best td:first-child { box-shadow: inset 3px 0 0 var(--cz-best); }
    .cz-cell {
        width: 100%; height: 2.25rem; padding: 0 .55rem; font-size: .85rem; color: var(--cz-ink);
        background: transparent; border: 1px solid transparent; border-radius: .5rem;
        transition: border-color .15s, background-color .15s;
    }
    .cz-cell::placeholder { color: var(--cz-faint); }
    .cz-cell:hover { border-color: var(--cz-line); }
    .cz-cell:focus { outline: none; border-color: var(--cz-primary); background: var(--cz-surface); }
    .cz-cell.is-num { text-align: right; font-variant-numeric: tabular-nums; }
    .cz-cell.is-num::-webkit-outer-spin-button, .cz-cell.is-num::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .cz-cell.is-num { -moz-appearance: textfield; }
    .cz-row-total { padding-right: .6rem !important; white-space: nowrap; font-weight: 700; color: var(--cz-ink); }
    .cz-best-tag { display: inline-flex; align-items: center; gap: .3rem; margin-right: .4rem; font-size: .68rem; font-weight: 600; color: var(--cz-best); }
    .cz-empty { padding: 1.5rem 1rem !important; text-align: center; font-size: .82rem; color: var(--cz-muted); }

    /* ── Botones de agregar ── */
    .cz-add {
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        min-height: 2.5rem; padding: 0 1rem; border-radius: .7rem; cursor: pointer;
        font-size: .85rem; font-weight: 600; color: var(--cz-primary);
        background: transparent; border: 1px dashed color-mix(in srgb, var(--cz-primary) 45%, transparent);
        transition: background-color .15s, border-color .15s;
    }
    .cz-add:hover { background: var(--cz-primary-soft); border-color: var(--cz-primary); }
    .cz-add--row { margin-top: .6rem; }
    .cz-add--block { width: 100%; }
    .cz-add--prop { width: 100%; min-height: 3.5rem; border-radius: 1rem; font-size: .92rem; }

    /* ── Barra de acciones ── */
    .cz-bar {
        position: sticky; bottom: .75rem; z-index: 20;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem;
        padding: .75rem .9rem .75rem 1.1rem; border-radius: 1rem;
        background: var(--cz-surface); border: 1px solid var(--cz-line);
        box-shadow: 0 12px 32px -12px rgba(10, 22, 40, 0.28);
    }
    .dark .cz-bar { box-shadow: 0 12px 32px -12px rgba(0, 0, 0, 0.7); }
    .cz-bar__info { font-size: .82rem; color: var(--cz-muted); }
    .cz-bar__info strong { color: var(--cz-ink); }
    .cz-bar__actions { display: flex; gap: .5rem; }
    .cz-btn {
        display: inline-flex; align-items: center; gap: .5rem; min-height: 2.5rem; padding: 0 1.1rem;
        border-radius: .7rem; border: 1px solid transparent; cursor: pointer;
        font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, transform .1s;
    }
    .cz-btn:active { transform: scale(.98); }
    .cz-btn--ghost { background: transparent; color: var(--cz-ink); border-color: var(--cz-line); }
    .cz-btn--ghost:hover { background: var(--cz-sunken); }
    .cz-btn--primary { background: #0052cc; color: #fff; }
    .cz-btn--primary:hover { background: #0747a6; }

    .cz-loading { display: flex; flex-direction: column; align-items: center; gap: .9rem; padding: 4rem 1rem; color: var(--cz-muted); font-size: .9rem; }
    .cz-loading i { font-size: 1.75rem; color: var(--cz-faint); }

    @media (max-width: 640px) {
        .cz-prop__stats { display: none; }
        .cz-bar { bottom: 0; border-radius: 1rem 1rem 0 0; }
        .cz-bar__actions { width: 100%; }
        .cz-bar__actions .cz-btn { flex: 1; justify-content: center; }
    }
    @media (prefers-reduced-motion: reduce) {
        .cz *, .cz *::before { transition: none !important; }
    }
</style>

<x-index-page
    title="Cotización #{{ $solicitud->SolicitudID }}"
    icon="fa-calculator"
    subtitle="Precios sin IVA"
    :show-count="false"
    :card="false"
    class="cz"
    x-data="cotizarPagina({{ $solicitud->SolicitudID }}, '{{ route('tickets.index') }}')"
>
    <x-slot name="headerActions">
        <a href="{{ route('tickets.index') }}" class="index-page__btn-secondary">Volver</a>
    </x-slot>

    <div x-show="cargando" class="cz-loading">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        <p class="m-0">Cargando cotizaciones…</p>
    </div>

    <div x-show="!cargando" x-cloak class="cz-layout">

        {{-- ═════ Lateral: resumen + datos de la solicitud ═════ --}}
        <aside class="cz-aside">
            <section class="cz-panel" aria-labelledby="cz-resumen">
                <div class="cz-panel__head">
                    <h2 id="cz-resumen" class="cz-panel__title">Resumen</h2>
                    <span class="cz-panel__meta" x-text="propuestas.length + (propuestas.length === 1 ? ' propuesta' : ' propuestas')"></span>
                </div>

                <div class="cz-total">
                    <p class="cz-total__label">Total cotizado</p>
                    <p class="cz-total__value cz-num" x-text="dinero(totalGeneral)"></p>
                    <p class="cz-total__sub">El gerente elige <strong x-text="textoGanadores"></strong> (1 por propuesta)</p>
                </div>

                <ul class="cz-sum">
                    <template x-for="propuesta in propuestas" :key="'resumen-' + propuesta.id">
                        <li class="cz-sum__prop">
                            <div class="cz-sum__row">
                                <span class="cz-sum__name" x-text="propuesta.nombre || ('Propuesta ' + propuesta.numero)"></span>
                                <span class="cz-sum__tag" x-text="etiquetaGanador(propuesta)"></span>
                            </div>
                            <template x-for="producto in propuesta.productos" :key="'resumen-prod-' + producto.id">
                                <div>
                                    <div class="cz-sum__prod">
                                        <span x-text="producto.nombre || 'Nuevo producto'"></span>
                                        <span x-text="'x' + (propuesta.cantidad || 1) + ' ' + (producto.unidad || 'PIEZA')"></span>
                                    </div>
                                    <div class="cz-sum__best" x-show="mejorCotizacion(producto, propuesta)" x-cloak>
                                        <span x-text="(mejorCotizacion(producto, propuesta)?.proveedor || 'Sin proveedor')"></span>
                                        <span class="cz-num" x-text="dinero(totalCotizacion(propuesta, mejorCotizacion(producto, propuesta) || {}))"></span>
                                    </div>
                                </div>
                            </template>
                            <p class="cz-sum__hint" x-show="variasOpciones(propuesta)" x-cloak>Solo un ganador por propuesta</p>
                        </li>
                    </template>
                </ul>
            </section>

            <section class="cz-panel" aria-labelledby="cz-detalles">
                <div class="cz-panel__head">
                    <h2 id="cz-detalles" class="cz-panel__title">Solicitud</h2>
                </div>
                <div class="cz-info">
                    <div>
                        <p class="cz-info__label">Motivo</p>
                        <p class="cz-info__text">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="cz-info__label">Requerimientos</p>
                        <p class="cz-info__text">{{ $solicitud->Requerimientos ?? 'N/A' }}</p>
                    </div>
                </div>
                <details class="cz-help">
                    <summary>¿Cómo funciona? <i class="fas fa-chevron-down text-xs" aria-hidden="true"></i></summary>
                    <ol class="cz-steps">
                        <li>Crea propuestas (opciones distintas)</li>
                        <li>Agrega los productos de cada propuesta</li>
                        <li>Registra la cotización de cada proveedor</li>
                        <li>Guarda y envía al gerente</li>
                    </ol>
                </details>
            </section>
        </aside>

        {{-- ═════ Principal: propuestas ═════ --}}
        <div class="cz-main">

            @if(($solicitud->Estatus ?? '') === 'Re-cotizar' && !empty($recotizarMotivo ?? ''))
            <div class="cz-note cz-note--warn" role="status">
                <span class="cz-note__icon" aria-hidden="true"><i class="fas fa-redo-alt"></i></span>
                <div>
                    <p class="cz-note__title">El gerente pidió re-cotizar</p>
                    @if(!empty($recotizarPropuestas ?? []))
                    <p class="cz-note__text">Propuestas: <strong>{{ implode(', ', array_map(fn($n) => 'Propuesta ' . $n, $recotizarPropuestas ?? [])) }}</strong></p>
                    @endif
                    <p class="cz-note__text"><strong>Motivo:</strong> {{ $recotizarMotivo }}</p>
                </div>
            </div>
            @endif

            <div x-show="tieneCotizacionesEnviadas" x-cloak class="cz-note cz-note--info" role="status">
                <span class="cz-note__icon" aria-hidden="true"><i class="fas fa-paper-plane"></i></span>
                <div>
                    <p class="cz-note__title">Enviadas al gerente</p>
                    <p class="cz-note__text">Están en revisión. Aún puedes editarlas.</p>
                </div>
            </div>

            <template x-for="(propuesta, propIndex) in propuestas" :key="propuesta.id">
                <section class="cz-prop">
                    <header class="cz-prop__head">
                        <span class="cz-prop__badge cz-num" x-text="propuesta.numero" aria-hidden="true"></span>
                        <input type="text" x-model="propuesta.nombre" placeholder="Nombre de la propuesta"
                            class="cz-prop__name" aria-label="Nombre de la propuesta">
                        <span class="cz-prop__stats"
                            x-text="propuesta.productos.length + (propuesta.productos.length === 1 ? ' producto' : ' productos') + ' · ' + cotizacionesPropuesta(propuesta) + ' con precio'"></span>
                        <label class="cz-qty">
                            Cantidad
                            <span class="cz-qty__box">
                                <button type="button" class="cz-qty__btn" aria-label="Restar"
                                    @click="propuesta.cantidad = Math.max(1, (parseInt(propuesta.cantidad) || 1) - 1)"><i class="fas fa-minus text-xs" aria-hidden="true"></i></button>
                                <input type="number" min="1" x-model.number="propuesta.cantidad" class="cz-qty__input cz-num">
                                <button type="button" class="cz-qty__btn" aria-label="Sumar"
                                    @click="propuesta.cantidad = (parseInt(propuesta.cantidad) || 0) + 1"><i class="fas fa-plus text-xs" aria-hidden="true"></i></button>
                            </span>
                        </label>
                        <button type="button" class="cz-icon-btn" @click="eliminarPropuesta(propIndex)"
                            x-show="propuestas.length > 1" title="Eliminar propuesta" aria-label="Eliminar propuesta">
                            <i class="fas fa-trash-alt text-sm" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="cz-prop__body">
                        <template x-for="(producto, prodIndex) in propuesta.productos" :key="producto.id">
                            <article class="cz-prod">
                                <div class="cz-prod__head">
                                    <button type="button" class="cz-prod__toggle" @click="producto.abierto = !producto.abierto" :aria-expanded="producto.abierto">
                                        <i class="fas fa-chevron-right cz-prod__chev" :class="{ 'is-open': producto.abierto }" aria-hidden="true"></i>
                                        <span class="cz-prod__name" x-text="producto.nombre || 'Nuevo producto'"></span>
                                        <span class="cz-chip"><span x-text="propuesta.cantidad"></span> <span x-text="producto.unidad"></span></span>
                                        <span class="cz-prod__best">
                                            <template x-if="mejorCotizacion(producto, propuesta)">
                                                <span>Desde <strong class="cz-num" x-text="dinero(totalCotizacion(propuesta, mejorCotizacion(producto, propuesta)))"></strong></span>
                                            </template>
                                            <template x-if="!mejorCotizacion(producto, propuesta)">
                                                <span>Sin precios</span>
                                            </template>
                                        </span>
                                    </button>
                                    <button type="button" class="cz-icon-btn" @click="eliminarProducto(propuesta, prodIndex)"
                                        x-show="propuesta.productos.length > 1" title="Eliminar producto" aria-label="Eliminar producto">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                </div>

                                <div x-show="producto.abierto" x-collapse>
                                    <div class="cz-prod__body">
                                        <div class="cz-fields">
                                            <div>
                                                <label class="cz-label" :for="'cz-nombre-' + producto.id">Nombre del producto</label>
                                                <input type="text" :id="'cz-nombre-' + producto.id" x-model="producto.nombre"
                                                    placeholder="Ej. Laptop HP Pavilion, Mouse Logitech" class="cz-input">
                                            </div>
                                            <div>
                                                <label class="cz-label" :for="'cz-unidad-' + producto.id">Unidad</label>
                                                <select :id="'cz-unidad-' + producto.id" x-model="producto.unidad" class="cz-input">
                                                    <option value="PIEZA">PIEZA</option>
                                                    <option value="LOTE">LOTE</option>
                                                    <option value="SERVICIO">SERVICIO</option>
                                                    <option value="KIT">KIT</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="cz-table-wrap">
                                            <table class="cz-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 22%">Proveedor</th>
                                                        <th style="width: 14%">No. parte</th>
                                                        <th>Descripción</th>
                                                        <th class="is-num" style="width: 12%">Precio unit.</th>
                                                        <th class="is-num" style="width: 10%">Envío</th>
                                                        <th class="is-num" style="width: 15%">Total</th>
                                                        <th style="width: 3rem"><span class="sr-only">Quitar</span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(cot, cotIndex) in producto.cotizaciones" :key="cotIndex">
                                                        <tr :class="{ 'is-best': mejorCotizacion(producto, propuesta) === cot && cotizacionesConPrecio(producto).length > 1 }">
                                                            <td><input type="text" x-model="cot.proveedor" placeholder="Proveedor" class="cz-cell" aria-label="Proveedor"></td>
                                                            <td><input type="text" x-model="cot.numeroParte" placeholder="#" class="cz-cell" aria-label="Número de parte"></td>
                                                            <td><input type="text" x-model="cot.descripcion" placeholder="Descripción" class="cz-cell" aria-label="Descripción"></td>
                                                            <td><input type="number" step="0.01" min="0" x-model.number="cot.precioUnitario" placeholder="0.00"
                                                                    @input="cot.total = ((propuesta.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"
                                                                    class="cz-cell is-num" aria-label="Precio unitario"></td>
                                                            <td><input type="number" step="0.01" min="0" x-model.number="cot.costoEnvio" placeholder="0.00"
                                                                    @input="cot.total = ((propuesta.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"
                                                                    class="cz-cell is-num" aria-label="Costo de envío"></td>
                                                            <td class="is-num cz-row-total">
                                                                <span class="cz-best-tag" x-show="mejorCotizacion(producto, propuesta) === cot && cotizacionesConPrecio(producto).length > 1" x-cloak>
                                                                    <i class="fas fa-arrow-down" aria-hidden="true"></i>Más bajo
                                                                </span>
                                                                <span class="cz-num" x-text="dinero(totalCotizacion(propuesta, cot))"></span>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="cz-icon-btn" @click="eliminarCotizacion(producto, cotIndex)" title="Quitar cotización" aria-label="Quitar cotización">
                                                                    <i class="fas fa-times" aria-hidden="true"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <tr x-show="producto.cotizaciones.length === 0">
                                                        <td colspan="7" class="cz-empty">Sin cotizaciones. Agrega la del primer proveedor.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <button type="button" class="cz-add cz-add--row" @click="agregarCotizacion(producto)">
                                            <i class="fas fa-plus text-xs" aria-hidden="true"></i> Agregar cotización
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>

                        <button type="button" class="cz-add cz-add--block" @click="agregarProducto(propuesta)">
                            <i class="fas fa-plus text-xs" aria-hidden="true"></i> Agregar producto
                        </button>
                    </div>
                </section>
            </template>

            <button type="button" class="cz-add cz-add--prop" @click="agregarPropuesta()">
                <i class="fas fa-layer-group" aria-hidden="true"></i> Nueva propuesta
            </button>

            <div class="cz-bar">
                <p class="cz-bar__info m-0">
                    <strong x-text="propuestas.length"></strong> <span x-text="propuestas.length === 1 ? 'propuesta' : 'propuestas'"></span>
                    · <strong class="cz-num" x-text="dinero(totalGeneral)"></strong>
                </p>
                <div class="cz-bar__actions">
                    <button type="button" class="cz-btn" :class="tieneCotizacionesGuardadas ? 'cz-btn--ghost' : 'cz-btn--primary'" @click="guardar()">
                        <i class="fas fa-save" aria-hidden="true"></i> Guardar
                    </button>
                    <button type="button" class="cz-btn cz-btn--primary" x-show="tieneCotizacionesGuardadas" @click="enviarAlGerente()">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> Enviar al gerente
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-index-page>


@verbatim
<script>
    (function () {
        function cotizarPagina(solicitudId, ticketsUrl) {
            ticketsUrl = ticketsUrl || '/tickets';
            return {
            solicitudId: solicitudId,
            ticketsUrl: ticketsUrl,
            cargando: true,
            propuestas: [],
            nextId: 1,
            tieneCotizacionesGuardadas: false,
            tieneCotizacionesEnviadas: false,
            

            async init() {
                await this.cargar();
            },

            get totalGeneral() {
                return this.propuestas.reduce((sum, prop) => sum + this.totalPropuesta(prop), 0);
            },

            totalPropuesta(propuesta) {
                return propuesta.productos.reduce((sum, prod) => sum + this.totalProducto(prod, propuesta), 0);
            },

            totalProducto(producto, propuesta) {
                const cantidad = propuesta ? propuesta.cantidad : 1;
                return producto.cotizaciones.reduce((s, c) => {
                    const u = parseFloat(c.precioUnitario) || 0;
                    const envio = parseFloat(c.costoEnvio) || 0;
                    return s + (cantidad * u) + envio;
                }, 0);
            },

            dinero(valor) {
                const n = parseFloat(valor) || 0;
                return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            totalCotizacion(propuesta, cot) {
                const cantidad = Math.max(1, parseInt(propuesta.cantidad, 10) || 1);
                const unitario = parseFloat(cot.precioUnitario) || 0;
                const envio = parseFloat(cot.costoEnvio) || 0;
                return (cantidad * unitario) + envio;
            },

            cotizacionesConPrecio(producto) {
                return producto.cotizaciones.filter(c => (parseFloat(c.precioUnitario) || 0) > 0);
            },

            // Solo para resaltar en la vista: la cotización con precio de menor total.
            mejorCotizacion(producto, propuesta) {
                return this.cotizacionesConPrecio(producto).reduce((mejor, c) =>
                    !mejor || this.totalCotizacion(propuesta, c) < this.totalCotizacion(propuesta, mejor) ? c : mejor, null);
            },

            cotizacionesPropuesta(propuesta) {
                return propuesta.productos.reduce((total, producto) => total + this.cotizacionesConPrecio(producto).length, 0);
            },

            propuestaLista(propuesta) {
                return this.cotizacionesPropuesta(propuesta) > 0;
            },

            conteoCotizaciones(propuesta) {
                return (propuesta.productos || []).reduce((total, producto) => total + (producto.cotizaciones || []).length, 0);
            },

            variasOpciones(propuesta) {
                return (propuesta.productos || []).length > 1 || this.conteoCotizaciones(propuesta) > 1;
            },

            etiquetaGanador(propuesta) {
                return this.variasOpciones(propuesta) ? '1 ganador' : 'Único ganador';
            },

            get propuestasAEnviar() {
                return this.propuestas.filter((propuesta) => this.propuestaLista(propuesta)).length;
            },

            get ganadoresRequeridos() {
                return this.propuestas.length;
            },

            get textoGanadores() {
                const total = this.ganadoresRequeridos;
                return total === 1 ? '1 ganador' : total + ' ganadores';
            },

            nuevaPropuesta(numero) {
                return {
                    id: this.nextId++,
                    numero: numero,
                    nombre: `Propuesta ${numero}`,
                    cantidad: 1,
                    productos: []
                };
            },

            nuevoProducto(nombre = 'Nuevo Producto') {
                return {
                    id: this.nextId++,
                    abierto: true,
                    nombre,
                    unidad: 'PIEZA',
                    cotizaciones: []
                };
            },

            nuevaCotizacion(producto) {
                const ultima = producto.cotizaciones?.at(0);
                
                return {
                    proveedor: '',
                    numeroParte: ultima ? ultima.numeroParte : '',
                    descripcion: ultima ? ultima.descripcion : '',
                    precioUnitario: '',
                    costoEnvio: 0,
                    total: '0.00'
                };
            },

            agregarPropuesta() {
                const numero = this.propuestas.length + 1;
                const prop = this.nuevaPropuesta(numero);
                const prod = this.nuevoProducto();
                prod.cotizaciones.push(this.nuevaCotizacion(prod));
                prop.productos.push(prod);
                this.propuestas.push(prop);
            },

            eliminarPropuesta(index) {
                this.propuestas.splice(index, 1);
                // Renumerar propuestas
                this.propuestas.forEach((prop, i) => {
                    prop.numero = i + 1;
                    if (!prop.nombre || prop.nombre.match(/^Propuesta \d+$/)) {
                        prop.nombre = `Propuesta ${i + 1}`;
                    }
                });
            },

            agregarProducto(propuesta) {
                const prod = this.nuevoProducto();
                prod.cotizaciones.push(this.nuevaCotizacion(prod));
                propuesta.productos.push(prod);
            },

            eliminarProducto(propuesta, index) {
                propuesta.productos.splice(index, 1);
            },

            agregarCotizacion(producto) {
                producto.cotizaciones.push(this.nuevaCotizacion(producto));
            },

            eliminarCotizacion(producto, index) {
                producto.cotizaciones.splice(index, 1);
            },

            async cargar() {
                this.cargando = true;
                try {
                    const res = await fetch(`/solicitudes/${this.solicitudId}/cotizaciones`);
                    const data = await res.json().catch(() => ({}));
                    const proveedores = data.proveedores || [];
                    const productos = data.productos || [];
                    this.tieneCotizacionesEnviadas = !!data.tieneCotizacionesEnviadas;
                    this.tieneCotizacionesGuardadas = productos.length > 0;

                    if (productos.length) {
                        // Agrupar productos por NumeroPropuesta
                        const propuestasMap = {};

                        productos.forEach(p => {
                            const numProp = p.numeroPropuesta || 1;

                            if (!propuestasMap[numProp]) {
                                propuestasMap[numProp] = [];
                            }

                            propuestasMap[numProp].push(p);
                        });

                        // Crear estructura de propuestas
                        this.propuestas = Object.keys(propuestasMap).sort((a, b) => a - b).map((numProp) => {
                            const productosEnPropuesta = propuestasMap[numProp];
                            const prop = this.nuevaPropuesta(parseInt(numProp));
                            prop.nombre = `Propuesta ${numProp}`;

                            // Ordenar productos por NumeroProducto
                            productosEnPropuesta.sort((a, b) => (a.numeroProducto || 0) - (b.numeroProducto || 0));

                            // Obtener la cantidad del primer producto para la propuesta
                            prop.cantidad = productosEnPropuesta.length > 0 ? Math.max(1, parseInt(productosEnPropuesta[0].cantidad) || 1) : 1;

                            prop.productos = productosEnPropuesta.map(p => {
                                const prod = this.nuevoProducto(p.nombreEquipo || p.descripcion || 'Producto');
                                prod.unidad = (p.unidad || 'PIEZA').trim() || 'PIEZA';

                                const precios = p.precios || {};
                                const descripciones = p.descripciones || {};
                                const numeroPartes = p.numeroPartes || {};

                                prod.cotizaciones = proveedores.map(prov => {
                                    const precioData = precios[prov];
                                    const uv = typeof precioData === 'object' && precioData !== null ?
                                        (parseFloat(precioData.precio_unitario) || 0) :
                                        (typeof precioData === 'number' ? precioData : parseFloat(precioData) || 0);
                                    const costoEnvio = typeof precioData === 'object' && precioData !== null ?
                                        (parseFloat(precioData.costo_envio) || 0) :
                                        0;
                                    if (uv <= 0) return null;
                                    return {
                                        proveedor: prov,
                                        numeroParte: (numeroPartes[prov] ?? p.numeroParte ?? '').trim() || (p.numeroParte || ''),
                                        descripcion: (descripciones[prov] || p.descripcion || '').trim() || (p.descripcion || ''),
                                        precioUnitario: uv,
                                        costoEnvio: costoEnvio,
                                        total: ((prop.cantidad * uv) + costoEnvio).toFixed(2)
                                    };
                                }).filter(Boolean);

                                if (prod.cotizaciones.length === 0) {
                                    prod.cotizaciones.push(this.nuevaCotizacion(prod));
                                }

                                return prod;
                            });

                            return prop;
                        });
                    } else {
                        // Inicializar con una propuesta vacía
                        this.agregarPropuesta();
                    }
                } catch (e) {
                    console.error(e);
                    this.agregarPropuesta();
                }
                this.cargando = false;
            },

            buildPayload() {
                const proveedoresSet = new Set();
                const productos = [];

                this.propuestas.forEach(propuesta => {
                    let contadorProducto = 1; // Contador por propuesta

                    propuesta.productos.forEach((producto) => {
                        const nombreProd = (producto.nombre || '').trim();
                        if (!nombreProd) return;

                        const precios = {};
                        const descripciones = {};
                        const numerosParte = {};

                        producto.cotizaciones.forEach(c => {
                            const prov = (c.proveedor || '').trim();
                            const u = parseFloat(c.precioUnitario) || 0;
                            if (!prov || u <= 0) return;

                            proveedoresSet.add(prov);
                            precios[prov] = {
                                precio_unitario: u,
                                costo_envio: parseFloat(c.costoEnvio) || 0
                            };
                            descripciones[prov] = (c.descripcion || '').trim() || nombreProd;
                            numerosParte[prov] = (c.numeroParte || '').trim();
                        });

                        if (Object.keys(precios).length) {
                            productos.push({
                                numero_propuesta: propuesta.numero,
                                numero_producto: contadorProducto++,
                                cantidad: Math.max(1, parseInt(propuesta.cantidad) || 1),
                                numero_parte: '',
                                descripcion: nombreProd,
                                nombre_equipo: nombreProd,
                                descripciones,
                                numeros_parte: numerosParte,
                                unidad: (producto.unidad || 'PIEZA').trim() || 'PIEZA',
                                precios,
                                tiempo_entrega: {},
                                observaciones: {}
                            });
                        }
                    });
                });

                const proveedores = Array.from(proveedoresSet);
                return {
                    proveedores,
                    productos
                };
            },

            async guardar() {
                const {
                    proveedores,
                    productos
                } = this.buildPayload();

                if (!productos.length) {
                    Swal.fire('Aviso', 'Agrega al menos una propuesta con productos y cotizaciones con precio.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const res = await fetch(`/solicitudes/${this.solicitudId}/guardar-cotizaciones`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            proveedores,
                            productos
                        })
                    });
                    const data = await res.json().catch(() => ({}));
                    Swal.close();

                    if (data.success) {
                        this.tieneCotizacionesGuardadas = true;
                        await Swal.fire('Éxito', data.message || 'Cotizaciones guardadas.', 'success');
                        await this.cargar();
                    } else {
                        Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                    }
                } catch (e) {
                    Swal.close();
                    Swal.fire('Error', 'Error al guardar las cotizaciones.', 'error');
                }
            },

            async enviarAlGerente() {
                const ok = await Swal.fire({
                    title: '¿Enviar cotizaciones al gerente?',
                    text: 'Se enviarán ' + this.propuestasAEnviar + ' propuestas. El gerente debe elegir ' + this.ganadoresRequeridos + ' ganadores (1 por propuesta).',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, enviar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0F766E'
                }).then(r => r.isConfirmed);

                if (!ok) return;

                Swal.fire({
                    title: 'Enviando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const res = await fetch(`/solicitudes/${this.solicitudId}/enviar-cotizaciones-gerente`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    });
                    const data = await res.json().catch(() => ({}));
                    Swal.close();

                    if (data.success) {
                        await Swal.fire('Éxito', data.message || 'Enviado al gerente.', 'success');
                        window.location.href = this.ticketsUrl;
                    } else {
                        Swal.fire('Error', data.message || 'Error al enviar.', 'error');
                    }
                } catch (e) {
                    Swal.close();
                    Swal.fire('Error', 'Error al enviar.', 'error');
                }
            }
        };
        }

        window.cotizarPagina = cotizarPagina;

        function registrarCotizarPagina() {
            if (!window.Alpine || typeof Alpine.data !== 'function') return;
            Alpine.data('cotizarPagina', window.cotizarPagina);
        }

        if (window.Alpine) {
            registrarCotizarPagina();
        } else {
            document.addEventListener('alpine:init', registrarCotizarPagina);
        }
    })();
</script>
@endverbatim
@endsection