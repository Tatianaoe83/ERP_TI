<div x-data="{
    abrirModalDesdeClick(event) {
        const boton = event.target.closest('[data-ver-solicitud]');
        if (!boton) return;

        event.preventDefault();
        this.abrirModal(boton.dataset.verSolicitud);
    },
    // Delega al modal global (partials/modal-detalle-solicitud, montado en el layout)
    abrirModal(id) {
        if (typeof window.__abrirModalSolicitud === 'function') {
            window.__abrirModalSolicitud(id);
        }
    },
    // Modal de asignación: el cascarón abre al instante con esqueleto; el servidor llena el contenido.
    asgOpen: false,
    asgCargando: false,
    asgTimer: null,
    asgCierresPropios: 0,
    asgAbrir(id) {
        // Reabrir antes de que salga el cierre: se cancela (openAsignacion reinicia el estado de todos modos).
        if (this.asgTimer) { clearTimeout(this.asgTimer); this.asgTimer = null; }
        this.asgCargando = true;
        this.asgOpen = true;
        this.$wire.abrirModalAsignacion(id);
    },
    asgCerrar() {
        if (window.__bloqueoCierreAsignacion || document.querySelector('.swal2-container')) return;
        this.asgOpen = false;
        this.asgTimer = setTimeout(() => {
            this.asgTimer = null;
            this.asgCierresPropios++;
            this.$wire.closeAsignacion();
        }, 160);
    },
    esMiInstancia(e) { return e.detail && e.detail.wid === this.$el.getAttribute('wire:id'); }
}"
    {{-- Livewire 2 hace Alpine.clone() al morfear un x-data: los nodos nuevos del modal quedan marcados (_x_marker)
         pero sin listeners. Tras cada respuesta se desmarcan esos "clonados" (directivas sin _x_cleanups) y se
         inicializan; initTree se salta los que ya funcionan. Un solo hook por página. --}}
    x-init="if (!window.__asgInitHook && window.Livewire) { window.__asgInitHook = true; Livewire.hook('message.processed', (m, c) => c.el.querySelectorAll('.asg-panel').forEach(p => { p.querySelectorAll('*').forEach(el => { if (el._x_marker && !(el._x_cleanups && el._x_cleanups.length) && Array.from(el.attributes).some(a => /^(x-|@|:)/.test(a.name))) delete el._x_marker }); Alpine.initTree(p) })) }"
    @click="abrirModalDesdeClick($event)"
    @asg-abierto.window="if (esMiInstancia($event)) { asgOpen = true; asgCargando = false }"
    @asg-cerrado.window="if (esMiInstancia($event)) { if (asgCierresPropios > 0) { asgCierresPropios-- } else { asgOpen = false; asgCargando = false } }"
    @keydown.escape.window="asgOpen && asgCerrar()">

    @if(!$soloModal)
    @php $hayFiltrosSol = $search !== '' || $filtroEstatus !== ''; @endphp
    <div class="sol-toolbar" role="search">
        <div class="sol-toolbar__search">
            <label for="searchSolicitudes" class="sol-toolbar__label">Buscar</label>
            <div class="sol-toolbar__input-icon">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input id="searchSolicitudes" type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="ID, empleado o motivo"
                    autocomplete="off"
                    class="sol-toolbar__input">
            </div>
        </div>
        <div class="sol-toolbar__field">
            <label for="filtroEstatus" class="sol-toolbar__label">Estatus</label>
            {{-- wire:ignore: el poll/morph de Livewire quitaba el contenedor de select2 y regresaba el select nativo --}}
            <div wire:ignore>
            <select id="filtroEstatus" wire:model.live="filtroEstatus" class="sol-toolbar__input">
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En revisión">En revisión</option>
                <option value="Cotizaciones Enviadas">Cotizaciones Enviadas</option>
                <option value="Re-cotizar">Re-cotizar</option>
                <option value="Aprobada">Aprobada</option>
                <option value="Cancelada">Cancelada</option>
            </select>
            </div>
        </div>
        <div class="sol-toolbar__field sol-toolbar__field--sm">
            <label for="solicitudesPorPagina" class="sol-toolbar__label">Por página</label>
            <div wire:ignore>
            <select id="solicitudesPorPagina" wire:model.live="perPage" class="sol-toolbar__input">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            </div>
        </div>
        @if($hayFiltrosSol)
        <button type="button" wire:click="limpiarFiltros"
            onclick="if (window.jQuery) { jQuery('#filtroEstatus').val('').trigger('change.select2'); jQuery('#solicitudesPorPagina').val('10').trigger('change.select2'); }" class="sol-toolbar__clear">
            <i class="fas fa-times" aria-hidden="true"></i> Limpiar filtros
        </button>
        @endif
    </div>

    <div class="index-page__card sol-card">

        <div @if(!$modalAsignacionAbierto) wire:poll.15s @endif>

            <div class="sol-card__head">
                <div>
                    <h3 class="sol-card__title">Solicitudes</h3>
                    <p class="sol-card__meta">{{ number_format($todasSolicitudes->total()) }} {{ $todasSolicitudes->total() === 1 ? 'resultado' : 'resultados' }}{{ $hayFiltrosSol ? ' con los filtros aplicados' : '' }}</p>
                </div>
                {{-- Solo para acciones del usuario: el poll de 15s no muestra carga. --}}
                <span class="sol-card__loading" wire:loading.delay
                    wire:target="search,filtroEstatus,perPage,limpiarFiltros,gotoPage,nextPage,previousPage"
                    role="status">
                    <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Cargando…
                </span>
            </div>

            <div class="sol-table-wrap">
                <table class="sol-table">
                    <thead>
                        <tr>
                            <th scope="col">Solicitud</th>
                            <th scope="col">Empleado</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Estatus</th>
                            <th scope="col">Aprobaciones</th>
                            <th scope="col">Facturas</th>
                            <th scope="col" class="sol-table__th-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:key="solicitudes-body-page-{{ $todasSolicitudes->currentPage() }}">
                        @forelse ($todasSolicitudes as $solicitud)
                        @php
                            $nombreSol = trim((string) $solicitud->nombreFormateado);
                            $inicialesSol = collect(preg_split('/\s+/', $nombreSol, -1, PREG_SPLIT_NO_EMPTY))
                                ->take(2)
                                ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
                                ->implode('') ?: '?';
                        @endphp
                        <tr wire:key="solicitud-row-{{ $todasSolicitudes->currentPage() }}-{{ $solicitud->SolicitudID }}">
                            <td data-label="Solicitud">
                                <span class="sol-id">#{{ $solicitud->SolicitudID }}</span>
                                <span class="sol-sub" title="{{ $solicitud->created_at->format('d/m/Y H:i') }}">
                                    {{ $solicitud->created_at->format('d/m/Y') }}
                                </span>
                            </td>
                            <td data-label="Empleado">
                                <div class="sol-persona">
                                    <span class="sol-avatar" aria-hidden="true">{{ $inicialesSol }}</span>
                                    <div class="sol-persona__texto">
                                        <span class="sol-persona__nombre">{{ $nombreSol ?: 'N/A' }}</span>
                                        <span class="sol-sub" title="{{ $solicitud->empleadoid->Correo ?? '' }}">{{ $solicitud->empleadoid->Correo ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Motivo">
                                <span class="sol-motivo" title="{{ $solicitud->Motivo ?? '' }}">{{ $solicitud->Motivo ?? 'N/A' }}</span>
                            </td>
                            <td data-label="Estatus">
                                @php
                                    $solStatusMod = match($solicitud->estatusDisplay) {
                                        'En revisión' => 'sol-status--revision',
                                        'Cancelada' => 'sol-status--cancelada',
                                        'Rechazada' => 'sol-status--rechazada',
                                        'Aprobada' => 'sol-status--aprobada',
                                        'Cotizaciones Enviadas' => 'sol-status--enviadas',
                                        'Pendiente' => 'sol-status--pendiente',
                                        'Re-cotizar' => 'sol-status--recotizar',
                                        'Listo' => 'sol-status--listo',
                                        default => 'sol-status--default',
                                    };
                                @endphp
                                <span class="erp-chip {{ $solStatusMod }}"
                                    @if($solicitud->estatusDisplay === 'Re-cotizar' && !empty($solicitud->recotizarPropuestasText))
                                    title="Recotizar propuesta(s): {{ trim($solicitud->recotizarPropuestasText, ' ()') }}"
                                    @endif>
                                    {{ $solicitud->estatusDisplay }}{{ $solicitud->recotizarPropuestasText ?? '' }}
                                </span>
                            </td>
                            <td data-label="Aprobaciones">
                                <div class="sol-approvals">
                                    @if($solicitud->pasoSupervisor)
                                    @if($solicitud->pasoSupervisor->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Supervisor: Aprobado"></i>
                                    @elseif($solicitud->pasoSupervisor->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Supervisor: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-yellow-500" title="Supervisor: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Supervisor: Pendiente"></i>
                                    @endif
                                    @if($solicitud->pasoGerencia)
                                    @if($solicitud->pasoGerencia->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Gerente: Aprobado"></i>
                                    @elseif($solicitud->pasoGerencia->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Gerente: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-orange-500" title="Gerente: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Gerente: En espera"></i>
                                    @endif
                                    @if($solicitud->pasoAdministracion)
                                    @if($solicitud->pasoAdministracion->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Administración: Aprobado"></i>
                                    @elseif($solicitud->pasoAdministracion->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Administración: Rechazado"></i>
                                    @else
                                    <i class="far fa-circle text-purple-500" title="Administración: Pendiente"></i>
                                    @endif
                                    @else
                                    <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Administración: En espera"></i>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Facturas">
                                @if($solicitud->totalFacturasNecesarias > 0)
                                @php
                                    $facSubidas = (int) $solicitud->facturasSubidas;
                                    $facTotal = (int) $solicitud->totalFacturasNecesarias;
                                    $facPct = min(100, (int) round($facSubidas / $facTotal * 100));
                                    [$facMod, $facIcono, $facTexto] = $facSubidas >= $facTotal
                                        ? ['ok', 'fa-check-circle', 'Completas']
                                        : ($facSubidas > 0 ? ['parcial', 'fa-exclamation-triangle', 'Parcial'] : ['pendiente', 'fa-times-circle', 'Pendiente']);
                                @endphp
                                <div class="sol-facturas sol-facturas--{{ $facMod }}">
                                    <div class="sol-facturas__linea">
                                        <span class="sol-facturas__estado"><i class="fas {{ $facIcono }}" aria-hidden="true"></i> {{ $facTexto }}</span>
                                        <span class="sol-facturas__conteo">{{ $facSubidas }}/{{ $facTotal }}</span>
                                    </div>
                                    <div class="sol-facturas__barra" role="progressbar" aria-valuenow="{{ $facSubidas }}" aria-valuemin="0" aria-valuemax="{{ $facTotal }}" aria-label="Facturas subidas">
                                        <span style="width: {{ $facPct }}%"></span>
                                    </div>
                                </div>
                                @else
                                <span class="sol-sub">No aplica</span>
                                @endif
                            </td>
                            <td data-label="Acciones">
                                @php
                                    $yaSubio = $solicitud->facturasSubidas >= $solicitud->totalFacturasNecesarias && $solicitud->totalFacturasNecesarias > 0;
                                    $puedeCerrar = !in_array($solicitud->estatusDisplay, ['Cancelada', 'Rechazada'], true);
                                @endphp
                                <div class="index-actions sol-acciones">
                                    <button type="button"
                                        data-ver-solicitud="{{ $solicitud->SolicitudID }}"
                                        @click.prevent.stop="abrirModal({{ $solicitud->SolicitudID }})"
                                        class="index-action index-action--view"
                                        title="Ver" aria-label="Ver solicitud #{{ $solicitud->SolicitudID }}">
                                        <i class="fas fa-fw fa-eye" aria-hidden="true"></i>
                                    </button>
                                    @if($solicitud->puedeCotizar)
                                    <a href="{{ route('solicitudes.cotizar', $solicitud->SolicitudID) }}"
                                        class="index-action index-action--edit no-underline"
                                        title="Cotizar" aria-label="Cotizar solicitud #{{ $solicitud->SolicitudID }}">
                                        <i class="fas fa-fw fa-calculator" aria-hidden="true"></i>
                                    </a>
                                    @endif
                                    @if($solicitud->puedeSubirFactura)
                                    <button type="button" @click="asgAbrir({{ $solicitud->SolicitudID }})"
                                        data-asignacion-solicitud="{{ $solicitud->SolicitudID }}"
                                        class="index-action index-action--view"
                                        title="{{ $yaSubio ? 'Ver asignación' : 'Asignación' }}"
                                        aria-label="{{ $yaSubio ? 'Ver asignación' : 'Asignación' }} de la solicitud #{{ $solicitud->SolicitudID }}">
                                        <i class="fas fa-fw fa-user-check" aria-hidden="true"></i>
                                    </button>
                                    @endif
                                    @if($puedeCerrar)
                                    <button type="button" @click="$dispatch('scm-abrir', {{ $solicitud->SolicitudID }})"
                                        class="index-action index-action--delete"
                                        title="Cerrar solicitud" aria-label="Cerrar solicitud #{{ $solicitud->SolicitudID }}">
                                        <i class="fas fa-fw fa-ban" aria-hidden="true"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="sol-table__vacia">
                            <td colspan="7">
                                <div class="sol-empty">
                                    <i class="fas fa-inbox" aria-hidden="true"></i>
                                    <p class="sol-empty__titulo">{{ $hayFiltrosSol ? 'Sin resultados' : 'No hay solicitudes registradas' }}</p>
                                    @if($hayFiltrosSol)
                                    <p class="sol-empty__texto">Ninguna solicitud coincide con la búsqueda o el estatus elegido.</p>
                                    <button type="button" wire:click="limpiarFiltros"
            onclick="if (window.jQuery) { jQuery('#filtroEstatus').val('').trigger('change.select2'); jQuery('#solicitudesPorPagina').val('10').trigger('change.select2'); }" class="sol-toolbar__clear">
                                        <i class="fas fa-times" aria-hidden="true"></i> Limpiar filtros
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
                $totalSol = $todasSolicitudes->total();
                $infoSol = $totalSol > 0
                    ? 'Mostrando ' . ($todasSolicitudes->firstItem() ?? 0) . ' a ' . ($todasSolicitudes->lastItem() ?? 0) . ' de ' . $totalSol
                    : 'Mostrando 0 a 0 de 0';
            @endphp
            <div class="index-page__dt-footer">
                <div class="dataTables_info" role="status">{{ $infoSol }}</div>
                {{ $todasSolicitudes->links('livewire.paginacion-compacta') }}
            </div>
        </div>
    </div>
    @endif

    {{-- Cascarón siempre en el DOM: Alpine lo muestra y anima; el contenido llega de Livewire. Estilos .asg-* en ux-pro-max.css --}}
    <div x-show="asgOpen" x-cloak class="asg"
        x-transition:enter="asg-in" x-transition:enter-start="asg-off" x-transition:enter-end="asg-on"
        x-transition:leave="asg-out" x-transition:leave-start="asg-on" x-transition:leave-end="asg-off">
        <div class="asg-backdrop" @click="asgCerrar()"></div>
        <div class="asg-panel" :class="{ 'is-cargando': asgCargando }" role="dialog" aria-modal="true" aria-labelledby="asg-titulo" wire:ignore.self>
            {{-- Visible solo mientras is-cargando (ver .asg-panel en ux-pro-max.css) --}}
            <div class="asg-skel" aria-busy="true" aria-label="Cargando asignación">
                <div class="asg-skel__bar" style="height: 2.2rem; width: 45%"></div>
                <div class="asg-skel__bar" style="height: 1rem; width: 30%"></div>
                <div class="asg-skel__bar" style="height: 9rem"></div>
                <div class="asg-skel__bar" style="height: 9rem"></div>
            </div>
            @if($modalAsignacionAbierto)
            @php
                $modalYaTieneFacturas = $modalEsSoloLectura;
                $asgUnidades = collect($propuestasAsignacion)->sum(fn($pp) => count($pp['unidades'] ?? []));
                $asgConUsuario = collect($propuestasAsignacion)->sum(fn($pp) => collect($pp['unidades'] ?? [])->filter(fn($uu) => !empty($uu['empleado_id']))->count());
            @endphp
            <div id="insumos-catalogo-asignacion" class="hidden" data-tiene="{{ $insumosTienenPresupuesto ? '1' : '0' }}">{!! json_encode($insumosDisponibles ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) !!}</div>

            <div class="asg-head">
                <div class="min-w-0">
                    <h3 id="asg-titulo" class="asg-head__title">
                        @if($modalYaTieneFacturas) Asignación registrada @else Asignación de activos @endif
                        @if($asignacionSolicitudId)<span class="asg-head__id">#{{ $asignacionSolicitudId }}</span>@endif
                    </h3>
                    <p class="asg-head__sub">
                        @if($modalYaTieneFacturas) Consulta de lo que se asignó. @else Asigna usuario final y fecha de entrega a cada unidad, y sube el XML de la factura. @endif
                    </p>
                    <div class="asg-stats">
                        <span class="asg-stat"><strong>{{ count($propuestasAsignacion) }}</strong> {{ count($propuestasAsignacion) === 1 ? 'propuesta' : 'propuestas' }}</span>
                        <span class="asg-stat {{ $asgUnidades > 0 && $asgConUsuario === $asgUnidades ? 'asg-stat--ok' : '' }}">
                            <i class="fas fa-user-check text-[10px]"></i><strong>{{ $asgConUsuario }}/{{ $asgUnidades }}</strong> con usuario
                        </span>
                        @if($modalYaTieneFacturas)
                        <span class="asg-stat"><i class="fas fa-lock text-[10px]"></i> Solo lectura</span>
                        @endif
                    </div>
                </div>
                <button type="button" class="asg-x" @click="asgCerrar()" aria-label="Cerrar">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            @php $proveedoresAgrupados = collect($propuestasAsignacion)->groupBy('proveedor')->filter(fn($g) => $g->count() > 1); @endphp
            @if($proveedoresAgrupados->isNotEmpty())
            <div class="asg-banner">
                <i class="fas fa-link" aria-hidden="true"></i>
                <span>Un mismo XML cubre las propuestas de:
                    @foreach($proveedoresAgrupados->keys() as $proveedor)
                    <strong>{{ $proveedor }}</strong>{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </span>
            </div>
            @endif

            @if(!$modalYaTieneFacturas)
            @php
            $proveedoresSinFactura = [];
            $proveedoresVistos = [];
            foreach ($propuestasAsignacion as $pIdx => $pItem) {
            $prov = $pItem['proveedor'] ?? '';
            if (!$prov || in_array($prov, $proveedoresVistos, true)) continue;
            $proveedoresVistos[] = $prov;
            $tieneAlgo = false;
            foreach (($pItem['unidades'] ?? []) as $uIdx => $uItem) {
            if (!empty($uItem['factura_xml_path']) || !empty($facturaXml[$pIdx][$uIdx])) { $tieneAlgo = true; break; }
            }
            if (!$tieneAlgo) $proveedoresSinFactura[] = $prov;
            }
            @endphp
            @endif

            <div class="asg-body">
                @if(empty($propuestasAsignacion))
                <div class="py-10 text-center text-slate-500 dark:text-slate-400 text-sm">No hay datos para asignación.</div>
                @else

                @foreach($propuestasAsignacion as $pIndex => $p)
                @php
                    $pUnidades = count($p['unidades'] ?? []);
                    $pConUsuario = collect($p['unidades'] ?? [])->filter(fn($uu) => !empty($uu['empleado_id']))->count();
                    $pPct = $pUnidades ? round(100 * $pConUsuario / $pUnidades) : 0;
                @endphp
                <section class="asg-prop" wire:key="prop-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $p['cotizacionId'] ?? 'x' }}">

                    <header class="asg-prop__head">
                        <span class="asg-prop__num">{{ $p['numeroPropuesta'] ?? ($pIndex + 1) }}</span>
                        <div class="min-w-0">
                            <h4 class="asg-prop__name">{{ $p['nombreEquipo'] ?? 'Sin nombre' }}</h4>
                            <p class="asg-prop__meta">{{ $p['proveedor'] ?? 'Sin proveedor' }} · {{ (int)($p['itemsTotal'] ?? 0) }} {{ (int)($p['itemsTotal'] ?? 0) === 1 ? 'unidad' : 'unidades' }}</p>
                        </div>
                        <div class="asg-prop__right">
                            <div class="asg-prop__progress">
                                <span>{{ $pConUsuario }}/{{ $pUnidades }} con usuario</span>
                                <div class="asg-prop__bar {{ $pPct === 100 ? 'is-full' : '' }}"><span style="width: {{ $pPct }}%"></span></div>
                            </div>
                            <div class="asg-prop__total">
                                <small>Precio unitario</small>
                                <strong>${{ number_format((float)($p['precioUnitario'] ?? 0), 2, '.', ',') }}</strong>
                            </div>
                        </div>
                    </header>

                    <div>
                        @foreach($p['unidades'] ?? [] as $uIndex => $u)
                        @php 
                        $yaFinalizado = !empty($u['fecha_fin_configuracion']) || !empty($u['config_lista_ui']);
                        // $yaGuardado es true solo si fue guardado en ESTA sesión (en $unidadesGuardadas)
                        // O si finalizó la config (fecha_fin_configuracion/config_lista_ui)
                        $yaGuardado = !empty($unidadesGuardadas["{$pIndex}_{$uIndex}"]) || $yaFinalizado;
                        @endphp
                        <div class="asg-unit" wire:key="unit-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                            <div class="asg-unit__grid">
                                <div class="asg-unit__badge {{ !empty($u['empleado_id']) && !empty($u['fecha_entrega']) ? 'is-done' : '' }}" title="Unidad {{ $u['unidadIndex'] ?? ($uIndex + 1) }}">
                                    <small>U</small>{{ $u['unidadIndex'] ?? ($uIndex + 1) }}
                                </div>
                                <div>
                                    <span class="asg-label">Factura (XML)</span>
                                    @php
                                    $xmlSavedPath = $u['factura_xml_path'] ?? '';
                                    $hasNewXml = !empty($facturaXml[$pIndex][$uIndex]);
                                    $esXmlGuardado = !empty($xmlSavedPath);
                                    $parsed = $xmlParseado[$pIndex][$uIndex] ?? null;
                                    $parsedOk = $parsed && empty($parsed['error']) && !empty($parsed['uuid']);
                                    $provActual = $p['proveedor'] ?? '';
                                    $esPrimerProv = true;
                                    for ($i = 0; $i < $pIndex; $i++) {
                                        if (($propuestasAsignacion[$i]['proveedor'] ?? '' )===$provActual) { $esPrimerProv=false; break; }
                                        }
                                        $mostrarInput=($uIndex===0 && $esPrimerProv);
                                        @endphp
                                        @if($mostrarInput)
                                        @if($modalYaTieneFacturas)
                                        @if($esXmlGuardado)
                                        <a href="{{ Storage::url($xmlSavedPath) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-xs font-medium bg-violet-50 border border-violet-200 text-violet-700 hover:bg-violet-100 transition-colors dark:bg-violet-900/20 dark:border-violet-700/50 dark:text-violet-300">
                                        <i class="fas fa-file-code text-violet-500"></i>
                                        <span class="truncate max-w-[7rem]">{{ basename($xmlSavedPath) }}</span>
                                        </a>
                                        @if($parsedOk)
                                        <p class="text-[11px] leading-tight font-semibold tabular-nums text-slate-800 dark:text-slate-100 mt-1">
                                            ${{ number_format((float) ($parsed['total'] ?? $parsed['subtotal'] ?? 0), 2, '.', ',') }}
                                            <span class="font-normal text-slate-500">{{ $parsed['moneda'] ?? 'MXN' }}</span>
                                        </p>
                                        <p class="text-[10px] leading-tight text-slate-500 dark:text-slate-400">Total del XML</p>
                                        @endif
                                        @else
                                        <span class="inline-flex items-center gap-1 h-9 px-3 rounded-lg text-xs font-medium bg-slate-100 border border-slate-200 text-slate-500 dark:bg-slate-800/50 dark:border-slate-700 dark:text-slate-400">
                                            <i class="fas fa-globe"></i> Sin XML
                                        </span>
                                        @endif
                                        @else
                                        <div class="flex flex-col gap-1">
                                            @if($esXmlGuardado && !$hasNewXml)
                                            <div class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border-2 border-violet-300 bg-violet-50 text-violet-700 text-xs dark:bg-violet-900/20 dark:border-violet-600 dark:text-violet-300 cursor-not-allowed opacity-70">
                                                <i class="fas fa-lock text-violet-500 text-[9px]"></i>
                                                <span class="font-medium truncate max-w-[7rem]">{{ basename($xmlSavedPath) }}</span>
                                            </div>
                                            @else
                                            <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border-2 border-dashed cursor-pointer transition-all text-xs
                                                {{ $hasNewXml ? 'bg-violet-50 border-violet-400 text-violet-700 hover:bg-violet-100 dark:bg-violet-900/20 dark:border-violet-600 dark:text-violet-300' : 'bg-violet-50/40 border-violet-300 text-violet-600 hover:bg-violet-50 hover:border-violet-400 dark:bg-violet-950/20 dark:border-violet-700 dark:text-violet-400' }}">
                                                <input type="file" class="hidden" accept="text/xml,application/xml,.xml"
                                                    wire:model.live="facturaXml.{{ $pIndex }}.{{ $uIndex }}">
                                                <i class="fas {{ $hasNewXml ? 'fa-check-circle text-violet-500' : 'fa-file-code text-violet-400' }}"></i>
                                                <span class="font-medium truncate max-w-[7rem]">
                                                    {{ $hasNewXml ? ($parsedOk ? 'XML Cargado' : 'XML cargado') : 'Subir XML' }}
                                                </span>
                                            </label>
                                            <div wire:loading wire:target="facturaXml.{{ $pIndex }}.{{ $uIndex }}" class="text-[10px] text-violet-500 flex items-center gap-1">
                                                <i class="fas fa-spinner fa-spin"></i> Validando...
                                            </div>
                                            @error("facturaXml.$pIndex.$uIndex")
                                            <p class="text-[10px] text-red-600 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                            @enderror
                                            @endif
                                            @if($parsedOk)
                                            <p class="text-[11px] leading-tight font-semibold tabular-nums text-slate-800 dark:text-slate-100">
                                                ${{ number_format((float) ($parsed['total'] ?? $parsed['subtotal'] ?? 0), 2, '.', ',') }}
                                                <span class="font-normal text-slate-500">{{ $parsed['moneda'] ?? 'MXN' }}</span>
                                            </p>
                                            <p class="text-[10px] leading-tight text-slate-500 dark:text-slate-400">Total del XML</p>
                                            @endif
                                        </div>
                                        @endif
                                        @else
                                        <div class="flex items-center justify-center h-9 px-3 rounded-lg text-xs text-slate-500 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                            @if($uIndex > 0)
                                            <i class="fas fa-link mr-1.5 opacity-60"></i> Compartido con U1
                                            @else
                                            <i class="fas fa-building mr-1.5 opacity-60 text-blue-500"></i> Mismo proveedor
                                            @endif
                                        </div>
                                        @if(!empty($u['factura_xml_path']))
                                        <a href="{{ Storage::url($u['factura_xml_path']) }}" target="_blank" class="block text-[10px] text-violet-600 dark:text-violet-400 hover:underline mt-1">
                                            <i class="fas fa-file-code mr-1"></i>Ver XML
                                        </a>
                                        @endif
                                        @endif
                                </div>
                                <div>
                                    <label class="asg-label" for="asg-fecha-{{ $pIndex }}-{{ $uIndex }}">Fecha de entrega</label>
                                    @if($modalYaTieneFacturas)
                                    <div class="asg-readonly">
                                        <i class="fas fa-calendar-alt text-slate-400 text-xs"></i> {{ $u['fecha_entrega'] ?? 'Sin fecha' }}
                                    </div>
                                    @else
                                    <input type="date" id="asg-fecha-{{ $pIndex }}-{{ $uIndex }}"
                                        wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.fecha_entrega"
                                        class="asg-field">
                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.fecha_entrega")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                    @endif
                                </div>
                                {{-- Combobox propio (aspecto select2): lista de empleados en el navegador, filtro instantáneo. Lógica inline para no depender de scripts globales. --}}
                                <div x-data="{
                                        open: false, q: '', activo: 0, pos: {}, lista: [], cargando: false,
                                        get filtrados() {
                                            const t = this.q.trim().toLowerCase().split(/\s+/).filter(Boolean);
                                            const r = !t.length ? this.lista : this.lista.filter(e => { const h = (e.name + ' ' + e.correo + ' ' + e.id).toLowerCase(); return t.every(x => h.includes(x)); });
                                            return r.slice(0, 60);
                                        },
                                        colocar() {
                                            const b = this.$refs.boton; if (!b) return;
                                            const r = b.getBoundingClientRect(), alto = 340;
                                            const arriba = (window.innerHeight - r.bottom) < alto && r.top > alto;
                                            this.pos = { left: r.left + 'px', width: Math.max(r.width, 280) + 'px', top: arriba ? 'auto' : (r.bottom + 6) + 'px', bottom: arriba ? (window.innerHeight - r.top + 6) + 'px' : 'auto' };
                                        },
                                        async abrir() {
                                            this.colocar(); this.q = ''; this.activo = 0; this.open = true;
                                            this.$nextTick(() => this.$refs.buscar && this.$refs.buscar.focus());
                                            if (this.lista.length) return;
                                            this.cargando = true;
                                            {{-- Una sola petición por página; si falla se reintenta en la siguiente apertura --}}
                                            if (!window.__asgEmpleadosP) window.__asgEmpleadosP = this.$wire.empleadosActivos().catch(() => { window.__asgEmpleadosP = null; return []; });
                                            this.lista = (await window.__asgEmpleadosP) || [];
                                            this.cargando = false;
                                        },
                                        cerrar() { this.open = false; },
                                        mover(d) {
                                            const n = this.filtrados.length; if (!n) return;
                                            this.activo = (this.activo + d + n) % n;
                                            this.$nextTick(() => { const el = this.$refs.lista && this.$refs.lista.querySelectorAll('[data-opt]')[this.activo]; if (el) el.scrollIntoView({ block: 'nearest' }); });
                                        },
                                        elegir(e) { if (!e) return; this.open = false; this.$wire.seleccionarEmpleado({{ $pIndex }}, {{ $uIndex }}, e.id); }
                                    }"
                                    x-init="$el.closest('.asg-body')?.addEventListener('scroll', () => cerrar(), { passive: true })"
                                    @resize.window="cerrar()"
                                    @click.outside="cerrar()"
                                    @keydown.escape="if (open) { $event.stopPropagation(); cerrar(); $refs.boton && $refs.boton.focus() }">
                                    <label class="asg-label" for="asg-usuario-{{ $pIndex }}-{{ $uIndex }}">Usuario final</label>
                                    @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado)
                                    <div class="asg-readonly">
                                        <i class="fas fa-user text-slate-400 text-xs"></i> {{ $u['empleado_nombre'] ?? '—' }}
                                    </div>
                                    <div class="asg-dept">
                                        <i class="fas fa-sitemap text-[10px]"></i>
                                        <span>{{ $u['departamento_nombre'] ?? '-' }}</span>
                                    </div>
                                    @else
                                    <button type="button" x-ref="boton" id="asg-usuario-{{ $pIndex }}-{{ $uIndex }}"
                                        class="asg-combo" @click="open ? cerrar() : abrir()"
                                        aria-haspopup="listbox" :aria-expanded="open">
                                        <span class="asg-combo__val {{ empty($u['empleado_nombre']) ? 'is-placeholder' : '' }}">{{ $u['empleado_nombre'] ?: 'Selecciona un empleado' }}</span>
                                        <i class="fas fa-chevron-down asg-combo__chev" :class="{ 'is-open': open }" aria-hidden="true"></i>
                                    </button>
                                    <div x-show="open" style="display: none" :style="pos" class="asg-combo__panel"
                                        x-transition:enter="asg-combo-in" x-transition:enter-start="asg-off" x-transition:enter-end="asg-on">
                                        <div class="asg-combo__search">
                                            <i class="fas fa-search" aria-hidden="true"></i>
                                            <input type="text" x-ref="buscar" x-model="q" @input="activo = 0" autocomplete="off"
                                                @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)"
                                                @keydown.enter.prevent="elegir(filtrados[activo])"
                                                placeholder="Buscar por nombre o correo…" aria-label="Buscar empleado">
                                        </div>
                                        <ul x-ref="lista" class="asg-combo__list" role="listbox">
                                            <template x-for="(e, i) in filtrados" :key="e.id">
                                                <li data-opt role="option" :aria-selected="i === activo" class="asg-combo__opt"
                                                    :class="{ 'is-active': i === activo, 'is-selected': e.id === {{ (int)($u['empleado_id'] ?? 0) }} }"
                                                    @mouseenter="activo = i" @click="elegir(e)">
                                                    <span class="asg-combo__name" x-text="e.name"></span>
                                                    <span class="asg-combo__mail" x-text="e.correo"></span>
                                                </li>
                                            </template>
                                        </ul>
                                        <p class="asg-combo__empty" x-show="cargando"><i class="fas fa-spinner fa-spin mr-1"></i> Cargando empleados…</p>
                                        <p class="asg-combo__empty" x-show="!cargando && !filtrados.length">Sin resultados para "<span x-text="q"></span>"</p>
                                    </div>
                                    @if(!empty($u['departamento_nombre']))
                                    <div class="asg-dept">
                                        <i class="fas fa-sitemap text-[10px]"></i>
                                        <span>{{ $u['departamento_nombre'] }}</span>
                                    </div>
                                    @endif
                                    @error("propuestasAsignacion.$pIndex.unidades.$uIndex.empleado_id")
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                    @enderror
                                    @endif
                                </div>

                            </div>

                            @php
                            $checklistFlat = collect($u['checklist'] ?? [])
                            ->flatMap(fn($items) => is_array($items) ? array_values($items) : [])
                            ->filter(fn($i) => is_array($i) && isset($i['nombre']));
                            $hasChecklistItems = $checklistFlat->isNotEmpty();
                            @endphp
                            @if($hasChecklistItems)
                            @php
                            $checklistFlat2 = collect($u['checklist'] ?? [])->flatMap(fn($items) => is_array($items) ? array_values($items) : [])->filter(fn($i) => is_array($i) && isset($i['nombre']));
                            $totalItemsCount = $checklistFlat2->count();
                            $initialDoneStates = [];
                            $flatIdx = 0;
                            foreach ($u['checklist'] ?? [] as $_ck => $_items) {
                            foreach ($_items as $_item) {
                            $initialDoneStates[$flatIdx] = !empty($_item['realizado']);
                            $flatIdx++;
                            }
                            }
                            @endphp
                            <div class="asg-check"
                                wire:key="checklist-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}"
                                x-data="{
                                checklistOpen: {{ ($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) ? 'false' : 'true' }},
                                reqConfig: {{ ($u['requiere_config'] ?? false) ? 'true' : 'false' }},
                                doneStates: @js($initialDoneStates),
                                totalItems: {{ $totalItemsCount }},
                                get marcados() { return Object.values(this.doneStates).filter(Boolean).length; },
                                get porcentaje() { return this.totalItems > 0 ? Math.round((this.marcados / this.totalItems) * 100) : 0; },
                                toggleItem(flatIdx) { this.doneStates[flatIdx] = !this.doneStates[flatIdx]; }
                            }">
                                <label class="inline-flex items-center gap-2 {{ (!$modalYaTieneFacturas && !$yaFinalizado && !$yaGuardado) ? 'cursor-pointer' : 'cursor-not-allowed opacity-70' }} mb-3 select-none"
                                    @if(!$modalYaTieneFacturas && !$yaFinalizado && !$yaGuardado)
                                    @click.prevent="
                                    let oldVal = reqConfig;
                                    reqConfig = !reqConfig;
                                    if (oldVal && !reqConfig) {
                                        Object.keys(doneStates).forEach(k => doneStates[k] = false);
                                    }
                                    $wire.toggleRequiereConfig({{ $pIndex }}, {{ $uIndex }})
                                "
                                    @endif>
                                    <input type="checkbox" class="peer sr-only" :checked="reqConfig"
                                        @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) disabled @endif>
                                    <div class="relative w-9 h-5 rounded-full border-2 transition-all"
                                        :class="reqConfig ? 'bg-violet-500 border-violet-500 dark:bg-violet-600 dark:border-violet-600' : 'bg-slate-200 border-slate-300 dark:bg-slate-700 dark:border-slate-600'">
                                        <span class="absolute left-0.5 top-0.5 w-3.5 h-3.5 rounded-full bg-gray-50 shadow transition-transform duration-200" :class="reqConfig ? 'translate-x-4' : ''"></span>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Requiere configuración</span>
                                </label>
                                <div x-show="reqConfig" wire:key="checklist-content-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                                    <button type="button" @click="checklistOpen = !checklistOpen"
                                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Checklist de configuración</span>
                                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': checklistOpen }"></i>
                                    </button>
                                    <div x-show="checklistOpen" x-transition class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @php $flatCounter = 0; @endphp
                                        @foreach($u['checklist'] ?? [] as $catKey => $items)
                                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $catKey }}</p>
                                            </div>
                                            <div class="p-3 space-y-2">
                                                @foreach($items as $idx => $item)
                                                @php $currentFlat = $flatCounter; $flatCounter++; @endphp
                                                <div class="flex items-center gap-3 p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                                    <label class="flex items-center justify-center cursor-pointer"
                                                        @if(!$modalYaTieneFacturas && !$yaFinalizado && !$yaGuardado)
                                                        @click.prevent="toggleItem({{ $currentFlat }}); $wire.marcarChecklist({{ $pIndex }}, {{ $uIndex }}, '{{ $catKey }}', {{ $idx }})"
                                                        @endif>
                                                        <input type="checkbox"
                                                            :checked="doneStates[{{ $currentFlat }}]"
                                                            @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) disabled @endif
                                                            class="peer sr-only">
                                                        <div class="w-5 h-5 rounded border-2 transition-all flex items-center justify-center"
                                                            :class="doneStates[{{ $currentFlat }}] ? 'bg-green-500 border-green-500' : 'border-slate-300 dark:border-slate-600'">
                                                            <i class="fas fa-check text-white text-[8px]" :class="doneStates[{{ $currentFlat }}] ? 'opacity-100' : 'opacity-0'"></i>
                                                        </div>
                                                    </label>
                                                    <span class="text-sm flex-1" :class="doneStates[{{ $currentFlat }}] ? 'line-through text-slate-400' : 'text-slate-800 dark:text-slate-200'">{{ $item['nombre'] ?? '' }}</span>
                                                    <input type="text"
                                                        wire:model.lazy="propuestasAsignacion.{{ $pIndex }}.unidades.{{ $uIndex }}.checklist.{{ $catKey }}.{{ $idx }}.responsable"
                                                        @if($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) readonly @endif
                                                        placeholder="Responsable"
                                                        class="h-7 w-24 px-2 text-xs border border-slate-200 rounded bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 text-center {{ ($modalYaTieneFacturas || $yaFinalizado || $yaGuardado) ? 'opacity-60 cursor-not-allowed' : '' }}">
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                {{-- Counter section: inside same x-data for Alpine reactivity --}}
                                <template x-if="reqConfig">
                                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3"
                                        wire:key="finalize-{{ $asignacionSolicitudId }}-{{ $pIndex }}-{{ $uIndex }}">
                                        @if(!$yaFinalizado && !$yaGuardado && $totalItemsCount > 0)
                                        <div class="flex items-center gap-2 text-xs text-slate-500">
                                            <span class="font-semibold" :class="marcados === totalItems ? 'text-emerald-600' : 'text-slate-600 dark:text-slate-300'" x-text="marcados + '/' + totalItems"></span>
                                            <span>tareas</span>
                                            <div class="w-20 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all"
                                                    :class="marcados === totalItems ? 'bg-emerald-500' : 'bg-violet-500'"
                                                    :style="'width: ' + porcentaje + '%'"></div>
                                            </div>
                                        </div>
                                        @else
                                        <div></div>
                                        @endif
                                        @if(!empty($u['config_lista_ui']))
                                        <div class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800">
                                            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                                            <div>
                                                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Ticket creado y equipo configurado</p>
                                                <p class="text-xs text-emerald-600 dark:text-emerald-400">Ya puedes subir el XML de factura.</p>
                                            </div>
                                        </div>
                                        @elseif($yaFinalizado || $yaGuardado)
                                        <span class="text-xs font-medium px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
                                            <i class="fas fa-check-double mr-1"></i>
                                            Finalizado el {{ \Carbon\Carbon::parse($u['fecha_fin_configuracion'])->format('d/m/Y H:i') }}
                                        </span>
                                        @else
                                        <button type="button"
                                            wire:loading.attr="disabled"
                                            wire:target="finalizarConfiguracionUnidad"
                                            onclick="
                                        var btn = this;
                                        Swal.fire({
                                            background: '#f9fafb', color: '#1e293b',
                                            title: 'Finalizar instalación',
                                            html: 'Se registrará la fecha y hora actual y se creará un ticket de instalación.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonText: 'Sí, finalizar',
                                            cancelButtonText: 'Cancelar',
                                            confirmButtonColor: '#4f46e5',
                                            cancelButtonColor: '#94a3b8',
                                            reverseButtons: true,
                                            customClass: { container: 'swal-modal-asignacion' },
                                        }).then(function(result) {
                                            if (result.isConfirmed) {
                                                var wireEl = btn.closest('[wire\\:id]');
                                                Livewire.find(wireEl.getAttribute('wire:id')).finalizarConfiguracionUnidad({{ $pIndex }}, {{ $uIndex }});
                                            }
                                        });
                                    "
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors disabled:opacity-50">
                                            <span wire:loading.remove wire:target="finalizarConfiguracionUnidad"><i class="fas fa-flag-checkered mr-1"></i> Finalizar Instalación</span>
                                            <span wire:loading wire:target="finalizarConfiguracionUnidad"><i class="fas fa-spinner fa-spin mr-1"></i> Registrando...</span>
                                        </button>
                                        @endif
                                    </div>
                                </template>
                            </div>
                            @endif

                        </div>
                        @endforeach
                    </div>
                </section>
                @endforeach

                @if(!$modalYaTieneFacturas)
                @php
                $todasFacturasParseadas = collect();
                $uuidsVistos = []; // Solo deduplicar por UUID, NO por proveedor
                foreach ($propuestasAsignacion as $pi => $p) {
                foreach (($p['unidades'] ?? []) as $ui => $u) {
                $parsed = $xmlParseado[$pi][$ui] ?? null;
                if (!$parsed || !empty($parsed['error'])) continue;
                // Deduplicar SOLO por UUID (si existe y ya se vio)
                $uuid = trim((string)($parsed['uuid'] ?? ''));
                if ($uuid && in_array($uuid, $uuidsVistos, true)) continue;
                if ($uuid) $uuidsVistos[] = $uuid;
                // Si no tiene UUID, siempre mostrar (no deduplicar por proveedor)
                $todasFacturasParseadas->push([
                'uuid' => $uuid,
                'emisor' => $parsed['emisor'] ?? '',
                'mes' => $parsed['mes'] ?? '',
                'anio' => $parsed['anio'] ?? '',
                'total' => $parsed['total'] ?? $parsed['subtotal'] ?? '0',
                'moneda' => $parsed['moneda'] ?? 'MXN',
                'insumoId' => $parsed['insumoId'] ?? null,
                'insumoNombre' => $parsed['insumoNombre'] ?? '',
                'insumoDesvio' => !empty($parsed['insumoDesvio']),
                'nombreEquipo' => $p['nombreEquipo'] ?? 'Sin nombre',
                'proveedor' => $p['proveedor'] ?? 'Sin proveedor',
                'pIndex' => $pi,
                'uIndex' => $ui,
                ]);
                }
                }
                $facturasSinInsumo = $todasFacturasParseadas->filter(fn($f) => empty($f['insumoId']) && !($f['insumoDesvio'] && trim((string) $f['insumoNombre']) !== ''))->count();
                $facturasConInsumo = $todasFacturasParseadas->count() - $facturasSinInsumo;
                @endphp

                @if($todasFacturasParseadas->isNotEmpty() && $insumosCargados && $presupuestoInternoAsignacion)
                <div class="asg-insumos" x-data="{ expandido: true }">
                    <button type="button" @click="expandido = !expandido"
                        class="w-full px-5 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="text-left">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Asignación de Insumos</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($facturasSinInsumo > 0)
                            <span class="text-[11px] font-bold px-2 py-1 rounded bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700/50">{{ $facturasSinInsumo }} sin insumo</span>
                            @endif
                            @if($facturasConInsumo > 0)
                            <span class="text-[11px] font-bold px-2 py-1 rounded bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700/50">{{ $facturasConInsumo }} asignados</span>
                            @endif
                            <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': expandido }"></i>
                        </div>
                    </button>

                    <div x-show="expandido" x-transition>
                        <div class="px-5 py-2.5 bg-sky-50 dark:bg-slate-800 border-b border-sky-100 dark:border-slate-700">
                                <p class="text-xs text-sky-700 dark:text-sky-400">
                                <i class="fas fa-lightbulb text-sky-500 mr-1"></i>
                                Insumos del presupuesto interno{{ $presupuestoInternoNombre !== '' ? ' '.$presupuestoInternoNombre : '' }}.
                                @if($insumosTienenPresupuesto)
                                    Si el de la factura no está en el corte, agrégalo como desviación.
                                @else
                                    Este presupuesto no tiene corte. El insumo que agregues cuenta como desviación.
                                @endif
                            </p>
                        </div>

                        <div class="overflow-visible">
                            <table class="w-full text-xs table-fixed">
                                <colgroup>
                                    <col class="w-[30%]">
                                    <col class="w-[28%]">
                                    <col class="w-[18%]">
                                    <col class="w-[24%]">
                                </colgroup>
                                <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="text-left px-4 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Cotización</th>
                                        <th class="text-left px-3 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Emisor</th>
                                        <th class="text-right px-3 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Total</th>
                                        <th class="text-left px-3 py-2.5 font-semibold tracking-wide uppercase text-[10px]">Asignación</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-gray-50 dark:bg-slate-900">
                                    @foreach($todasFacturasParseadas as $fIdx => $facturaData)
                                    @php
                                    $fPIndex = $facturaData['pIndex'];
                                    $fUIndex = $facturaData['uIndex'];
                                    $tieneMatch = !empty($facturaData['insumoId']);
                                    $esDesvio = !empty($facturaData['insumoDesvio']) && !empty($facturaData['insumoNombre']);
                                    $insumoActual = $tieneMatch ? (int)$facturaData['insumoId'] : null;
                                    $nombreInsumoActual = '';
                                    if ($insumoActual) {
                                    $found = collect($insumosDisponibles)->firstWhere('id', $insumoActual);
                                    $nombreInsumoActual = $found['nombre'] ?? '';
                                    }
                                    @endphp
                                    <tr class="{{ $tieneMatch ? '' : 'bg-amber-50/30 dark:bg-amber-950/10' }} hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                        wire:key="factura-row-{{ $fPIndex }}-{{ $fUIndex }}">
                                        <td class="px-4 py-3">
                                            <span class="text-slate-800 dark:text-slate-200 font-medium leading-tight block truncate">{{ Str::limit($facturaData['nombreEquipo'], 38) }}</span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="text-slate-500 dark:text-slate-400 leading-tight block truncate">{{ Str::limit($facturaData['emisor'], 32) }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono tabular-nums">${{ number_format((float)($facturaData['total'] ?? 0), 2) }}</span>
                                        </td>
                                        <td class="px-3 py-3" wire:key="insumo-td-{{ $fPIndex }}-{{ $fUIndex }}">
                                            <button type="button"
                                                id="insumo-btn-{{ $fPIndex }}-{{ $fUIndex }}"
                                                data-insumo-id="{{ $insumoActual ?? '' }}"
                                                data-desvio="{{ $esDesvio ? '1' : '0' }}"
                                                onclick="window.__insumoDD.open({{ $fPIndex }}, {{ $fUIndex }}, this)"
                                                class="w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group
                                                    {{ $tieneMatch
                                                        ? 'bg-emerald-50 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-700/50 dark:hover:bg-emerald-900/40'
                                                        : ($esDesvio
                                                            ? 'bg-amber-50 border-amber-300 hover:bg-amber-100 dark:bg-amber-900/20 dark:border-amber-700/50'
                                                            : 'bg-amber-50 border-dashed border-2 border-amber-300 hover:bg-amber-100 dark:bg-amber-900/30 dark:border-amber-700/50 dark:hover:bg-amber-900/50') }}">
                                                @if($tieneMatch)
                                                <i class="fas fa-check-circle text-emerald-500 dark:text-emerald-400 text-[10px] shrink-0"></i>
                                                <span class="text-[11px] font-medium text-emerald-800 dark:text-emerald-200 truncate flex-1">{{ Str::limit($nombreInsumoActual, 22) }}</span>
                                                <i class="fas fa-pencil-alt text-[8px] text-emerald-400 shrink-0 opacity-40 group-hover:opacity-100 transition-opacity"></i>
                                                @elseif($esDesvio)
                                                <i class="fas fa-exclamation-triangle text-amber-500 text-[10px] shrink-0"></i>
                                                <span class="text-[11px] font-medium text-amber-800 dark:text-amber-200 truncate flex-1">{{ Str::limit($facturaData['insumoNombre'], 22) }}</span>
                                                @else
                                                <i class="fas fa-plus-circle text-amber-500 dark:text-amber-400 text-[10px] shrink-0"></i>
                                                <span class="text-[11px] font-medium text-amber-700 dark:text-amber-300">Asignar insumo</span>
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($todasFacturasParseadas->isNotEmpty() && $insumosCargados && !$presupuestoInternoAsignacion)
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-lg shrink-0"></i>
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Este usuario no tiene presupuesto interno</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">La factura se guarda contra el presupuesto interno. Elige un usuario que lo tenga asignado.</p>
                        </div>
                    </div>
                </div>
                @elseif($todasFacturasParseadas->isNotEmpty() && !$insumosCargados)
                <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-blue-500 text-lg shrink-0"></i>
                        <div>
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">Asigna un usuario final para cargar insumos</p>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">Se muestran los insumos del presupuesto interno de ese usuario.</p>
                        </div>
                    </div>
                </div>
                @endif
                @endif

                @endif
            </div>

            <div class="asg-foot">
                <p class="asg-foot__hint">
                    @if($modalYaTieneFacturas) Para modificar, contacta al administrador. @else Guarda para conservar los XML y datos capturados. @endif
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="asgCerrar()" class="asg-btn asg-btn--ghost" wire:loading.attr="disabled">
                        Cerrar
                    </button>
                    @if(!$modalYaTieneFacturas)
                    <button type="button"
                        wire:loading.attr="disabled"
                        wire:target="persistAsignacion"
                        onclick="
                            var btn = this;
                            Swal.fire({
                                background: '#f9fafb', color: '#1e293b',
                                title: 'Guardar XML y avance',
                                text: 'Se validará que hayas asignado responsable y fecha de entrega.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Guardar y cerrar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#059669',
                                cancelButtonColor: '#94a3b8',
                                reverseButtons: true,
                                customClass: { container: 'swal-modal-asignacion' },
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    var wireEl = btn.closest('[wire\\:id]');
                                    Livewire.find(wireEl.getAttribute('wire:id')).persistAsignacion(true, true);
                                }
                            });
                        "
                        class="asg-btn asg-btn--ok">
                        <span wire:loading.remove wire:target="persistAsignacion"><i class="fas fa-save mr-1"></i> Guardar</span>
                        <span wire:loading wire:target="persistAsignacion"><i class="fas fa-spinner fa-spin mr-1"></i> Guardando...</span>
                    </button>
                    @endif
                </div>
            </div>

            <div wire:loading wire:target="persistAsignacion"
                class="absolute inset-0 bg-gray-50/70 dark:bg-slate-900/70 flex items-center justify-center z-50">
                <div class="flex flex-col items-center gap-2">
                    <i class="fas fa-spinner fa-spin text-2xl text-slate-600 dark:text-slate-300"></i>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Guardando…</p>
                </div>
            </div>
            @endif
        </div>
    </div>


    {{-- Siempre en el DOM para que Alpine anime entrada y salida; estilos .scm-* en ux-pro-max.css.
         Solo en la instancia de la tabla: la global del layout (soloModal) también escucharía scm-abrir y abriría un segundo modal. --}}
    @if(!$soloModal)
    <div x-data="{
            open: false,
            id: null, len: 0, min: 10, max: 1000,
            {{-- Abre sin ir al servidor; el id y el motivo viajan al confirmar.
                 Sin @entangle: la navegación SPA inicia Alpine antes que Livewire y el entangle truena. --}}
            abrir(id) { this.id = id; this.len = 0; $refs.motivo.value = ''; this.open = true; $nextTick(() => $refs.motivo.focus()) },
            cerrar() { this.open = false; $wire.cerrarModalCancelacion() },
            confirmar() { if (this.len >= this.min) $wire.confirmarCancelacion(this.id) }
        }"
        @scm-abrir.window="abrir($event.detail)"
        @scm-cerrar.window="open = false"
        x-show="open" x-cloak
        x-transition:enter="scm-in" x-transition:enter-start="scm-fade-off" x-transition:enter-end="scm-on"
        x-transition:leave="scm-out" x-transition:leave-start="scm-on" x-transition:leave-end="scm-fade-off"
        @keydown.escape.window="open && cerrar()"
        class="scm">

        <div @click="cerrar()" class="scm-backdrop"></div>

        <div class="scm-panel" role="dialog" aria-modal="true" aria-labelledby="scm-titulo" aria-describedby="scm-lead">

            <div class="scm-top">
                <p id="scm-titulo" class="scm-kicker">Cancelar solicitud</p>
                <button type="button" class="scm-close" @click="cerrar()" aria-label="Cerrar">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="scm-body">
                <p class="scm-id" :class="{ 'is-void': len >= min }" x-text="'#' + (id ?? '')"></p>
                <p id="scm-lead" class="scm-lead">El solicitante recibirá este motivo por correo. No se puede deshacer.</p>

                <label for="scm-motivo" class="sr-only">Motivo de cancelación</label>
                <textarea id="scm-motivo" x-ref="motivo" wire:model.defer="motivoCancelacion" rows="3" maxlength="1000"
                    @input="len = $event.target.value.length"
                    @keydown.ctrl.enter="confirmar()"
                    aria-required="true" @error('motivoCancelacion') aria-invalid="true" @enderror
                    placeholder="¿Por qué se cancela?"
                    class="scm-field @error('motivoCancelacion') is-error @enderror"></textarea>
                <div class="scm-meta">
                    @error('motivoCancelacion')
                    <span class="scm-error" role="alert">{{ $message }}</span>
                    @else
                    <span x-text="len < min ? 'Faltan ' + (min - len) + ' caracteres' : ''"></span>
                    @enderror
                    <span class="scm-meta__count" x-text="len + '/' + max"></span>
                </div>
            </div>

            <div class="scm-foot">
                <button type="button" class="scm-btn scm-btn--ghost" @click="cerrar()"
                    wire:loading.attr="disabled" wire:target="confirmarCancelacion">No</button>
                <button type="button" class="scm-btn scm-btn--danger" @click="confirmar()"
                    :disabled="len < min" wire:loading.attr="disabled" wire:target="confirmarCancelacion">
                    <span wire:loading.remove wire:target="confirmarCancelacion">Cancelar</span>
                    <span wire:loading wire:target="confirmarCancelacion"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cancelando…</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

