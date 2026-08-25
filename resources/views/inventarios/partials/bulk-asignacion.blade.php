@if($empleadoActivo && $permitePresupuestado && !$presupuestadoForzado)
<div class="inv-bulk" data-bulk-table="{{ $tabla }}" data-bulk-tipo="{{ $tipo }}">
    <label class="inv-bulk-all-wrap">
        <input type="checkbox" class="inv-bulk-all">
        <span>Seleccionar</span>
    </label>
    <span class="inv-bulk-count">0 seleccionados</span>
    <button type="button" class="inv-bulk-btn inv-bulk-btn--stock" data-modo="0" disabled>
        <i class="fas fa-cube"></i> Pasar a Stock
    </button>
    <button type="button" class="inv-bulk-btn inv-bulk-btn--share" data-modo="2" disabled>
        <i class="fas fa-link"></i> Pasar a Compartido
    </button>
    <span class="inv-bulk-help">Solo stock y compartido. Los extra se editan uno por uno.</span>
</div>
@endif
