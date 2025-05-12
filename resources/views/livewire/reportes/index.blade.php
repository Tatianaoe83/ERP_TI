<div>
    {{-- Selección de modelo --}}
    <div class="mb-4">
        <label>Selecciona un modelo:</label>
        <select wire:model="modelo" class="form-control">
            <option value="">-- Elige un modelo --</option>
            @foreach($modelosDisponibles as $nombre => $clase)
                <option value="{{ $clase }}">{{ $nombre }}</option>
            @endforeach
        </select>
    </div>

    @if($columnas)
        {{-- Columnas --}}
        <div class="mb-3">
            <h5>Columnas a mostrar:</h5>
            @foreach($columnas as $col)
                <label class="mr-2">
                    <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $col }}"> {{ $col }}
                </label>
            @endforeach
        </div>

        {{-- Relaciones (básico, se puede mejorar con introspección automática de relaciones del modelo) --}}
        @if($relacionesSeleccionadas)
            <div class="mb-4">
                <h5>Columnas de relaciones:</h5>
                @foreach($relacionesSeleccionadas as $rel)
                    <h6>{{ $rel }}</h6>
                    @foreach($columnasRelaciones[$rel] ?? [] as $col)
                        <label class="mr-2">
                            <input type="checkbox"
                                wire:model="columnasRelacionesSeleccionadas.{{ $rel }}"
                                value="{{ $col }}">
                            {{ $col }}
                        </label>
                    @endforeach
                @endforeach
            </div>
        @endif

        {{-- Filtros --}}
        <div class="mb-3">
            <h5>Condiciones (Filtros):</h5>
            @foreach($columnas as $col)
                <input type="text" class="form-control mb-2" wire:model.lazy="filtros.{{ $col }}" placeholder="Filtro para {{ $col }}">
            @endforeach
        </div>

        {{-- Agrupar --}}
        <div class="mb-3">
            <h5>Grupo por (group by):</h5>
            <select wire:model="grupo" class="form-control">
                <option value="">-- Ninguno --</option>
                @foreach($columnas as $col)
                    <option value="{{ $col }}">{{ $col }}</option>
                @endforeach
            </select>
        </div>

        {{-- Ordenar --}}
        <div class="mb-3">
            <h5>Ordenar por:</h5>
            <select wire:model="ordenColumna" class="form-control mb-2">
                <option value="">-- Columna --</option>
                @foreach($columnas as $col)
                    <option value="{{ $col }}">{{ $col }}</option>
                @endforeach
            </select>

            <select wire:model="ordenDireccion" class="form-control">
                <option value="asc">Ascendente</option>
                <option value="desc">Descendente</option>
            </select>
        </div>

        {{-- Limite --}}
        <div class="mb-3">
            <h5>Límite de resultados:</h5>
            <input type="number" class="form-control" wire:model="limite" placeholder="Ej: 100">
        </div>

        <button wire:click="generarReporte" class="btn btn-primary">Generar Reporte</button>
    @endif

    @if($resultados)
        <h5 class="mt-4">Resultados:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    @foreach($columnasSeleccionadas as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($resultados as $fila)
                    <tr>
                        @foreach($columnasSeleccionadas as $col)
                            <td>{{ $fila[$col] }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
