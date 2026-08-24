@extends('layouts.app')

@php
    $puedeGenerar = auth()->check() && auth()->user()->can('crear-auditorias');
    $puedeBorrar  = auth()->check() && auth()->user()->can('borrar-auditorias');
@endphp

@section('content')
@include('flash::message')
@include('auditorias.partials.styles')

<div class="aud">
<x-index-page
    id="auditorias-page"
    title="Auditorías"
    icon="fa-clipboard-check"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        @if($puedeGenerar)
        {{-- Generar no es directo: primero se eligen las licencias que entran a la corrida. --}}
        <button type="button" class="aud-btn aud-btn--primary" id="btnAbrirLicencias"
                aria-haspopup="dialog" aria-controls="modalLicencias">
            <i class="fas fa-play" aria-hidden="true"></i> Generar auditoría
        </button>
        @endif
    </x-slot>

    @if($errors->any())
        <div class="aud-alerta" role="alert">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ $errors->first() }}
        </div>
    @endif

    {{-- Referencia a la corrida anterior: es el dato que justifica generar una nueva. --}}
    <div class="aud-bar">
        <div>
            <div class="aud-bar__label">Auditoría anterior</div>
            <div class="aud-bar__valor">
                @if($ultima)
                    {{ $ultima->Folio }} · {{ $ultima->created_at?->format('d/m/Y H:i') ?: '—' }}
                @else
                    --
                @endif
            </div>
        </div>
        <div>
            <div class="aud-bar__label">Generada por</div>
            <div class="aud-bar__valor">{{ $ultima?->generada_por_nombre ?: '—' }}</div>
        </div>
        <div>
            <div class="aud-bar__label">Empleado auditado</div>
            <div class="aud-bar__valor">{{ $ultima?->empleado?->NombreEmpleado ?: '—' }}</div>
        </div>
    </div>

    <div class="index-page__card">
        @if($auditorias->isEmpty())
            <div class="aud-vacio">
                <span class="aud-vacio__ico"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                <p class="aud-vacio__titulo">Aún no hay auditorías generadas</p>
                <p>Genera la primera para congelar el estado actual del inventario y poder compararlo después.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table index-table w-full">
                <thead>
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Generada por</th>
                        <th scope="col">Empleado auditado</th>
                        <th scope="col">Tipo de persona</th>
                        <th scope="col">Gerencia</th>
                        <th scope="col">Equipo</th>
                        <th scope="col">Tipo de equipo</th>
                        <th scope="col">Licencias auditadas</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditorias as $a)
                    <tr>
                        {{-- Tipo de persona y gerencia salen del empleado, no de la cabecera. --}}
                        <td class="aud-strong">{{ $a->Folio }}</td>
                        <td class="aud-num">{{ $a->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        <td>{{ $a->generada_por_nombre ?: 'Sin usuario' }}</td>
                        <td>{{ $a->empleado?->NombreEmpleado ?: '—' }}</td>
                        <td>{{ $a->empleado?->tipo_persona ?: '—' }}</td>
                        <td>{{ $a->empleado?->puestos?->departamentos?->gerencia?->NombreGerencia ?: '—' }}</td>
                        {{-- Categoría, marca/modelo, serie y folio del equipo que
                             resguarda el empleado, leídos del inventario en vivo. --}}
                        @php $equiposFila = collect($equiposPorAuditoria[$a->id] ?? []); @endphp
                        <td>
                            @include('auditorias.partials.equipos-lista', [
                                'equipos' => $equiposFila,
                                'compacto' => true,
                                'mostrarTipo' => false,
                            ])
                        </td>

                        {{-- Modalidad real de cada equipo: un chip por valor distinto. --}}
                        <td>
                            @forelse($equiposFila->pluck('tipoEquipo')->map(fn($t) => (int) $t)->unique()->sort() as $tipo)
                                {!! \App\Helpers\PresupuestoAsignacion::chipHtml($tipo) !!}
                            @empty
                                <span class="aud-muted">—</span>
                            @endforelse
                        </td>
                        <td>
                            @if($a->auditoTodasLasLicencias())
                                <span class="aud-muted">Todas</span>
                            @else
                                <span class="aud-mini" title="{{ implode(', ', $a->licencias_auditadas) }}">
                                    {{ $a->total_licencias_auditadas }}
                                    {{ $a->total_licencias_auditadas === 1 ? 'licencia' : 'licencias' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('auditorias.show', $a->id) }}" class="aud-btn aud-btn--ghost aud-btn--sm">
                                    <i class="fas fa-eye" aria-hidden="true"></i> Ver
                                </a>
                                @if($puedeBorrar)
                                <form method="POST" action="{{ route('auditorias.destroy', $a->id) }}"
                                      class="form-borrar-auditoria" data-folio="{{ $a->Folio }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="aud-btn aud-btn--danger aud-btn--sm">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i> Eliminar
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación propia: el paginador de Bootstrap no usa los tokens del módulo
             y se pierde en modo oscuro. --}}
        @if($auditorias->hasPages())
        <nav class="aud-pag" role="navigation" aria-label="Paginación de auditorías">
            <span class="aud-muted">
                Mostrando {{ $auditorias->firstItem() }}–{{ $auditorias->lastItem() }}
                de {{ $auditorias->total() }} auditorías
            </span>

            <div class="aud-pag__botones">
                @if($auditorias->onFirstPage())
                    <span class="aud-pag__btn is-disabled" aria-disabled="true">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                    </span>
                @else
                    <a href="{{ $auditorias->previousPageUrl() }}" class="aud-pag__btn" rel="prev">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                    </a>
                @endif

                @php
                    // Ventana de páginas alrededor de la actual: con muchas corridas la
                    // lista completa de números no cabe.
                    $actual = $auditorias->currentPage();
                    $ultima = $auditorias->lastPage();
                    $desde  = max(1, min($actual - 2, $ultima - 4));
                    $hasta  = min($ultima, max($actual + 2, 5));
                @endphp

                @if($desde > 1)
                    <a href="{{ $auditorias->url(1) }}" class="aud-pag__btn aud-num">1</a>
                    @if($desde > 2)<span class="aud-pag__gap" aria-hidden="true">…</span>@endif
                @endif

                @for($p = $desde; $p <= $hasta; $p++)
                    @if($p === $actual)
                        <span class="aud-pag__btn is-actual aud-num" aria-current="page">{{ $p }}</span>
                    @else
                        <a href="{{ $auditorias->url($p) }}" class="aud-pag__btn aud-num">{{ $p }}</a>
                    @endif
                @endfor

                @if($hasta < $ultima)
                    @if($hasta < $ultima - 1)<span class="aud-pag__gap" aria-hidden="true">…</span>@endif
                    <a href="{{ $auditorias->url($ultima) }}" class="aud-pag__btn aud-num">{{ $ultima }}</a>
                @endif

                @if($auditorias->hasMorePages())
                    <a href="{{ $auditorias->nextPageUrl() }}" class="aud-pag__btn" rel="next">
                        Siguiente <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="aud-pag__btn is-disabled" aria-disabled="true">
                        Siguiente <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </span>
                @endif
            </div>
        </nav>
        @endif
        @endif
    </div>
