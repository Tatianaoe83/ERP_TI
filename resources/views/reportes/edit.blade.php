@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h4><strong>{{ $reportes->title }}</strong></h4>

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
                <div class="d-flex flex-wrap gap-2 mt-1">
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
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-primary fw-bold border-bottom">
                        {{ ucfirst($tablaPrincipal) }}
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-2">
                            @foreach ($columnasPrincipales as $col)
                            @php $colPrincipal = $tablaPrincipal . '.' . $col; @endphp
                            <div class="col mb-2">
                                <div class="form-check small">
                                    <input type="checkbox" class="form-check-input border border-dark columna-check"
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
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white text-primary fw-bold border-bottom">
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
        <option value="like" {{ $filtro['operador'] == 'like' ? 'selected' : '' }}>si contiene</option>
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
    <input type="text" name="filtros[{{ $i }}][valor]" class="form-control form-control-sm"
        placeholder="Valor" value="{{ $filtro['valor'] ?? '' }}">
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

        <div class="text-end mt-4">
            <a href="{{ route('reportes.index') }}" class="btn btn-outline-danger">Cancelar</a>
            <button type="button" class="btn btn-outline-info btn-xs" id="btnPreview">Previsualizar reporte</button>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
    </form>
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
                    valor = { inicio, fin };
                }
            } else {
                const valorInput = item.querySelector('input[name$="[valor]"]');
                valor = valorInput?.value?.trim();
            }

            if (columna && operador && valor) {
                filtros.push({columna,operador,valor});
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
    const selectColumnas = columnas.map(col => `<option value="${col}">${col}</option>`).join(''); // llenar las columnas seleccionables

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
    const valorDiv = document.getElementById(`valor_filtro_${filtroId}`);

    if (operadorSelect.value === 'between') {
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
@endsection