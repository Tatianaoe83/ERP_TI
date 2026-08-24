@php
    // Laravel 8 no trae @selected ni @disabled (llegaron en la 9): se arman a mano
    // o se imprimen como texto dentro del <option>.
    $tiene = (bool) $fila->tiene_licencia;
    $original = $fila->original === null ? '' : (string) (int) $fila->original;

    $claseTiene = $tiene ? 'si' : 'no';
    $claseOriginal = $original === '' ? 'pendiente' : ($original === '1' ? 'si' : 'alerta');
    $etiqueta = $fila->NombreLicencia ?: 'sin nombre';
@endphp

{{-- Una fila por licencia del empleado auditado. Los dos estados se capturan aquí
     mismo: son <select> reales, no celdas que sólo parecen editables. --}}
<tr data-fila="{{ $fila->id }}">
    {{-- La licencia es el dato principal de la fila: va sin atenuar. --}}
    <td class="aud-strong">{{ $fila->NombreLicencia ?: '—' }}</td>

    <td>
        <label class="aud-sr" for="tiene-{{ $fila->id }}">¿Tiene la licencia {{ $etiqueta }}?</label>
        <select id="tiene-{{ $fila->id }}"
                class="aud-editable aud-editable--{{ $claseTiene }}"
                data-fila="{{ $fila->id }}" data-campo="tiene_licencia">
            <option value="1" {{ $tiene ? 'selected' : '' }}>Sí</option>
            <option value="0" {{ $tiene ? '' : 'selected' }}>No</option>
        </select>
    </td>

    {{-- Tri-estado: sin revisar no es lo mismo que "no original", por eso es una
         opción propia y no la ausencia de valor. --}}
    <td>
        <label class="aud-sr" for="original-{{ $fila->id }}">¿Es original la licencia {{ $etiqueta }}?</label>
        <select id="original-{{ $fila->id }}"
                class="aud-editable aud-editable--{{ $claseOriginal }}"
                data-fila="{{ $fila->id }}" data-campo="original"
                {{ $tiene ? '' : 'disabled' }}>
            <option value=""  {{ $original === ''  ? 'selected' : '' }}>Sin revisar</option>
            <option value="1" {{ $original === '1' ? 'selected' : '' }}>Sí</option>
            <option value="0" {{ $original === '0' ? 'selected' : '' }}>No</option>
        </select>
    </td>
</tr>
