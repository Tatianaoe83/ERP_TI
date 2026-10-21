<div class="aud-historial__inner">
    <div class="aud-historial__panel">
        @if($datos->isEmpty())
            <p class="aud-muted" style="margin:0;padding:0.9rem 1rem;">Este empleado no tiene inventario asignado.</p>
        @else
        <table class="aud-historial__tabla">
            <thead>
                <tr>
                    <th scope="col">Categoría</th>
                    <th scope="col">Marca</th>
                    <th scope="col">Modelo</th>
                    <th scope="col">Serie</th>
                    <th scope="col">Asignación</th>
                    <th scope="col">Folio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos as $item)
                @php
                    $icono = $item->tipo === 'TELEFONO' ? 'fa-phone' : ($item->tipo === 'INSUMO' ? 'fa-key' : 'fa-laptop');
                @endphp
                <tr class="aud-historial__fila">
                    <td>
                        <span class="aud-alcance__item">
                            <i class="fas {{ $icono }}" aria-hidden="true"></i>
                            {{ $item->categoria ?: 'Sin categoría' }}
                        </span>
                    </td>
                    <td>{{ $item->Marca ?: '—' }}</td>
                    <td>{{ $item->Modelo ?: '—' }}</td>
                    <td class="aud-num">{{ $item->NumSerie ?: '—' }}</td>
                    <td class="aud-num">{{ $item->FechaAsignacion ?: '—' }}</td>
                    <td class="aud-strong">{{ empty($item->Folio) ? 'Sin asignar' : $item->Folio }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
