{{-- Alcance de la corrida: qué equipos entran y qué licencias se revisan.
     Sólo se ofrecen laptops y PC de escritorio de personal físico. --}}
<div class="aud-modal" id="modalGenerar" hidden>
    <div class="aud-modal__fondo" data-cerrar-modal></div>

    <div class="aud-modal__caja" role="dialog" aria-modal="true" aria-labelledby="modalGenerarTitulo">
        <form method="POST" action="{{ route('auditorias.store') }}" id="formGenerar">
            @csrf

            <div class="aud-modal__cabecera">
                <div>
                    <h2 class="aud-modal__titulo" id="modalGenerarTitulo">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i> Generar auditoría
                    </h2>
                    <p class="aud-modal__ayuda">
                        Sólo entran laptops y PC de escritorio resguardadas por personal físico.
                    </p>
                </div>
                <button type="button" class="aud-btn aud-btn--ghost aud-btn--sm" data-cerrar-modal>
                    <i class="fas fa-xmark" aria-hidden="true"></i> Cerrar
                </button>
            </div>

            @if($catalogoEquipos->isEmpty() || $catalogoLicencias->isEmpty())
                <div class="aud-vacio">
                    <span class="aud-vacio__ico"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                    <p class="aud-vacio__titulo">Faltan datos para auditar</p>
                    <p>
                        @if($catalogoEquipos->isEmpty())
                            No hay laptops ni PC de escritorio asignadas a personal físico.
                        @else
                            No hay licencias registradas en el inventario.
                        @endif
                    </p>
                </div>
            @else
                {{-- Dos columnas: cada lista trae su propio scroll, apiladas se veían
                     como dos cajas desplazables encimadas. --}}
                <div class="aud-pasos">
                {{-- ── Paso 1: equipos ── --}}
                <fieldset class="aud-paso">
                    <legend class="aud-paso__titulo">
                        <span class="aud-paso__num">1</span> Equipos a auditar
                    </legend>

                    {{-- La corrida es de un empleado: su tipo de persona y su gerencia
                         salen del propio empleado, por eso aquí sólo se elige a quién.
                         Los filtros de arriba acotan la lista del buscador, no el POST. --}}
                    <div class="aud-filtros">
                        <div class="aud-campo">
                            <label class="aud-campo__label" for="filtroGerencia">Área</label>
                            <select id="filtroGerencia" class="aud-select">
                                <option value="">Todas</option>
                                @foreach($gerencias as $g)
                                    <option value="{{ Str::lower($g) }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="aud-campo">
                            <label class="aud-campo__label" for="filtroDepartamento">Departamento</label>
                            <select id="filtroDepartamento" class="aud-select">
                                <option value="">Todos</option>
                                @foreach($departamentos as $d)
                                    <option value="{{ Str::lower($d) }}">{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="aud-campo">
                            <label class="aud-campo__label" for="filtroTipoPersona">Tipo de empleado</label>
                            <select id="filtroTipoPersona" class="aud-select">
                                <option value="">Todos</option>
                                @foreach($tiposPersona as $t)
                                    <option value="{{ Str::lower($t) }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="aud-campo">
                            <label class="aud-campo__label" for="selectTipoEquipo">Tipo de equipo</label>
                            <select id="selectTipoEquipo" name="tipoEquipo" class="aud-select">
                                <option value="">Cualquiera</option>
                                <option value="0">Stock</option>
                                <option value="1">Extra</option>
                                <option value="2">Compartido</option>
                                <option value="3">Propio</option>
                            </select>
                        </div>
                    </div>

                    {{-- Combobox: escribir filtra, ↑↓ recorre, Enter elige, Esc cierra.
                         El valor real viaja en el hidden, no en el texto visible. --}}
                    <div class="aud-campo aud-campo--empleado">
                        <label class="aud-campo__label" for="buscarEmpleado">
                            Empleado a auditar <span class="aud-campo__req" aria-hidden="true">*</span>
                        </label>

                        <div class="aud-combo" id="comboEmpleado">
                            <i class="fas fa-magnifying-glass aud-combo__ico" aria-hidden="true"></i>
                            <input type="text" id="buscarEmpleado" class="aud-combo__input"
                                   placeholder="Escribe el nombre del empleado…"
                                   autocomplete="off" role="combobox"
                                   aria-expanded="false" aria-autocomplete="list"
                                   aria-controls="listaEmpleados"
                                   aria-describedby="ayudaEmpleado">
                            <button type="button" class="aud-combo__limpiar" id="limpiarEmpleado"
                                    aria-label="Limpiar empleado seleccionado" hidden>
                                <i class="fas fa-xmark" aria-hidden="true"></i>
                            </button>
                            <input type="hidden" name="EmpleadoID" id="selectEmpleado" value="">

                            <ul class="aud-combo__lista" id="listaEmpleados" role="listbox"
                                aria-label="Empleados auditables" hidden>
                                @foreach($empleados as $emp)
                                    @php $sinLicencias = ! $emp->licencias; @endphp
                                    {{-- Sin licencias no hay nada que auditar: la opción
                                         se muestra y se explica, pero no se puede elegir. --}}
                                    <li class="aud-combo__opcion {{ $sinLicencias ? 'is-bloqueada' : '' }}"
                                        role="option" tabindex="-1"
                                        id="empleado-{{ $emp->EmpleadoID }}"
                                        aria-selected="false"
                                        @if($sinLicencias) aria-disabled="true" @endif
                                        data-valor="{{ $emp->EmpleadoID }}"
                                        data-nombre="{{ $emp->NombreEmpleado }}"
                                        data-licencias="{{ (int) $emp->licencias }}"
                                        data-gerencia="{{ Str::lower($emp->gerencia) }}"
                                        data-departamento="{{ Str::lower($emp->departamento) }}"
                                        data-tipo-persona="{{ Str::lower($emp->tipo_persona) }}"
                                        data-busqueda="{{ Str::lower($emp->NombreEmpleado . ' ' . $emp->gerencia . ' ' . $emp->departamento . ' ' . $emp->tipo_persona) }}">
                                        <span class="aud-combo__nombre">{{ $emp->NombreEmpleado }}</span>
                                        <span class="aud-combo__meta">
                                            <span class="aud-mini">{{ $emp->tipo_persona }}</span>
                                            <span class="aud-mini">{{ $emp->gerencia }}</span>
                                            <span class="aud-mini">{{ $emp->departamento }}</span>
                                            @if($sinLicencias)
                                                <span class="aud-mini aud-mini--bloqueo">
                                                    <i class="fas fa-ban" aria-hidden="true"></i> Sin licencias
                                                </span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                                <li class="aud-combo__vacio" id="sinEmpleados" hidden>
                                    Ningún empleado coincide con los filtros.
                                </li>
                            </ul>
                        </div>

                        <p class="aud-campo__ayuda" id="ayudaEmpleado">
                            Se auditan sus laptops y PC. Destilda abajo lo que quieras dejar fuera.
                        </p>
                    </div>

                    <div id="bloqueEquipos">
                        <div class="aud-modal__barra">
                            <label class="aud-sr" for="buscarEquipo">Buscar equipo</label>
                            <input type="search" id="buscarEquipo" class="aud-buscar"
                                   placeholder="Serie, folio o modelo…" autocomplete="off">

                            <div class="aud-modal__acciones">
                                <button type="button" class="aud-btn aud-btn--ghost aud-btn--sm" id="btnEquiposTodos">
                                    <i class="fas fa-check-double" aria-hidden="true"></i> Visibles
                                </button>
                                <button type="button" class="aud-btn aud-btn--ghost aud-btn--sm" id="btnEquiposNinguno">
                                    <i class="fas fa-eraser" aria-hidden="true"></i> Ninguno
                                </button>
                            </div>
                        </div>

                        <div class="aud-lic-lista aud-lic-lista--equipos" id="listaEquipos">
                            @foreach($catalogoEquipos as $equipo)
                                @php
                                    $gerencia = trim((string) $equipo->GerenciaEquipo) ?: 'Sin gerencia';
                                    $modelo = trim($equipo->Marca . ' ' . $equipo->Modelo);
                                    $busqueda = Str::lower(implode(' ', array_filter([
                                        $equipo->NombreEmpleado, $equipo->CategoriaEquipo,
                                        $modelo, $equipo->NumSerie, $equipo->Folio, $gerencia,
                                    ])));
                                @endphp
                                <label class="aud-lic aud-lic--equipo"
                                       data-gerencia="{{ Str::lower($gerencia) }}"
                                       data-empleado="{{ $equipo->EmpleadoID }}"
                                       data-tipo="{{ (int) $equipo->tipoEquipo }}"
                                       data-busqueda="{{ $busqueda }}">
                                    <input type="checkbox" name="equipos[]" value="{{ $equipo->InventarioID }}"
                                           class="aud-lic__check aud-equipo__check">
                                    <span class="aud-equipo">
                                        <span class="aud-equipo__nombre">{{ $equipo->NombreEmpleado ?: 'Sin asignar' }}</span>
                                        <span class="aud-equipo__meta">
                                            <span class="aud-mini">{{ $equipo->CategoriaEquipo }}</span>
                                            @if($modelo)<span class="aud-mini">{{ $modelo }}</span>@endif
                                            @if($equipo->NumSerie)<span class="aud-mini aud-num">{{ $equipo->NumSerie }}</span>@endif
                                        </span>
                                        <span class="aud-equipo__gerencia">{{ $gerencia }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <p class="aud-modal__vacio-busqueda" id="sinEquipos" hidden>
                            Ningún equipo coincide con el filtro.
                        </p>

                        <p class="aud-muted" id="contadorEquipos" aria-live="polite"></p>
                    </div>
                </fieldset>

                {{-- ── Paso 2: licencias ── --}}
                <fieldset class="aud-paso">
                    <legend class="aud-paso__titulo">
                        <span class="aud-paso__num">2</span> Licencias a revisar
                    </legend>

                    <div class="aud-modal__barra">
                        <label class="aud-sr" for="buscarLicencia">Buscar licencia</label>
                        <input type="search" id="buscarLicencia" class="aud-buscar"
                               placeholder="Buscar licencia…" autocomplete="off">

                        <div class="aud-modal__acciones">
                            <button type="button" class="aud-btn aud-btn--ghost aud-btn--sm" id="btnTodas">
                                <i class="fas fa-check-double" aria-hidden="true"></i> Todas
                            </button>
                            <button type="button" class="aud-btn aud-btn--ghost aud-btn--sm" id="btnNinguna">
                                <i class="fas fa-eraser" aria-hidden="true"></i> Ninguna
                            </button>
                        </div>
                    </div>

                    <div class="aud-lic-lista" id="listaLicencias">
                        @foreach($catalogoLicencias as $lic)
                        <label class="aud-lic" data-nombre="{{ Str::lower($lic) }}">
                            <input type="checkbox" name="licencias[]" value="{{ $lic }}"
                                   class="aud-lic__check" checked>
                            <span class="aud-lic__nombre">{{ $lic }}</span>
                        </label>
                        @endforeach
                    </div>

                    <p class="aud-modal__vacio-busqueda" id="sinResultados" hidden>
                        Ninguna licencia coincide con la búsqueda.
                    </p>

                    <p class="aud-muted" id="contadorLicencias" aria-live="polite"></p>
                </fieldset>
                </div>

                <div class="aud-modal__pie">
                    <span class="aud-muted" id="resumenAlcance" aria-live="polite"></span>
                    <button type="submit" class="aud-btn aud-btn--primary" id="btnConfirmarGenerar">
                        <i class="fas fa-play" aria-hidden="true"></i> Generar auditoría
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>
