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
                    <label class="form-label small">Columna</label>
                    <select name="filtros[{{ $i }}][columna]" class="form-select form-select-sm">
                        <option value="">-- Selecciona --</option>
                        @foreach ($columnasSeleccionadas as $col)
                        <option value="{{ $col }}" {{ $filtro['columna'] == $col ? 'selected' : '' }}>{{ $col }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Operador</label>
                    <select name="filtros[{{ $i }}][operador]" class="form-select form-select-sm">
                        <option value="=" {{ $filtro['operador'] == '=' ? 'selected' : '' }}>=</option>
                        <option value="like" {{ $filtro['operador'] == 'like' ? 'selected' : '' }}>like</option>
                        <option value=">" {{ $filtro['operador'] == '>' ? 'selected' : '' }}>&gt;</option>
                        <option value="<" {{ $filtro['operador'] == '<' ? 'selected' : '' }}>&lt;</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Valor</label>
                    <input type="text" name="filtros[{{ $i }}][valor]" class="form-control form-control-sm" value="{{ $filtro['valor'] ?? '' }}">
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
                <label for="grupo" class="form-label small fw-semibold text-muted">Grupo (GROUP BY)</label>
                <select id="grupo" name="grupo" class="form-select form-select-sm">
                    <option value="">-- Sin agrupación --</option>
                </select>
            </div>

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
            <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

<script>
    let contadorFiltros = {{ count($condiciones ?? []) }};

    function obtenerColumnasSeleccionadas() {
        const columnas = [];
        document.querySelectorAll('.columna-check:checked').forEach(cb => {
            columnas.push(cb.value);
        });
        return columnas;
    }

    function actualizarSelectsDinamicos() {
        const columnas = obtenerColumnasSeleccionadas();

        const selectGrupo = document.getElementById('grupo');
        const selectOrden = document.getElementById('ordenColumna');

        const valorGrupoActual = '{{ $grupo }}';
        const valorOrdenActual = '{{ $ordenCol }}';

        const opcionesGrupo = columnas.map(col => {
            const selected = col === valorGrupoActual ? 'selected' : '';
            return `<option value="${col}" ${selected}>${col}</option>`;
        });

        const opcionesOrden = columnas.map(col => {
            const selected = col === valorOrdenActual ? 'selected' : '';
            return `<option value="${col}" ${selected}>${col}</option>`;
        });

        selectGrupo.innerHTML = '<option value="">-- Sin agrupación --</option>' + opcionesGrupo.join('');
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

    document.getElementById('agregar-filtro').addEventListener('click', function () {
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
                <select name="filtros[${contadorFiltros}][operador]" class="form-select form-select-sm">
                    <option value="=">=</option>
                    <option value="like">like</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                </select>
            </div>
            <div class="col-md-4">
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
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.eliminar-filtro')) {
            e.preventDefault();
            e.target.closest('.filtro-item').remove();
        }
    });

    actualizarSelectsDinamicos();
    actualizarSelectsFiltros();
</script>
@endsection