@once
@push('third_party_scripts')
<script>
    /* ── Dropdown de Insumos: vanilla JS, un solo panel flotante compartido ── */
    window.__insumoDD = (function() {
        var panel = null;
        var input = null;
        var listEl = null;
        var currentPIdx = null;
        var currentUIdx = null;
        var currentBtn = null;
        var debounceTimer = null;

        function getWireId() {
            if (!currentBtn) return null;
            var el = currentBtn.closest('[wire\\:id]');
            return el ? el.getAttribute('wire:id') : null;
        }

        function ensurePanel() {
            if (panel) return;
            panel = document.createElement('div');
            panel.id = '__insumo-panel';
            panel.style.cssText = 'display:none;position:fixed;z-index:150000;width:300px;';
            panel.className = 'insumo-dd rounded-xl overflow-hidden';
            panel.innerHTML =
                '<div class="insumo-dd__search">' +
                '<div class="relative">' +
                '<input type="text" id="__insumo-search" placeholder="Escribe para buscar insumo...">' +
                '<i class="fas fa-search text-[9px] absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>' +
                '</div>' +
                '</div>' +
                '<div id="__insumo-list" class="insumo-dd__list"></div>' +
                '<div class="insumo-dd__foot">' +
                '<span id="__insumo-count"></span>' +
                '<button type="button" class="insumo-dd__cerrar" onclick="window.__insumoDD.close()">' +
                '<i class="fas fa-times mr-1"></i>Cerrar' +
                '</button>' +
                '</div>';
            document.body.appendChild(panel);

            input = document.getElementById('__insumo-search');
            listEl = document.getElementById('__insumo-list');

            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    renderList();
                }, 150);
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') close();
            });
            document.addEventListener('mousedown', function(e) {
                if (e.target && e.target.closest && e.target.closest('.swal2-container')) return;
                if (panel && panel.style.display !== 'none' && !panel.contains(e.target) && e.target !== currentBtn && currentBtn && !currentBtn.contains(e.target)) {
                    close();
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && panel && panel.style.display !== 'none' && !(e.target && e.target.closest && e.target.closest('.swal2-container'))) close();
            });
        }

        function leerCatalogo() {
            var nodo = document.getElementById('insumos-catalogo-asignacion');
            if (!nodo) return { items: [], tiene: false };
            var items = [];
            try { items = JSON.parse(nodo.textContent || '[]'); } catch (e) { items = []; }
            if (!Array.isArray(items)) items = [];
            return { items: items, tiene: nodo.getAttribute('data-tiene') === '1' };
        }

        function renderList() {
            var q = input.value.trim().toLowerCase();
            var catalogo = leerCatalogo();
            var todos = catalogo.items;
            var countEl = document.getElementById('__insumo-count');
            var html = '';
            var btn = currentBtn;

            html += '<button type="button" class="insumo-dd__desvio" onclick="window.__insumoDD.agregarDesvio()">' +
                '<i class="fas fa-plus-circle shrink-0"></i><span>Agregar insumo (desviación)</span></button>';

            if (btn && (btn.dataset.insumoId || btn.dataset.desvio === '1')) {
                html += '<button type="button" class="insumo-dd__quitar" onclick="window.__insumoDD.select(null, \'\')">' +
                    '<i class="fas fa-times-circle shrink-0"></i><span>Quitar insumo asignado</span></button>';
            }

            var lista = todos.filter(function(i) {
                return !q || String(i.nombre || '').toLowerCase().indexOf(q) !== -1;
            }).slice(0, 40);

            if (!todos.length) {
                html += '<div class="insumo-dd__empty">Este presupuesto interno no tiene corte. El insumo que agregues cuenta como desviación.</div>';
            } else if (!lista.length) {
                html += '<div class="insumo-dd__empty">Sin resultados. Puedes agregarlo como desviación.</div>';
            } else {
                for (var i = 0; i < lista.length; i++) {
                    var ins = lista[i];
                    var safeName = (ins.nombre || '').replace(/\\/g, '\\\\').replace(/'/g, '\\\'');
                    html += '<button type="button" class="insumo-dd__item" onclick="window.__insumoDD.select(' + ins.id + ', \'' + safeName + '\')">' +
                        '<i class="fas fa-tag text-[9px] shrink-0"></i>' +
                        '<span class="truncate">' + String(ins.nombre || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></button>';
                }
            }
            if (countEl) {
                countEl.textContent = todos.length
                    ? (lista.length + ' de ' + todos.length)
                    : 'Sin corte';
            }
            listEl.innerHTML = html;
        }

        function open(pIdx, uIdx, btnEl) {
            ensurePanel();
            currentPIdx = pIdx;
            currentUIdx = uIdx;
            currentBtn = btnEl;

            var rect = btnEl.getBoundingClientRect();
            var left = Math.min(rect.left, window.innerWidth - 320);
            var w = Math.max(rect.width, 300);
            panel.style.left = left + 'px';
            panel.style.width = w + 'px';
            if (rect.bottom + 340 > window.innerHeight) {
                panel.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
                panel.style.top = 'auto';
            } else {
                panel.style.top = (rect.bottom + 4) + 'px';
                panel.style.bottom = 'auto';
            }

            input.value = '';
            panel.style.display = 'block';
            renderList();
            setTimeout(function() {
                input.focus();
            }, 30);
        }

        function close() {
            if (panel) panel.style.display = 'none';
            currentPIdx = null;
            currentUIdx = null;
            currentBtn = null;
        }

        function select(insumoId, insumoNombre) {
            var wId = getWireId();
            if (wId && currentBtn) {
                if (insumoId) {
                    currentBtn.dataset.insumoId = insumoId;
                    currentBtn.dataset.desvio = '0';

                    // Actualizar clases al estilo "seleccionado" (verde)
                    currentBtn.className = 'w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group' +
                        ' bg-emerald-50 border-emerald-200 hover:bg-emerald-100' +
                        ' dark:bg-emerald-900/20 dark:border-emerald-700/50 dark:hover:bg-emerald-900/40';

                    // Truncar nombre igual que PHP: Str::limit($nombreInsumoActual, 22)
                    var nombreCorto = insumoNombre.length > 22 ?
                        insumoNombre.substring(0, 22) + '…' :
                        insumoNombre;

                    currentBtn.innerHTML =
                        '<i class="fas fa-check-circle text-emerald-500 dark:text-emerald-400 text-[10px] shrink-0"></i>' +
                        '<span class="text-[11px] font-medium text-emerald-800 dark:text-emerald-200 truncate flex-1">' +
                        nombreCorto.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                        '</span>' +
                        '<i class="fas fa-pencil-alt text-[8px] text-emerald-400 shrink-0 opacity-40 group-hover:opacity-100 transition-opacity"></i>';

                } else {
                    currentBtn.dataset.insumoId = '';
                    currentBtn.dataset.desvio = '0';

                    currentBtn.className = 'w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group' +
                        ' bg-amber-50 border-dashed border-2 border-amber-300 hover:bg-amber-100' +
                        ' dark:bg-amber-900/30 dark:border-amber-700/50 dark:hover:bg-amber-900/50';

                    currentBtn.innerHTML =
                        '<i class="fas fa-plus-circle text-amber-500 dark:text-amber-400 text-[10px] shrink-0"></i>' +
                        '<span class="text-[11px] font-medium text-amber-700 dark:text-amber-300">Asignar insumo</span>';
                }

                try {
                    var lwComponent = Livewire.find(wId);
                    if (lwComponent && typeof lwComponent.actualizarInsumoConcepto === 'function') {
                        lwComponent.actualizarInsumoConcepto(currentPIdx, currentUIdx, insumoId || 0, insumoNombre || '');
                    }
                } catch (e) {
                    console.error('Error calling actualizarInsumoConcepto:', e);
                }
            }
            close();
        }

        function aplicarDesvio(btn, wId, pIdx, uIdx, nombre) {
            nombre = String(nombre || '').trim();
            if (!nombre || !wId || !btn) return;
            btn.dataset.insumoId = '';
            btn.dataset.desvio = '1';
            btn.className = 'w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition-colors text-left group bg-amber-50 border-amber-300 hover:bg-amber-100 dark:bg-amber-900/20 dark:border-amber-700/50';
            var corto = nombre.length > 22 ? nombre.substring(0, 22) + '…' : nombre;
            btn.innerHTML = '<i class="fas fa-exclamation-triangle text-amber-500 text-[10px] shrink-0"></i>' +
                '<span class="text-[11px] font-medium text-amber-800 dark:text-amber-200 truncate flex-1">' +
                corto.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
            window.__bloqueoCierreAsignacion = true;
            setTimeout(function() { window.__bloqueoCierreAsignacion = false; }, 500);
            setTimeout(function() {
                try {
                    var lwComponent = Livewire.find(wId);
                    if (lwComponent) lwComponent.actualizarInsumoConcepto(pIdx, uIdx, 0, nombre);
                } catch (e) {
                    console.error(e);
                }
            }, 0);
        }

        function agregarDesvio() {
            var pIdx = currentPIdx;
            var uIdx = currentUIdx;
            var btn = currentBtn;
            var wId = getWireId();
            if (panel) panel.style.display = 'none';
            var catalogo = leerCatalogo();
            var texto = catalogo.tiene
                ? 'Este insumo no está en el corte. Se cuenta como desviación.'
                : 'Este presupuesto interno no tiene corte. El insumo se cuenta como desviación.';
            if (typeof Swal === 'undefined') {
                aplicarDesvio(btn, wId, pIdx, uIdx, window.prompt(texto));
                return;
            }
            Swal.fire({
                title: 'Insumo nuevo',
                text: texto,
                input: 'text',
                inputPlaceholder: 'Nombre del insumo',
                showCancelButton: true,
                confirmButtonText: 'Usar insumo',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d97706',
                returnFocus: false,
                heightAuto: false,
                target: document.body,
                customClass: { container: 'swal-modal-asignacion' },
                didOpen: function() {
                    var campo = Swal.getInput();
                    var popup = Swal.getPopup();
                    if (campo) campo.focus();
                    if (popup) {
                        popup.addEventListener('keydown', function(ev) {
                            if (ev.key === 'Escape') ev.stopPropagation();
                        });
                    }
                },
                preConfirm: function(valor) {
                    var nombre = String(valor || '').trim();
                    if (!nombre) {
                        Swal.showValidationMessage('Escribe el nombre del insumo');
                        return false;
                    }
                    return nombre;
                }
            }).then(function(res) {
                if (!res || !res.isConfirmed) return;
                aplicarDesvio(btn, wId, pIdx, uIdx, res.value);
            });
        }

        return {
            open: open,
            close: close,
            select: select,
            agregarDesvio: agregarDesvio
        };
    })();

    // Una sola vez por página: este script se re-ejecuta en cada navegación SPA y Livewire.restart()
    // vuelve a disparar livewire:load; sin la bandera cada aviso salía repetido N veces.
    if (!window.__swalAsignacionListeners) {
        window.__swalAsignacionListeners = true;
        const _swalBase = {
            background: '#f9fafb',
            color: '#1e293b',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#94a3b8',
            customClass: { container: 'swal-modal-asignacion' }
        };

        window.addEventListener('swal:success', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Cambios guardados correctamente');
            Swal.fire({
                ..._swalBase,
                icon: 'success',
                title: 'Listo',
                text: msg,
                timer: 3500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });

        window.addEventListener('swal:error', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Ocurrio un error inesperado');
            Swal.fire({
                ..._swalBase,
                icon: 'error',
                title: 'Error',
                text: msg
            });
        });

        window.addEventListener('swal:warning', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : 'Atencion requerida');
            Swal.fire({
                ..._swalBase,
                icon: 'warning',
                title: 'Atencion',
                text: msg
            });
        });

        window.addEventListener('swal:info', function(e) {
            var data = e.detail;
            var msg = (typeof data === 'string') ? data : (data && data.message ? data.message : '');
            if (!msg) return;
            Swal.fire({
                ..._swalBase,
                icon: 'info',
                text: msg,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    }
</script>
@endpush
@endonce