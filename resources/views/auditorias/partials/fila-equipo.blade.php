@php
    $chips = [
        1 => ['clase' => 'extra',  'icono' => 'fa-calendar-alt', 'texto' => 'Extra'],
        2 => ['clase' => 'propio', 'icono' => 'fa-user-shield',  'texto' => 'Propio'],
    ];
    $chip = $chips[$fila->tipoEquipo] ?? ['clase' => 'stock', 'icono' => 'fa-cube', 'texto' => 'Stock'];
    $licencias = $fila->listaLicencias();
    // Piratas del resguardante de ESTE equipo, no de todo el inventario.
    $piratasDelEquipo = $piratas[$fila->InventarioID] ?? [];
    $licenciasPiratas = array_values(array_filter(
        $licencias,
        fn($l) => in_array(mb_strtolower($l), $piratasDelEquipo, true)
    ));
@endphp

<tr>
    <td class="aud-strong">{{ $fila->CategoriaEquipo }}</td>
    <td>{{ trim($fila->Marca . ' ' . $fila->Modelo) ?: '—' }}</td>
    <td class="aud-num">{{ $fila->NumSerie ?: '—' }}</td>
    <td>{{ $fila->NombreEmpleado }}</td>
    <td>{{ $fila->GerenciaEquipo }}</td>
    @unless($soloPiratas)
    <td>
        <span class="aud-chip aud-chip--{{ $chip['clase'] }}">
            <i class="fas {{ $chip['icono'] }}" aria-hidden="true"></i> {{ $chip['texto'] }}
        </span>
    </td>
    @endunless
    <td>
        @if($soloPiratas)
            @forelse($licenciasPiratas as $licencia)
                <span class="aud-mini aud-mini--pirata" title="Licencia pirata">{{ $licencia }}</span>
            @empty
                <span class="aud-muted">Sin licencias piratas</span>
            @endforelse
        @else
            @forelse($licencias as $licencia)
                @php $esPirata = in_array(mb_strtolower($licencia), $piratasDelEquipo, true); @endphp
                <span class="aud-mini {{ $esPirata ? 'aud-mini--pirata' : '' }}"
                      @if($esPirata) title="Licencia pirata" @endif>{{ $licencia }}</span>
            @empty
                <span class="aud-muted">Sin licencias</span>
            @endforelse
        @endif
    </td>
</tr>
