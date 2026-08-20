{{-- Estilos UI moderna para Asignar inventario (solo presentación) --}}
<style>
    /* Usar todo el ancho del main y reducir aire vacío */
    body:has(.inv-assign-page) main {
        padding-top: 0.35rem !important;
        padding-bottom: 0.5rem !important;
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }

    .inv-assign-page {
        max-width: none;
        width: 100%;
        margin: 0;
        padding: 0 0 0.75rem;
    }

    .inv-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        margin-bottom: 0.65rem;
        padding: 0.55rem 0.75rem;
        background: #fff;
        border: 1px solid #e8ecf1;
        border-radius: 0.75rem;
    }

    .dark .inv-hero {
        background: #1e293b;
        border-color: #334155;
    }

    .inv-hero-left {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
    }

    .inv-avatar {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.82rem;
        letter-spacing: 0.04em;
        color: #fff;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        flex-shrink: 0;
    }

    .inv-hero-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin: 0 0 0.05rem;
    }

    .inv-hero-name {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        word-break: break-word;
    }

    .dark .inv-hero-name { color: #f8fafc; }

    .inv-hero-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .inv-btn-back {
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #334155;
        border-radius: 0.55rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none !important;
    }

    .inv-btn-back:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .dark .inv-btn-back {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    .inv-tabs {
        display: flex;
        gap: 0.15rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 0.65rem;
        overflow-x: auto;
    }

    .dark .inv-tabs { border-bottom-color: #334155; }

    .inv-tabs .nav-link {
        border: none !important;
        background: transparent !important;
        color: #64748b !important;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.45rem 0.75rem !important;
        border-bottom: 2px solid transparent !important;
        border-radius: 0 !important;
        white-space: nowrap;
    }

    .inv-tabs .nav-link.active {
        color: #4f46e5 !important;
        border-bottom-color: #4f46e5 !important;
    }

    .inv-kpi-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
        margin-bottom: 0.65rem;
    }

    @media (max-width: 900px) {
        .inv-kpi-row { grid-template-columns: 1fr; }
    }

    .inv-kpi {
        background: #fff;
        border: 1px solid #e8ecf1;
        border-radius: 0.7rem;
        padding: 0.55rem 0.75rem;
        min-height: 0;
    }

    .dark .inv-kpi {
        background: #1e293b;
        border-color: #334155;
    }

    .inv-kpi-stock { border-left: 3px solid #10b981; }
    .inv-kpi-extra { border-left: 3px solid #7c3aed; }
    .inv-kpi-total { border-left: 3px solid #64748b; }

    .inv-kpi-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 0.15rem;
    }

    .inv-kpi-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }

    .dark .inv-kpi-value { color: #f8fafc; }

    .inv-kpi-sub {
        margin-top: 0.1rem;
        font-size: 0.72rem;
        color: #64748b;
    }

    .inv-panel {
        background: #fff;
        border: 1px solid #e8ecf1;
        border-radius: 0.75rem;
        overflow: hidden;
        margin-bottom: 0.65rem;
    }

    .dark .inv-panel {
        background: #1e293b;
        border-color: #334155;
    }

    .inv-panel-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.45rem 0.75rem;
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: 0.75rem;
    }

    .inv-panel-head-asignados { background: #059669; }
    .inv-panel-head-extra { background: #6d28d9; }
    .inv-panel-head-disponibles { background: #4338ca; }

    .inv-panel-body { padding: 0.55rem 0.7rem 0.7rem; }

    .inv-dual {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 0.55rem;
    }

    @media (max-width: 768px) {
        .inv-dual { grid-template-columns: 1fr; }
    }

    .inv-dual-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        padding: 0.55rem 0.7rem;
        background: #f8fafc;
        cursor: pointer;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .dark .inv-dual-card {
        background: #0f172a;
        border-color: #334155;
    }

    .inv-dual-card.is-active {
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .inv-dual-card[data-filtro="no_presupuestados"].is-active {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }

    .inv-dual-card[data-filtro="presupuestados"].is-active {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
    }

    .inv-dual-title {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
    }

    .inv-dual-title.stock { color: #047857; }
    .inv-dual-title.extra { color: #6d28d9; }

    .inv-dual-empty {
        color: #94a3b8;
        font-size: 0.75rem;
        padding: 0.2rem 0;
    }

    .inv-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .inv-search {
        flex: 1;
        min-width: 180px;
        position: relative;
    }

    .inv-search i {
        position: absolute;
        left: 0.7rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .inv-search input {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 0.55rem;
        padding: 0.4rem 0.7rem 0.4rem 1.9rem;
        background: #fff;
        font-size: 0.85rem;
    }

    .dark .inv-search input {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    .inv-panel .table {
        margin-bottom: 0;
        font-size: 0.82rem;
    }

    .inv-panel .table thead th {
        border-top: none;
        font-size: 0.68rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        white-space: nowrap;
        background: transparent;
        padding: 0.4rem 0.5rem;
    }

    .inv-panel .table td {
        vertical-align: middle;
        padding: 0.4rem 0.5rem;
    }

    .inv-money {
        font-weight: 800;
        color: #1e293b;
        white-space: nowrap;
        font-size: 0.9rem;
    }

    .dark .inv-money { color: #e2e8f0; }

    .inv-mes-pill {
        display: inline-flex;
        padding: 0.12rem 0.45rem;
        border-radius: 999px;
        background: #ede9fe;
        color: #5b21b6;
        font-size: 0.68rem;
        font-weight: 700;
    }

    .inv-modal-content {
        border: none;
        border-radius: 1rem;
        overflow: hidden;
    }

    .inv-modal-header {
        border-bottom: 1px solid #eef2f6;
        padding: 0.85rem 1.1rem;
    }

    .inv-modal-title {
        font-weight: 800;
        font-size: 1rem;
        color: #0f172a;
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

    .inv-segment {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.35rem;
        padding: 0.25rem;
        background: #f1f5f9;
        border-radius: 0.7rem;
        margin-bottom: 0.65rem;
    }

    .dark .inv-segment { background: #0f172a; }

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
        background: #6d28d9;
        color: #fff;
    }

    .inv-segment .inv-modo-card.is-active[data-value="2"] {
        background: #1d4ed8;
        color: #fff;
    }

    .inv-segment .inv-modo-card.is-active .modo-title,
    .inv-segment .inv-modo-card.is-active i {
        color: #fff !important;
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
        background: #f5f3ff;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }

    .inv-modo-hint.propio {
        display: flex;
        gap: 0.5rem;
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }

    .inv-form-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.2rem;
    }

    .inv-empleado-card {
        background: #fff;
        border: 1px solid #e8ecf1;
        border-radius: 0.75rem;
        padding: 0.75rem 0.9rem;
    }

    .dark .inv-empleado-card {
        background: #1e293b;
        border-color: #334155;
    }

    .inv-empleado-card .row > [class*="col-"] {
        margin-bottom: 0.55rem;
    }

    .inv-empleado-card .form-control,
    .inv-empleado-card select.form-control {
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
        min-height: 34px;
        font-size: 0.88rem;
    }

    @media (min-width: 992px) {
        .inv-empleado-card .row > .col-sm-6 {
            flex: 0 0 33.333%;
            max-width: 33.333%;
        }
    }

    .inv-hero .text-muted.small {
        font-size: 0.75rem !important;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 0.8rem;
        margin-top: 0.35rem;
        margin-bottom: 0.15rem;
    }
</style>
