<div class="container-fluid py-3 px-2">

    @include('flash::message')

    @if ($errors->any())
    <div class="alert alert-danger border border-danger-subtle shadow-sm rounded small d-flex align-items-start gap-2 mb-3">
        <i class="fas fa-exclamation-triangle mt-1 text-danger"></i>
        <div>
            <strong>Completa todos los campos requeridos.</strong>
        </div>
    </div>
    @endif

    {{-- Título del reporte --}}
    <div class="p-3 border rounded bg-light mb-3 shadow-sm">
        <label class="fw-bold d-block mb-2">Título del Reporte</label>
        <input wire:model="titulo" type="text" required class="form-control shadow-sm border-primary-subtle" placeholder="Ingresa el título del reporte">
    </div>

    {{-- Tabla principal y relaciones --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <div class="p-3 border rounded bg-white shadow-sm">
                <label for="modelo" class="fw-semibold mb-2 d-block">Tabla Principal</label>
                <select wire:model="modelo" class="form-select shadow-sm border-primary-subtle">
                    <option value="">-- Selecciona --</option>
                    @foreach($tablasDisponibles as $tabla)
                    <option value="{{ $tabla }}">{{ ucfirst($tabla) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(is_array($relaciones) && count($relaciones))
        <div class="col-12 col-md-6">
            <div class="p-3 border rounded bg-white shadow-sm">
                <label class="fw-semibold mb-2 d-block">Relación Disponible</label>
                <select wire:model="relacionActual" class="form-select shadow-sm border-primary-subtle">
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
        <div class="row g-3">
            {{-- Tabla principal --}}
            @if($columnas)
            <div class="col-12 col-lg-6">
                <div class="border rounded p-3 bg-white shadow-sm h-100">
                    <h6 class="fw-bold text-primary mb-3">{{ ucfirst($modelo) }}</h6>
                    <div class="row g-2">
                        @foreach($columnas as $columna)
                        <div class="col-6 col-sm-4 col-md-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $columna }}" class="form-check-input border border-dark">
                                <label class="form-check-label small">{{ Str::afterLast($columna, '.') }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Tablas relacionadas --}}
            @foreach($columnasPorRelacion as $relacion => $columnas)
            <div class="col-12 col-lg-6">
                <div class="border rounded p-3 bg-white shadow-sm h-100">
                    <h6 class="fw-bold text-primary mb-3">{{ ucfirst($relacion) }}</h6>
                    <div class="row g-2">
                        @foreach($columnas as $col)
                        <div class="col-6 col-sm-4 col-md-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $col }}" class="form-check-input border border-dark">
                                <label class="form-check-label small">{{ Str::afterLast($col, '.') }}</label>
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
    <div class="p-3 border rounded bg-light shadow-sm mb-4">
        <label class="fw-semibold mb-3 d-block">Filtros</label>
        @foreach($filtros as $index => $filtro)
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small mb-1">Columna</label>
                <select wire:model="filtros.{{$index}}.columna" class="form-select">
                    <option value="">Selecciona Columna</option>
                    @foreach($columnasSeleccionadas as $columna)
                    <option value="{{$columna}}">{{Str::afterLast($columna, '.')}}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small mb-1">Operador</label>
                <select wire:model="filtros.{{$index}}.operador" class="form-select">
                    <option value="=">igual</option>
                    <option value="like">empiece en</option>
                    <option value=">">mayor que</option>
                    <option value="<">menor que</option>
                    <option value="between">entre</option>
                    <option value="!=">es diferente</option>
                </select>
            </div>

            <div class="col-6 col-sm-3 col-md-5">
                @if($filtro['operador'] === 'between')
                <label class="form-label small mb-1">Rango</label>
                <div class="d-flex align-items-center gap-1">
                    <input type="text" wire:model="filtros.{{ $index }}.valor.inicio" class="form-control" placeholder="desde">
                    <span class="fw-semibold small">y</span>
                    <input type="text" wire:model="filtros.{{ $index }}.valor.fin" class="form-control" placeholder="hasta">
                    <span
                        class="d-inline-block"
                        tabindex="0"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Introduce un rango desde - hasta.">
                        <i class="fas fa-info-circle text-primary"></i>
                    </span>
                </div>
                @else
                <label class="form-label small mb-1">Valor</label>
                <div class="d-flex align-items-center gap-1">
                    <input type="text" wire:model="filtros.{{ $index }}.valor" class="form-control" placeholder="valor">
                    <span
                        class="d-inline-block"
                        tabindex="0"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Introduce un valor para filtrar.">
                        <i class="fas fa-info-circle text-primary"></i>
                    </span>
                </div>
                @endif
            </div>

            <div class="col-12 col-sm-12 col-md-1 text-center text-md-end">
                <button wire:click.prevent="eliminarFiltro({{ $index }})" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        @endforeach
        <button wire:click.prevent="agregarFiltro" class="btn btn-danger btn-sm">Añadir filtro</button>
    </div>

    <div class="p-3 border rounded bg-white shadow-sm mb-4">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="fw-semibold small mb-1 d-block">Ordenar por</label>
                <select wire:model="ordenColumna" class="form-select">
                    <option value="">--</option>
                    @foreach($columnasSeleccionadas as $columna)
                    <option value="{{ $columna }}">{{ Str::afterLast($columna, '.') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-sm-6 col-md-2">
                <label class="fw-semibold small mb-1 d-block">Dirección</label>
                <select wire:model="ordenDireccion" class="form-select">
                    <option value="asc">Asc</option>
                    <option value="desc">Desc</option>
                </select>
            </div>

            <div class="col-6 col-sm-12 col-md-3">
                <label class="fw-semibold small mb-1 d-block">Límite</label>
                <input type="number" wire:model="limite" class="form-control" placeholder="Ej: 100">
            </div>
        </div>
    </div>

    {{-- Botón generar y preview --}}
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end mt-4">
        <a href="{{ route('reportes.index') }}" class="btn btn-outline-danger">Cancelar</a>
        <button type="button" class="btn btn-outline-info" wire:click="mostrarPreview" data-bs-toggle="modal" data-bs-target="#modalPreview">
            <i class="fas fa-eye me-1"></i>Previsualizar
        </button>
        <button wire:click="generarReporte" type="button" class="btn btn-success px-4 shadow-sm">
            <i class="fas fa-file-alt me-1"></i>Generar Reporte
        </button>
    </div>

    <div wire:ignore.self class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPreviewLabel">Vista Previa del Reporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-2 p-sm-3">
                    @if(!empty($resultado) && count($resultado) > 0)
                    <div class="table-responsive">
                        <table id="tabla-preview" class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    @foreach(array_keys((array)$resultado[0]) as $col)
                                    <th class="px-2 px-sm-3 py-2 small">{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(collect($resultado)->take(10) as $fila)
                                <tr>
                                    @foreach((array)$fila as $valor)
                                    <td class="px-2 px-sm-3 py-2 small">{{ $valor }}</td>
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
                    },
                    scrollX: true,
                    autoWidth: false
                });
            }, 200);
        });
    });
</script>
@endpush