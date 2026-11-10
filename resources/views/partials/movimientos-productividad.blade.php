@php
    $mov = isset($productividad) ? $productividad : ($stats['movimientos'] ?? null);
    $horas = (float) ($mov['horas_promedio'] ?? 0);
    $tiempo = $horas >= 24 ? round($horas / 24, 1) . ' días' : round($horas, 1) . ' h';
    $anioMov = $mov['anio'] ?? now()->year;
@endphp
@if($mov)
<p class="dash-section-title">Movimientos ERP {{ $anioMov }}</p>
<div class="dash-kpi-grid dash-kpi-grid--ops">
    <a href="{{ route('movimientos-erp.index', ['estatus' => \App\Models\SolicitudMovimiento::PENDIENTE]) }}" class="dash-kpi" style="text-decoration:none;color:inherit;">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Pendiente aprobación</p>
                <p class="dash-kpi__hint">Esperan el visto bueno</p>
                <p class="dash-kpi__value">{{ $mov['pendientes'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-clock"></i></span>
        </div>
    </a>
    <a href="{{ route('movimientos-erp.index', ['estatus' => \App\Models\SolicitudMovimiento::APROBADA]) }}" class="dash-kpi" style="text-decoration:none;color:inherit;">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Aprobadas</p>
                <p class="dash-kpi__hint">Falta confirmarlas en Neodata</p>
                <p class="dash-kpi__value">{{ $mov['aprobadas'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-check"></i></span>
        </div>
    </a>
    <a href="{{ route('movimientos-erp.index', ['estatus' => \App\Models\SolicitudMovimiento::APLICADA]) }}" class="dash-kpi" style="text-decoration:none;color:inherit;">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Aplicadas</p>
                <p class="dash-kpi__hint">Hechas en Neodata este año</p>
                <p class="dash-kpi__value">{{ $mov['aplicadas'] ?? 0 }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-check-circle"></i></span>
        </div>
    </a>
    <div class="dash-kpi">
        <div class="dash-kpi__top">
            <div>
                <p class="dash-kpi__label">Tiempo promedio</p>
                <p class="dash-kpi__hint">De la solicitud a aplicada · {{ $mov['rechazadas'] ?? 0 }} rechazadas</p>
                <p class="dash-kpi__value">{{ $tiempo }}</p>
            </div>
            <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-hourglass-half"></i></span>
        </div>
    </div>
</div>
@endif
