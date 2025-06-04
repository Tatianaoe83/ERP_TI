<div>
    <h3 class="mb-3">Add Query</h3>

    <div class="mb-3">
        <label for="titulo">Título</label>
        <input wire:model="titulo" type="text" class="form-control" placeholder="Ingresa el título">
    </div>

    <div class="mb-3">
        <label for="modelo">Selecciona Tabla Principal</label>
        <select wire:model="modelo" class="form-select">
            <option value="">-- Selecciona --</option>
            @foreach($tablasDisponibles as $tabla)
            <option value="{{ $tabla }}">{{ ucfirst($tabla) }}</option>
            @endforeach
        </select>
    </div>

    @if(is_array($relaciones) && count($relaciones))
    <div class="mb-3">
        <label>Selecciona una relación</label>
        <select wire:model="relacionActual" class="form-select">
            <option value="">-- Elegir relación --</option>
            @foreach($relaciones as $relacion => $etiqueta)
            <option value="{{ $relacion }}">{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    @endif

    @if($columnas || $columnasRelacionActual)
    <div class="mb-3">
        <div class="row">
            @if($columnas)
            <div class="col-md-6">
                <label class="fw-bold">{{ $modelo }}</label>
                <div class="row">
                    @foreach($columnas as $columna)
                    <div class="col-md-6">
                        <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $columna }}">
                        {{ $columna }}
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($columnasRelacionActual)
            <div class="col-md-6">
                <label class="fw-bold">{{ $relacionActual }}</label>
                <div class="row">
                    @foreach($columnasRelacionActual as $col)
                    <div class="col-md-6">
                        <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $col }}">
                        {{ Str::afterLast($col, '.') }}
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($columnasSeleccionadas)
    <!-- <pre>{{ json_encode($columnasSeleccionadas, JSON_PRETTY_PRINT) }}</pre> -->
    <div class="mb-3">
        <label> Condiciones </label>
        @foreach($filtros as $index=>$filtro)
        <div class="row mb-2">
            <div class="col-md-4">
                <select wire:model="filtros.{{$index}}.columna" class="form-select">
                    <option value="">Selecciona Columna</option>
                    @foreach($columnasSeleccionadas as $columna)
                    <option value="{{$columna}}">{{Str::afterLast($columna, '.')}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select wire:model="filtros.{{$index}}.operador" class="form-select">
                    <option value="=">=</option>
                    <option value="like">like</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" wire:model="filtros.{{ $index }}.valor" class="form-control">
            </div>
            <div class="col-md-2">
                <button wire:click.prevent="eliminarFiltro({{ $index }})" class="btn btn-danger">X</button>
            </div>
        </div>
        @endforeach
        <button wire:click.prevent="agregarFiltro" class="btn btn-secondary">Añade condicion</button>
    </div>
    @endif

    @if($columnasSeleccionadas)
    <div class="row mb-3">
        <div class="col-md-4">
            <label>Order By</label>
            <select wire:model="ordenColumna" class="form-select">
                <option value="">--</option>
                @foreach($columnasSeleccionadas as $columna)
                <option value="{{ $columna }}">{{ Str::afterLast($columna, '.') }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label>Direction</label>
            <select wire:model="ordenDireccion" class="form-select">
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>Group By</label>
            <select wire:model="grupo" class="form-select">
                <option value="">--</option>
                @foreach($columnasSeleccionadas as $columna)
                <option value="{{ $columna }}">{{ Str::afterLast($columna, '.') }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <div class="col-md-3">
        <label>Limit</label>
        <input type="number" wire:model="limite" class="form-control">
    </div>

    <button wire:click="generarReporte" type="button" class="btn btn-danger">TEST</button>

    @if(count($resultados) && count($columnasVistaPrevia))
    <div class="table-responsive mt-4">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    @foreach ($columnasVistaPrevia as $col)
                    <th>{{ Str::afterLast($col, '.') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($resultados as $fila)
                <tr>
                    @foreach ($columnasVistaPrevia as $col)
                    @php
                    $valor = data_get($fila, $col);
                    $colFinal = Str::afterLast($col, '.');
                    @endphp

                    <td>
                        @if(is_iterable($valor))
                        {{-- Mostrar como array limpio de valores --}}
                        {{ '[' . collect($valor)->pluck($colFinal)->implode(', ') . ']' }}
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
    @endif
</div>