@php
    // Tarjeta de sólo lectura: es el snapshot de OTRA corrida, nunca se captura
    // desde aquí.
    $marca = $fila->marca ?? 'igual';
    $esBaja = $marca === 'baja';

    $estado = ! $fila->tiene_licencia
        ? 'No la tenía'
        : ($fila->original === null ? 'Sin revisar' : ($fila->original ? 'Original' : 'No original'));

    $claseEstado = ! $fila->tiene_licencia
        ? 'no'
        : ($fila->original === null ? 'pendiente' : ($fila->original ? 'si' : 'alerta'));

    $demora = min(($loop->index ?? 0) * 35, 350);
@endphp

<div class="aud-card aud-card--ref {{ $esBaja ? 'aud-card--baja aud-card--fantasma' : 'aud-card--igual' }}"
     style="animation-delay: {{ $demora }}ms">

    @if($esBaja)
        <span class="aud-card__badge aud-marca aud-marca--baja">
            <i class="fas fa-circle-minus" aria-hidden="true"></i> Se quitó
        </span>
    @else
        <span class="aud-card__badge aud-marca aud-marca--igual">
            <i class="fas fa-check" aria-hidden="true"></i> Se mantiene
        </span>
    @endif

    <div class="aud-card__cab">
        <i class="fas fa-key aud-card__ico" aria-hidden="true"></i>
        <div class="aud-card__titulo">{{ $fila->NombreLicencia ?: '—' }}</div>
    </div>

    <span class="aud-estado aud-estado--{{ $claseEstado }}">{{ $estado }}</span>

    @if($fila->observaciones)
        <p class="aud-card__nota-vieja">{{ $fila->observaciones }}</p>
    @endif
</div>
