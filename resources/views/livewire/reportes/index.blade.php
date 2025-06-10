<div class="container py-3" style="max-width: 1200px;">
    {{-- Título del reporte --}}
    <div class="p-3 border rounded bg-light mb-3">
        <label class="fw-bold d-block mb-2">Título del Reporte</label>
        <input wire:model="titulo" type="text" id="titulo" class="form-control form-control-sm" placeholder="Ingresa el título del reporte">
    </div>

    {{-- Tabla principal y relación --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="p-3 border rounded bg-white shadow-sm">
                <label for="modelo" class="fw-semibold mb-2 d-block">Tabla Principal</label>
                <select wire:model="modelo" id="modelo" class="form-select form-select-sm">
                    <option value="">-- Selecciona --</option>
                    @foreach($tablasDisponibles as $tabla)
                    <option value="{{ $tabla }}">{{ ucfirst($tabla) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(is_array($relaciones) && count($relaciones))
        <div class="col-md-6">
            <div class="p-3 border rounded bg-white shadow-sm">
                <label for="relacionActual" class="fw-semibold mb-2 d-block">Relación Disponible</label>
                <select wire:model="relacionActual" id="relacionActual" class="form-select form-select-sm">
                    <option value="">-- Selecciona Relación --</option>
                    @foreach($relaciones as $relacion => $etiqueta)
                    <option value="{{ $relacion }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif
    </div>

    {{-- Columnas disponibles --}}
    @if($columnas || $columnasRelacionActual)
    <div class="p-3 border rounded bg-light mb-3">
        <div class="row">
            @if($columnas)
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-2">{{ ucfirst($modelo) }}</label>
                <div class="row g-2">
                    @foreach($columnas as $columna)
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $columna }}" class="form-check-input">
                            <label class="form-check-label">{{ $columna }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($columnasRelacionActual)
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-2">{{ ucfirst($relacionActual) }}</label>
                <div class="row g-2">
                    @foreach($columnasRelacionActual as $col)
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $col }}" class="form-check-input">
                            <label class="form-check-label">{{ Str::afterLast($col, '.') }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Condiciones --}}
    <div class="p-3 border rounded bg-light mb-3">
        <label class="fw-semibold mb-2">Condiciones</label>
        @foreach($filtros as $index => $filtro)
        <div class="row g-2 mb-2 align-items-end">
            <div class="col-md-4">
                <select wire:model="filtros.{{$index}}.columna" class="form-select form-select-sm">
                    <option value="">Selecciona Columna</option>
                    @foreach($columnasSeleccionadas as $columna)
                    <option value="{{$columna}}">{{Str::afterLast($columna, '.')}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select wire:model="filtros.{{$index}}.operador" class="form-select form-select-sm">
                    <option value="=">=</option>
                    <option value="like">like</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" wire:model="filtros.{{ $index }}.valor" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button wire:click.prevent="eliminarFiltro({{ $index }})" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </div>
        </div>
        @endforeach
        <button wire:click.prevent="agregarFiltro" class="btn btn-sm btn-secondary">Añadir condición</button>
    </div>

    {{-- Ordenamiento y límite --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="fw-semibold">Ordenar por</label>
            <select wire:model="ordenColumna" class="form-select form-select-sm">
                <option value="">--</option>
                @foreach($columnasSeleccionadas as $columna)
                <option value="{{ $columna }}">{{ Str::afterLast($columna, '.') }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="fw-semibold">Dirección</label>
            <select wire:model="ordenDireccion" class="form-select form-select-sm">
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="fw-semibold">Agrupar por</label>
            <select wire:model="grupo" class="form-select form-select-sm">
                <option value="">--</option>
                @foreach($columnasSeleccionadas as $columna)
                <option value="{{ $columna }}">{{ Str::afterLast($columna, '.') }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="fw-semibold">Límite</label>
            <input type="number" wire:model="limite" class="form-control form-control-sm">
        </div>
    </div>

    <div class="text-end">
        <button wire:click="generarReporte" type="button" class="btn btn-primary">Generar</button>
    </div>

    <!-- {{-- Resultados --}}
    @if(count($resultados))
    <div class="table-responsive mt-4">
        <table class="table table-sm table-bordered table-striped w-100 text-center align-middle" style="font-size: 0.875rem;">
            <thead class="table-light">
                <tr>
                    @foreach ($columnasSeleccionadas as $col)
                    <th class="small">{{ Str::afterLast($col, '.') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($resultados as $fila)
                <tr>
                    @foreach ($columnasSeleccionadas as $col)
                    @php
                    $valor = data_get($fila, $col);
                    $colFinal = Str::afterLast($col, '.');
                    @endphp
                    <td class="text-break" style="max-width: 200px;">
                        @if(is_iterable($valor))
                        <span class="badge bg-secondary">
                            {{ collect($valor)->pluck($colFinal)->implode(', ') ?: '—' }}
                        </span>
                        @elseif(is_null($valor))
                        <span class="text-muted">—</span>
                        @else
                        {{ $valor }}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif -->
</div>