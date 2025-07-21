@extends('layouts.app')

@section('content')

<h3 class="dark:bg-[#101010] dark:text-white">Editar Reporte: {{ $reportes->title }}</h3>

<div class="section-body">
    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <form action="{{ route('reportes.update', $reportes->id) }}" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" name="title" value="{{ $reportes->title }}">
            <input type="hidden" name="tabla_principal" value="{{ $tablaPrincipal }}">
            <input type="hidden" name="tabla_relacion" value='@json($tablaRelacion)'>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="fw-semibold small text-muted">Tabla Principal</label>
                    <select class="form-select form-select-sm border-secondary" disabled>
                        <option>{{ ucfirst($tablaPrincipal) }}</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="fw-semibold small text-muted">Relaciones Seleccionadas</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($tablaRelacion as $rel)
                        <span class="badge bg-primary-subtle text-dark border px-3 py-2 rounded-pill">
                            {{ ucfirst($rel) }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Tabla Principal --}}
                <div class="col-md-6">
                    <div class="shadow-sm h-100 flex flex-col gap-3">
                        <div class="text-[#101D49] fw-bold dark:text-white">
                            {{ ucfirst($tablaPrincipal) }}
                        </div>
                        <div class="card-body">
                            <div class="row row-cols-2">
                                @foreach ($columnasPrincipales as $col)
                                @php $colPrincipal = $tablaPrincipal . '.' . $col; @endphp
                                <div class="col mb-2">
                                    <div class="form-check small">
                                        <input type="checkbox" style="cursor: pointer;" class="form-check-input border border-dark columna-check"
                                            name="columnas[]"
                                            value="{{ $colPrincipal }}"
                                            {{ in_array($colPrincipal, $columnasSeleccionadas) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ $col }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tablas Relacionadas --}}
                @foreach ($columnasRelacion as $tabla => $columnas)
                <div class="col-md-6">
                    <div class="shadow-sm h-100 flex flex-col gap-3">
                        <div class="text-[#101D49] dark:text-white fw-bold">
                            {{ ucfirst($tabla) }}
                        </div>
                        <div class="card-body">
                            <div class="row row-cols-2">
                                @foreach ($columnas as $col)
                                @php $relCol = $tabla . '.' . $col; @endphp
                                <div class="col mb-2">
                                    <div class="form-check small">
                                        <input type="checkbox" class="form-check-input border border-dark columna-check"
                                            name="columnas[]"
                                            value="{{ $relCol }}"
                                            {{ in_array($relCol, $columnasSeleccionadas) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ $col }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <hr class="my-4">

            <h6 class="fw-bold text-muted mb-3">Filtros aplicados</h6>

            <div id="filtros-wrapper">
                @foreach ($condiciones as $i => $filtro)
                <div class="row align-items-end mb-3 filtro-item">
                    <div class="col-md-4">
                        <select name="filtros[{{ $i }}][columna]" class="form-select form-select-sm">
                            <option value="">-- Selecciona --</option>
                            @foreach ($columnasSeleccionadas as $col)
                            <option value="{{ $col }}" {{ $filtro['columna'] == $col ? 'selected' : '' }}>{{ $col }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="filtros[{{ $i }}][operador]" class="form-select form-select-sm" onchange="mostrarFiltrosRange({{ $i }})">
                            <option value="=" {{ $filtro['operador'] == '=' ? 'selected' : '' }}>igual</option>
                            <option value="like" {{ $filtro['operador'] == 'like' ? 'selected' : '' }}>contiene</option>
                            <option value=">" {{ $filtro['operador'] == '>' ? 'selected' : '' }}>mayor que</option>
                            <option value="<" {{ $filtro['operador'] == '<' ? 'selected' : '' }}>menor que</option>
                            <option value="between" {{ $filtro['operador'] == 'between' ? 'selected' : '' }}>entre</option>
                        </select>
                    </div>

                    <div class="col-md-4" id="valor_filtro_{{ $i }}">
                        @if(is_array($filtro['valor']))
                        <div class="row">
                            <div class="col-6 col-md-5">
                                <input type="text" name="filtros[{{ $i }}][valor][inicio]" class="form-control form-control-sm"
                                    placeholder="Desde" value="{{ $filtro['valor']['inicio'] ?? '' }}">
                            </div>
                            <div class="col-6 col-md-2 text-center d-flex justify-content-center align-items-center">
                                <span class="text-muted">a</span>
                            </div>
                            <div class="col-6 col-md-5">
                                <input type="text" name="filtros[{{ $i }}][valor][fin]" class="form-control form-control-sm"
                                    placeholder="Hasta" value="{{ $filtro['valor']['fin'] ?? '' }}">
                            </div>
                        </div>
                        @else
                        @php
                        $partes = explode('.', $filtro['columna']);
                        $tabla = $partes[0] ?? null;
                        $columna = $partes[1] ?? null;
                        @endphp
                        @if(in_array($filtro['operador'], ['=','like']) && $tabla && $columna)
                        <input type="text" class="form-control form-control-sm autocomplete w-4"
                            placeholder="Valor" value="{{$filtro['valor'] ?? ''}}"
                            data-name="filtros[{{$i}}][valor]" data-tabla="{{$tabla}}"
                            data-columna="{{$columna}}">
                        @else
                        <input type="text" name="filtros[{{ $i }}][valor]" class="form-control form-control-sm"
                            placeholder="Valor" value="{{ $filtro['valor'] ?? '' }}">
                        @endif
                        @endif
                    </div>

                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger eliminar-filtro">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-filtro">+ Agregar Filtro</button>
            </div>

            <hr class="my-4">

            <div class="flex flex-col gap-2">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="ordenColumna" class="form-label small fw-semibold text-muted">Ordenar por columna</label>
                        <select id="ordenColumna" name="ordenColumna" class="form-select form-select-sm">
                            <option value="">-- Sin orden --</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="ordenDireccion" class="form-label small fw-semibold text-muted">Tipo de orden</label>
                        <select id="ordenDireccion" name="ordenDireccion" class="form-select form-select-sm">
                            <option value="">Sin orden</option>
                            <option value="asc" {{ $ordenDir === 'asc' ? 'selected' : '' }}>Ascendente</option>
                            <option value="desc" {{ $ordenDir === 'desc' ? 'selected' : '' }}>Descendente</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="limite" class="form-label small fw-semibold text-muted">Límite de resultados</label>
                        <input type="number" id="limite" name="limite" value="{{ $limite }}" class="form-control form-control-sm" min="1" placeholder="Ej: 100">
                    </div>
                </div>

                <div>
                    <a href="{{ route('reportes.index') }}" class="btn btn-danger">Cancelar</a>
                    <button type="button" class="btn btn-info btn-xs" id="btnPreview">Previsualizar reporte</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
        </form>
    </div>
</div>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
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