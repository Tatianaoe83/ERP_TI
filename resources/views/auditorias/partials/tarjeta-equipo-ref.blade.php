@php
    // Tarjeta de sólo lectura: es el snapshot de OTRA corrida, nunca se captura
    // desde aquí. Sólo se pinta si ya no aparece en la corrida de la derecha.
    $eq = $fila->equipo;
    $vivo = (bool) $eq;

    // Mientras el equipo siga en el inventario se lee en vivo (por si se corrigió
    // un dato); si ya no existe, se cae a la ficha que se congeló al generar esa
    // corrida. Sólo cuando ni eso hay, es un registro viejo sin identidad.
    $categoria = $eq->CategoriaEquipo ?? $fila->CategoriaEquipo ?? null;
    $marcaEquipo = $eq->Marca ?? $fila->Marca ?? null;
    $modelo = $eq->Modelo ?? $fila->Modelo ?? null;
    $serie = $eq->NumSerie ?? $fila->NumSerie ?? null;
    $folio = $eq->Folio ?? $fila->Folio ?? null;

    $hayFicha = $categoria || $marcaEquipo || $modelo || $serie || $folio;
    $nombre = trim((string) $marcaEquipo) ?: ($categoria ?: ($hayFicha ? 'Equipo' : 'Equipo sin identificar'));

    $marca = $fila->marca ?? 'igual';
    $esBaja = $marca === 'baja';

    $demora = min(($loop->index ?? 0) * 35, 350);
@endphp

<div class="aud-card aud-card--ref {{ $esBaja ? 'aud-card--baja aud-card--fantasma' : 'aud-card--igual' }}"
     style="animation-delay: {{ $demora }}ms">

    @if($esBaja)
        <span class="aud-card__badge aud-marca aud-marca--baja"
              title="Ya no aparece en el resguardo de este empleado; puede seguir en el inventario general, sólo asignado a alguien más o sin asignar.">
            <i class="fas fa-right-left" aria-hidden="true"></i> Ya no lo tiene
        </span>
    @else
        <span class="aud-card__badge aud-marca aud-marca--igual">
            <i class="fas fa-check" aria-hidden="true"></i> Se mantiene
        </span>
    @endif

    <div class="aud-card__cab">
        @if($categoria)
            <i class="fas {{ Str::contains(Str::upper($categoria), 'LAPTOP') ? 'fa-laptop' : 'fa-desktop' }} aud-card__ico"
               aria-hidden="true"></i>
        @endif
        <div>
            <div class="aud-card__titulo">{{ $nombre }}</div>
            <div class="aud-mini aud-muted">
                @if($hayFicha)
                    {{ $categoria }}@if($modelo) · {{ $modelo }}@endif
                @else
                    Sin ficha guardada de este equipo
                @endif
            </div>
        </div>
    </div>

    <div class="aud-card__meta">
        <span class="aud-card__dato">
            <span class="aud-eqficha__etq">Serie</span>
            <span class="aud-num">{{ $serie ?? '—' }}</span>
        </span>
        <span class="aud-card__dato">
            <span class="aud-eqficha__etq">Folio</span>
            <span class="aud-num">{{ $folio ?? '—' }}</span>
        </span>
    </div>

    @if($fila->observaciones)
        <p class="aud-card__nota-vieja">{{ $fila->observaciones }}</p>
    @endif
</div>
