<div class="modal fade" id="modalFaltantes" tabindex="-1" aria-labelledby="modalFaltantesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content val-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFaltantesLabel">
                    <i class="fas fa-exclamation-triangle val-modal__aviso" aria-hidden="true"></i>
                    Faltan datos para el reporte
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <p class="val-modal__intro">Hay inventarios incompletos. Complétalos para generar el presupuesto.</p>

                <div class="val-modal__lista" id="infoAdicionalEmpleados">
                    <p class="val-modal__fila">
                        <span>Total de empleados</span>
                        <span id="totalEmpleadosModal" class="erp-chip erp-chip--ok">0</span>
                    </p>
                    <p class="val-modal__fila">
                        <span>Insumos mensuales con renovación y sin mes de pago</span>
                        <span id="sinMesPagoMensualModal" class="erp-chip erp-chip--ok">0</span>
                    </p>
                    <p class="val-modal__fila">
                        <span>Insumos anuales con renovación y sin mes de pago</span>
                        <span id="sinMesPagoAnualModal" class="erp-chip erp-chip--ok">0</span>
                    </p>
                    <p class="val-modal__fila">
                        <span>Líneas disponibles con renovación y sin empleado</span>
                        <span id="lineasSinAsignarConFechaModal" class="erp-chip erp-chip--ok">0</span>
                    </p>
                    <p class="val-modal__fila">
                        <span>Insumos disponibles con renovación y sin empleado</span>
                        <span id="insumosSinAsignarConFechaModal" class="erp-chip erp-chip--ok">0</span>
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="index-page__btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="{{ route('inventarios.index') }}" class="index-page__btn-primary">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                    Corregir en inventarios
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .val-modal__aviso {
        margin-right: 0.45rem;
        color: var(--erp-warning, #ffab00);
    }
    .val-modal__intro {
        margin: 0 0 0.9rem;
        font-size: 0.875rem;
        line-height: 1.45;
        color: var(--erp-muted, #64748b);
    }
    .val-modal__lista {
        border: 1px solid var(--erp-border, #e2e8f0);
        border-radius: 12px;
        overflow: hidden;
        background: var(--erp-surface, #fff);
    }
    .val-modal__fila {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin: 0;
        padding: 0.75rem 0.95rem;
        font-size: 0.875rem;
        line-height: 1.35;
        color: var(--erp-text, #0a1628);
    }
    .val-modal__fila + .val-modal__fila {
        border-top: 1px solid var(--erp-border, #e2e8f0);
    }
    html.dark .val-modal__intro,
    .dark .val-modal__intro {
        color: #a3b3c9;
    }
    html.dark .val-modal__lista,
    .dark .val-modal__lista {
        background: #161920;
        border-color: #2a2f3a;
    }
    html.dark .val-modal__fila,
    .dark .val-modal__fila {
        color: #e8eef6;
    }
    html.dark .val-modal__fila + .val-modal__fila,
    .dark .val-modal__fila + .val-modal__fila {
        border-top-color: #2a2f3a;
    }
</style>
