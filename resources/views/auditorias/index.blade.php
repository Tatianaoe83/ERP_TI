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
    subtitle="Corridas de auditoría de equipos tecnológicos"
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
                    {{ $ultima->Folio }} · {{ $ultima->generada_en->format('d/m/Y H:i') }}
                @else
                    Nunca se ha generado una auditoría
                @endif
            </div>
        </div>
        <div>
            <div class="aud-bar__label">Generada por</div>
            <div class="aud-bar__valor">{{ $ultima?->generada_por_nombre ?: '—' }}</div>
        </div>
        <div>
            <div class="aud-bar__label">Equipos revisados</div>
            <div class="aud-bar__valor aud-num">{{ $ultima?->total_equipos ?? 0 }}</div>
        </div>
        @if($ultima && $ultima->total_piratas)
        <div>
            <div class="aud-bar__label">Licencias piratas</div>
            <div class="aud-bar__valor aud-num" style="color: var(--aud-danger);">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ $ultima->total_piratas }}
            </div>
        </div>
        @endif
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
                        <th scope="col">Equipos</th>
                        <th scope="col">Laptops</th>
                        <th scope="col">PC</th>
                        <th scope="col">Otros</th>
                        <th scope="col">Propios</th>
                        <th scope="col">Licencias auditadas</th>
                        <th scope="col">Piratas</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditorias as $a)
                    <tr>
                        <td class="aud-strong">{{ $a->Folio }}</td>
                        <td class="aud-num">{{ $a->generada_en->format('d/m/Y H:i') }}</td>
                        <td>{{ $a->generada_por_nombre ?: 'Sin usuario' }}</td>
                        <td class="aud-num aud-strong">{{ $a->total_equipos }}</td>
                        <td class="aud-num">{{ $a->total_laptops }}</td>
                        <td class="aud-num">{{ $a->total_pcs }}</td>
                        <td class="aud-num">{{ $a->total_otros }}</td>
                        <td class="aud-num">{{ $a->total_propios }}</td>
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
                        <td class="aud-num">
                            @if($a->total_piratas)
                                <span class="aud-chip aud-chip--pirata">
                                    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ $a->total_piratas }}
                                </span>
                            @else
                                <span class="aud-muted">0</span>
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
        var radios   = modal ? Array.prototype.slice.call(modal.querySelectorAll('input[name="alcance"]')) : [];

        var contador        = document.getElementById('contadorLicencias');
        var contadorEquipos = document.getElementById('contadorEquipos');
        var resumen         = document.getElementById('resumenAlcance');
        var bloqueEquipos   = document.getElementById('bloqueEquipos');

        function marcadas() {
            return checks.filter(function (c) { return c.checked; }).length;
        }

        function equiposMarcados() {
            return equipos.filter(function (c) { return c.checked; }).length;
        }

        function esGeneral() {
            var elegido = radios.filter(function (r) { return r.checked; })[0];
            return !elegido || elegido.value === 'todos';
        }

        // Auditar cero licencias, o cero equipos en modo selección, no significa nada:
        // el submit queda bloqueado hasta que el alcance tenga sentido.
        function refrescar() {
            var lic = marcadas();
            var eq = esGeneral() ? equipos.length : equiposMarcados();

            if (contador) {
                contador.textContent = lic + ' de ' + checks.length + ' licencias seleccionadas';
            }

            if (contadorEquipos) {
                contadorEquipos.textContent = equiposMarcados() + ' de ' + equipos.length + ' equipos seleccionados';
            }

            if (resumen) {
                resumen.textContent = eq + (eq === 1 ? ' equipo' : ' equipos') +
                    ' · ' + lic + (lic === 1 ? ' licencia' : ' licencias');
            }

            if (boton) boton.disabled = lic === 0 || eq === 0;
        }

        // El bloque de equipos sólo estorba en modo general; los checkboxes se limpian
        // para que no viajen en el POST.
        function refrescarAlcance() {
            var general = esGeneral();

            if (bloqueEquipos) bloqueEquipos.hidden = general;
            if (general) equipos.forEach(function (c) { c.checked = false; });

            refrescar();
        }

        // Filtro combinado: el área y el texto se aplican juntos.
        function filtrarEquipos() {
            var area = (document.getElementById('filtroGerencia') || {}).value || '';
            var q = ((document.getElementById('buscarEquipo') || {}).value || '').trim().toLowerCase();
            var visibles = 0;

            filas.forEach(function (fila) {
                var coincide = (!area || fila.dataset.gerencia === area) &&
                               (!q || fila.dataset.busqueda.indexOf(q) !== -1);
                fila.hidden = !coincide;
                if (coincide) visibles++;
            });

            var vacio = document.getElementById('sinEquipos');
            if (vacio) vacio.hidden = visibles !== 0;
        }

        function abrirModal() {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            var primero = modal.querySelector('input[name="alcance"]');
            if (primero) primero.focus();
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
        radios.forEach(function (r) { r.addEventListener('change', refrescarAlcance); });

        var filtroGerencia = document.getElementById('filtroGerencia');
        var buscarEquipo = document.getElementById('buscarEquipo');

        if (filtroGerencia) filtroGerencia.addEventListener('change', filtrarEquipos);
        if (buscarEquipo) buscarEquipo.addEventListener('input', filtrarEquipos);

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

        refrescarAlcance();

        // Generar toma unos segundos con inventarios grandes: bloquear el botón evita
        // corridas duplicadas por doble clic.
        if (form) {
            form.addEventListener('submit', function (e) {
                if (marcadas() === 0 || (!esGeneral() && equiposMarcados() === 0)) {
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
                    title: 'Eliminar ' + f.dataset.folio + '?',
                    text: 'Se borra la corrida y todo su detalle. No se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#B91C1C',
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                }).then(function (r) {
                    if (r.isConfirmed) f.submit();
                });
            });
        });
    })();
</script>
@endpush
