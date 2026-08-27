@extends('layouts.app')

@section('content')
@include('flash::message')
@include('adminlte-templates::common.errors')

@php
    $nEquipos = $equiposAsignados->count();
    $nInsumos = $insumosAsignados->count();
    $nLineas = $LineasAsignados->count();
@endphp

<x-index-page
    class="crud-page"
    title="Transferir inventario"
    icon="fa-exchange-alt"
    :subtitle="$inventario->NombreEmpleado"
    :show-count="true"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Regresar</a>
    </x-slot>

    <p class="xfer-lede">
        Solo se transfieren asignaciones de <strong>stock</strong> y <strong>compartido</strong>. El destino debe ser persona física o extraordinaria. El mantenimiento preventivo se genera desde esta misma pantalla.
    </p>

    <div class="xfer-stats">
        <div class="xfer-stat">
            <span class="xfer-stat__icon xfer-stat__icon--equipos"><i class="fas fa-laptop"></i></span>
            <span class="xfer-stat__body">
                <span class="xfer-stat__value">{{ $nEquipos }}</span>
                <span class="xfer-stat__label">Equipos</span>
            </span>
        </div>
        <div class="xfer-stat">
            <span class="xfer-stat__icon xfer-stat__icon--insumos"><i class="fas fa-box-open"></i></span>
            <span class="xfer-stat__body">
                <span class="xfer-stat__value">{{ $nInsumos }}</span>
                <span class="xfer-stat__label">Insumos</span>
            </span>
        </div>
        <div class="xfer-stat">
            <span class="xfer-stat__icon xfer-stat__icon--lineas"><i class="fas fa-sim-card"></i></span>
            <span class="xfer-stat__body">
                <span class="xfer-stat__value">{{ $nLineas }}</span>
                <span class="xfer-stat__label">Líneas</span>
            </span>
        </div>
        <div class="xfer-stat xfer-stat--sel">
            <span class="xfer-stat__icon xfer-stat__icon--sel"><i class="fas fa-check-double"></i></span>
            <span class="xfer-stat__body">
                <span class="xfer-stat__value" data-xfer-selcount>0</span>
                <span class="xfer-stat__label">Seleccionados</span>
            </span>
        </div>
    </div>

    @php
        $tareasPreven = [
            1 => 'Desarme y ensamble de equipo',
            2 => 'Formateo e instalación del sistema operativo',
            3 => 'Limpieza interna',
            4 => 'Respaldo de información',
            6 => 'Cambio de pasta térmica',
            7 => 'Limpieza de periféricos',
            8 => 'Actualizaciones de software',
            9 => 'Eliminación de temporales',
            10 => 'Limpieza de ventiladores',
            11 => 'Limpieza de fuente de poder',
            12 => 'Instalación de software por licencia',
            14 => 'Limpieza del teclado',
            15 => 'Cambio de piezas',
            16 => 'Cambio de pasta térmica en la tarjeta gráfica',
            17 => 'Cambio de equipo de cómputo',
        ];
        $empleadoId = $inventario->EmpleadoID;
    @endphp

    <div class="index-page__card crud-page__card xfer-card xfer-preven" style="margin-bottom: 1.5rem;">
        <div class="xfer-card-head">
            <span class="xfer-card-head__icon xfer-card-head__icon--preven"><i class="fas fa-tools"></i></span>
            <div>
                <h2>Mantenimiento preventivo</h2>
                <span class="index-page__count">Selecciona el equipo y las actividades realizadas</span>
            </div>
        </div>

        <form id="formulario2" action="{{ route('inventarios.mantenimiento', $empleadoId) }}" method="POST" target="_blank">
            @csrf
            <div class="form-group">
                <label for="IdEquipo">Equipo</label>
                {!! Form::select(
                    'IdEquipo',
                    App\Models\InventarioEquipo::select(DB::raw("CONCAT(IFNULL(Folio, 'Sin folio'),' - ', CategoriaEquipo) AS NombreEq, InventarioID"))
                        ->where('EmpleadoID', '=', $empleadoId)
                        ->where(function ($q) {
                            \App\Helpers\PresupuestoAsignacion::aplicarWhere($q, 'inventario', \App\Helpers\PresupuestoAsignacion::COLUMNA_EQUIPOS);
                        })
                        ->pluck('NombreEq', 'InventarioID'),
                    null,
                    ['placeholder' => 'Seleccionar...', 'class' => 'jz form-control', 'id' => 'IdEquipo', 'required' => true]
                ) !!}
            </div>

            <div class="crud-select-all" id="selectAllPreven">Seleccionar todos</div>
            <div class="crud-perms">
                @foreach ($tareasPreven as $valor => $etiqueta)
                    <label>
                        <input class="name cursor-pointer" type="checkbox" name="inventarioPreven[]" value="{{ $valor }}" id="defaultCheck{{ $valor }}">
                        <span>{{ $etiqueta }}</span>
                    </label>
                @endforeach
            </div>

            <div class="crud-page__actions">
                <button type="submit" class="index-page__btn-primary">Generar formato</button>
            </div>
        </form>
    </div>

    <form action="{{ route('inventarios.transpaso', $inventario->EmpleadoID) }}" method="POST" id="form-transferir">
        @csrf
        @method('PUT')

        <div class="index-page__stack">
        <div class="index-page__card overflow-hidden xfer-card">
            <div class="xfer-card-head">
                <span class="xfer-card-head__icon xfer-card-head__icon--equipos"><i class="fas fa-laptop"></i></span>
                <div>
                    <h2>Equipos asignados</h2>
                    <span class="index-page__count xfer-card-count">{{ $nEquipos === 1 ? '1 registro' : $nEquipos . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table id="equiposAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th class="xfer-check-col"><input type="checkbox" class="selectAll xfer-check" data-table="equiposAsignadosTable" title="Seleccionar todos"></th>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th>Marca</th>
                            <th>Características</th>
                            <th>Modelo</th>
                            <th>Precio</th>
                            <th>Fecha de asignación</th>
                            <th>Fecha de compra</th>
                            <th>Núm. serie</th>
                            <th>Folio</th>
                            <th>Gerencia</th>
                            <th>Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equiposAsignados as $equiposAsignado)
                        <tr data-id="{{ $equiposAsignado->InventarioID }}">
                            <td><input type="checkbox" class="selectItem xfer-check" name="equipos[]" value="{{ $equiposAsignado->InventarioID }}"></td>
                            <td>{{ $equiposAsignado->CategoriaEquipo }}</td>
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($equiposAsignado->tipoEquipo) !!}</td>
                            <td>{{ $equiposAsignado->Marca }}</td>
                            <td>{{ $equiposAsignado->Caracteristicas }}</td>
                            <td>{{ $equiposAsignado->Modelo }}</td>
                            <td>{{ $equiposAsignado->Precio }}</td>
                            <td>{{ $equiposAsignado->FechaAsignacion }}</td>
                            <td>{{ $equiposAsignado->FechaDeCompra }}</td>
                            <td>{{ $equiposAsignado->NumSerie }}</td>
                            <td>{{ $equiposAsignado->Folio }}</td>
                            <td data-gerencia-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->gerencia->NombreGerencia ?? 'Sin gerencia' }}</td>
                            <td>{{ $equiposAsignado->Comentarios }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="index-page__card overflow-hidden xfer-card">
            <div class="xfer-card-head">
                <span class="xfer-card-head__icon xfer-card-head__icon--insumos"><i class="fas fa-box-open"></i></span>
                <div>
                    <h2>Insumos asignados</h2>
                    <span class="index-page__count xfer-card-count">{{ $nInsumos === 1 ? '1 registro' : $nInsumos . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table id="insumosAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th class="xfer-check-col"><input type="checkbox" class="selectAll xfer-check" data-table="insumosAsignadosTable" title="Seleccionar todos"></th>
                            <th>Categoría</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Costo mensual</th>
                            <th>Costo anual</th>
                            <th>Observaciones</th>
                            <th>Fecha de asignación</th>
                            <th>Núm. serie</th>
                            <th>Comentarios</th>
                            <th>Meses de pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumosAsignados as $insumosAsignado)
                        <tr data-id="{{ $insumosAsignado->InventarioID }}">
                            <td><input type="checkbox" class="selectItem xfer-check" name="insumos[]" value="{{ $insumosAsignado->InventarioID }}"></td>
                            <td>{{ $insumosAsignado->CateogoriaInsumo }}</td>
                            <td>{{ $insumosAsignado->NombreInsumo }}</td>
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($insumosAsignado->Presupuestado) !!}</td>
                            <td>{{ $insumosAsignado->CostoMensual }}</td>
                            <td>{{ $insumosAsignado->CostoAnual }}</td>
                            <td>{{ $insumosAsignado->Observaciones }}</td>
                            <td>{{ $insumosAsignado->FechaAsignacion }}</td>
                            <td>{{ $insumosAsignado->NumSerie }}</td>
                            <td>{{ $insumosAsignado->Comentarios }}</td>
                            <td>@include('inventarios.partials.meses-pills', ['mesesValor' => $insumosAsignado->MesDePago, 'mesesFrecuencia' => $insumosAsignado->FrecuenciaDePago])</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="index-page__card overflow-hidden xfer-card">
            <div class="xfer-card-head">
                <span class="xfer-card-head__icon xfer-card-head__icon--lineas"><i class="fas fa-sim-card"></i></span>
                <div>
                    <h2>Líneas asignadas</h2>
                    <span class="index-page__count xfer-card-count">{{ $nLineas === 1 ? '1 registro' : $nLineas . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table id="lineasAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th class="xfer-check-col"><input type="checkbox" class="selectAll xfer-check" data-table="lineasAsignadosTable" title="Seleccionar todos"></th>
                            <th>Teléfono</th>
                            <th>Tipo</th>
                            <th>Compañía</th>
                            <th>Plan</th>
                            <th>Renta mensual</th>
                            <th>Cuenta padre</th>
                            <th>Cuenta hija</th>
                            <th>Tipo línea</th>
                            <th>Obra</th>
                            <th>Fecha fianza</th>
                            <th>Costo fianza</th>
                            <th>Fecha de asignación</th>
                            <th>Estado</th>
                            <th>Comentarios</th>
                            <th>Monto renovación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($LineasAsignados as $LineasAsignado)
                        <tr data-id="{{ $LineasAsignado->InventarioID }}">
                            <td><input type="checkbox" class="selectItem xfer-check" name="lineas[]" value="{{ $LineasAsignado->InventarioID }}"></td>
                            <td>{{ $LineasAsignado->NumTelefonico }}</td>
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($LineasAsignado->Presupuestado) !!}</td>
                            <td>{{ $LineasAsignado->Compania }}</td>
                            <td>{{ $LineasAsignado->PlanTel }}</td>
                            <td>{{ $LineasAsignado->CostoRentaMensual }}</td>
                            <td>{{ $LineasAsignado->CuentaPadre }}</td>
                            <td>{{ $LineasAsignado->CuentaHija }}</td>
                            <td>{{ $LineasAsignado->TipoLinea }}</td>
                            <td>{{ $LineasAsignado->Obra }}</td>
                            <td>{{ $LineasAsignado->FechaFianza }}</td>
                            <td>{{ $LineasAsignado->CostoFianza }}</td>
                            <td>{{ $LineasAsignado->FechaAsignacion }}</td>
                            <td>{{ $LineasAsignado->Estado }}</td>
                            <td>{{ $LineasAsignado->Comentarios }}</td>
                            <td>{{ $LineasAsignado->MontoRenovacionFianza }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <div class="xfer-actions">
            <div class="xfer-actions__info">
                <span class="xfer-actions__count" data-xfer-selcount>0</span>
                <span class="xfer-actions__text">elemento(s) seleccionado(s) para transferir</span>
            </div>
            <div class="xfer-actions__buttons">
                <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Regresar</a>
                <button type="submit" class="index-page__btn-primary show_confirm">
                    <i class="fas fa-exchange-alt"></i> Transferir
                </button>
            </div>
        </div>
    </form>
</x-index-page>
@endsection

@push('third_party_stylesheets')
    @include('layouts.datatables_css')
    <style>
        /* ============================================================
           Transferir inventario · estilo "data-dense dashboard"
           slate + verde stock · light/dark · transiciones 150-200ms
           ============================================================ */
        :root {
            --xf-surface: #ffffff;
            --xf-surface-2: #f8fafc;
            --xf-border: #e6e8ea;
            --xf-ink: #0f172a;
            --xf-muted: #64748b;
            --xf-navy: var(--index-navy, #101d49);
            --xf-accent: #059669;
            --xf-accent-soft: #ecfdf5;
            --xf-ring: rgba(5, 150, 105, 0.35);
        }
        .dark, .dark .crud-page {
            --xf-surface: #0f1729;
            --xf-surface-2: #131c30;
            --xf-border: #26304a;
            --xf-ink: #e2e8f0;
            --xf-muted: #94a3b8;
            --xf-navy: #e2e8f0;
            --xf-accent: #34d399;
            --xf-accent-soft: rgba(52, 211, 153, 0.12);
            --xf-ring: rgba(52, 211, 153, 0.4);
        }

        .xfer-lede {
            font-size: 0.85rem;
            line-height: 1.55;
            color: var(--xf-muted);
            margin: -0.15rem 0 1.15rem;
            max-width: 70ch;
        }
        .xfer-lede strong { color: var(--xf-ink); font-weight: 600; }

        /* ---- KPI strip -------------------------------------------------- */
        .xfer-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 720px) {
            .xfer-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .xfer-stat {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.8rem 0.9rem;
            background: var(--xf-surface);
            border: 1px solid var(--xf-border);
            border-radius: 0.85rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .xfer-stat--sel { background: var(--xf-accent-soft); border-color: transparent; }
        .xfer-stat__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.6rem;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .xfer-stat__icon--equipos { background: #eef2ff; color: #4338ca; }
        .xfer-stat__icon--insumos { background: #fff7ed; color: #c2410c; }
        .xfer-stat__icon--lineas  { background: #ecfeff; color: #0e7490; }
        .xfer-stat__icon--sel     { background: var(--xf-accent); color: #fff; }
        .dark .xfer-stat__icon--equipos { background: rgba(99,102,241,.18); color: #a5b4fc; }
        .dark .xfer-stat__icon--insumos { background: rgba(249,115,22,.18); color: #fdba74; }
        .dark .xfer-stat__icon--lineas  { background: rgba(6,182,212,.18);  color: #67e8f9; }
        .xfer-stat__body { display: flex; flex-direction: column; line-height: 1.15; }
        .xfer-stat__value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--xf-navy);
            font-variant-numeric: tabular-nums;
        }
        .xfer-stat__label {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--xf-muted);
        }

        /* ---- Cards ---------------------------------------------------- */
        .xfer-card { margin-bottom: 0; border-radius: 0.9rem; }
        .xfer-preven { margin-bottom: 1.5rem !important; }
        .index-page__stack .xfer-card + .xfer-card { margin-top: 1.25rem; }

        .xfer-card-head {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 1.1rem 0.85rem;
            border-bottom: 1px solid var(--xf-border);
        }
        .xfer-card-head__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.6rem;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .xfer-card-head__icon--preven  { background: #f1f5f9; color: #334155; }
        .xfer-card-head__icon--equipos { background: #eef2ff; color: #4338ca; }
        .xfer-card-head__icon--insumos { background: #fff7ed; color: #c2410c; }
        .xfer-card-head__icon--lineas  { background: #ecfeff; color: #0e7490; }
        .dark .xfer-card-head__icon--preven  { background: #1e293b; color: #cbd5e1; }
        .dark .xfer-card-head__icon--equipos { background: rgba(99,102,241,.18); color: #a5b4fc; }
        .dark .xfer-card-head__icon--insumos { background: rgba(249,115,22,.18); color: #fdba74; }
        .dark .xfer-card-head__icon--lineas  { background: rgba(6,182,212,.18);  color: #67e8f9; }
        .xfer-card-head h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--xf-navy);
            letter-spacing: -0.01em;
        }
        .xfer-card-head .index-page__count {
            font-size: 0.72rem;
            color: var(--xf-muted);
        }

        /* ---- Preventivo form --------------------------------------- */
        .xfer-preven .form-group,
        .xfer-preven .crud-select-all,
        .xfer-preven .crud-perms { margin-left: 1.1rem; margin-right: 1.1rem; }
        .xfer-preven .form-group { margin-top: 0.9rem; margin-bottom: 0.35rem; }
        .xfer-preven .form-group label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--xf-muted);
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .xfer-preven .select2-container { width: 100% !important; }
        .xfer-preven .select2-container .select2-selection--single {
            height: 2.6rem !important;
            border-radius: 0.65rem !important;
            border: 1px solid var(--xf-border) !important;
            background: var(--xf-surface) !important;
            display: flex;
            align-items: center;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .xfer-preven .select2-container--open .select2-selection--single,
        .xfer-preven .select2-container--focus .select2-selection--single {
            border-color: var(--xf-accent) !important;
            box-shadow: 0 0 0 3px var(--xf-ring) !important;
        }
        .xfer-preven .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.4 !important;
            padding-left: 0.8rem;
            color: var(--xf-ink);
        }
        .xfer-preven .select2-container--default .select2-selection--single .select2-selection__arrow { height: 100% !important; }
        .dark .xfer-preven .select2-container--default .select2-selection--single .select2-selection__rendered { color: #f1f5f9 !important; }
        .dark .xfer-preven .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #94a3b8 !important; }

        .xfer-preven .crud-select-all {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.9rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--xf-accent);
            cursor: pointer;
            user-select: none;
        }
        .xfer-preven .crud-select-all:hover { text-decoration: underline; }
        .xfer-preven .crud-perms {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 0.4rem 0.9rem;
            margin-top: 0.6rem;
        }
        .xfer-preven .crud-perms label {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid var(--xf-border);
            border-radius: 0.6rem;
            font-size: 0.82rem;
            color: var(--xf-ink);
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }
        .xfer-preven .crud-perms label:hover { border-color: var(--xf-accent); background: var(--xf-accent-soft); }
        .xfer-preven .crud-perms input[type="checkbox"] { margin-top: 0.15rem; accent-color: var(--xf-accent); cursor: pointer; }
        .xfer-preven .crud-page__actions {
            margin: 1rem 1.1rem 0;
            padding: 0.85rem 0 0.25rem;
            border-top: 1px solid var(--xf-border);
        }

        /* ---- Table polish ----------------------------------------- */
        .xfer-card,
        .xfer-card .index-page__table-wrap,
        .xfer-card .table-responsive { overflow: visible !important; }
        .xfer-card .dataTables_wrapper .dataTables_scroll,
        .xfer-card .dataTables_wrapper .dataTables_scrollBody {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        .xfer-card .index-page__dt-toolbar { padding: 0.75rem 1.1rem 0.5rem; }

        .xfer-card table.index-table { border-collapse: separate; border-spacing: 0; }
        .xfer-card table.index-table th,
        .xfer-card table.index-table td {
            white-space: nowrap;
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid var(--xf-border);
        }
        .xfer-card table.index-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--xf-surface-2);
            color: var(--xf-muted);
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid var(--xf-border);
        }
        .xfer-card table.index-table tbody tr {
            transition: background .12s ease;
        }
        .xfer-card table.index-table tbody tr:hover { background: var(--xf-surface-2); }
        .xfer-card table.index-table tbody tr.xfer-row-selected { background: var(--xf-accent-soft); }
        .xfer-card table.index-table tbody tr:last-child td { border-bottom: 0; }
        /* números legibles y alineados */
        .xfer-card table.index-table td:nth-child(n) { font-variant-numeric: tabular-nums; }

        /* sticky checkbox column */
        .xfer-card table.index-table th.xfer-check-col,
        .xfer-card table.index-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 1;
            background: var(--xf-surface);
        }
        .xfer-card table.index-table thead th.xfer-check-col { z-index: 3; background: var(--xf-surface-2); }
        .xfer-card table.index-table tbody tr:hover td:first-child { background: var(--xf-surface-2); }
        .xfer-card table.index-table tbody tr.xfer-row-selected td:first-child { background: var(--xf-accent-soft); }

        .xfer-check-col { width: 2.75rem; }
        .xfer-check {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: var(--xf-accent);
            cursor: pointer;
        }
        .xfer-check:focus-visible { outline: 2px solid var(--xf-accent); outline-offset: 2px; }

        /* ---- Sticky action bar ------------------------------------ */
        .xfer-actions {
            position: sticky;
            bottom: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding: 0.85rem 1.1rem;
            background: var(--xf-surface);
            border: 1px solid var(--xf-border);
            border-radius: 0.85rem;
            box-shadow: 0 -2px 14px rgba(15, 23, 42, 0.06);
        }
        .xfer-actions__info { display: flex; align-items: baseline; gap: 0.45rem; font-size: 0.85rem; color: var(--xf-muted); }
        .xfer-actions__count {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--xf-accent);
            font-variant-numeric: tabular-nums;
        }
        .xfer-actions__buttons { display: flex; align-items: center; gap: 0.6rem; }
        .xfer-actions .index-page__btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; }

        .xfer-swal-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--xf-muted);
            margin: 0 0 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
        }

        /* ---- Badges (Tipo + meses de pago) ----------------------- */
        .inv-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.4rem;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .inv-chip-stock  { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .inv-chip-extra  { background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; }
        .inv-chip-share  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .inv-chip-propio { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        .dark .inv-chip-stock  { background: rgba(16,185,129,.14); color: #6ee7b7; border-color: rgba(16,185,129,.35); }
        .dark .inv-chip-extra  { background: rgba(249,115,22,.14); color: #fdba74; border-color: rgba(249,115,22,.35); }
        .dark .inv-chip-share  { background: rgba(59,130,246,.14); color: #93c5fd; border-color: rgba(59,130,246,.35); }
        .dark .inv-chip-propio { background: rgba(139,92,246,.16); color: #c4b5fd; border-color: rgba(139,92,246,.35); }

        .inv-meses-pills {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.2rem;
            max-width: 260px;
            white-space: normal;
        }
        .inv-mes-pill {
            display: inline-flex;
            padding: 0.1rem 0.42rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.66rem;
            font-weight: 700;
        }
        .dark .inv-mes-pill { background: #1e293b; color: #cbd5e1; }

        @media (prefers-reduced-motion: reduce) {
            .crud-page * { transition: none !important; }
        }
    </style>
@endpush

@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('layouts.partials.index-page-js')
    <script>
        $(document).ready(function () {
            var dtLanguage = {
                sProcessing: 'Procesando...',
                sZeroRecords: 'No se encontraron resultados',
                sEmptyTable: 'Ningún dato disponible en esta tabla',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
                sInfoEmpty: 'Mostrando 0 a 0 de 0',
                sInfoFiltered: '(filtrado de _MAX_ registros)',
                sSearch: '',
                searchPlaceholder: 'Buscar...',
                oPaginate: {
                    sFirst: 'Primero',
                    sLast: 'Último',
                    sNext: 'Siguiente',
                    sPrevious: 'Anterior'
                }
            };

            function bindTable(selector, emptyLabel) {
                return $(selector).DataTable({
                    destroy: true,
                    responsive: false,
                    scrollX: true,
                    paging: true,
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    columnDefs: [
                        { orderable: false, searchable: false, targets: 0 }
                    ],
                    order: [],
                    dom: "<'index-page__dt-toolbar'f>t<'index-page__dt-footer'ip>",
                    language: Object.assign({}, dtLanguage, {
                        sEmptyTable: emptyLabel
                    }),
                    drawCallback: function () {
                        var api = this.api();
                        var info = api.page.info();
                        var total = info.recordsDisplay;
                        var label = total === 1 ? '1 registro' : total + ' registros';
                        $(api.table().container()).closest('.index-page__card').find('.xfer-card-count').text(label);
                    },
                    initComplete: function () {
                        if (window.IndexPage) {
                            var $input = $(this.api().table().container()).find('.dataTables_filter input');
                            if ($input.length && !$input.attr('placeholder')) {
                                $input.attr('placeholder', 'Buscar...');
                            }
                        }
                    }
                });
            }

            var table = bindTable('#equiposAsignadosTable', 'No hay equipos asignados');
            var table2 = bindTable('#insumosAsignadosTable', 'No hay insumos asignados');
            var table3 = bindTable('#lineasAsignadosTable', 'No hay líneas asignadas');
            var tables = [table, table2, table3];

            function recomputarSeleccion() {
                var total = 0;
                tables.forEach(function (t) {
                    t.$('input.selectItem').each(function () {
                        var sel = this.checked;
                        if (sel) total++;
                        $(this).closest('tr').toggleClass('xfer-row-selected', sel);
                    });
                });
                $('[data-xfer-selcount]').text(total);
                return total;
            }

            $(document).on('change', 'input.selectItem', recomputarSeleccion);

            $('.selectAll').on('click', function (e) {
                e.stopPropagation();
                var tableId = $(this).data('table');
                var checked = $(this).prop('checked');
                var dataTableInstance = null;
                if (tableId === 'equiposAsignadosTable') dataTableInstance = table;
                else if (tableId === 'insumosAsignadosTable') dataTableInstance = table2;
                else if (tableId === 'lineasAsignadosTable') dataTableInstance = table3;
                if (dataTableInstance) {
                    dataTableInstance.$('input.selectItem').prop('checked', checked);
                }
                recomputarSeleccion();
            });

            tables.forEach(function (t) {
                t.on('draw', recomputarSeleccion);
            });
            recomputarSeleccion();

            $('.show_confirm').on('click', function (event) {
                var form = $(this).closest('form');
                event.preventDefault();

                var totalSeleccionados = 0;
                var tables = [table, table2, table3];
                tables.forEach(function (t) {
                    totalSeleccionados += t.$('input.selectItem:checked').length;
                });

                if (totalSeleccionados === 0) {
                    swal.fire({
                        title: '¡No hay elementos seleccionados!',
                        text: 'Debes seleccionar al menos un equipo, insumo o línea telefónica para realizar la transferencia.',
                        icon: 'error',
                        confirmButtonColor: '#101D49',
                        didOpen: function () {
                            $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                            $('.swal2-title').addClass('dark:text-white');
                        }
                    });
                    return;
                }

                var empleadosOptions = '';
                @foreach($Empleados as $empleado)
                empleadosOptions += `<option value="{{ $empleado->EmpleadoID }}">{{ $empleado->NombreEmpleado }} ({{ strtoupper((string) $empleado->tipo_persona) === 'EXTRAORDINARIO' ? 'Extraordinario' : 'Física' }})</option>`;
                @endforeach

                swal.fire({
                    title: '¿Está seguro de que desea realizar esta acción?',
                    icon: 'warning',
                    html: `
                        <label for="empleado" class="xfer-swal-label">Empleado destino</label>
                        <select id="empleado" class="form-control">
                            <option value="">Seleccionar...</option>
                            ${empleadosOptions}
                        </select>
                    `,
                    confirmButtonColor: '#101D49',
                    didOpen: function () {
                        $('#empleado').select2({
                            dropdownParent: $('.swal2-popup'),
                            width: '100%',
                            placeholder: 'Seleccionar...',
                        });
                        $('.swal2-popup').addClass('dark:bg-[#101010]');
                        setTimeout(function () {
                            $('.select2-search__field').addClass('dark:bg-[#101010] dark:text-white dark:placeholder-gray-400');
                            $('.select2-results__option').addClass('dark:bg-[#101010] dark:text-white');
                            $('.select2-dropdown').addClass('dark:bg-[#101010]');
                        }, 100);
                    },
                    showDenyButton: true,
                    confirmButtonText: 'Confirmar',
                    denyButtonText: 'Cerrar',
                    dangerMode: true,
                }).then(function (result) {
                    var selectedEmpleado = $('#empleado').val();
                    if (result.isConfirmed) {
                        if (!selectedEmpleado) {
                            swal.fire({
                                title: '¡Debes seleccionar un empleado!',
                                icon: 'error',
                                confirmButtonColor: '#101D49',
                                didOpen: function () {
                                    $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                                    $('.swal2-title').addClass('dark:text-white');
                                }
                            });
                        } else {
                            swal.fire({
                                title: 'Acción completada exitosamente',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 2000,
                                didOpen: function () {
                                    $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                                    $('.swal2-title').addClass('dark:text-white');
                                }
                            }).then(function () {
                                tables.forEach(function (t) {
                                    t.$('input.selectItem:checked').each(function () {
                                        var name = $(this).attr('name');
                                        form.append('<input type="hidden" name="' + name + '" value="' + this.value + '">');
                                    });
                                });
                                $('input.selectItem').prop('disabled', true);
                                form.append('<input type="hidden" name="empleado_id" value="' + selectedEmpleado + '">');
                                form.submit();
                            });
                        }
                    } else if (result.isDenied) {
                        swal.fire({
                            title: 'Cambios no realizados',
                            icon: 'error',
                            showConfirmButton: false,
                            timer: 2000,
                            didOpen: function () {
                                $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                                $('.swal2-title').addClass('dark:text-white');
                            }
                        });
                    }
                });
            });
        });
        var selectAllPreven = document.getElementById('selectAllPreven');
        if (selectAllPreven) {
            selectAllPreven.addEventListener('click', function () {
                var checkboxes = document.querySelectorAll('input[name="inventarioPreven[]"]');
                if (!checkboxes.length) return;
                var isChecked = checkboxes[0].checked;
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = !isChecked;
                });
                this.textContent = isChecked ? 'Seleccionar todos' : 'Deseleccionar todos';
            });
        }
    </script>
    <style>
        .dark .select2-search__field {
            background-color: #101010 !important;
            color: white !important;
        }
        .dark .select2-search__field::placeholder { color: #9ca3af !important; }
        .dark .select2-results__option {
            background-color: #101010 !important;
            color: white !important;
        }
        .dark .select2-results__option--highlighted {
            background-color: #3b82f6 !important;
            color: white !important;
        }
        .dark .select2-dropdown {
            background-color: #101010 !important;
            border-color: #374151 !important;
        }
        .dark .select2-container--default .select2-selection--single {
            background-color: #101010 !important;
            border-color: #374151 !important;
            color: white !important;
        }
        .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white !important;
        }
        .dark .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }
    </style>
@endpush
