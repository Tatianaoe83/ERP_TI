@php
    // "presente" es tri-estado igual que "original": null = todavía no se revisó.
    $presente = $fila->presente === null ? '' : (string) (int) $fila->presente;
    $clasePresente = $presente === '' ? 'pendiente' : ($presente === '1' ? 'si' : 'alerta');

    $eq = $fila->equipo;
    $nombre = $eq ? (trim((string) $eq->Marca) ?: ($eq->CategoriaEquipo ?: 'Equipo')) : 'Equipo dado de baja';
    $etiqueta = $nombre . ($eq && $eq->NumSerie ? ' serie ' . $eq->NumSerie : '');

    $marca = $fila->marca ?? 'nueva';
    $previa = $fila->previa ?? null;

    $marcas = [
        'nueva'  => ['icono' => 'fa-circle-plus',          'texto' => 'Nuevo',      'clase' => 'nueva'],
        'cambio' => ['icono' => 'fa-triangle-exclamation', 'texto' => 'Cambió',     'clase' => 'cambio'],
        'baja'   => ['icono' => 'fa-circle-minus',         'texto' => 'No apareció','clase' => 'baja'],
        'igual'  => ['icono' => 'fa-equals',               'texto' => 'Sin cambio', 'clase' => 'igual'],
    ];
    $m = $marcas[$marca] ?? $marcas['nueva'];
@endphp

{{-- Una fila por equipo que el empleado resguardaba al generarse la corrida. --}}
<tr data-fila="{{ $fila->id }}" data-marca="{{ $marca }}">

    {{-- Equipo: la ficha se lee del inventario en vivo, no del snapshot. --}}
    <td>
        <div class="aud-strong">
            @if($eq)
                <i class="fas {{ Str::contains(Str::upper($eq->CategoriaEquipo ?? ''), 'LAPTOP') ? 'fa-laptop' : 'fa-desktop' }}"
                   aria-hidden="true"></i>
            @endif
            {{ $nombre }}
        </div>
        <div class="aud-mini aud-muted">
            @if($eq)
                {{ $eq->CategoriaEquipo }}@if($eq->Modelo) · {{ $eq->Modelo }}@endif
            @else
                Ya no está en el inventario
            @endif
        </div>
    </td>

    <td class="aud-num">{{ $eq->NumSerie ?? '—' }}</td>
    <td class="aud-num">{{ $eq->Folio ?? '—' }}</td>

    {{-- ¿Está el equipo?: tri-estado. --}}
    <td>
        <label class="aud-sr" for="presente-{{ $fila->id }}">¿Está el equipo {{ $etiqueta }}?</label>
        <select id="presente-{{ $fila->id }}"
                class="aud-editable aud-editable--{{ $clasePresente }}"
                data-fila="{{ $fila->id }}" data-campo="presente" data-recurso="equipos">
            <option value=""  {{ $presente === ''  ? 'selected' : '' }}>Sin revisar</option>
            <option value="1" {{ $presente === '1' ? 'selected' : '' }}>Sí</option>
            <option value="0" {{ $presente === '0' ? 'selected' : '' }}>No</option>
        </select>
    </td>

    {{-- Observaciones de ESTA corrida: nace vacía, no hereda la anterior. --}}
    <td>
        <label class="aud-sr" for="obs-eq-{{ $fila->id }}">Observaciones de {{ $etiqueta }}</label>
        <textarea id="obs-eq-{{ $fila->id }}" class="aud-obs" rows="1" maxlength="2000"
                  placeholder="Anotar hallazgo…"
                  data-fila="{{ $fila->id }}" data-campo="observaciones" data-recurso="equipos">{{ $fila->observaciones }}</textarea>
    </td>

    {{-- Historial: qué decía la corrida anterior de este mismo equipo. Solo lectura. --}}
    <td>
        <div class="aud-antes">
            <span class="aud-marca aud-marca--{{ $m['clase'] }}">
                <i class="fas {{ $m['icono'] }}" aria-hidden="true"></i> {{ $m['texto'] }}
            </span>

            @if($previa)
                <span class="aud-antes__estado">
                    {{ $previa->presente === null ? 'Sin revisar' : ($previa->presente ? 'Estaba' : 'No apareció') }}
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
