@extends('layouts.app')

@php
    $puedeGenerar = auth()->check() && auth()->user()->can('crear-auditorias');
    $puedeBorrar  = auth()->check() && auth()->user()->can('borrar-auditorias');

    $estados = [
        'pendiente' => ['clase' => 'cambio', 'icono' => 'fa-clock-rotate-left', 'texto' => 'Pendiente'],
        'alDia'     => ['clase' => 'igual',  'icono' => 'fa-circle-check',      'texto' => 'Al día'],
        'sinNada'   => ['clase' => 'todas',  'icono' => 'fa-minus',             'texto' => 'Nada que auditar'],
    ];
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
        <button type="button" class="aud-btn aud-btn--primary" id="btnAbrirLicencias"
                aria-haspopup="dialog" aria-controls="modalGenerar">
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

    {{-- Buscador: la lista ya viene ordenada por urgencia, esto sólo sirve para
         saltar a alguien concreto. Es GET para que la búsqueda sea compartible. --}}
    <form method="GET" action="{{ route('auditorias.index') }}" class="aud-buscador">
        <label class="aud-sr" for="buscarEmpleadoLista">Buscar empleado o equipo</label>
        <div class="aud-buscador__campo">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" id="buscarEmpleadoLista" class="aud-buscar"
                   value="{{ $busqueda }}" autocomplete="off"
                   placeholder="Empleado, área, marca, modelo, serie o folio…">
        </div>
        <button type="submit" class="aud-btn aud-btn--ghost aud-btn--sm">Buscar</button>
        @if($busqueda !== '')
            <a href="{{ route('auditorias.index') }}" class="aud-btn aud-btn--ghost aud-btn--sm">
                <i class="fas fa-xmark" aria-hidden="true"></i> Limpiar
            </a>
        @endif
    </form>

    {{-- Una fila por empleado YA auditado: la lista crece conforme se generan
         corridas. Dentro de eso, lo pendiente y más viejo sube solo. --}}
    <div class="index-page__card">
        @if($grupos->isEmpty())
            <div class="aud-vacio">
                <span class="aud-vacio__ico"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                <p class="aud-vacio__titulo">
                    {{ $busqueda !== '' ? 'Nadie coincide con la búsqueda' : 'Aún no hay auditorías generadas' }}
                </p>
                <p>
                    {{ $busqueda !== ''
                        ? 'Prueba con otro nombre, área, serie o folio.'
                        : 'Genera la primera para congelar lo que resguarda un empleado y poder compararlo después.' }}
                </p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table index-table aud-grupos w-full">
                <thead>
                    <tr>
                        <th scope="col" class="aud-col-toggle"><span class="aud-sr">Desplegar historial</span></th>
                        <th scope="col">Empleado</th>
                        <th scope="col">Equipos</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Última auditoría</th>
                        <th scope="col">Licencias hoy</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupos as $g)
                    @php
                        $panelId = 'historial-' . $g->clave;
                        $est = $estados[$g->estado] ?? $estados['sinNada'];
                    @endphp

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
                            <div class="aud-mini aud-muted">{{ $g->gerencia }} · {{ $g->tipo_persona }}</div>
                        </td>

                        {{-- La corrida cubre todos sus equipos, pero la lista no los
                             pinta: con 16 máquinas la fila se vuelve ilegible. Va un
                             badge con el conteo y el detalle se abre bajo demanda. --}}
                        <td>
                            @if($g->equipos->isEmpty())
                                <span class="aud-muted">Sin equipos</span>
                            @else
                                <button type="button" class="aud-chip-eq"
                                        data-ver-equipos="{{ $g->clave }}"
                                        data-nombre="{{ $g->NombreEmpleado }}"
                                        aria-haspopup="dialog">
                                    <i class="fas fa-laptop" aria-hidden="true"></i>
                                    <span class="aud-num">{{ $g->equipos->count() }}</span>
                                    {{ $g->equipos->count() === 1 ? 'equipo' : 'equipos' }}
                                </button>

                                {{-- Origen del modal: se clona al abrir. Así el detalle
                                     lo arma Blade y el JS no reconstruye markup. --}}
                                <div class="aud-eqdatos" id="equipos-{{ $g->clave }}" hidden>
                                    @foreach($g->equipos as $eq)
                                        <div class="aud-eqficha">
                                            <div class="aud-eqficha__top">
                                                <span class="aud-eqficha__cat">
                                                    <i class="fas {{ Str::contains(Str::upper($eq->CategoriaEquipo ?? ''), 'LAPTOP') ? 'fa-laptop' : 'fa-desktop' }}"
                                                       aria-hidden="true"></i>
                                                    {{ $eq->CategoriaEquipo ?: 'Sin categoría' }}
                                                </span>
                                                @if($eq->tipoEquipo !== null)
                                                    {!! \App\Helpers\PresupuestoAsignacion::chipHtml((int) $eq->tipoEquipo) !!}
                                                @endif
                                            </div>

                                            <div class="aud-eqficha__modelo">
                                                {{ trim($eq->Marca . ' ' . $eq->Modelo) ?: 'Sin modelo' }}
                                            </div>

                                            <div class="aud-eqficha__ids">
                                                <span class="aud-eqficha__id">
                                                    <span class="aud-eqficha__etq">Serie</span>
                                                    <span class="aud-num">{{ $eq->NumSerie ?: '—' }}</span>
                                                </span>
                                                <span class="aud-eqficha__id">
                                                    <span class="aud-eqficha__etq">Folio</span>
                                                    <span class="aud-num">{{ $eq->Folio ?: '—' }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        {{-- El icono acompaña al color: el estado no se comunica sólo con color. --}}
                        <td>
                            <span class="aud-marca aud-marca--{{ $est['clase'] }}">
                                <i class="fas {{ $est['icono'] }}" aria-hidden="true"></i> {{ $est['texto'] }}
                            </span>
                        </td>

                        <td>
                            @if($g->ultima)
                                <div class="aud-strong">{{ $g->ultima->Folio }}</div>
                                <div class="aud-mini aud-muted">
                                    {{ $g->ultima->created_at?->format('d/m/Y') ?: '—' }}
                                    · {{ $g->total }} {{ $g->total === 1 ? 'auditoría' : 'auditorías' }}
                                </div>
                            @else
                                <span class="aud-muted">—</span>
                            @endif
                        </td>

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
                            <div class="aud-historial__acciones">
                                {{-- Auditar desde la fila: el modal ya sabe a quién, así
                                     no hay que volver a buscarlo en el combo. --}}
                                @if($puedeGenerar && $g->estado !== 'sinNada')
                                <button type="button" class="aud-btn aud-btn--primary aud-btn--sm"
                                        data-auditar="{{ $g->EmpleadoID }}"
                                        data-nombre="{{ $g->NombreEmpleado }}">
                                    <i class="fas fa-play" aria-hidden="true"></i> Auditar
                                </button>
                                @endif
                                @if($g->ultima)
                                <a href="{{ route('auditorias.show', $g->ultima->id) }}"
                                   class="aud-btn aud-btn--ghost aud-btn--sm">
                                    <i class="fas fa-eye" aria-hidden="true"></i> Ver
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- Historial del empleado. Nace cerrado: son pocas filas por
                         empleado y así el toggle no necesita ir al servidor. --}}
                    <tr id="{{ $panelId }}" class="aud-historial" hidden>
                        <td colspan="7">
                            <div class="aud-historial__inner">
                                <table class="aud-historial__tabla">
                                    <thead>
                                        <tr>
                                            <th scope="col">Folio</th>
                                            <th scope="col">Fecha</th>
                                            <th scope="col">Generada por</th>
                                            <th scope="col">Alcance</th>
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
                                            <td class="aud-mini">
                                                {{ $c->equipos }} eq · {{ $c->licencias }} lic
                                            </td>

                                            {{-- El delta se calcula al leer, nunca se guarda:
                                                 corregir una corrida vieja corrige el resumen. --}}
                                            <td>
                                                @php $cambios = $c->cambios; @endphp
                                                @if($c->esPrimera)
                                                    <span class="aud-mini aud-muted">Primera auditoría</span>
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
             y se pierde en modo oscuro. --}}
        @if($grupos->hasPages())
        <nav class="aud-pag" role="navigation" aria-label="Paginación de auditorías">
            <span class="aud-muted">
                Mostrando {{ $grupos->firstItem() }}–{{ $grupos->lastItem() }}
                de {{ $grupos->total() }} empleados
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
                    // Ventana de páginas alrededor de la actual: con 200+ empleados la
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

