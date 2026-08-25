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

    {{-- Una fila por par (empleado, equipo). Las corridas siguen guardándose una por
         una; aquí sólo se presentan juntas, y el historial se abre bajo demanda. --}}
    <div class="index-page__card">
        @if($grupos->isEmpty())
            <div class="aud-vacio">
                <span class="aud-vacio__ico"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                <p class="aud-vacio__titulo">Aún no hay auditorías generadas</p>
                <p>Genera la primera para congelar el estado actual del inventario y poder compararlo después.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table index-table aud-grupos w-full">
                <thead>
                    <tr>
                        <th scope="col" class="aud-col-toggle"><span class="aud-sr">Desplegar historial</span></th>
                        <th scope="col">Empleado</th>
                        <th scope="col">Equipo revisado</th>
                        <th scope="col">Auditorías</th>
                        <th scope="col">Última</th>
                        <th scope="col">Licencias hoy</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupos as $g)
                    @php $panelId = 'historial-' . $g->clave; @endphp

                    <tr class="aud-grupo" data-grupo="{{ $g->clave }}">
                        <td class="aud-col-toggle">
                            {{-- Botón real: Enter y Espacio funcionan sin JS extra. --}}
                            <button type="button" class="aud-toggle"
                                    data-toggle-grupo="{{ $panelId }}"
                                    aria-expanded="false" aria-controls="{{ $panelId }}">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                <span class="aud-sr">
                                    Ver las {{ $g->total }} auditorías de {{ $g->NombreEmpleado }}
                                </span>
                            </button>
                        </td>

                        <td>
                            <div class="aud-strong">{{ $g->NombreEmpleado }}</div>
                            <div class="aud-mini aud-muted">
                                {{ $g->gerencia }} · {{ $g->tipo_persona }}
                            </div>
                        </td>

                        <td>
                            @include('auditorias.partials.equipo-ficha', [
                                'equipo' => $g->equipo,
                                'compacto' => true,
                            ])
                        </td>

                        <td>
                            <span class="aud-conteo" title="Corridas guardadas de este par">
                                {{ $g->total }}
                            </span>
                        </td>

                        <td>
                            @if($g->ultima)
                                <div class="aud-strong">{{ $g->ultima->Folio }}</div>
                                <div class="aud-mini aud-muted">
                                    {{ $g->ultima->created_at?->format('d/m/Y') ?: '—' }}
                                    · {{ $g->ultima->generada_por_nombre ?: 'Sin usuario' }}
                                </div>
                            @else
                                <span class="aud-muted">—</span>
                            @endif
                        </td>

                        {{-- Semáforo del estado vigente del empleado. El icono acompaña
                             al color: el estado no se comunica sólo con color. --}}
                        <td>
                            @if($g->licencias === 0)
                                <span class="aud-muted">Sin licencias</span>
                            @else
                                <div class="aud-semaforo">
                                    @if($g->alDia)
                                        <span class="aud-marca aud-marca--igual">
                                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                                            Al día <span class="aud-num">{{ $g->alDia }}</span>
                                        </span>
                                    @endif
                                    @if($g->caducadas)
                                        <span class="aud-marca aud-marca--cambio">
                                            <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                                            Caducadas <span class="aud-num">{{ $g->caducadas }}</span>
                                        </span>
                                    @endif
                                    @if($g->nunca)
                                        <span class="aud-marca aud-marca--baja">
                                            <i class="fas fa-circle-question" aria-hidden="true"></i>
                                            Sin revisar <span class="aud-num">{{ $g->nunca }}</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td>
                            @if($g->ultima)
                            <a href="{{ route('auditorias.show', $g->ultima->id) }}"
                               class="aud-btn aud-btn--ghost aud-btn--sm">
                                <i class="fas fa-eye" aria-hidden="true"></i> Ver última
                            </a>
                            @endif
                        </td>
                    </tr>

                    {{-- Historial del par. Se pinta siempre pero nace cerrado: son pocas
                         filas por grupo y así el toggle no necesita ir al servidor. --}}
                    <tr id="{{ $panelId }}" class="aud-historial" hidden>
                        <td colspan="7">
                            <div class="aud-historial__inner">
                                <table class="aud-historial__tabla">
                                    <thead>
                                        <tr>
                                            <th scope="col">Folio</th>
                                            <th scope="col">Fecha</th>
                                            <th scope="col">Generada por</th>
                                            <th scope="col">Licencias</th>
                                            <th scope="col">Contra la corrida anterior</th>
                                            <th scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Más reciente arriba: es la que se consulta. --}}
                                        @foreach($g->corridas->reverse() as $c)
                                        <tr>
                                            <td class="aud-strong">{{ $c->Folio }}</td>
                                            <td class="aud-num">{{ $c->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                            <td>{{ $c->generada_por_nombre ?: 'Sin usuario' }}</td>
                                            <td class="aud-num">{{ $c->licencias }}</td>

                                            {{-- El delta se calcula al leer, nunca se guarda:
                                                 corregir una corrida vieja corrige el resumen. --}}
                                            <td>
                                                @php $cambios = $c->cambios; @endphp
                                                @if($c->esPrimera)
                                                    <span class="aud-mini aud-muted">Primera auditoría del empleado</span>
                                                @elseif($cambios['nueva'] === 0 && $cambios['baja'] === 0 && $cambios['cambio'] === 0)
                                                    <span class="aud-marca aud-marca--igual">
                                                        <i class="fas fa-equals" aria-hidden="true"></i> Sin cambios
                                                    </span>
                                                @else
                                                    <div class="aud-semaforo">
                                                        @if($cambios['nueva'])
                                                            <span class="aud-marca aud-marca--nueva">
                                                                <i class="fas fa-circle-plus" aria-hidden="true"></i>
                                                                Nuevas <span class="aud-num">{{ $cambios['nueva'] }}</span>
                                                            </span>
                                                        @endif
                                                        @if($cambios['cambio'])
                                                            <span class="aud-marca aud-marca--cambio">
                                                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                                                Cambiaron <span class="aud-num">{{ $cambios['cambio'] }}</span>
                                                            </span>
                                                        @endif
                                                        @if($cambios['baja'])
                                                            <span class="aud-marca aud-marca--baja">
                                                                <i class="fas fa-circle-minus" aria-hidden="true"></i>
                                                                Bajas <span class="aud-num">{{ $cambios['baja'] }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="aud-historial__acciones">
                                                    <a href="{{ route('auditorias.show', $c->id) }}"
                                                       class="aud-btn aud-btn--ghost aud-btn--sm">
                                                        <i class="fas fa-eye" aria-hidden="true"></i> Ver
                                                    </a>
                                                    @if($puedeBorrar)
                                                    <form method="POST" action="{{ route('auditorias.destroy', $c->id) }}"
                                                          class="form-borrar-auditoria" data-folio="{{ $c->Folio }}">
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
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación propia: el paginador de Bootstrap no usa los tokens del módulo
             y se pierde en modo oscuro. Se pagina por grupo, así que abrir una fila
             nunca parte su historial entre dos páginas. --}}
        @if($grupos->hasPages())
        <nav class="aud-pag" role="navigation" aria-label="Paginación de auditorías">
            <span class="aud-muted">
                Mostrando {{ $grupos->firstItem() }}–{{ $grupos->lastItem() }}
                de {{ $grupos->total() }} equipos auditados
            </span>

            <div class="aud-pag__botones">
                @if($grupos->onFirstPage())
                    <span class="aud-pag__btn is-disabled" aria-disabled="true">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                    </span>
                @else
                    <a href="{{ $grupos->previousPageUrl() }}" class="aud-pag__btn" rel="prev">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                    </a>
                @endif

                @php
                    // Ventana de páginas alrededor de la actual: con muchos grupos la
                    // lista completa de números no cabe.
                    $actual = $grupos->currentPage();
                    $ultimaPag = $grupos->lastPage();
                    $desde = max(1, min($actual - 2, $ultimaPag - 4));
                    $hasta = min($ultimaPag, max($actual + 2, 5));
                @endphp

                @if($desde > 1)
                    <a href="{{ $grupos->url(1) }}" class="aud-pag__btn aud-num">1</a>
                    @if($desde > 2)<span class="aud-pag__gap" aria-hidden="true">…</span>@endif
                @endif

                @for($p = $desde; $p <= $hasta; $p++)
                    @if($p === $actual)
                        <span class="aud-pag__btn is-actual aud-num" aria-current="page">{{ $p }}</span>
                    @else
                        <a href="{{ $grupos->url($p) }}" class="aud-pag__btn aud-num">{{ $p }}</a>
                    @endif
                @endfor

                @if($hasta < $ultimaPag)
                    @if($hasta < $ultimaPag - 1)<span class="aud-pag__gap" aria-hidden="true">…</span>@endif
                    <a href="{{ $grupos->url($ultimaPag) }}" class="aud-pag__btn aud-num">{{ $ultimaPag }}</a>
                @endif

                @if($grupos->hasMorePages())
                    <a href="{{ $grupos->nextPageUrl() }}" class="aud-pag__btn" rel="next">
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
        'estadoLicencias'   => $estadoLicencias,
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
        // ── Desplegar el historial de un grupo ───────────────────────────────
        // Delegado en el contenedor: sobrevive al repintado de AppNav y no deja
        // un listener por fila.
        var tabla = document.querySelector('.aud-grupos');

        if (tabla) {
            tabla.addEventListener('click', function (e) {
                var boton = e.target.closest('[data-toggle-grupo]');
                if (!boton) return;

                var panel = document.getElementById(boton.dataset.toggleGrupo);
                if (!panel) return;

                var abierto = boton.getAttribute('aria-expanded') === 'true';
                var inner = panel.querySelector('.aud-historial__inner');

                boton.setAttribute('aria-expanded', abierto ? 'false' : 'true');
                boton.classList.toggle('is-abierto', !abierto);
                boton.closest('.aud-grupo, tr').classList.toggle('is-abierto', !abierto);

                if (abierto) {
                    if (inner) inner.classList.remove('is-abierto');
                    panel.hidden = true;
                    return;
                }

                panel.hidden = false;
                // Doble rAF: el navegador necesita ver el estado inicial antes de
                // que la clase dispare la transición.
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        if (inner) inner.classList.add('is-abierto');
                    });
                });
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

        // ── Modal de generación ──────────────────────────────────────────────
        var modal  = document.getElementById('modalGenerar');
        var abrir  = document.getElementById('btnAbrirLicencias');
        var form   = document.getElementById('formGenerar');
        var boton  = document.getElementById('btnConfirmarGenerar');

        var checks   = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaLicencias .aud-lic__check')) : [];
        // Orden del catálogo tal como lo pintó el servidor: es el desempate dentro de
        // cada grupo cuando la lista se reordena por empleado.
        var filasLic = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaLicencias .aud-lic')) : [];
        var equipos  = modal ? Array.prototype.slice.call(modal.querySelectorAll('.aud-equipo__check')) : [];
        var filas    = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaEquipos .aud-lic')) : [];
        var opciones = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaEmpleados .aud-combo__opcion')) : [];

        var contador        = document.getElementById('contadorLicencias');
        var contadorEquipos = document.getElementById('contadorEquipos');
        var resumen         = document.getElementById('resumenAlcance');

        // Estado vigente por (empleado, licencia): lo que ya se auditó y cuándo.
        var estadoLicencias = {};
        try {
            var datos = document.getElementById('estadoLicenciasData');
            if (datos) estadoLicencias = JSON.parse(datos.textContent || '{}');
        } catch (e) {
            estadoLicencias = {};
        }

        var ETIQUETA_ESTADO = {
            alDia:    { texto: 'revisada',      clase: 'aud-lic__estado--aldia'    },
            caducada: { texto: 'caducada',      clase: 'aud-lic__estado--caducada' },
            nunca:    { texto: 'sin revisar',   clase: 'aud-lic__estado--nunca'    },
            noTiene:  { texto: 'no la tiene',   clase: 'aud-lic__estado--notiene'  }
        };

        var comboInput   = document.getElementById('buscarEmpleado');
        var comboLista   = document.getElementById('listaEmpleados');
        var comboValor   = document.getElementById('selectEmpleado');
        var comboLimpiar = document.getElementById('limpiarEmpleado');
        var comboVacio   = document.getElementById('sinEmpleados');
        var activo = -1;

        function marcadas() {
            return checks.filter(function (c) { return c.checked; }).length;
        }

        // Radio: como mucho uno. Una corrida revisa un equipo.
        function equiposMarcados() {
            return equipos.filter(function (c) { return c.checked; }).length;
        }

        // Pinta, junto a cada licencia del catálogo, cómo quedó la última vez que se
        // revisó en este empleado. Sin empleado elegido no hay nada que decir.
        //
        // Las que el empleado no resguarda se destildan pero siguen a la vista: quien
        // audita necesita poder marcarlas si encuentra una instalada que el inventario
        // no tiene registrada. Esconderlas le quitaría esa salida.
        function pintarEstadoLicencias() {
            var empleado = comboValor ? comboValor.value : '';
            var delEmpleado = (empleado && estadoLicencias[empleado]) || null;

            checks.forEach(function (check) {
                var fila = check.closest('.aud-lic');
                if (!fila) return;

                var etq = fila.querySelector('[data-lic-estado]');
                if (!etq) return;

                etq.className = 'aud-lic__estado aud-mini';
                etq.textContent = '';
                fila.classList.remove('is-ajena');

                if (!delEmpleado) {
                    check.checked = true;
                    return;
                }

                var info = delEmpleado[fila.dataset.licencia];

                // Sólo entran marcadas las que el empleado sí tiene hoy.
                check.checked = !!info;

                var meta = ETIQUETA_ESTADO[info ? info.estado : 'noTiene'];
                if (!meta) return;

                if (!info) fila.classList.add('is-ajena');

                etq.classList.add(meta.clase);
                etq.textContent = info && info.fecha
                    ? meta.texto + ' ' + info.fecha
                    : meta.texto;
            });

            ordenarLicencias();
            refrescar();
        }

        // Primero lo que el empleado sí resguarda, después lo ajeno. Dentro de cada
        // grupo se respeta el orden del catálogo. Sin empleado elegido nada es ajeno,
        // así que la lista vuelve sola a su orden original.
        function ordenarLicencias() {
            var lista = document.getElementById('listaLicencias');
            if (!lista) return;

            var propias = [];
            var ajenas = [];

            filasLic.forEach(function (fila) {
                (fila.classList.contains('is-ajena') ? ajenas : propias).push(fila);
            });

            propias.concat(ajenas).forEach(function (fila) { lista.appendChild(fila); });
        }

        // Equipos del empleado elegido, ya pasados por los filtros.
        function equiposVisibles() {
            return filas.filter(function (f) { return !f.hidden; }).length;
        }

        function hayEmpleado() {
            return !comboValor || !!comboValor.value;
        }

        // Sin licencias, sin equipo o sin empleado no hay corrida que valga:
        // el submit queda bloqueado hasta que el alcance tenga sentido.
        function refrescar() {
            var lic = marcadas();
            var disponibles = equiposVisibles();
            var eq = equiposMarcados();

            if (contador) {
                contador.textContent = lic + ' de ' + checks.length + ' licencias seleccionadas';
            }

            if (contadorEquipos) {
                contadorEquipos.textContent = eq
                    ? '1 equipo elegido de ' + disponibles + ' disponibles'
                    : 'Elige 1 de ' + disponibles + (disponibles === 1 ? ' equipo' : ' equipos');
            }

            if (resumen) {
                resumen.textContent = (eq ? '1 equipo' : 'sin equipo') +
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
            pintarEstadoLicencias();
        }

        function limpiarEmpleado() {
            comboValor.value = '';
            comboInput.value = '';
            if (comboLimpiar) comboLimpiar.hidden = true;
            filtrarEmpleados(false);
            filtrarEquipos(false);
            pintarEstadoLicencias();
            comboInput.focus();
        }

        // Filtro de equipos: manda el empleado elegido; el tipo y el texto afinan.
        // Sin empleado no se muestra ninguno: la corrida es de uno solo.
        function filtrarEquipos(autoElegir) {
            var empleado = comboValor ? comboValor.value : '';
            var tipo = (document.getElementById('selectTipoEquipo') || {}).value || '';
            var q = ((document.getElementById('buscarEquipo') || {}).value || '').trim().toLowerCase();
            var visibles = [];

            filas.forEach(function (fila) {
                var coincide = !!empleado &&
                               fila.dataset.empleado === empleado &&
                               (!tipo || fila.dataset.tipo === tipo) &&
                               (!q || fila.dataset.busqueda.indexOf(q) !== -1);
                fila.hidden = !coincide;

                var check = fila.querySelector('.aud-equipo__check');
                // Lo que no se ve no viaja en el POST.
                if (check && !coincide) check.checked = false;

                if (coincide) visibles.push(fila);
            });

            // La mayoría resguarda una sola máquina: si sólo queda una, se elige sola
            // en vez de obligar a un clic que no decide nada.
            if (autoElegir && visibles.length === 1) {
                var unico = visibles[0].querySelector('.aud-equipo__check');
                if (unico) unico.checked = true;
            }

            var vacio = document.getElementById('sinEquipos');
            if (vacio) vacio.hidden = visibles.length !== 0;

            refrescar();
        }

        function abrirModal() {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            // Se recalcula al abrir: el alcance depende del empleado ya elegido.
            filtrarEmpleados(false);
            filtrarEquipos(false);
            pintarEstadoLicencias();
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

                modal.querySelectorAll('#listaLicencias .aud-lic').forEach(function (fila) {
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
    })();
</script>
@endpush
