{{-- Estilos UI para Asignar inventario (alineados al catálogo) --}}
<style>
    .inv-assign-page .inv-kpi-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    @media (max-width: 900px) {
        .inv-assign-page .inv-kpi-row { grid-template-columns: 1fr; }
    }

    .inv-kpi {
        background: var(--index-card, #fff);
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.85rem;
        padding: 0.85rem 1rem;
    }

    .dark .inv-kpi {
        background: #1f2937;
        border-color: #374151;
    }

    .inv-kpi-stock { border-left: 3px solid #10b981; }
    .inv-kpi-extra { border-left: 3px solid #f97316; }
    .inv-kpi-total { border-left: 3px solid var(--index-navy, #101d49); }

    .inv-kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--index-muted, #6b7280);
        margin-bottom: 0.2rem;
    }

    .inv-kpi-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--index-navy, #101d49);
        line-height: 1.15;
    }

    .dark .inv-kpi-value { color: #f8fafc; }

    .inv-kpi-sub {
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: var(--index-muted, #6b7280);
    }

    .inv-panel {
        background: var(--index-card, #fff);
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.85rem;
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 2px rgba(16, 29, 73, 0.04);
    }

    .dark .inv-panel {
        background: #1f2937;
        border-color: #374151;
    }

    .inv-panel-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        color: #fff;
        font-weight: 600;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        font-size: 0.78rem;
        background: var(--index-navy, #101d49);
    }

    .inv-panel-head-asignados { background: var(--index-navy, #101d49); }
    .inv-panel-head-extra { background: #c2410c; }
    .inv-panel-head-disponibles { background: #1e3a5f; }

    .inv-panel-body { padding: 0.35rem 0.5rem 0.75rem; }

    .inv-dual {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.65rem;
        margin: 0.65rem 0.35rem 0.85rem;
    }

    @media (max-width: 768px) {
        .inv-dual { grid-template-columns: 1fr; }
    }

    .inv-dual-card {
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.65rem;
        padding: 0.65rem 0.8rem;
        background: #f8fafc;
        cursor: pointer;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .dark .inv-dual-card {
        background: #111827;
        border-color: #374151;
    }

    .inv-dual-card[data-filtro="no_presupuestados"].is-active {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }

    .inv-dual-card[data-filtro="presupuestados"].is-active {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
    }

    .inv-dual-title {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
    }

    .inv-dual-title.stock { color: #047857; }
    .inv-dual-title.extra { color: #c2410c; }

    .inv-dual-empty {
        color: #94a3b8;
        font-size: 0.75rem;
        padding: 0.2rem 0;
    }

    .inv-panel .index-table { margin-bottom: 0; }

    .inv-money {
        font-weight: 700;
        color: var(--index-navy, #101d49);
        white-space: nowrap;
        font-size: 0.9rem;
    }

    .dark .inv-money { color: #e2e8f0; }

    .inv-mes-pill {
        display: inline-flex;
        padding: 0.12rem 0.45rem;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 0.68rem;
        font-weight: 700;
    }

    .inv-modal-content {
        border: none;
        border-radius: 0.85rem;
        overflow: hidden;
    }

    .inv-modal-header {
        border-bottom: 1px solid var(--index-border, #e5e7eb);
        padding: 0.85rem 1.1rem;
    }

    .inv-modal-title {
        font-weight: 700;
        font-size: 1rem;
        color: var(--index-navy, #101d49);
    }

    /* Tres modalidades sólo en equipos (stock / presupuestado / propio). */
    .inv-segment-3 {
        grid-template-columns: 1fr 1fr 1fr !important;
    }

    @media (max-width: 575.98px) {
        .inv-segment-3 {
            grid-template-columns: 1fr !important;
        }
    }
    .dark .inv-modal-title { color: #fff; }

    .inv-locked-tag,
    .inv-opt-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-left: 0.35rem;
        padding: 0.08rem 0.4rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .inv-locked-tag {
        background: #e5e7eb;
        color: #374151;
    }

    .dark .inv-locked-tag {
        background: #374151;
        color: #e5e7eb;
    }

    .inv-opt-tag {
        display: none;
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }

    .is-modo-extra .inv-opt-tag {
        display: inline-flex;
    }

    /* Equipo propio: se ocultan Precio, Fechas, Folio y Meses de pago. Quedan en
       grid de dos columnas Núm. serie + Gerencia; Comentarios pasa a ancho
       completo porque su compañera (Meses de pago) desaparece. */
    #editForm.is-modo-propio .equipo-solo-empresa {
        display: none !important;
    }

    #editForm.is-modo-propio .equipo-comentarios {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .inv-linea-hint {
        margin: 0 0 1rem;
        padding: 0.65rem 0.85rem;
        border-radius: 0.65rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        font-size: 0.82rem;
        line-height: 1.35;
    }

    .dark .inv-linea-hint {
        background: #431407;
        border-color: #9a3412;
        color: #fed7aa;
    }

    .inv-pendiente {
        display: inline-block;
        padding: 0.1rem 0.45rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .dark .inv-pendiente {
        background: #1e293b;
        color: #94a3b8;
    }

    .inv-panel-head .crear-btn-linea-extra {
        text-transform: none;
        letter-spacing: 0;
        font-size: 0.75rem;
        padding: 0.28rem 0.7rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.45);
        background: rgba(255,255,255,.12);
        color: #fff;
    }

    .inv-panel-head .crear-btn-linea-extra:hover {
        background: rgba(255,255,255,.22);
        color: #fff;
    }

    .inv-locked,
    .form-control.inv-locked,
    textarea.inv-locked {
        background: #e5e7eb !important;
        color: #4b5563 !important;
        border-color: #9ca3af !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
        opacity: 1;
    }

    .dark .inv-locked,
    .dark .form-control.inv-locked,
    .dark textarea.inv-locked {
        background: #111827 !important;
        color: #9ca3af !important;
        border-color: #4b5563 !important;
    }

    .inv-segment {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.35rem;
        padding: 0.25rem;
        background: #f3f4f6;
        border-radius: 0.7rem;
        margin-bottom: 0.65rem;
    }

    .inv-segment--3 {
        grid-template-columns: 1fr 1fr 1fr;
    }

    /* Equipos: stock / extra / compartido / propio en una sola fila. */
    .inv-segment--4 {
        grid-template-columns: repeat(4, 1fr);
    }

    @media (max-width: 991px) {
        .inv-segment--4 { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 575px) {
        .inv-segment--3,
        .inv-segment--4 { grid-template-columns: 1fr; }
    }

    .dark .inv-segment { background: #111827; }

    .inv-segment .inv-modo-card {
        margin: 0;
        text-align: center;
        border: none;
        box-shadow: none !important;
        background: transparent;
        padding: 0.55rem 0.4rem;
        border-radius: 0.55rem;
    }

    .inv-segment .inv-modo-card .modo-desc { display: none; }

    .inv-segment .inv-modo-card.is-active[data-value="0"] {
        background: #059669;
        color: #fff;
    }

    .inv-segment .inv-modo-card.is-active[data-value="1"] {
        background: #c2410c;
        color: #fff;
    }

    .inv-segment .inv-modo-card.is-active[data-value="2"] {
        background: #1d4ed8;
        color: #fff;
    }

    .inv-segment .inv-modo-card.is-active[data-value="3"] {
        background: #6d28d9;
        color: #fff;
    }

    .inv-segment .inv-modo-card.is-active .modo-title,
    .inv-segment .inv-modo-card.is-active i {
        color: #fff !important;
    }

    .inv-segment .inv-modo-card.is-locked {
        opacity: 0.65;
        cursor: not-allowed;
        pointer-events: none;
    }

    .inv-modo-hint {
        border-radius: 0.65rem;
        padding: 0.55rem 0.7rem;
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
        display: none;
    }

    .inv-modo-hint.stock {
        display: flex;
        gap: 0.5rem;
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .inv-modo-hint.extra {
        display: flex;
        gap: 0.5rem;
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }

    .inv-modo-hint.share {
        display: flex;
        gap: 0.5rem;
        background: #eff6ff;
        color: #1e3a8a;
        border: 1px solid #bfdbfe;
    }

    .inv-modo-hint.propio {
        display: flex;
        gap: 0.5rem;
        background: #f5f3ff;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }

    .inv-form-label {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--index-muted, #6b7280);
        margin-bottom: 0.3rem;
    }

    .inv-empleado-card .row > [class*="col-"] {
        margin-bottom: 0.75rem;
    }

    @media (min-width: 1200px) {
        .inv-empleado-card .row > .col-sm-6 {
            flex: 0 0 33.333%;
            max-width: 33.333%;
        }
    }

    .inv-assign-page .tab-content > .tab-pane { display: none; }
    .inv-assign-page .tab-content > .tab-pane.active { display: block; }

    .inv-assign-page .index-page__header-actions .inv-tipo-badge {
        align-self: center;
    }

    .inv-assign-page,
    .inv-assign-page .index-page,
    .inv-assign-page .index-page__tabs,
    .inv-assign-page .index-page__card,
    .inv-assign-page .tab-content,
    .inv-assign-page .tab-pane {
        min-width: 0;
        max-width: 100%;
    }

    .inv-assign-page .index-page__count {
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal;
    }

    .inv-assign-page .index-page__tabs {
        width: 100%;
    }

    .inv-assign-page .app-tabs {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.45rem;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    .inv-assign-page .app-tabs__btn {
        width: 100%;
        min-width: 0;
        white-space: normal;
        text-align: center;
        line-height: 1.3;
        padding: 0.55rem 0.65rem;
    }

    .inv-assign-note {
        margin: 0 0 1rem;
        font-size: 0.85rem;
        line-height: 1.45;
        color: #6b7280;
        max-width: 100%;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .dark .inv-assign-note {
        color: #9ca3af;
    }

    .dark .inv-assign-page .app-tabs {
        background: #1f2937;
        border-color: #4b5563;
    }

    .dark .inv-assign-page .app-tabs__btn {
        color: #e5e7eb;
        background: #111827;
    }

    .dark .inv-assign-page .app-tabs__btn:hover {
        color: #fff;
        background: #374151;
    }

    .dark .inv-assign-page .app-tabs__btn.is-active {
        background: #2563eb;
        color: #fff;
    }

    .inv-assign-page .inv-empleado-card .row {
        margin-left: 0;
        margin-right: 0;
    }

    .inv-assign-page .inv-empleado-card .row > [class*="col-"] {
        min-width: 0;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .inv-assign-page .form-control,
    .inv-assign-page select.form-control {
        max-width: 100%;
        min-width: 0;
    }

    .inv-assign-page .table-responsive,
    .inv-assign-page .index-page__table-wrap {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .inv-assign-page .inv-panel-head {
        min-width: 0;
    }

    @media (max-width: 1199px) {
        .inv-assign-page .app-tabs {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .inv-assign-page .inv-empleado-card .row > .col-sm-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }

    @media (max-width: 767px) {
        .inv-assign-page .app-tabs {
            grid-template-columns: 1fr 1fr;
            gap: 0.4rem;
        }

        .inv-assign-page .app-tabs__btn {
            font-size: 0.8rem;
            padding: 0.5rem 0.45rem;
        }

        .inv-assign-page .inv-empleado-card .row > .col-sm-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .inv-assign-page .inv-panel-head {
            flex-direction: column;
            align-items: stretch;
        }
    }

    @media (max-width: 479px) {
        .inv-assign-page .app-tabs {
            grid-template-columns: 1fr;
        }
    }

    .pago-meses {
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.75rem;
        padding: 0.75rem 0.85rem;
        background: var(--index-card, #fff);
    }

    .dark .pago-meses {
        background: #1f2937;
        border-color: #374151;
    }

    .pago-meses__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
    }

    .pago-meses__label {
        font-weight: 600;
        margin: 0;
        color: var(--index-navy, #101d49);
    }

    .dark .pago-meses__label { color: #f8fafc; }

    .pago-meses__actions {
        display: flex;
        gap: 0.4rem;
    }

    .pago-meses__btn {
        border: 1px solid var(--index-border, #d1d5db);
        background: #fff;
        color: var(--index-navy, #101d49);
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.7rem;
        cursor: pointer;
    }

    .dark .pago-meses__btn {
        background: #111827;
        color: #e5e7eb;
        border-color: #4b5563;
    }

    .pago-meses__grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.4rem;
    }

    @media (max-width: 767px) {
        .pago-meses__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    .pago-meses__chip {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        cursor: pointer;
        border: 1px solid var(--index-border, #d1d5db);
        border-radius: 0.5rem;
        padding: 0.35rem 0.2rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--index-navy, #101d49);
        background: #f8fafc;
        user-select: none;
    }

    .dark .pago-meses__chip {
        background: #111827;
        color: #e5e7eb;
        border-color: #4b5563;
    }

    .pago-meses__chip input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .pago-meses__chip:has(input:checked),
    .pago-meses__chip.is-on {
        background: var(--index-navy, #101d49);
        border-color: var(--index-navy, #101d49);
        color: #fff;
    }

    .pago-meses__hint {
        margin: 0.55rem 0 0;
        font-size: 0.75rem;
        color: var(--index-muted, #6b7280);
    }

    .inv-meses-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.2rem;
        max-width: 220px;
    }

    .inv-bulk {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.55rem 0.75rem;
        margin: 0 0 0.75rem;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.7rem;
        background: #f8fafc;
    }

    .dark .inv-bulk {
        background: #111827;
        border-color: #374151;
    }

    .inv-bulk-all-wrap,
    .inv-check {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin: 0;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--index-navy, #101d49);
        cursor: pointer;
    }

    .dark .inv-bulk-all-wrap { color: #e5e7eb; }

    .inv-bulk-check,
    .inv-bulk-all {
        width: 1rem;
        height: 1rem;
        accent-color: #1d4ed8;
        cursor: pointer;
    }

    .inv-check--off {
        width: 1rem;
        height: 1rem;
        border-radius: 0.2rem;
        background: #e2e8f0;
        cursor: not-allowed;
    }

    .inv-bulk-count {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--index-muted, #6b7280);
        min-width: 7rem;
    }

    .inv-bulk-btn {
        border: none;
        border-radius: 999px;
        padding: 0.28rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
    }

    .inv-bulk-btn--stock { background: #059669; }
    .inv-bulk-btn--share { background: #1d4ed8; }
    .inv-bulk-btn:disabled { opacity: 0.45; cursor: not-allowed; }

    .inv-bulk-help {
        font-size: 0.72rem;
        color: var(--index-muted, #6b7280);
    }
</style>
