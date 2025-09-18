<div class="container-fluid py-3 px-2" wire:init="initModel">

    @include('flash::message')

    @if ($errors->any())
    <div class="alert alert-danger border border-danger-subtle rounded small d-flex align-items-start gap-2 mb-3">
        <i class="fas fa-exclamation-triangle mt-1 text-danger"></i>
        <div>
            <strong>Completa todos los campos requeridos.</strong>
        </div>
    </div>
    @endif

     <!-- Información adicional -->
     <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-4">
        <div class="flex items-start">
            <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg mr-4">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">
                    Información sobre Relaciones de Inventarios
                </h4>
                <ul class="text-blue-700 dark:text-blue-300 text-sm space-y-1">
                    <li> <strong>📋 Relaciones Directas:</strong> Todos los inventarios (Equipos, Líneas Telefónicas e Insumos) 
                        están directamente relacionados con la tabla de <strong>Empleados</strong>.</li>
                    <li> <strong>🔗 Consultas Indirectas:</strong> Las demás tablas del sistema (Departamentos, Gerencias, Obras, etc.) 
                        se consultan de forma indirecta a través de las relaciones establecidas en las tablas.</li>
                   
                </ul>
            </div>
        </div>
    </div>

    {{-- Título del reporte --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">1</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Título del Reporte</h6>
                    <p class="small text-muted mb-0">Define un nombre descriptivo para identificar tu reporte.</p>
                </div>
            </div>
            <input wire:model="titulo" type="text" required class="form-control" placeholder="Ej: Inventario de Equipos por Departamento - Enero 2024">
        </div>
    </div>

    {{-- Tabla principal y relaciones --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">2</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Selección de Datos</h6>
                    <p class="small text-muted mb-0">Elige la tabla principal y sus relaciones para obtener los datos necesarios.</p>
                </div>
            </div>
            <div class="row g-3">
        <div class="col-12 col-md-6">
                    <div class="rounded">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-database text-muted"></i>
                            <label for="modelo" class="fw-semibold mb-0 d-block">Tabla Principal</label>
                            <span class="badge bg-secondary text-white small">Requerido</span>
                        </div>
                        <p class="small text-muted mb-2">Selecciona la tabla base de tu consulta.</p>
                <select wire:model="modelo" class="form-select">
                            <option value="">-- Selecciona una tabla --</option>
                    @foreach($tablasDisponibles as $tabla)
                            <option value="{{ $tabla }}">
                                {{ ucfirst($tabla) }}
                                @if(in_array($tabla, ['equipos', 'lineas_telefonicas', 'insumos']))
                                    (Inventario)
                                @endif
                            </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(is_array($relaciones) && count($relaciones))
        <div class="col-12 col-md-6">
                    <div class="rounded">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-link text-muted"></i>
                            <label class="fw-semibold mb-0 d-block">Tablas Relacionadas</label>
                            <span class="badge bg-light text-dark small border">Opcional</span>
                            <span
                                class="d-inline-block"
                                tabindex="0"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Las tablas relacionadas te permiten obtener información adicional conectada a través de empleados">
                                <i class="fas fa-question-circle text-muted small"></i>
                            </span>
                        </div>
                        <p class="small text-muted mb-2">Agrega tablas relacionadas para enriquecer tu reporte.</p>
                <select wire:model="relacionActual" class="form-select">
                            <option value="">-- Agregar tabla relacionada --</option>
                    @foreach($relaciones as $relacion => $etiqueta)
                    <option value="{{ $relacion }}">
                        {{ $etiqueta }}
                                @if(in_array($relacion, $relacionesSeleccionadas)) ✓ Agregada @endif
                    </option>
                    @endforeach
                </select>
                        @if(!empty($relacionesSeleccionadas))
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-check-circle"></i> 
                                {{ count($relacionesSeleccionadas) }} tabla(s) relacionada(s) agregada(s)
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Columnas disponibles --}}
    @if($columnas || count($columnasPorRelacion))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">3</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Selección de Columnas</h6>
                    <p class="small text-muted mb-0">Elige qué información deseas incluir en tu reporte.</p>
                </div>
            </div>
            <div class="row g-4">
            {{-- Tabla principal --}}
            @if($columnas)
            <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-table text-muted"></i>
                            <h6 class="fw-bold mb-0">
                                {{ ucfirst($modelo) }}
                                @if(in_array($modelo, ['equipos', 'lineas_telefonicas', 'insumos']))
                                    <span class="badge bg-light text-dark small ms-1 border">Inventario</span>
                                @endif
                            </h6>
                        </div>
                    <div class="row g-2">
                        @foreach($columnas as $columna)
                        <div class="col-6 col-sm-4 col-md-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $columna }}" class="form-check-input">
                                <label class="form-check-label small">
                                    {{ ucfirst(str_replace('_', ' ', Str::afterLast($columna, '.'))) }}
                                </label>
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
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-link text-muted"></i>
                            <h6 class="fw-bold mb-0">
                                {{ ucfirst($relacion) }}
                                <span class="badge bg-light text-dark small ms-1 border">Relacionada</span>
                            </h6>
                        </div>
                    <div class="row g-2">
                        @foreach($columnas as $col)
                        <div class="col-6 col-sm-4 col-md-6">
                            <div class="form-check">
                                <input type="checkbox" wire:model="columnasSeleccionadas" value="{{ $col }}" class="form-check-input">
                                <label class="form-check-label small">
                                    {{ ucfirst(str_replace('_', ' ', Str::afterLast($col, '.'))) }}
                                </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @if(!empty($columnasSeleccionadas))
            <div class="mt-3 p-2 bg-light rounded border">
                <small class="text-muted">
                    <i class="fas fa-check-circle"></i> 
                    {{ count($columnasSeleccionadas) }} columna(s) seleccionada(s)
                </small>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Condiciones --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">4</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Filtros de Datos</h6>
                    <div class="d-flex align-items-center gap-2">
                        <p class="small text-muted mb-0">Aplica filtros para obtener solo los datos que necesitas.</p>
                        <span class="badge bg-light text-dark small border">Opcional</span>
                    </div>
                </div>
            </div>
        @foreach($filtros as $index=> $filtro)
        <div wire:key="filtro-{{$filtro['id']}}">
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
                        <option value="=">igual a</option>
                        <option value="like">contiene</option>
                        <option value=">">mayor que</option>
                        <option value="<">menor que</option>
                        <option value="between">entre</option>
                        <option value="!=">sin</option>
                    </select>
                </div>

                <div class="col-6 col-sm-3 col-md-5">
                    @if($filtro['operador'] === 'between')
                    <label class="form-label small mb-1">Entre</label>
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
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <label class="form-label small mb-0">Valor</label>
                        <span
                            class="d-inline-block"
                            tabindex="0"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Para búsquedas que contengan texto usa 'contiene', para valores exactos usa 'igual a'">
                            <i class="fas fa-question-circle text-muted small"></i>
                        </span>
                    </div>
                    @php
                    $col = $filtro['columna'] ?? null;
                    $oper = $filtro['operador'] ?? null;
                    @endphp
                    @if($col && in_array($oper, ['like', '=']) && str_contains($col, '.'))
                    @php [$tabla, $campo] = explode('.',$col); @endphp
                    <livewire:autocomplete-input
                        :tabla="$tabla"
                        :columna="$campo"
                        :indice="$index"
                        wire:model="filtros.{{ $index }}.valor"
                        wire:key="autocompletado-{{ $index }}" />
                    @else
                    <input type="text" wire:model="filtros.{{ $index }}.valor" class="form-control" placeholder="Ingresa el valor a filtrar">
                    @endif
                    @endif
                </div>

                <div class="col-12 col-sm-12 col-md-1 text-center text-md-end">
                    <button wire:click.prevent="eliminarFiltro({{ $index }})" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
        <div class="d-flex align-items-center gap-2">
            <button wire:click.prevent="agregarFiltro" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-plus"></i> Añadir filtro
            </button>
            @if(count($filtros) > 0)
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                {{ count($filtros) }} filtro(s) aplicado(s)
            </small>
            @endif
        </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">5</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Configuración de Resultados</h6>
                    <div class="d-flex align-items-center gap-2">
                        <p class="small text-muted mb-0">Personaliza cómo quieres que se muestren los resultados.</p>
                        <span class="badge bg-light text-dark small border">Opcional</span>
                    </div>
                </div>
            </div>
        <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-sort-amount-down text-muted"></i>
                        <label class="fw-semibold small mb-0 d-block">Ordenar por</label>
                    </div>
                <select wire:model="ordenColumna" class="form-select">
                        <option value="">-- Sin ordenar --</option>
                    @foreach($columnasSeleccionadas as $columna)
                        <option value="{{ $columna }}">{{ ucfirst(str_replace('_', ' ', Str::afterLast($columna, '.'))) }}</option>
                    @endforeach
                </select>
            </div>

                <div class="col-6 col-sm-6 col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-arrow-up-down text-muted"></i>
                        <label class="fw-semibold small mb-0 d-block">Dirección</label>
                    </div>
                <select wire:model="ordenDireccion" class="form-select">
                        <option value="asc">Ascendente (A-Z, 1-9)</option>
                        <option value="desc">Descendente (Z-A, 9-1)</option>
                </select>
            </div>

                <div class="col-6 col-sm-6 col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-list-ol text-muted"></i>
                        <label class="fw-semibold small mb-0 d-block">Límite de registros</label>
                    </div>
                    <input type="number" wire:model="limite" class="form-control" placeholder="Ej: 100 (vacío = todos)" min="0">
                    <small class="text-muted">Deja vacío para mostrar todos los registros</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Botones de acción --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">6</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Generar Reporte</h6>
                    <p class="small text-muted mb-0">Previsualiza o genera tu reporte personalizado.</p>
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" wire:click="mostrarPreview" data-bs-toggle="modal" data-bs-target="#modalPreview">
                    <i class="fas fa-eye"></i>
                    Vista Previa
        </button>
                <button wire:click="generarReporte" type="button" class="btn btn-dark d-flex align-items-center gap-2 px-4">
                    <i class="fas fa-file-export"></i>
            Generar Reporte
        </button>
            </div>
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-lightbulb"></i>
                    Tip: Usa "Vista Previa" para verificar los datos antes de generar el reporte final
                </small>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
            <div class="modal-content dark:bg-black">
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