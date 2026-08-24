@php
    $switchId = $switchId ?? 'editPresupuestadoEquipo';
    $forzado = $presupuestadoForzado ?? false;
    // Sólo los equipos tienen la cuarta modalidad "Propio" (columna tipoEquipo).
    $permitePropio = $permitePropio ?? false;
    $columnas = $permitePropio ? 'inv-segment--4' : 'inv-segment--3';
@endphp

<div class="dark:text-white">
    <label class="inv-form-label d-block">Tipo de asignación</label>

    @if($forzado)
        <div class="inv-segment {{ $columnas }}" data-switch="#{{ $switchId }}">
            <button type="button" class="inv-modo-card is-active is-locked" data-value="1" disabled style="grid-column: 1 / -1;">
                <span class="modo-title"><i class="fas fa-calendar-alt"></i> Registro presupuestado</span>
                <span class="modo-desc">Todo es extra</span>
            </button>
        </div>
        <div class="inv-modo-hint extra" style="display:flex;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Persona extraordinaria: todo lo asignado es presupuesto futuro (extra).</span>
        </div>
        <input type="hidden" id="{{ $switchId }}" value="1">
        <span id="{{ $switchId }}Label" class="d-none">Extra</span>
    @else
        <div class="inv-segment {{ $columnas }}" data-switch="#{{ $switchId }}">
            <button type="button" class="inv-modo-card is-active" data-value="0">
                <span class="modo-title"><i class="fas fa-cube"></i> Stock</span>
                <span class="modo-desc">Sólo inventario</span>
            </button>
            <button type="button" class="inv-modo-card" data-value="1">
                <span class="modo-title"><i class="fas fa-calendar-alt"></i> Extra</span>
                <span class="modo-desc">Sólo presupuesto</span>
            </button>
            <button type="button" class="inv-modo-card" data-value="2">
                <span class="modo-title"><i class="fas fa-link"></i> Compartido</span>
                <span class="modo-desc">Inventario y presupuesto</span>
            </button>
            @if($permitePropio)
            <button type="button" class="inv-modo-card" data-value="3">
                <span class="modo-title"><i class="fas fa-user-shield"></i> Equipo propio</span>
                <span class="modo-desc">Del empleado</span>
            </button>
            @endif
        </div>
        <div class="inv-modo-hint stock" data-hint-for="{{ $switchId }}">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Sólo inventario actual. No entra al reporte de presupuesto.</span>
        </div>
        <div class="inv-modo-hint extra" data-hint-for="{{ $switchId }}" style="display:none;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Sólo proyección. Cuando se asigne de verdad, cámbielo a Stock o Compartido.</span>
        </div>
        <div class="inv-modo-hint share" data-hint-for="{{ $switchId }}" style="display:none;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>Un solo registro: aparece en inventario y en presupuesto. No hay que duplicar.</span>
        </div>
        @if($permitePropio)
        <div class="inv-modo-hint propio" data-hint-for="{{ $switchId }}" style="display:none;">
            <i class="fas fa-info-circle mt-1"></i>
            <span>El equipo es del empleado: no lleva precio, folio, fecha de compra ni mes de pago, y nunca entra al presupuesto.</span>
        </div>
        @endif
        <input type="hidden" id="{{ $switchId }}" value="0">
        <span id="{{ $switchId }}Label" class="d-none">Stock</span>
    @endif
</div>
