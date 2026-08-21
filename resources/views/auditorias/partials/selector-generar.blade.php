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

                    <div class="aud-opciones">
                        <label class="aud-opcion">
                            <input type="radio" name="alcance" value="todos" checked>
                            <span>
                                <span class="aud-opcion__titulo">General</span>
                                <span class="aud-opcion__ayuda">Los {{ $catalogoEquipos->count() }} equipos auditables</span>
                            </span>
                        </label>
                        <label class="aud-opcion">
                            <input type="radio" name="alcance" value="seleccion">
                            <span>
                                <span class="aud-opcion__titulo">Por equipo</span>
                                <span class="aud-opcion__ayuda">Elegir a quién se le audita</span>
                            </span>
                        </label>
                    </div>

                    <div id="bloqueEquipos" hidden>
                        <div class="aud-modal__barra">
                            <label class="aud-sr" for="filtroGerencia">Filtrar por área</label>
                            <select id="filtroGerencia" class="aud-select">
                                <option value="">Todas las áreas</option>
                                @foreach($gerencias as $g)
                                    <option value="{{ Str::lower($g) }}">{{ $g }}</option>
                                @endforeach
                            </select>

                            <label class="aud-sr" for="buscarEquipo">Buscar equipo</label>
                            <input type="search" id="buscarEquipo" class="aud-buscar"
                                   placeholder="Empleado, serie o modelo…" autocomplete="off">

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
