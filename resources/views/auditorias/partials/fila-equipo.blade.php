@php
    $chips = [
        1 => ['clase' => 'extra',  'icono' => 'fa-calendar-alt', 'texto' => 'Extra'],
        2 => ['clase' => 'share',  'icono' => 'fa-link',         'texto' => 'Compartido'],
        3 => ['clase' => 'propio', 'icono' => 'fa-user-shield',  'texto' => 'Propio'],
    ];
    $chip = $chips[$fila->tipoEquipo] ?? ['clase' => 'stock', 'icono' => 'fa-cube', 'texto' => 'Stock'];
@endphp

{{-- Una fila por licencia: el equipo se repite tantas veces como licencias tenga. --}}
<tr>
    <td class="aud-strong">{{ $fila->CategoriaEquipo }}</td>
    <td>{{ trim($fila->Marca . ' ' . $fila->Modelo) ?: '—' }}</td>
    <td class="aud-num">{{ $fila->NumSerie ?: '—' }}</td>
    <td>{{ $fila->NombreEmpleado }}</td>
    <td>{{ $fila->GerenciaEquipo }}</td>
    <td>
        <span class="aud-chip aud-chip--{{ $chip['clase'] }}">
            <i class="fas {{ $chip['icono'] }}" aria-hidden="true"></i> {{ $chip['texto'] }}
        </span>
    </td>
    <td>
        @if($fila->tiene_licencia && $fila->NombreLicencia)
            <span class="aud-mini {{ $fila->pirata ? 'aud-mini--pirata' : '' }}"
                  @if($fila->pirata) title="Licencia pirata" @endif>{{ $fila->NombreLicencia }}</span>
        @else
            <span class="aud-muted">Sin licencia</span>
        @endif
    </td>
    <td>
        {{-- Texto además del color: el estado no puede depender sólo del tono. --}}
        @if($fila->en_dominio)
            <span class="aud-chip aud-chip--stock">
                <i class="fas fa-network-wired" aria-hidden="true"></i> En dominio
            </span>
        @else
            <span class="aud-muted">Fuera del dominio</span>
        @endif
    </td>
</tr>
