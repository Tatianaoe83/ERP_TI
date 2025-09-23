@extends('layouts.app')

@section('content')

<div class="container-fluid py-3 px-2">

        @include('adminlte-templates::common.errors')

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
                    <h6 class="fw-bold mb-1">Editando Reporte</h6>
                    <p class="small text-muted mb-0">{{ $reportes->title }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Configuración de datos --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">2</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Configuración de Datos</h6>
                    <p class="small text-muted mb-0">Tabla principal y relaciones seleccionadas (no editables).</p>
                </div>
            </div>

        <form action="{{ route('reportes.update', $reportes->id) }}" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" name="title" value="{{ $reportes->title }}">
            <input type="hidden" name="tabla_principal" value="{{ $tablaPrincipal }}">
            <input type="hidden" name="tabla_relacion" value='@json($tablaRelacion)'>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-database text-muted"></i>
                            <label class="fw-semibold small mb-0">Tabla Principal</label>
                        </div>
                        <select class="form-select" disabled>
                        <option>{{ ucfirst($tablaPrincipal) }}</option>
                    </select>
                </div>

                <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-link text-muted"></i>
                            <label class="fw-semibold small mb-0">Relaciones Seleccionadas</label>
                        </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($tablaRelacion as $rel)
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                            {{ ucfirst($rel) }}
                        </span>
                        @endforeach
                    </div>
                    </div>
                </div>
        </div>
    </div>

    {{-- Selección de columnas --}}
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
                {{-- Tabla Principal --}}
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-table text-muted"></i>
                            <h6 class="fw-bold mb-0">
                            {{ ucfirst($tablaPrincipal) }}
                                @if(in_array($tablaPrincipal, ['equipos', 'lineas_telefonicas', 'insumos']))
                                    <span class="badge bg-light text-dark small ms-1 border">Inventario</span>
                                @endif
                            </h6>
                        </div>
                        <div class="row g-2">
                                @foreach ($columnasPrincipales as $col)
                                @php $colPrincipal = $tablaPrincipal . '.' . $col; @endphp
                            <div class="col-6 col-sm-4 col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input columna-check"
                                            name="columnas[]"
                                            value="{{ $colPrincipal }}"
                                            {{ in_array($colPrincipal, $columnasSeleccionadas) ? 'checked' : '' }}>
                                    <label class="form-check-label small">{{ ucfirst(str_replace('_', ' ', $col)) }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Tablas Relacionadas --}}
                @foreach ($columnasRelacion as $tabla => $columnas)
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-link text-muted"></i>
                            <h6 class="fw-bold mb-0">
                            {{ ucfirst($tabla) }}
                                <span class="badge bg-light text-dark small ms-1 border">Relacionada</span>
                            </h6>
                        </div>
                        <div class="row g-2">
                                @foreach ($columnas as $col)
                                @php $relCol = $tabla . '.' . $col; @endphp
                            <div class="col-6 col-sm-4 col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input columna-check"
                                            name="columnas[]"
                                            value="{{ $relCol }}"
                                            {{ in_array($relCol, $columnasSeleccionadas) ? 'checked' : '' }}>
                                    <label class="form-check-label small">{{ ucfirst(str_replace('_', ' ', $col)) }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
            </div>

    {{-- Filtros aplicados --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                    <span class="fw-bold text-muted">4</span>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Filtros Aplicados</h6>
                    <p class="small text-muted mb-0">Modifica los filtros existentes del reporte.</p>
                </div>
            </div>

            <div id="filtros-wrapper">
                @foreach ($condiciones as $i => $filtro)
                <div class="row g-2 mb-3 align-items-end filtro-item">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label small mb-1">Columna</label>
                        <select name="filtros[{{ $i }}][columna]" class="form-select">
                            <option value="">Selecciona Columna</option>
                            @foreach ($columnasSeleccionadas as $col)
                            <option value="{{ $col }}" {{ $filtro['columna'] == $col ? 'selected' : '' }}>{{ Str::afterLast($col, '.') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-sm-3 col-md-2">
                        <label class="form-label small mb-1">Operador</label>
                        <select name="filtros[{{ $i }}][operador]" class="form-select" onchange="mostrarFiltrosRange({{ $i }})">
                            <option value="=" {{ $filtro['operador'] == '=' ? 'selected' : '' }}>igual a</option>
                            <option value="like" {{ $filtro['operador'] == 'like' ? 'selected' : '' }}>contiene</option>
                            <option value=">" {{ $filtro['operador'] == '>' ? 'selected' : '' }}>mayor que</option>
                            <option value="<" {{ $filtro['operador'] == '<' ? 'selected' : '' }}>menor que</option>
                            <option value="between" {{ $filtro['operador'] == 'between' ? 'selected' : '' }}>entre</option>
                            <option value="!=" {{ $filtro['operador'] == '!=' ? 'selected' : '' }}>sin</option>
                        </select>
                    </div>

                    <div class="col-6 col-sm-3 col-md-5" id="valor_filtro_{{ $i }}">
                        @if(is_array($filtro['valor']))
                        <label class="form-label small mb-1">Entre</label>
                        <div class="d-flex align-items-center gap-1">
                            <input type="text" name="filtros[{{ $i }}][valor][inicio]" class="form-control"
                                placeholder="desde" value="{{ $filtro['valor']['inicio'] ?? '' }}">
                            <span class="fw-semibold small">y</span>
                            <input type="text" name="filtros[{{ $i }}][valor][fin]" class="form-control"
                                placeholder="hasta" value="{{ $filtro['valor']['fin'] ?? '' }}">
                        </div>
                        @else
                        <label class="form-label small mb-1">Valor</label>
                        @php
                        $partes = explode('.', $filtro['columna']);
                        $tabla = $partes[0] ?? null;
                        $columna = $partes[1] ?? null;
                        @endphp
                        @if(in_array($filtro['operador'], ['=','like']) && $tabla && $columna)
                        <input type="text" class="form-control autocomplete"
                            placeholder="Ingresa el valor a filtrar" value="{{$filtro['valor'] ?? ''}}"
                            data-name="filtros[{{$i}}][valor]" data-tabla="{{$tabla}}"
                            data-columna="{{$columna}}">
                        @else
                        <input type="text" name="filtros[{{ $i }}][valor]" class="form-control"
                            placeholder="Ingresa el valor a filtrar" value="{{ $filtro['valor'] ?? '' }}">
                        @endif
                        @endif
                    </div>

                    <div class="col-12 col-sm-12 col-md-1 text-center text-md-end">
                        <button type="button" class="btn btn-outline-danger btn-sm eliminar-filtro">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="agregar-filtro">
                    <i class="fas fa-plus"></i> Añadir filtro
                </button>
                @if(count($condiciones) > 0)
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    {{ count($condiciones) }} filtro(s) aplicado(s)
                </small>
                @endif
            </div>
        </div>
            </div>

    {{-- Configuración de resultados --}}
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
                        <label for="ordenColumna" class="fw-semibold small mb-0 d-block">Ordenar por</label>
                    </div>
                    <select id="ordenColumna" name="ordenColumna" class="form-select">
                        <option value="">-- Sin ordenar --</option>
                        </select>
                    </div>

                <div class="col-6 col-sm-6 col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-arrow-up-down text-muted"></i>
                        <label for="ordenDireccion" class="fw-semibold small mb-0 d-block">Dirección</label>
                    </div>
                    <select id="ordenDireccion" name="ordenDireccion" class="form-select">
                            <option value="">Sin orden</option>
                        <option value="asc" {{ $ordenDir === 'asc' ? 'selected' : '' }}>Ascendente (A-Z, 1-9)</option>
                        <option value="desc" {{ $ordenDir === 'desc' ? 'selected' : '' }}>Descendente (Z-A, 9-1)</option>
                        </select>
                    </div>

                <div class="col-6 col-sm-6 col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-list-ol text-muted"></i>
                        <label for="limite" class="fw-semibold small mb-0 d-block">Límite de registros</label>
                    </div>
                    <input type="number" id="limite" name="limite" value="{{ $limite }}" class="form-control" min="0" placeholder="Ej: 100 (vacío = todos)">
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
                    <h6 class="fw-bold mb-1">Guardar Cambios</h6>
                    <p class="small text-muted mb-0">Previsualiza o guarda los cambios realizados en el reporte.</p>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" id="btnPreview">
                    <i class="fas fa-eye"></i>
                    Vista Previa
                </button>
                <button type="submit" class="btn btn-dark d-flex align-items-center gap-2 px-4">
                    <i class="fas fa-save"></i>
                    Guardar Cambios
                </button>
            </div>
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-lightbulb"></i>
                    Tip: Usa "Vista Previa" para verificar los cambios antes de guardar
                </small>
            </div>
        </div>
    </div>
    </form>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content dark:bg-gray-800 dark:text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPreviewLabel">Vista previa del reporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <p class="text-muted">Cargando datos...</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('btnPreview').addEventListener('click', async () => {
        const tablaPrincipal = document.querySelector('[name="tabla_principal"]').value;
        const columnasSeleccionadas = Array.from(document.querySelectorAll('[name="columnas[]"]:checked')).map(el => el.value);
        const tablaRelacion = JSON.parse(document.querySelector('[name="tabla_relacion"]').value || '[]');

        const ordenColumna = document.querySelector('[name="ordenColumna"]').value;
        const ordenDireccion = document.querySelector('[name="ordenDireccion"]').value;
        const limite = document.querySelector('[name="limite"]').value;

        const filtros = [];
        document.querySelectorAll('.filtro-item').forEach(item => {
            const columna = item.querySelector('[name*="[columna]"]').value;
            const operador = item.querySelector('[name*="[operador]"]').value;
            let valor = null;

            if (operador === 'between') {
                const inicio = item.querySelector('[name*="[valor][inicio]"]')?.value?.trim();
                const fin = item.querySelector('[name*="[valor][fin]"]')?.value?.trim();
                if (inicio && fin) {
                    valor = {
                        inicio,
                        fin
                    };
                }
            } else {
                const valorInput = item.querySelector('input[name$="[valor]"]');
                valor = valorInput?.value?.trim();
            }

            if (columna && operador && valor) {
                filtros.push({
                    columna,
                    operador,
                    valor
                });
            }
        });

        if (!tablaPrincipal || columnasSeleccionadas.length === 0) {
            alert('Debes seleccionar una tabla principal y al menos una columna.');
            return;
        }

        try {
            const response = await fetch("{{ route('reportes.preview') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    tabla_principal: tablaPrincipal,
                    columnas: columnasSeleccionadas,
                    tabla_relacion: tablaRelacion,
                    ordenColumna: ordenColumna,
                    ordenDireccion: ordenDireccion,
                    limite: limite,
                    filtros: filtros
                })
            });

            const data = await response.json();

            const modalBody = document.getElementById('previewContent');
            if (data.html) {
                modalBody.innerHTML = data.html;
            } else if (data.error) {
                modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            }

            setTimeout(() => {
                const modal = new bootstrap.Modal(document.getElementById('modalPreview'));
                modal.show();
            }, 0);
        } catch (error) {
            console.error('Error en preview:', error);
            document.getElementById('previewContent').innerHTML = '<div class="alert alert-danger">Error al cargar la vista previa.</div>';
        }
    });