{{-- Equipos de un empleado. Uno solo para toda la tabla: se llena al abrirlo con
     el contenido que ya pintó Blade en su fila. --}}
<div class="aud-modal" id="modalEquipos" hidden>
    <div class="aud-modal__fondo" data-cerrar-equipos></div>

    <div class="aud-modal__caja aud-modal__caja--sm" role="dialog" aria-modal="true"
         aria-labelledby="modalEquiposTitulo">
        <div class="aud-modal__cabecera">
            <div>
                <h2 class="aud-modal__titulo" id="modalEquiposTitulo">
                    <i class="fas fa-laptop" aria-hidden="true"></i> Equipos del resguardante
                </h2>
                <p class="aud-modal__ayuda" id="modalEquiposSub"></p>
            </div>
            <button type="button" class="aud-btn aud-btn--ghost aud-btn--sm" data-cerrar-equipos>
                <i class="fas fa-xmark" aria-hidden="true"></i> Cerrar
            </button>
        </div>

        <div class="aud-eqlista" id="modalEquiposLista"></div>
    </div>
</div>

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
        // ── Desplegar el historial de un empleado ────────────────────────────
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
                boton.closest('tr').classList.toggle('is-abierto', !abierto);

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

        // ── Modal de equipos ─────────────────────────────────────────────────
        // Un solo modal para toda la tabla: al abrirlo se le copia el contenido que
        // Blade ya dejó en la fila, así el JS no reconstruye markup.
        var modalEq  = document.getElementById('modalEquipos');
        var listaEq  = document.getElementById('modalEquiposLista');
        var subEq    = document.getElementById('modalEquiposSub');
        var origenEq = null;

        function cerrarEquipos() {
            if (!modalEq) return;
            modalEq.hidden = true;
            document.body.style.overflow = '';
            if (listaEq) listaEq.innerHTML = '';
            // El foco vuelve al badge que lo abrió, no al principio de la página.
            if (origenEq) origenEq.focus();
            origenEq = null;
        }

        if (tabla && modalEq) {
            tabla.addEventListener('click', function (e) {
                var badge = e.target.closest('[data-ver-equipos]');
                if (!badge) return;

                var datos = document.getElementById('equipos-' + badge.dataset.verEquipos);
                if (!datos) return;

                origenEq = badge;
                if (listaEq) listaEq.innerHTML = datos.innerHTML;
                if (subEq) subEq.textContent = badge.dataset.nombre || '';

                modalEq.hidden = false;
                document.body.style.overflow = 'hidden';

                var cerrar = modalEq.querySelector('[data-cerrar-equipos]');
                if (cerrar) cerrar.focus();
            });

            modalEq.querySelectorAll('[data-cerrar-equipos]').forEach(function (el) {
                el.addEventListener('click', cerrarEquipos);
            });

            // AppNav reinyecta este script; el listener en document sobreviviría al
            // intercambio y se acumularía apuntando a modales muertos.
            if (window.__audEqEscHandler) {
                document.removeEventListener('keydown', window.__audEqEscHandler);
            }

            window.__audEqEscHandler = function (e) {
                var actual = document.getElementById('modalEquipos');
                if (e.key === 'Escape' && actual && !actual.hidden) cerrarEquipos();
            };

            document.addEventListener('keydown', window.__audEqEscHandler);
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
        var filasEq  = modal ? Array.prototype.slice.call(modal.querySelectorAll('#listaEquipos .aud-lic')) : [];
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

        // Los equipos no se eligen: la corrida los toma todos. Sólo se cuentan para
        // decirle al auditor qué alcance va a generar.
        function equiposVisibles() {
            return filasEq.filter(function (f) { return !f.hidden; }).length;
        }

        function hayEmpleado() {
            return !comboValor || !!comboValor.value;
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

        // Sin empleado, o sin nada que congelar, no hay corrida que valga.
        function refrescar() {
            var lic = marcadas();
            var eq = equiposVisibles();

            if (contador) {
                contador.textContent = lic + ' de ' + checks.length + ' licencias seleccionadas';
            }

            if (contadorEquipos) {
                contadorEquipos.textContent = hayEmpleado()
                    ? eq + (eq === 1 ? ' equipo entra' : ' equipos entran') + ' a la auditoría'
                    : '';
            }

            if (resumen) {
                resumen.textContent = eq + (eq === 1 ? ' equipo' : ' equipos') +
                    ' · ' + lic + (lic === 1 ? ' licencia' : ' licencias');
            }

            if (boton) boton.disabled = !hayEmpleado() || (lic === 0 && eq === 0);
        }

        // ── Combobox de empleado ──────────────────────────────────────────────
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

        function elegirEmpleado(opcion) {
            if (!opcion) return;

            comboValor.value = opcion.dataset.valor;
            comboInput.value = opcion.dataset.nombre;
            if (comboLimpiar) comboLimpiar.hidden = false;
            cerrarLista();
            filtrarEquipos();
            pintarEstadoLicencias();
        }

        function limpiarEmpleado() {
            comboValor.value = '';
            comboInput.value = '';
            if (comboLimpiar) comboLimpiar.hidden = true;
            filtrarEmpleados(false);
            filtrarEquipos();
            pintarEstadoLicencias();
            comboInput.focus();
        }

        // Sólo se muestran los equipos del empleado elegido: es el alcance real de
        // la corrida, no una lista para escoger.
        function filtrarEquipos() {
            var empleado = comboValor ? comboValor.value : '';
            var visibles = 0;

            filasEq.forEach(function (fila) {
                var coincide = !!empleado && fila.dataset.empleado === empleado;
                fila.hidden = !coincide;
                if (coincide) visibles++;
            });

            var vacio = document.getElementById('sinEquipos');
            if (vacio) vacio.hidden = !empleado || visibles !== 0;

            refrescar();
        }

        function abrirModal() {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            filtrarEmpleados(false);
            filtrarEquipos();
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

        // Auditar desde la fila: se abre el modal con el empleado ya puesto. Evita
        // volver a buscarlo entre 200 nombres y garantiza que se audite al que se vio.
        if (tabla && modal) {
            tabla.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-auditar]');
                if (!btn) return;

                var opcion = opciones.filter(function (o) {
                    return o.dataset.valor === btn.dataset.auditar;
                })[0];

                abrirModal();

                if (opcion) {
                    elegirEmpleado(opcion);
                } else if (comboValor) {
                    // El empleado no está en el combo (sin licencias): igual se fija.
                    comboValor.value = btn.dataset.auditar;
                    comboInput.value = btn.dataset.nombre || '';
                    if (comboLimpiar) comboLimpiar.hidden = false;
                    filtrarEquipos();
                    pintarEstadoLicencias();
                }
            });
        }

        checks.forEach(function (c) { c.addEventListener('change', refrescar); });

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
                }
            });
        });

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
                    filtrarEquipos();
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
                        elegirEmpleado(vis[activo >= 0 ? activo : 0]);
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
                if (!hayEmpleado() || (marcadas() === 0 && equiposVisibles() === 0)) {
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
