<style>
    .inv-tipo-badge,
    .inv-chip,
    .inv-mes-pill,
    .inv-locked-tag,
    .inv-opt-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        padding: 0.22rem 0.72rem;
        border-radius: 999px;
        font-family: Inter, sans-serif;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        line-height: 1.3;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .inv-tipo-fisica,
    .inv-chip-share {
        background: rgba(0, 82, 204, 0.1);
        color: #0052cc;
        border-color: rgba(0, 82, 204, 0.32);
    }

    .inv-tipo-referenciado,
    .inv-chip-propio {
        background: rgba(101, 84, 192, 0.12);
        color: #403294;
        border-color: rgba(101, 84, 192, 0.38);
    }

    .inv-tipo-extraordinario,
    .inv-chip-extra,
    .inv-mes-pill,
    .inv-opt-tag {
        background: rgba(255, 171, 0, 0.16);
        color: #9a6700;
        border-color: rgba(255, 171, 0, 0.5);
    }

    .inv-chip-stock {
        background: rgba(54, 179, 126, 0.14);
        color: #00875a;
        border-color: rgba(54, 179, 126, 0.4);
    }

    .inv-chip-off,
    .inv-locked-tag {
        background: rgba(10, 22, 40, 0.05);
        color: #334155;
        border-color: rgba(10, 22, 40, 0.16);
    }

    html.dark .inv-tipo-fisica,
    html.dark .inv-chip-share,
    .dark .inv-tipo-fisica,
    .dark .inv-chip-share {
        background: rgba(76, 154, 255, 0.16);
        color: #4c9aff;
        border-color: rgba(76, 154, 255, 0.45);
    }

    html.dark .inv-tipo-referenciado,
    html.dark .inv-chip-propio,
    .dark .inv-tipo-referenciado,
    .dark .inv-chip-propio {
        background: rgba(101, 84, 192, 0.2);
        color: #c0b6ff;
        border-color: rgba(101, 84, 192, 0.48);
    }

    html.dark .inv-tipo-extraordinario,
    html.dark .inv-chip-extra,
    html.dark .inv-mes-pill,
    html.dark .inv-opt-tag,
    .dark .inv-tipo-extraordinario,
    .dark .inv-chip-extra,
    .dark .inv-mes-pill,
    .dark .inv-opt-tag {
        background: rgba(255, 171, 0, 0.16);
        color: #ffc400;
        border-color: rgba(255, 171, 0, 0.5);
    }

    html.dark .inv-chip-stock,
    .dark .inv-chip-stock {
        background: rgba(54, 179, 126, 0.16);
        color: #57d9a3;
        border-color: rgba(54, 179, 126, 0.45);
    }

    html.dark .inv-chip-off,
    html.dark .inv-locked-tag,
    .dark .inv-chip-off,
    .dark .inv-locked-tag {
        background: rgba(255, 255, 255, 0.06);
        color: #e8eef6;
        border-color: rgba(255, 255, 255, 0.16);
    }

    .inv-leyenda {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        border-radius: 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .dark .inv-leyenda {
        background: #1e293b;
        border-color: #334155;
    }

    .inv-leyenda-item {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 12px;
        color: #475569;
        max-width: 280px;
    }

    .dark .inv-leyenda-item {
        color: #cbd5e1;
    }

    .inv-leyenda-item strong {
        display: block;
        color: #0f172a;
        margin-bottom: 0.1rem;
    }

    .dark .inv-leyenda-item strong {
        color: #f1f5f9;
    }

    .inv-persona-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        margin-bottom: 1rem;
        border-radius: 0.85rem;
        border: 1px solid #e2e8f0;
        background: #fff;
    }

    .dark .inv-persona-header {
        background: #1e293b;
        border-color: #334155;
    }

    .inv-persona-header.tipo-FISICA {
        border-left: 4px solid #3b82f6;
    }

    .inv-persona-header.tipo-REFERENCIADO {
        border-left: 4px solid #8b5cf6;
    }

    .inv-persona-header.tipo-EXTRAORDINARIO {
        border-left: 4px solid #f97316;
    }

    .inv-persona-meta {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .inv-persona-meta h3 {
        margin: 0;
        font-size: 1.15rem;
        color: #101D49;
    }

    .dark .inv-persona-meta h3 {
        color: #fff;
    }

    .inv-persona-rules {
        font-size: 12.5px;
        color: #64748b;
        margin: 0;
    }

    .dark .inv-persona-rules {
        color: #94a3b8;
    }

    .inv-section-title {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 12px 0;
    }

    .inv-section-asignados {
        background: #ecfdf5;
        color: #047857;
    }

    .inv-section-disponibles {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .inv-modo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    @media (max-width: 576px) {
        .inv-modo-grid {
            grid-template-columns: 1fr;
        }
    }

    .inv-modo-card {
        text-align: left;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.85rem 0.95rem;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }

    .dark .inv-modo-card {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    .inv-modo-card:hover {
        border-color: #94a3b8;
    }

    .inv-modo-card.is-active[data-value="0"] {
        border-color: #10b981;
        background: #ecfdf5;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
    }

    .inv-modo-card.is-active[data-value="1"] {
        border-color: #f97316;
        background: #fff7ed;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
    }

    .inv-modo-card.is-active[data-value="2"] {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .inv-modo-card.is-active[data-value="3"] {
        border-color: #8b5cf6;
        background: #f5f3ff;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
    }

    .inv-modo-card .modo-title {
        display: block;
        font-weight: 700;
        font-size: 0.92rem;
        margin-bottom: 0.2rem;
    }

    .inv-modo-card .modo-desc {
        display: block;
        font-size: 0.75rem;
        color: #64748b;
        line-height: 1.35;
    }

    .inv-modo-card.is-locked {
        cursor: default;
        opacity: 0.95;
    }

    .inventario-filtros .pill-filtro[data-filtro="no_presupuestados"].activo {
        background-color: #059669 !important;
    }

    .inventario-filtros .pill-filtro[data-filtro="presupuestados"].activo {
        background-color: #ea580c !important;
    }

    .inv-filtro-hint {
        font-size: 11px;
        color: #64748b;
        width: 100%;
        margin-top: 0.35rem;
    }

    .index-action--extra {
        color: #ea580c;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: lowercase;
        gap: 0.28rem;
        padding: 0.28rem 0.55rem;
        min-width: auto;
        width: auto !important;
        height: auto;
        white-space: nowrap;
        border-radius: 999px;
        border: 1px solid #fdba74;
        background: #fff7ed;
    }

    .index-action--extra:hover {
        background: #ffedd5;
        color: #c2410c;
        border-color: #fb923c;
    }

    .dark .index-action--extra {
        background: rgba(194, 65, 12, 0.18);
        color: #fdba74;
        border-color: rgba(251, 146, 60, 0.45);
    }

    .dark .index-action--extra:hover {
        background: rgba(194, 65, 12, 0.32);
        color: #fed7aa;
    }
</style>