</script>

<script>
    let contadorFiltros = {{count($condiciones ?? [])}};

    function obtenerColumnasSeleccionadas() {
        const columnas = [];
        document.querySelectorAll('.columna-check:checked').forEach(cb => {
            columnas.push(cb.value);
        });
        return columnas;
    }

    function actualizarSelectsDinamicos() {
        const columnas = obtenerColumnasSeleccionadas();

        const selectOrden = document.getElementById('ordenColumna');

        const valorOrdenActual = '{{ $ordenCol }}';

        const opcionesOrden = columnas.map(col => {
            const selected = col === valorOrdenActual ? 'selected' : '';
            return `<option value="${col}" ${selected}>${col}</option>`;
        });

        selectOrden.innerHTML = '<option value="">-- Sin orden --</option>' + opcionesOrden.join('');
    }

    document.querySelectorAll('.columna-check').forEach(cb => {
        cb.addEventListener('change', () => {
            actualizarSelectsDinamicos();
            actualizarSelectsFiltros();
        });
    });

    function actualizarSelectsFiltros() {
        const columnas = obtenerColumnasSeleccionadas();
        document.querySelectorAll('.filtro-item select[name*="[columna]"]').forEach(select => {
            const valorActual = select.value;
            select.innerHTML = `<option value="">-- Selecciona --</option>` +
                columnas.map(col => `<option value="${col}" ${col === valorActual ? 'selected' : ''}>${col}</option>`).join('');
        });
    }

    document.getElementById('agregar-filtro').addEventListener('click', function() {
        const columnas = obtenerColumnasSeleccionadas();

        const wrapper = document.getElementById('filtros-wrapper');
        const selectColumnas = columnas.map(col => `<option value="${col}">${col}</option>`).join('');

        const fila = document.createElement('div');
        fila.className = 'row align-items-end mb-3 filtro-item';
        fila.innerHTML = `
        <div class="col-md-4">
            <select name="filtros[${contadorFiltros}][columna]" class="form-select form-select-sm">
                <option value="">-- Selecciona --</option>
                ${selectColumnas}
            </select>
        </div>
        <div class="col-md-3">
            <select name="filtros[${contadorFiltros}][operador]" class="form-select form-select-sm" onchange="mostrarFiltrosRange(${contadorFiltros})">
                <option value="=">igual</option>
                <option value="like">si contiene</option>
                <option value=">">mayor que</option>
                <option value="<">menor que</option>
                <option value="between">entre</option>
            </select>
        </div>
        <div class="col-md-4" id="valor_filtro_${contadorFiltros}">
            <input type="text" name="filtros[${contadorFiltros}][valor]" class="form-control form-control-sm" placeholder="Valor">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger eliminar-filtro">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

        wrapper.appendChild(fila);
        contadorFiltros++;

        document.querySelectorAll('.eliminar-filtro').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.target.closest('.filtro-item').remove();
            });
        });
    });

    function mostrarFiltrosRange(filtroId) {
        const operadorSelect = document.querySelector(`[name="filtros[${filtroId}][operador]"]`);
        const operador = operadorSelect?.value;
        const valorDiv = document.getElementById(`valor_filtro_${filtroId}`);

        if (operador === 'between') {
            valorDiv.innerHTML = `
            <div class="row">
                <div class="col-6 col-md-5">
                    <input type="text" name="filtros[${filtroId}][valor][inicio]" class="form-control form-control-sm" placeholder="Desde">
                </div>
                <div class="col-6 col-md-2 text-center d-flex justify-content-center align-items-center">
                    <span class="text-muted">a</span>
                </div>
                <div class="col-6 col-md-5">
                    <input type="text" name="filtros[${filtroId}][valor][fin]" class="form-control form-control-sm" placeholder="Hasta">
                </div>
            </div>
        `;
        } else if (operador === '=' || operador === 'like') {
            const selectCol = document.querySelector(`[name="filtros[${filtroId}][columna]"]`);
            const [tabla, columna] = selectCol?.value.split('.') ?? ['', ''];

            valorDiv.innerHTML = `
        <input type="text"
               class="form-control form-control-sm autocomplete"
               placeholder="Valor"
               data-name="filtros[${filtroId}][valor]"
               data-tabla="${tabla}"
               data-columna="${columna}">
    `;

            setTimeout(() => {
                const nuevoInput = valorDiv.querySelector('.autocomplete');
                if (nuevoInput) inicializarAutocompletado(nuevoInput);
            }, 5);
        } else {
            valorDiv.innerHTML = `<input type="text" name="filtros[${filtroId}][valor]" class="form-control form-control-sm" placeholder="Valor">`;
        }
    }


    document.addEventListener('click', function(e) {
        if (e.target.closest('.eliminar-filtro')) {
            e.preventDefault();
            e.target.closest('.filtro-item').remove();
        }
    });

    actualizarSelectsDinamicos();
    actualizarSelectsFiltros();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.autocomplete').forEach(inicializarAutocompletado);
    });

    function inicializarAutocompletado(input) {
        const ul = document.createElement('ul');
        ul.className = 'list-group position-absolute w-90 z-50 bg-white';
        ul.style.maxHeight = '200px';
        ul.style.overflowY = 'auto';
        input.parentNode.appendChild(ul);

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = input.dataset.name;
        hiddenInput.value = input.value;
        input.parentNode.appendChild(hiddenInput);

        input.addEventListener('input', function() {
            const query = this.value;
            if (query.length < 2) {
                ul.innerHTML = '';
                return;
            }

            fetch(`/autocomplete?tabla=${input.dataset.tabla}&columna=${input.dataset.columna}&query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    ul.innerHTML = data.map(val => `<li class="list-group-item sugerencia" style="cursor:pointer;">${val}</li>`).join('');
                });
        });

        ul.addEventListener('click', function(e) {
            if (e.target.classList.contains('sugerencia')) {
                input.value = e.target.textContent;
                hiddenInput.value = e.target.textContent;
                ul.innerHTML = '';
            }
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name^="filtros"][name$="[operador]"]')) {
            const match = e.target.name.match(/\[([0-9]+)\]\[operador\]/);
            if (match) {
                mostrarFiltrosRange(match[1]);
            }
        }

        if (e.target.matches('select[name^="filtros"][name$="[columna]"]')) {
            const match = e.target.name.match(/\[([0-9]+)\]\[columna\]/);
            if (match) {
                mostrarFiltrosRange(match[1]);
            }
        }
    });
</script>

@endsection