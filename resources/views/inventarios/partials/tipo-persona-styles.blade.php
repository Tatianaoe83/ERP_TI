<style>
    .inv-tipo-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        line-height: 1.2;
        white-space: nowrap;
    }

    .inv-tipo-fisica {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .inv-tipo-referenciado {
        background: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
    }

    .inv-tipo-extraordinario {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }

    .inv-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.22rem 0.55rem;
        border-radius: 0.4rem;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .inv-chip-stock {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .inv-chip-extra {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fdba74;
    }

    .inv-chip-share {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .inv-chip-propio {
        background: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
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
</style>
