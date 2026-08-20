@php
    $switchId = $switchId ?? 'editPresupuestadoEquipo';
    $forzado = $presupuestadoForzado ?? false;
    // Sólo los equipos tienen la tercera modalidad "Propio" (columna tipoEquipo).
    $permitePropio = $permitePropio ?? false;
@endphp

<div class="dark:text-white">
    <label class="inv-form-label d-block">Tipo de asignación</label>

    @if($forzado)
        <div class="inv-segment" data-switch="#{{ $switchId }}">
            <button type="button" class="inv-modo-card is-active is-locked" data-value="1" disabled style="grid-column: 1 / -1;">
                <span class="modo-title"><i class="fas fa-calendar-alt"></i> Registro presupuestado</span>
                <span class="modo-desc">Todo es extra</span>
            </button>
        </div>
        <div class="inv-modo-hint extra" style="display:flex;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Persona extraordinaria: todo lo asignado es presupuesto futuro (extra).</span>
        </div>
        <input type="hidden" id="{{ $switchId }}Valor" value="1">
        <input type="checkbox" class="d-none" role="switch" id="{{ $switchId }}" checked>
        <span id="{{ $switchId }}Label" class="d-none">Si</span>
    @else
        <div class="inv-segment {{ $permitePropio ? 'inv-segment-3' : '' }}" data-switch="#{{ $switchId }}">
            <button type="button" class="inv-modo-card is-active" data-value="0">
                <span class="modo-title"><i class="fas fa-cube"></i> Asignación de stock</span>
                <span class="modo-desc">Inventario actual</span>
            </button>
            <button type="button" class="inv-modo-card" data-value="1">
                <span class="modo-title"><i class="fas fa-calendar-alt"></i> Registro presupuestado</span>
                <span class="modo-desc">Extra / futuro</span>
            </button>
            @if($permitePropio)
            <button type="button" class="inv-modo-card" data-value="2">
                <span class="modo-title"><i class="fas fa-user-shield"></i> Equipo propio</span>
                <span class="modo-desc">Del empleado</span>
            </button>
            @endif
        </div>
        <div class="inv-modo-hint stock" data-hint-for="{{ $switchId }}">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Modo stock: el equipo/insumo/línea queda en resguardo actual de la persona.</span>
        </div>
        <div class="inv-modo-hint extra" data-hint-for="{{ $switchId }}" style="display:none;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Modo planificación: registra un gasto proyectado / extra para presupuesto futuro.</span>
        </div>
        @if($permitePropio)
        <div class="inv-modo-hint propio" data-hint-for="{{ $switchId }}" style="display:none;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Equipo propiedad del empleado: se lista con el stock, pero no genera costo ni entra al presupuesto.</span>
        </div>
        @endif
        <input type="hidden" id="{{ $switchId }}Valor" value="0">
        <div class="form-check form-switch d-none">
            <input class="form-check-input" type="checkbox" role="switch" id="{{ $switchId }}">
            <label class="form-check-label" for="{{ $switchId }}" id="{{ $switchId }}Label">No</label>
        </div>
    @endif
</div>
