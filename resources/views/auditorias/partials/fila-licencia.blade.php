@php
    $original = $fila->original === null ? '' : (string) (int) $fila->original;

    $claseOriginal = $original === '' ? 'pendiente' : ($original === '1' ? 'si' : 'alerta');
    $etiqueta = $fila->NombreLicencia ?: 'sin nombre';

    $esBaja = ! (bool) $fila->tiene_licencia;

    $marca = $fila->marca ?? 'nueva';
    $previa = $fila->previa ?? null;

    $marcas = [
        'nueva'  => ['icono' => 'fa-circle-plus',          'texto' => 'Nueva',      'clase' => 'nueva'],
        'cambio' => ['icono' => 'fa-triangle-exclamation', 'texto' => 'Cambió',     'clase' => 'cambio'],
        'baja'   => ['icono' => 'fa-circle-minus',         'texto' => 'Baja',       'clase' => 'baja'],
        'igual'  => ['icono' => 'fa-equals',               'texto' => 'Sin cambio', 'clase' => 'igual'],
    ];
    $m = $marcas[$marca] ?? $marcas['nueva'];
@endphp

{{-- Una fila por licencia del empleado auditado. --}}
<tr data-fila="{{ $fila->id }}" data-marca="{{ $marca }}"
    @if($esBaja) class="aud-fila--baja" @endif>

    {{-- Licencia: dato principal de la fila. --}}
    <td class="aud-strong">{{ $fila->NombreLicencia ?: '—' }}</td>

    {{-- Tiene licencia: siempre Sí para licencias del inventario; No para bajas. --}}
    <td>
        @if($esBaja)
            <span class="aud-badge aud-badge--no">No</span>
        @else
            <span class="aud-badge aud-badge--si">Sí</span>
        @endif
    </td>

    {{-- ¿Es original?: tri-estado. Deshabilitado en bajas porque ya no existe. --}}
    <td>
        <label class="aud-sr" for="original-{{ $fila->id }}">¿Es original la licencia {{ $etiqueta }}?</label>
        <select id="original-{{ $fila->id }}"
                class="aud-editable aud-editable--{{ $esBaja ? 'pendiente' : $claseOriginal }}"
                data-fila="{{ $fila->id }}" data-campo="original" data-recurso="licencias"
                {{ $esBaja ? 'disabled' : '' }}>
            <option value=""  {{ $original === ''  ? 'selected' : '' }}>Sin revisar</option>
            <option value="1" {{ $original === '1' ? 'selected' : '' }}>Sí</option>
            <option value="0" {{ $original === '0' ? 'selected' : '' }}>No</option>
        </select>
    </td>

    {{-- Observaciones de ESTA corrida: nace vacía, no hereda la anterior. --}}
    <td>
        <label class="aud-sr" for="obs-lic-{{ $fila->id }}">Observaciones de {{ $etiqueta }}</label>
        <textarea id="obs-lic-{{ $fila->id }}" class="aud-obs" rows="1" maxlength="2000"
                  placeholder="{{ $esBaja ? 'Baja de inventario…' : 'Anotar hallazgo…' }}"
                  data-fila="{{ $fila->id }}" data-campo="observaciones" data-recurso="licencias"
                  {{ $esBaja ? 'disabled' : '' }}>{{ $fila->observaciones }}</textarea>
    </td>

    {{-- Historial: qué decía la corrida anterior de esta misma licencia. Solo lectura. --}}
    <td>
        <div class="aud-antes">
            <span class="aud-marca aud-marca--{{ $m['clase'] }}">
                <i class="fas {{ $m['icono'] }}" aria-hidden="true"></i> {{ $m['texto'] }}
            </span>

            @if($previa)
                <span class="aud-antes__estado">
                    {{ $previa->original === null ? 'Sin revisar' : ($previa->original ? 'Original' : 'No original') }}
                </span>

                @if($previa->observaciones)
                    <span class="aud-antes__nota" title="{{ $previa->observaciones }}">
                        "{{ \Illuminate\Support\Str::limit($previa->observaciones, 60) }}"
                    </span>
                @endif
            @else
                <span class="aud-antes__estado aud-muted">No estaba en la auditoría anterior</span>
            @endif
        </div>
    </td>
</tr>
