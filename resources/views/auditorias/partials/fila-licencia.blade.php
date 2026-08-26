@php
    $original = $fila->original === null ? '' : (string) (int) $fila->original;

    $claseOriginal = $original === '' ? 'pendiente' : ($original === '1' ? 'si' : 'alerta');
    $etiqueta = $fila->NombreLicencia ?: 'sin nombre';

    $esBaja = ! (bool) $fila->tiene_licencia;

    $marca = $fila->marca ?? 'nueva';

    $marcas = [
        'nueva'  => ['icono' => 'fa-circle-plus',          'texto' => 'Se agregó',  'clase' => 'nueva'],
        'cambio' => ['icono' => 'fa-triangle-exclamation', 'texto' => 'Cambió',     'clase' => 'cambio'],
        'baja'   => ['icono' => 'fa-circle-minus',         'texto' => 'Se quitó',   'clase' => 'baja'],
        'igual'  => ['icono' => 'fa-equals',               'texto' => 'Sin cambio', 'clase' => 'igual'],
    ];
    $m = $marcas[$marca] ?? $marcas['nueva'];

    // Sólo se compara si hay corrida anterior: en la primera auditoría todo sería
    // "nuevo" sin serlo, así que la tarjeta se pinta neutral y sin badge.
    $comparando = $comparando ?? false;

    $demora = min(($loop->index ?? 0) * 35, 350);
@endphp

<div class="aud-card {{ $comparando ? 'aud-card--' . $m['clase'] : '' }} {{ $esBaja ? 'aud-card--fantasma' : '' }}"
     style="animation-delay: {{ $demora }}ms"
     data-fila="{{ $fila->id }}" data-marca="{{ $marca }}">

    @if($comparando)
        <span class="aud-card__badge aud-marca aud-marca--{{ $m['clase'] }}">
            <i class="fas {{ $m['icono'] }}" aria-hidden="true"></i> {{ $m['texto'] }}
        </span>
    @endif

    <div class="aud-card__cab">
        <i class="fas fa-key aud-card__ico" aria-hidden="true"></i>
        <div>
            <div class="aud-card__titulo">{{ $fila->NombreLicencia ?: '—' }}</div>
            <div class="aud-mini aud-muted">
                @if($esBaja)
                    Ya no la tiene
                @else
                    La tiene asignada
                @endif
            </div>
        </div>
    </div>

    <div class="aud-card__campo">
        <label class="aud-campo__label" for="original-{{ $fila->id }}">¿Es original?</label>
        <select id="original-{{ $fila->id }}"
                class="aud-editable aud-editable--{{ $esBaja ? 'pendiente' : $claseOriginal }}"
                data-fila="{{ $fila->id }}" data-campo="original" data-recurso="licencias"
                {{ $esBaja ? 'disabled' : '' }}>
            <option value=""  {{ $original === ''  ? 'selected' : '' }}>Sin revisar</option>
            <option value="1" {{ $original === '1' ? 'selected' : '' }}>Sí</option>
            <option value="0" {{ $original === '0' ? 'selected' : '' }}>No</option>
        </select>
    </div>

    <label class="aud-sr" for="obs-lic-{{ $fila->id }}">Observaciones de {{ $etiqueta }}</label>
    <textarea id="obs-lic-{{ $fila->id }}" class="aud-obs" rows="1" maxlength="2000"
              placeholder="{{ $esBaja ? 'Baja de inventario…' : 'Anotar hallazgo…' }}"
              data-fila="{{ $fila->id }}" data-campo="observaciones" data-recurso="licencias"
              {{ $esBaja ? 'disabled' : '' }}>{{ $fila->observaciones }}</textarea>
</div>
