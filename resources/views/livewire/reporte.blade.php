<div class="container py-4" style="max-width: 1200px;">
    {{-- Título del reporte --}}
    <div class="p-3 border rounded bg-light mb-3 shadow-sm">
        <label class="fw-bold d-block mb-2">Título del Reporte</label>
        <input wire:model="titulo" type="text" class="form-control form-control-sm shadow-sm border-primary-subtle" placeholder="Ingresa el título del reporte">
    </div>

    {{-- Tabla principal y relaciones --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="p-3 border rounded bg-white shadow-sm">
                <label for="modelo" class="fw-semibold mb-2 d-block">Tabla Principal</label>
                <select wire:model="modelo" class="form-select form-select-sm shadow-sm border-primary-subtle">
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
                <label class="fw-semibold mb-2 d-block">Relación Disponible</label>
                <select wire:model="relacionActual" class="form-select form-select-sm shadow-sm border-primary-subtle">
                    <option value="">-- Selecciona o deselecciona relación --</option>
                    @foreach($relaciones as $relacion => $etiqueta)
                    <option value="{{ $relacion }}">
                        {{ $etiqueta }}
                        @if(in_array($relacion, $relacionesSeleccionadas)) ✔️ @endif
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif
    </div>

    {{-- Columnas disponibles --}}
    @if($columnas || count($columnasPorRelacion))
    <div class="p-3 border rounded bg-light mb-3 shadow-sm">
        <div class="row g-4">
            {{-- Tabla principal --}}
            @if($columnas)
            <div class="col-md-6">
                <div class="border rounded p-3 bg-white shadow-sm h-100">
                    <h6 class="fw-bold text-primary mb-3">{{ ucfirst($modelo) }}</h6>
                    <div class="row g-2">
                        @foreach($columnas as $columna)
                        <div class="col-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $columna }}" class="form-check-input border border-dark">
                                <label class="form-check-label">{{ Str::afterLast($columna, '.') }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Tablas relacionadas --}}
            @foreach($columnasPorRelacion as $relacion => $columnas)
            <div class="col-md-6">
                <div class="border rounded p-3 bg-white shadow-sm h-100">
                    <h6 class="fw-bold text-primary mb-3">{{ ucfirst($relacion) }}</h6>
                    <div class="row g-2">
                        @foreach($columnas as $col)
                        <div class="col-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $col }}" class="form-check-input border border-dark">
                                <label class="form-check-label">{{ Str::afterLast($col, '.') }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Condiciones --}}
    <div class="p-3 border rounded bg-light shadow-sm">
        <label class="fw-semibold mb-2">Filtros</label>
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
                    <option value="=">igual</option>
                    <option value="like">empiece en</option>
                    <option value=">">mayor que</option>
                    <option value="<">menor que</option>
                    <option value="between">entre</option>
                    <option value="!=">es diferente</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" wire:model="filtros.{{ $index }}.valor" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 text-end">
                <button wire:click.prevent="eliminarFiltro({{ $index }})" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </div>
        </div>
        @endforeach
        <button wire:click.prevent="agregarFiltro" class="btn btn-sm btn-danger">Añadir filtro</button>
    </div>

    {{-- Ordenamiento y límite --}}
    <div class="row g-3 mb-3 mt-3">
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

    {{-- Botón generar y preview --}}
    <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-info btn-sm" wire:click="mostrarPreview" data-bs-toggle="modal" data-bs-target="#modalPreview">
            Previsualizar
        </button>
        <button wire:click="generarReporte" type="button" class="btn btn-success btn-sm px-4 shadow-sm">
            <i class="fas fa-cogs me-1"></i> Generar Reporte
        </button>
    </div>

    <div wire:ignore.self class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPreviewLabel">Vista Previa del Reporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @if(!empty($resultado) && count($resultado) > 0)
                    <div class="table-responsive">
                        <table id="tabla-preview" class="table table-bordered table-sm w-100">
                            <thead>
                                <tr>
                                    @foreach(array_keys((array)$resultado[0]) as $col)
                                    <th>{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultado as $fila)
                                <tr>
                                    @foreach((array)$fila as $valor)
                                    <td>{{ $valor }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center">No hay datos para mostrar.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function() {
        window.addEventListener('mostrarPreviewModal', () => {
            const modalEl = document.getElementById('modalPreview');

            modalEl.removeAttribute('aria-hidden');

            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            setTimeout(() => {
                $('#tabla-preview').DataTable({
                    responsive: true,
                    pageLength: 10,
                    destroy: true,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    }
                });
            }, 200);
        });
    });
</script>
@endpush