</x-index-page>

@if($puedeGenerar)
    @include('auditorias.partials.selector-generar', [
        'catalogoLicencias' => $catalogoLicencias,
        'catalogoEquipos'   => $catalogoEquipos,
        'gerencias'         => $gerencias,
        'departamentos'     => $departamentos,
        'tiposPersona'      => $tiposPersona,
        'empleados'         => $empleados,
    ])
@endif
</div>
@endsection

{{-- Va en el stack, no en el contenido: SweetAlert y jQuery se cargan al final del
     layout, después de #app-main. --}}
@push('third_party_scripts')
<script>
    (function () {
        var modal  = document.getElementById('modalGenerar');
        var abrir  = document.getElementById('btnAbrirLicencias');
        var form   = document.getElementById('formGenerar');
        var boton  = document.getElementById('btnConfirmarGenerar');

        var checks   = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaLicencias .aud-lic__check')) : [];
        var equipos  = modal ? Array.prototype.slice.call(modal.querySelectorAll('.aud-equipo__check')) : [];
        var filas    = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaEquipos .aud-lic')) : [];
        var opciones = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaEmpleados .aud-combo__opcion')) : [];

        var contador        = document.getElementById('contadorLicencias');
        var contadorEquipos = document.getElementById('contadorEquipos');
        var resumen         = document.getElementById('resumenAlcance');

        var comboInput   = document.getElementById('buscarEmpleado');
        var comboLista   = document.getElementById('listaEmpleados');
        var comboValor   = document.getElementById('selectEmpleado');
        var comboLimpiar = document.getElementById('limpiarEmpleado');
        var comboVacio   = document.getElementById('sinEmpleados');
        var activo = -1;

        function marcadas() {
            return checks.filter(function (c) { return c.checked; }).length;
        }

        function equiposMarcados() {
            return equipos.filter(function (c) { return c.checked; }).length;
        }

        // Equipos del empleado elegido, ya pasados por los filtros.
        function equiposVisibles() {
            return filas.filter(function (f) { return !f.hidden; }).length;
        }

        function hayEmpleado() {
            return !comboValor || !!comboValor.value;
        }

        // Sin licencias, sin equipos o sin empleado no hay corrida que valga:
        // el submit queda bloqueado hasta que el alcance tenga sentido.
        function refrescar() {
            var lic = marcadas();
            var disponibles = equiposVisibles();
            var eq = equiposMarcados();

            if (contador) {
                contador.textContent = lic + ' de ' + checks.length + ' licencias seleccionadas';
            }

            if (contadorEquipos) {
                contadorEquipos.textContent = eq + ' de ' + disponibles + ' equipos seleccionados';
            }

            if (resumen) {
                resumen.textContent = eq + (eq === 1 ? ' equipo' : ' equipos') +
                    ' · ' + lic + (lic === 1 ? ' licencia' : ' licencias');
            }

            if (boton) boton.disabled = lic === 0 || eq === 0 || !hayEmpleado();
        }

        // ── Combobox de empleado ──────────────────────────────────────────────
        // Los tres filtros de arriba acotan el universo; el texto busca dentro de él.
        function opcionesVisibles() {
            return opciones.filter(function (o) { return !o.hidden; });
        }

        function filtrarEmpleados(usarTexto) {
            var area  = (document.getElementById('filtroGerencia') || {}).value || '';
            var depto = (document.getElementById('filtroDepartamento') || {}).value || '';
            var tipo  = (document.getElementById('filtroTipoPersona') || {}).value || '';
            var q = usarTexto ? (comboInput.value || '').trim().toLowerCase() : '';
            var visibles = 0;

            opciones.forEach(function (o) {
                var coincide = (!area || o.dataset.gerencia === area) &&
                               (!depto || o.dataset.departamento === depto) &&
                               (!tipo || o.dataset.tipoPersona === tipo) &&
                               (!q || o.dataset.busqueda.indexOf(q) !== -1);
                o.hidden = !coincide;
                if (coincide) visibles++;
            });

            if (comboVacio) comboVacio.hidden = visibles !== 0;
            activo = -1;
            marcarActivo();
        }

        function abrirLista() {
            if (!comboLista) return;
            comboLista.hidden = false;
            comboInput.setAttribute('aria-expanded', 'true');
        }

        function cerrarLista() {
            if (!comboLista) return;
            comboLista.hidden = true;
            comboInput.setAttribute('aria-expanded', 'false');
            activo = -1;
            marcarActivo();
        }

        function marcarActivo() {
            var vis = opcionesVisibles();
            vis.forEach(function (o, i) {
                var esActivo = i === activo;
                o.classList.toggle('is-activo', esActivo);
                o.setAttribute('aria-selected', esActivo ? 'true' : 'false');
                if (esActivo) o.scrollIntoView({ block: 'nearest' });
            });
            comboInput.setAttribute('aria-activedescendant',
                activo >= 0 && vis[activo] ? vis[activo].id : '');
        }

        // Sin licencias no hay nada que revisar: la opción se ve, pero no se elige.
        function bloqueada(opcion) {
            return !!opcion && opcion.dataset.licencias === '0';
        }

        function elegirEmpleado(opcion) {
            if (!opcion) return;

            if (bloqueada(opcion)) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin licencias que auditar',
                        text: opcion.dataset.nombre + ' no tiene ninguna licencia registrada en el inventario.'
                    });
                }
                return;
            }

            comboValor.value = opcion.dataset.valor;
            comboInput.value = opcion.dataset.nombre;
            if (comboLimpiar) comboLimpiar.hidden = false;
            cerrarLista();
            filtrarEquipos(true);
        }

        function limpiarEmpleado() {
            comboValor.value = '';
            comboInput.value = '';
            if (comboLimpiar) comboLimpiar.hidden = true;
            filtrarEmpleados(false);
            filtrarEquipos(false);
            comboInput.focus();
        }

        // Filtro de equipos: manda el empleado elegido; el tipo y el texto afinan.
        // Sin empleado no se muestra ninguno: la corrida es de uno solo.
        function filtrarEquipos(marcarTodos) {
            var empleado = comboValor ? comboValor.value : '';
            var tipo = (document.getElementById('selectTipoEquipo') || {}).value || '';
            var q = ((document.getElementById('buscarEquipo') || {}).value || '').trim().toLowerCase();
            var visibles = 0;

            filas.forEach(function (fila) {
                var coincide = !!empleado &&
                               fila.dataset.empleado === empleado &&
                               (!tipo || fila.dataset.tipo === tipo) &&
                               (!q || fila.dataset.busqueda.indexOf(q) !== -1);
                fila.hidden = !coincide;

                var check = fila.querySelector('.aud-equipo__check');
                if (check) {
                    // Lo que no se ve no viaja en el POST; lo del empleado entra marcado
                    // por defecto y el usuario destilda lo que quiera dejar fuera.
                    if (!coincide) check.checked = false;
                    else if (marcarTodos) check.checked = true;
                }

                if (coincide) visibles++;
            });

            var vacio = document.getElementById('sinEquipos');
            if (vacio) vacio.hidden = visibles !== 0;

            refrescar();
        }

        function abrirModal() {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            // Se recalcula al abrir: el alcance depende del empleado ya elegido.
            filtrarEmpleados(false);
            filtrarEquipos(false);
            if (comboInput) comboInput.focus();
        }

        function cerrarModal() {
            modal.hidden = true;
            document.body.style.overflow = '';
            if (abrir) abrir.focus();
        }

        if (abrir && modal) {
            abrir.addEventListener('click', abrirModal);

            modal.querySelectorAll('[data-cerrar-modal]').forEach(function (el) {
                el.addEventListener('click', cerrarModal);
            });

            // AppNav re-ejecuta este script en cada navegación; el listener en document
            // sobrevive al intercambio y se acumularía apuntando a modales muertos.
            if (window.__audEscHandler) {
                document.removeEventListener('keydown', window.__audEscHandler);
            }

            window.__audEscHandler = function (e) {
                var actual = document.getElementById('modalGenerar');
                if (e.key === 'Escape' && actual && !actual.hidden) cerrarModal();
            };

            document.addEventListener('keydown', window.__audEscHandler);
        }

        checks.forEach(function (c) { c.addEventListener('change', refrescar); });
        equipos.forEach(function (c) { c.addEventListener('change', refrescar); });

        var buscarEquipo = document.getElementById('buscarEquipo');
        var selectTipoEquipo = document.getElementById('selectTipoEquipo');

        // Cambiar de área, departamento o tipo de empleado invalida al ya elegido si
        // deja de cumplir: se limpia en vez de dejar una selección incoherente.
        ['filtroGerencia', 'filtroDepartamento', 'filtroTipoPersona'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;

            el.addEventListener('change', function () {
                filtrarEmpleados(false);

                var elegido = opciones.filter(function (o) {
                    return o.dataset.valor === (comboValor ? comboValor.value : '');
                })[0];

                if (comboValor && comboValor.value && (!elegido || elegido.hidden)) {
                    limpiarEmpleado();
                } else {
                    filtrarEquipos(false);
                }
            });
        });

        // El tipo de equipo cambia el universo del empleado: se remarca lo que queda.
        if (selectTipoEquipo) {
            selectTipoEquipo.addEventListener('change', function () { filtrarEquipos(true); });
        }

        // Buscar dentro de los equipos sólo oculta; no debe desmarcar lo ya elegido.
        if (buscarEquipo) {
            buscarEquipo.addEventListener('input', function () { filtrarEquipos(false); });
        }

        if (comboInput) {
            comboInput.addEventListener('focus', function () {
                filtrarEmpleados(true);
                abrirLista();
            });

            comboInput.addEventListener('input', function () {
                // Escribir invalida la selección previa: el texto ya no la representa.
                if (comboValor.value) {
                    comboValor.value = '';
                    if (comboLimpiar) comboLimpiar.hidden = true;
                    filtrarEquipos(false);
                }
                filtrarEmpleados(true);
                abrirLista();
            });

            comboInput.addEventListener('keydown', function (e) {
                var vis = opcionesVisibles();

                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (comboLista.hidden) abrirLista();
                    if (!vis.length) return;
                    activo = e.key === 'ArrowDown'
                        ? (activo + 1) % vis.length
                        : (activo <= 0 ? vis.length - 1 : activo - 1);
                    marcarActivo();
                    return;
                }

                if (e.key === 'Enter') {
                    if (!comboLista.hidden && vis.length) {
                        e.preventDefault();
                        // Con el teclado se salta a la siguiente elegible en vez de
                        // dejar al usuario atorado en una opción que no responde.
                        var elegible = vis.slice(activo >= 0 ? activo : 0).filter(function (o) {
                            return !bloqueada(o);
                        })[0];
                        elegirEmpleado(elegible || vis[activo >= 0 ? activo : 0]);
                    }
                    return;
                }

                // Esc cierra la lista sin cerrar el modal completo.
                if (e.key === 'Escape' && !comboLista.hidden) {
                    e.stopPropagation();
                    cerrarLista();
                }
            });

            opciones.forEach(function (o) {
                o.addEventListener('click', function () { elegirEmpleado(o); });
            });

            document.addEventListener('click', function (e) {
                var combo = document.getElementById('comboEmpleado');
                if (combo && !combo.contains(e.target)) cerrarLista();
            });
        }

        if (comboLimpiar) comboLimpiar.addEventListener('click', limpiarEmpleado);

        // "Visibles" respeta el filtro: marca lo que se está viendo, no todo el catálogo.
        var btnEquiposTodos = document.getElementById('btnEquiposTodos');
        var btnEquiposNinguno = document.getElementById('btnEquiposNinguno');

        if (btnEquiposTodos) {
            btnEquiposTodos.addEventListener('click', function () {
                filas.forEach(function (fila) {
                    if (fila.hidden) return;
                    var check = fila.querySelector('.aud-equipo__check');
                    if (check) check.checked = true;
                });
                refrescar();
            });
        }

        if (btnEquiposNinguno) {
            btnEquiposNinguno.addEventListener('click', function () {
                equipos.forEach(function (c) { c.checked = false; });
                refrescar();
            });
        }

        var btnTodas = document.getElementById('btnTodas');
        var btnNinguna = document.getElementById('btnNinguna');

        if (btnTodas) {
            btnTodas.addEventListener('click', function () {
                checks.forEach(function (c) { c.checked = true; });
                refrescar();
            });
        }

        if (btnNinguna) {
            btnNinguna.addEventListener('click', function () {
                checks.forEach(function (c) { c.checked = false; });
                refrescar();
            });
        }

        // Filtro de texto: sólo oculta, no desmarca — lo escondido sigue enviándose.
        var buscar = document.getElementById('buscarLicencia');
        var sinResultados = document.getElementById('sinResultados');

        if (buscar) {
            buscar.addEventListener('input', function () {
                var q = buscar.value.trim().toLowerCase();
                var visibles = 0;

                modal.querySelectorAll('.aud-lic').forEach(function (fila) {
                    var coincide = !q || fila.dataset.nombre.indexOf(q) !== -1;
                    fila.hidden = !coincide;
                    if (coincide) visibles++;
                });

                if (sinResultados) sinResultados.hidden = visibles !== 0;
            });
        }

        refrescar();

        // Generar toma unos segundos con inventarios grandes: bloquear el botón evita
        // corridas duplicadas por doble clic.
        if (form) {
            form.addEventListener('submit', function (e) {
                if (marcadas() === 0 || equiposMarcados() === 0 || !hayEmpleado()) {
                    e.preventDefault();
                    return;
                }
                boton.disabled = true;
                boton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Generando…';
            });
        }

        // Eliminar es irreversible: se confirma antes.
        document.querySelectorAll('.form-borrar-auditoria').forEach(function (f) {
            f.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar ' + f.dataset.folio + '?',
                    text: 'Se borra la corrida y todo su detalle. No se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#B91C1C',
                }).then(function (r) {
                    if (r.isConfirmed) f.submit();
                });
            });
        });
    })();
</script>
@endpush
