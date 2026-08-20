@php
    $empleadoActivo = $empleadoActivo ?? ($inventario->Estado == 1 || $inventario->Estado === true);
    $permitePresupuestado = $permitePresupuestado ?? false;
    $presupuestadoForzado = $presupuestadoForzado ?? false;

    // Equipos: tipoEquipo 0 = stock, 1 = presupuestado, 2 = propio. El propio se lista
    // junto al stock porque también es inventario actual.
    $equiposStock = collect($equiposAsignados)->filter(fn ($e) => (int) ($e->tipoEquipo ?? 0) !== 1);
    $equiposExtra = collect($equiposAsignados)->filter(fn ($e) => (int) ($e->tipoEquipo ?? 0) === 1);
    $insumosStock = collect($insumosAsignados)->filter(fn ($e) => !(int) ($e->Presupuestado ?? 0));
    $insumosExtra = collect($insumosAsignados)->filter(fn ($e) => (int) ($e->Presupuestado ?? 0) === 1);
    $lineasStock = collect($LineasAsignados)->filter(fn ($e) => !(int) ($e->Presupuestado ?? 0));
    $lineasExtra = collect($LineasAsignados)->filter(fn ($e) => (int) ($e->Presupuestado ?? 0) === 1);

    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 0);
@endphp

<ul class="nav inv-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#empleados">Empleado</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#equipo">Equipo de cómputo</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#insumo">Insumo</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#linea">Línea de telefonía</a>
    </li>
</ul>

<div class="tab-content">




    <!-- TAB Empleado -->
    <div class="tab-pane fade show active" id="empleados">
        <div class="inv-empleado-card">
        <div class="row">
            <!-- NombreEmpleado Field -->
            <div class="col-sm-6">
                {!! Form::label('NombreEmpleado', 'Nombre del Empleado:', ['class' => 'inv-form-label dark:text-white']) !!}
                {!! Form::text('NombreEmpleado', old('NombreEmpleado', $inventario->NombreEmpleado ?? ''), ['class' => 'form-control', 'maxlength' => 100, 'disabled']) !!}
            </div>

            <!-- UnidadNegocio Field -->
            <div class="col-sm-6">
                {!! Form::label('UnidadNegocioID', 'Unidad de Negocio:',['class' => 'dark:text-white']) !!}

                {!!Form::select('UnidadNegocioID',App\Models\UnidadesDeNegocio::all()->
                pluck('NombreEmpresa','UnidadNegocioID'),$inventario->UnidadNegocioID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

            <!-- UnidadNegocio Field -->
            <div class="col-sm-6">
                {!! Form::label('GerenciaID', 'Gerencia:', ['class' => 'dark:text-white']) !!}

                {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
                pluck('NombreGerencia','GerenciaID'),$inventario->GerenciaID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>

            <!-- ObraID Field -->
            <div class="col-sm-6">
                {!! Form::label('ObraID', 'Obra:', ['class' => 'dark:text-white']) !!}

                {!!Form::select('ObraID',App\Models\Obras::all()->
                pluck('NombreObra','ObraID'),$inventario->ObraID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}


            </div>

            <!-- ObraID Field -->
            <div class="col-sm-6">
                {!! Form::label('DepartamentoID', 'Departamento:', ['class' => 'dark:text-white']) !!}

                {!!Form::select('DepartamentoID',App\Models\Departamentos::all()->
                pluck('NombreDepartamento','DepartamentoID'),$inventario->DepartamentoID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>


            <!-- PuestoID Field -->
            <div class="col-sm-6">
                {!! Form::label('PuestoID', 'Puesto:', ['class' => 'dark:text-white']) !!}
                {!!Form::select('PuestoID',App\Models\Puestos::all()->
                pluck('NombrePuesto','PuestoID'),$inventario->PuestoID ?? NULL,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'disabled'])!!}
            </div>


            <!-- NumTelefono Field -->
            <div class="col-sm-6">
                {!! Form::label('NumTelefono', 'Número de Teléfono:', ['class' => 'dark:text-white']) !!}
                {!! Form::text('NumTelefono', old('NumTelefono', $inventario->NumTelefono ?? ''), ['class' => 'form-control', 'maxlength' => 50, 'disabled']) !!}
            </div>

            <!-- Correo Field -->
            <div class="col-sm-6">
                {!! Form::label('Correo', 'Correo Electrónico:', ['class' => 'dark:text-white']) !!}
                {!! Form::email('Correo', old('Correo', $inventario->Correo ?? ''), ['class' => 'form-control', 'maxlength' => 150, 'disabled']) !!}
            </div>

        </div>
        </div>
    </div>

    <!-- TAB Equipo de Computo -->
    <div class="tab-pane fade" id="equipo">

        <div class="inv-kpi-row">
            <div class="inv-kpi inv-kpi-total">
                <div class="inv-kpi-label">Equipos asignados</div>
                <div class="inv-kpi-value" id="kpi-equipos-total">{{ $equiposAsignados->count() }}</div>
                <div class="inv-kpi-sub">En resguardo / proyección</div>
            </div>
            @if($permitePresupuestado)
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Stock</div>
                <div class="inv-kpi-value">{{ $fmtMoney($equiposStock->sum('Precio')) }}</div>
                <div class="inv-kpi-sub">{{ $equiposStock->count() }} equipo(s) · asignado actual</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Extra / Presupuesto</div>
                <div class="inv-kpi-value">{{ $fmtMoney($equiposExtra->sum('Precio')) }}</div>
                <div class="inv-kpi-sub">{{ $equiposExtra->count() }} equipo(s) · futuro</div>
            </div>
            @else
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Solo stock</div>
                <div class="inv-kpi-value">{{ $fmtMoney(collect($equiposAsignados)->sum('Precio')) }}</div>
                <div class="inv-kpi-sub">Referenciado: sin extras de presupuesto</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-label">Disponibles</div>
                <div class="inv-kpi-value">{{ collect($equipos)->count() }}</div>
                <div class="inv-kpi-sub">Catálogo para asignar</div>
            </div>
            @endif
        </div>

        <div class="inv-panel">
            <div class="inv-panel-head inv-panel-head-asignados">
                <span><i class="fas fa-check-circle mr-1"></i> Equipos asignados</span>
                @include('inventarios.filtro_presupuestado', [
                    'tabla' => 'equiposAsignadosTable',
                    'tipo' => 'equipos',
                    'permitePresupuestado' => $permitePresupuestado,
                    'presupuestadoForzado' => $presupuestadoForzado,
                    'empleadoID' => $inventario->EmpleadoID,
                    'compacto' => true,
                ])
            </div>
            <div class="inv-panel-body">
                @if($permitePresupuestado && !$presupuestadoForzado)
                <div class="inv-dual" data-tabla="equiposAsignadosTable">
                    <div class="inv-dual-card" data-filtro="no_presupuestados">
                        <div class="inv-dual-title stock"><i class="fas fa-cube"></i> Stock en uso (<span class="conteo-no">{{ $equiposStock->count() }}</span>)</div>
                        <div class="inv-dual-empty">Inventario actual en resguardo</div>
                        <div class="inv-money">{{ $fmtMoney($equiposStock->sum('Precio')) }}</div>
                    </div>
                    <div class="inv-dual-card" data-filtro="presupuestados">
                        <div class="inv-dual-title extra"><i class="fas fa-calendar-alt"></i> Extra / Presupuesto (<span class="conteo-si">{{ $equiposExtra->count() }}</span>)</div>
                        <div class="inv-dual-empty">Proyección futura</div>
                        <div class="inv-money">{{ $fmtMoney($equiposExtra->sum('Precio')) }}</div>
                    </div>
                </div>
                @endif

            <!-- equiposAsignados Seleccionados -->

            <div class="table-responsive">
                <table id="equiposAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Categoria</th>
                            <th>Marca</th>
                            <th>Caracteristicas</th>
                            <th>Modelo</th>
                            <th>Precio</th>
                            <th>Fecha Asignacion</th>
                            <th>Fecha de Compra</th>
                            <th>Num. Serie</th>
                            <th>Folio</th>
                            <th>Gerencia Equipo</th>
                            <th>Comentarios</th>
                            @if($permitePresupuestado)
                            <th>Stock / Extra</th>
                            <th>Mes de pago</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equiposAsignados as $equiposAsignado)
                        <tr data-id="{{ $equiposAsignado->InventarioID }}">
                            <td>
                                @if($empleadoActivo)
                                <div class="index-actions">
                                    <button type="button" class="index-action index-action--edit edit-btn" data-id="{{ $equiposAsignado->InventarioID }}" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroy', $equiposAsignado->InventarioID], 'class' => 'index-action-form']) !!}
                                    {!! Form::button('<i class="fas fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'index-action index-action--delete delete-btn',
                                    'title' => 'Eliminar',
                                    'data-id' => $equiposAsignado->InventarioID
                                    ]) !!}
                                    {!! Form::close() !!}
                                </div>
                                @else
                                <span class="text-muted small">—</span>
                                @endif

                            </td>
                            <td>{{ $equiposAsignado->CategoriaEquipo }}</td>
                            <td>{{ $equiposAsignado->Marca }}</td>
                            <td>{{ $equiposAsignado->Caracteristicas }}</td>
                            <td>{{ $equiposAsignado->Modelo }}</td>
                            <td>{{ $equiposAsignado->Precio }}</td>
                            <td>{{ $equiposAsignado->FechaAsignacion }}</td>
                            <td>{{ $equiposAsignado->FechaDeCompra }}</td>
                            <td>{{ $equiposAsignado->NumSerie }}</td>
                            <td>{{ $equiposAsignado->Folio }}</td>
                            <td data-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->GerenciaEquipo }}</td>
                            <td>{{ $equiposAsignado->Comentarios }}</td>
                            @if($permitePresupuestado)
                            <td>{!! [
                                    1 => '<span class="inv-chip inv-chip-extra">Extra</span>',
                                    2 => '<span class="inv-chip inv-chip-propio">Propio</span>',
                                ][(int) ($equiposAsignado->tipoEquipo ?? 0)]
                                ?? '<span class="inv-chip inv-chip-stock">Stock</span>' !!}</td>
                            <td>@if($equiposAsignado->MesDePago)<span class="inv-mes-pill">{{ $equiposAsignado->MesDePago }}</span>@endif</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>

            <!-- equiposAsignados Disponibles -->
            @if($empleadoActivo)
        <div class="inv-panel">
            <div class="inv-panel-head inv-panel-head-disponibles">
                <span><i class="fas fa-plus-circle mr-1"></i> Equipos disponibles</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-toolbar">
                    <div class="inv-search">
                        <i class="fas fa-search"></i>
                        <input type="text" class="inv-table-search" data-tabla="equiposTable" placeholder="Buscar por marca, modelo o características...">
                    </div>
                </div>

            <div class="drag-area" id="disponibles">
                <div class="table-responsive">
                    <table id="equiposTable" class="table index-table w-full">
                        <thead>
                            <tr>
                                <th>Asignar</th>
                                <th>Categoria</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Caracteristicas</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($equipos as $equipo)
                            <tr>
                                <td>


                                    <button class='btn btn-outline-success btn-xs crear-btn' data-id="{{ $equipo->CategoriaID }}">
                                        <i class="fas fa-plus"></i>
                                    </button>

                                </td>
                                <td>{{ $equipo->categorias->Categoria }}</td>
                                <td>{{ $equipo->Marca }}</td>
                                <td>{{ $equipo->Modelo }}</td>
                                <td>{{ $equipo->Caracteristicas }}</td>
                                <td>{{ $equipo->Precio }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                      </div>
            </div>
            </div>
        </div>
        @endif
    </div>

    <!-- TAB Insumo -->
    <div class="tab-pane fade" id="insumo">

        <div class="inv-kpi-row">
            <div class="inv-kpi inv-kpi-total">
                <div class="inv-kpi-label">Insumos asignados</div>
                <div class="inv-kpi-value">{{ $insumosAsignados->count() }}</div>
                <div class="inv-kpi-sub">En resguardo / proyección</div>
            </div>
            @if($permitePresupuestado)
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Stock</div>
                <div class="inv-kpi-value">{{ $insumosStock->count() }}</div>
                <div class="inv-kpi-sub">Asignado actual</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Extra / Presupuesto</div>
                <div class="inv-kpi-value">{{ $insumosExtra->count() }}</div>
                <div class="inv-kpi-sub">Proyección futura</div>
            </div>
            @else
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Solo stock</div>
                <div class="inv-kpi-value">{{ collect($insumosAsignados)->count() }}</div>
                <div class="inv-kpi-sub">Referenciado</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-label">Disponibles</div>
                <div class="inv-kpi-value">{{ collect($insumos)->count() }}</div>
                <div class="inv-kpi-sub">Catálogo</div>
            </div>
            @endif
        </div>

        <div class="inv-panel">
            <div class="inv-panel-head inv-panel-head-asignados">
                <span><i class="fas fa-check-circle mr-1"></i> Insumos asignados</span>
                @include('inventarios.filtro_presupuestado', [
                    'tabla' => 'insumosAsignadosTable',
                    'tipo' => 'insumos',
                    'permitePresupuestado' => $permitePresupuestado,
                    'presupuestadoForzado' => $presupuestadoForzado,
                    'empleadoID' => $inventario->EmpleadoID,
                    'compacto' => true,
                ])
            </div>
            <div class="inv-panel-body">
                @if($permitePresupuestado && !$presupuestadoForzado)
                <div class="inv-dual" data-tabla="insumosAsignadosTable">
                    <div class="inv-dual-card" data-filtro="no_presupuestados">
                        <div class="inv-dual-title stock"><i class="fas fa-cube"></i> Stock en uso (<span class="conteo-no">{{ $insumosStock->count() }}</span>)</div>
                        <div class="inv-dual-empty">Asignaciones actuales</div>
                    </div>
                    <div class="inv-dual-card" data-filtro="presupuestados">
                        <div class="inv-dual-title extra"><i class="fas fa-calendar-alt"></i> Extra / Presupuesto (<span class="conteo-si">{{ $insumosExtra->count() }}</span>)</div>
                        <div class="inv-dual-empty">Proyecciones registradas</div>
                    </div>
                </div>
                @endif

            <div class="table-responsive">
                <table id="insumosAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Categoria Insumo</th>
                            <th>Nombre Insumo</th>
                            <th>Costo Mensual</th>
                            <th>Costo Anual</th>
                            <th>Frecuencia de Pago</th>
                            <th>Fecha de Renovacion</th>
                            <th>Observaciones</th>
                            <th>Fecha de Asignacion</th>
                            <th>Num. Serie</th>
                            <th>Comentarios</th>
                            <th>Mes de pago </th>
                            @if($permitePresupuestado)
                            <th>Stock / Extra</th>
                            @endif

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumosAsignados as $insumosAsignado)
                        <tr data-id="{{ $insumosAsignado->InventarioID }}">
                            <td>
                                @if($empleadoActivo)
                                <div class="index-actions">
                                    <button type="button" class="index-action index-action--edit edit-btn-insum" data-id="{{ $insumosAsignado->InventarioID }}" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroyInsumo', $insumosAsignado->InventarioID], 'class' => 'index-action-form']) !!}
                                    {!! Form::button('<i class="fas fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'index-action index-action--delete delete-btn-insumo',
                                    'title' => 'Eliminar',
                                    'data-id' => $insumosAsignado->InventarioID
                                    ]) !!}
                                    {!! Form::close() !!}
                                </div>
                                @else
                                <span class="text-muted small">—</span>
                                @endif


                            </td>


                            <td>{{ $insumosAsignado->CateogoriaInsumo }}</td>
                            <td>{{ $insumosAsignado->NombreInsumo }}</td>
                            <td>{{ $insumosAsignado->CostoMensual }}</td>
                            <td>{{ $insumosAsignado->CostoAnual }}</td>
                            <td>{{ $insumosAsignado->FrecuenciaDePago }}</td>
                            <td>{{ (empty($insumosAsignado->FechaRenovacion) || in_array($insumosAsignado->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($insumosAsignado->FechaRenovacion)->format('d/m/Y') }}</td>
                            <td>{{ $insumosAsignado->Observaciones }}</td>
<td>{{ $insumosAsignado->FechaAsignacion ? \Carbon\Carbon::parse($insumosAsignado->FechaAsignacion)->format('d/m/Y') : 'Sin asignar' }}</td>                            <td>{{ $insumosAsignado->NumSerie }}</td>
                            <td>{{ $insumosAsignado->Comentarios }}</td>
                            <td>{{ $insumosAsignado->MesDePago }}</td>
                            @if($permitePresupuestado)
                            <td>{!! $insumosAsignado->Presupuestado
                                ? '<span class="inv-chip inv-chip-extra">Extra</span>'
                                : '<span class="inv-chip inv-chip-stock">Stock</span>' !!}</td>
                            @endif
                        </tr>
                        @endforeach


                    </tbody>
                </table>
            </div>

            </div>
        </div>

            <!-- insumos Disponibles -->
            @if($empleadoActivo)
        <div class="inv-panel">
            <div class="inv-panel-head inv-panel-head-disponibles">
                <span><i class="fas fa-plus-circle mr-1"></i> Insumos disponibles</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-toolbar">
                    <div class="inv-search">
                        <i class="fas fa-search"></i>
                        <input type="text" class="inv-table-search" data-tabla="insumosTable" placeholder="Buscar insumo...">
                    </div>
                </div>
            <div class="drag-area" id="disponibles">
                <div class="table-responsive">
                    <table id="insumosTable" class="table index-table w-full">
                        <thead>
                            <tr>
                                <th>Asignar</th>
                                <th>Categoria Insumo </th>
                                <th>Nombre Insumo</th>
                                <th>Costo Mensual</th>
                                <th>Costo Anual</th>
                                <th>Frecuencia de Pago</th>
                                <th>Fecha de Renovacion</th>
                                <th>Observaciones</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insumos as $insumo)
                            <tr>
                                <td>


                                    <button class='btn btn-outline-success btn-xs crear-btn-insumo' data-id="{{ $insumo->CategoriaID }}">
                                        <i class="fas fa-plus"></i>
                                    </button>

                                </td>

                                <td>{{ $insumo->categorias->Categoria }}</td>
                                <td>{{ $insumo->NombreInsumo }}</td>
                                <td>{{ $insumo->CostoMensual }}</td>
                                <td>{{ $insumo->CostoAnual }}</td>
                                <td>{{ $insumo->FrecuenciaDePago }}</td>
                                <td>{{ (empty($insumo->FechaRenovacion) || in_array($insumo->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($insumo->FechaRenovacion)->format('d/m/Y') }}</td>
                                <td>{{ $insumo->Observaciones }}</td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            </div>
        </div>
            @endif
    </div>


    <!-- TAB Línea -->
    <div class="tab-pane fade" id="linea">

        <div class="inv-kpi-row">
            <div class="inv-kpi inv-kpi-total">
                <div class="inv-kpi-label">Líneas asignadas</div>
                <div class="inv-kpi-value">{{ $LineasAsignados->count() }}</div>
                <div class="inv-kpi-sub">En uso / proyección</div>
            </div>
            @if($permitePresupuestado)
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Stock en uso</div>
                <div class="inv-kpi-value">{{ $lineasStock->count() }}</div>
                <div class="inv-kpi-sub">Líneas físicas asignadas</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Presupuestado</div>
                <div class="inv-kpi-value">{{ $lineasExtra->count() }}</div>
                <div class="inv-kpi-sub">Proyecciones registradas</div>
            </div>
            @else
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Solo stock</div>
                <div class="inv-kpi-value">{{ collect($LineasAsignados)->count() }}</div>
                <div class="inv-kpi-sub">Referenciado</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-label">Disponibles</div>
                <div class="inv-kpi-value">{{ collect($Lineas)->count() }}</div>
                <div class="inv-kpi-sub">Catálogo</div>
            </div>
            @endif
        </div>

        <div class="inv-panel">
            <div class="inv-panel-head inv-panel-head-asignados">
                <span><i class="fas fa-check-circle mr-1"></i> Líneas asignadas</span>
                @include('inventarios.filtro_presupuestado', [
                    'tabla' => 'lineasAsignadosTable',
                    'tipo' => 'lineas',
                    'permitePresupuestado' => $permitePresupuestado,
                    'presupuestadoForzado' => $presupuestadoForzado,
                    'empleadoID' => $inventario->EmpleadoID,
                    'compacto' => true,
                ])
            </div>
            <div class="inv-panel-body">
                @if($permitePresupuestado && !$presupuestadoForzado)
                <div class="inv-dual" data-tabla="lineasAsignadosTable">
                    <div class="inv-dual-card" data-filtro="no_presupuestados">
                        <div class="inv-dual-title stock"><i class="fas fa-cube"></i> Stock en uso (<span class="conteo-no">{{ $lineasStock->count() }}</span>)</div>
                        <div class="inv-dual-empty">No hay líneas físicas asignadas si el conteo es 0</div>
                    </div>
                    <div class="inv-dual-card" data-filtro="presupuestados">
                        <div class="inv-dual-title extra"><i class="fas fa-calendar-alt"></i> Presupuestado (<span class="conteo-si">{{ $lineasExtra->count() }}</span>)</div>
                        <div class="inv-dual-empty">No hay proyecciones registradas si el conteo es 0</div>
                    </div>
                </div>
                @endif

            <div class="table-responsive">
                <table id="lineasAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Num. Tel.</th>
                            <th>Compania</th>
                            <th>Plan</th>
                            <th>Costo Renta Mensual</th>
                            <th>Cuenta Padre</th>
                            <th>Cuenta Hija</th>
                            <th>Tipo Linea</th>
                            <th>Obra</th>
                            <th>Fecha Fianza</th>
                            <th>Costo Fianza</th>
                            <th>Fecha Asignación</th>
                            <th>Comentario</th>
                            <th>Monto Renovación Fianza</th>
                            <th>Fecha Renovación</th>
                            @if($permitePresupuestado)
                            <th>Stock / Extra</th>
                            @endif



                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($LineasAsignados as $LineasAsignado)
                        <tr data-id="{{ $LineasAsignado->InventarioID }}">
                            <td>
                                @if($empleadoActivo)
                                <div class="index-actions">
                                    <button type="button" class="index-action index-action--edit edit-btn-linea" data-id="{{ $LineasAsignado->InventarioID }}" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {!! Form::open(['method' => 'DELETE', 'route' => ['inventarios.destroylinea', $LineasAsignado->InventarioID], 'class' => 'index-action-form']) !!}
                                    {!! Form::button('<i class="fas fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'index-action index-action--delete delete-btn-linea',
                                    'title' => 'Eliminar',
                                    'data-id' => $LineasAsignado->InventarioID
                                    ]) !!}
                                    {!! Form::close() !!}
                                </div>
                                @else
                                <span class="text-muted small">—</span>
                                @endif


                            </td>


                            <td>{{ $LineasAsignado->NumTelefonico}}</td>
                            <td>{{ $LineasAsignado->Compania}}</td>
                            <td>{{ $LineasAsignado->PlanTel}}</td>
                            <td>{{ $LineasAsignado->CostoRentaMensual}}</td>
                            <td>{{ $LineasAsignado->CuentaPadre}}</td>
                            <td>{{ $LineasAsignado->CuentaHija}}</td>
                            <td>{{ $LineasAsignado->TipoLinea}}</td>  
                            <td>{{ $LineasAsignado->lineastelefonicas->obras->NombreObra ?? 'Sin asignar'}}</td>
                            <td>{{ $LineasAsignado->FechaFianza ? \Carbon\Carbon::parse($LineasAsignado->FechaFianza)->format('d/m/Y') : '' }}</td>
                            <td>{{ $LineasAsignado->CostoFianza}}</td>
                            <td>{{ $LineasAsignado->FechaAsignacion ? \Carbon\Carbon::parse($LineasAsignado->FechaAsignacion)->format('d/m/Y') : '' }}</td>
                            <td>{{ $LineasAsignado->Comentarios}}</td>
                            <td>{{ $LineasAsignado->MontoRenovacionFianza}}</td>
                            <td>{{ (empty($LineasAsignado->FechaRenovacion) || in_array($LineasAsignado->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($LineasAsignado->FechaRenovacion)->format('d/m/Y') }}</td>
                            @if($permitePresupuestado)
                            <td>{!! $LineasAsignado->Presupuestado
                                ? '<span class="inv-chip inv-chip-extra">Extra</span>'
                                : '<span class="inv-chip inv-chip-stock">Stock</span>' !!}</td>
                            @endif

                        </tr>
                        @endforeach


                    </tbody>
                </table>
            </div>

            </div>
        </div>

            <!-- lineas Disponibles -->
            @if($empleadoActivo)
        <div class="inv-panel">
            <div class="inv-panel-head inv-panel-head-disponibles">
                <span><i class="fas fa-plus-circle mr-1"></i> Líneas disponibles</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-toolbar">
                    <div class="inv-search">
                        <i class="fas fa-search"></i>
                        <input type="text" class="inv-table-search" data-tabla="lineasTable" placeholder="Buscar línea...">
                    </div>
                </div>
            <div class="drag-area" id="disponibles">
                <div class="table-responsive">
                    <table id="lineasTable" class="table index-table w-full">
                        <thead>
                            <tr>
                                <th>Asignar</th>
                                <th>Num. Tel.</th>
                                <th>Plan</th>
                                <th>Cuenta Padre</th>
                                <th>Cuenta Hija</th>
                                <th>Tipo Linea</th>
                                <th>Obra</th>
                                <th>Fecha Fianza</th>
                                <th>Costo Fianza</th>
                                <th>Activo</th>
                                <th>Monto Renovación Fianza</th>
                                <th>Fecha Renovación</th>



                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Lineas as $Linea)
                            <tr>
                                <td>


                                    <button class='btn btn-outline-success btn-xs crear-btn-linea' data-id="{{ $Linea->LineaID }}">
                                        <i class="fas fa-plus"></i>
                                    </button>

                                </td>


                                <td>{{ $Linea->NumTelefonico}}</td>
                                <td>{{ $Linea->planes->NombrePlan}}</td>
                                <td>{{ $Linea->CuentaPadre}}</td>
                                <td>{{ $Linea->CuentaHija}}</td>
                                <td>{{ $Linea->TipoLinea}}</td>
                                <td>{{ $Linea->obras->NombreObra}}</td>
                                <td>{{(empty($Linea->FechaFianza) ||in_array($Linea->FechaFianza, ['Sin asignar', 'Sin asigna', '0000-00-00']))? 'Sin asignar': \Carbon\Carbon::parse($Linea->FechaFianza)->format('d/m/Y')}}</td>
                                <td>{{ $Linea->CostoFianza}}</td>
                                <td>
                                    <input class="form-check-input" type="checkbox" value="" id="flexCheckCheckedDisabled1" checked disabled>
                                    <label class="form-check-label" for="flexCheckCheckedDisabled1">
                                    </label>
                                </td>
                                <td>{{ $Linea->MontoRenovacionFianza}}</td>
                                <td>{{ (empty($Linea->FechaRenovacion) || in_array($Linea->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($Linea->FechaRenovacion)->format('d/m/Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
            @endif
    </div>


</div>

@push('third_party_stylesheets')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@include('inventarios.partials.tipo-persona-styles')
@include('inventarios.partials.asignar-ui-styles')

<style>
    .inventario-filtros .pill-group {
        gap: 8px;
    }

    .inventario-filtros .pill-filtro {
        border: none;
        border-radius: 999px;
        padding: 6px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        background-color: #e2e8f0;
        transition: background-color .15s ease, color .15s ease;
    }

    .inventario-filtros .pill-filtro:hover {
        background-color: #cbd5e1;
    }

    .inventario-filtros .pill-filtro.activo {
        background-color: #3b82f6;
        color: #fff;
    }

    .dark .inventario-filtros .pill-filtro {
        background-color: #1e293b;
        color: #cbd5e1;
    }

    .dark .inventario-filtros .pill-filtro:hover {
        background-color: #334155;
    }

    .dark .inventario-filtros .pill-filtro.activo {
        background-color: #3b82f6;
        color: #fff;
    }
</style>
@endpush

@push('third_party_scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
    const empleadoInventarioActivo = @json($empleadoActivo);
    const permitePresupuestado = @json($permitePresupuestado);
    const presupuestadoForzado = @json($presupuestadoForzado);

    // El switch sólo existe en el DOM para FISICA; en EXTRAORDINARIO todo lo
    // asignado es presupuestado y para el resto el campo viaja siempre en 0.
    // (El servidor vuelve a aplicar la regla, esto es sólo para la UI.)
    function htmlChipPresupuestado(esExtra) {
        return esExtra
            ? '<span class="inv-chip inv-chip-extra">Extra</span>'
            : '<span class="inv-chip inv-chip-stock">Stock</span>';
    }

    // Equipos: 0 = stock, 1 = presupuestado (extra), 2 = propio del empleado.
    function htmlChipTipoEquipo(valor) {
        const v = String(valor ?? '0');
        if (v === '1') return '<span class="inv-chip inv-chip-extra">Extra</span>';
        if (v === '2') return '<span class="inv-chip inv-chip-propio">Propio</span>';
        return '<span class="inv-chip inv-chip-stock">Stock</span>';
    }

    function textoCeldaEsExtra(texto) {
        const v = String(texto ?? '').trim().toLowerCase();
        return v === 'si' || v.indexOf('extra') !== -1 || v.indexOf('presupuest') !== -1;
    }

    // Traduce el texto de la celda (o un valor crudo) a la modalidad 0 / 1 / 2.
    function valorModoDesdeTexto(texto) {
        const v = String(texto ?? '').trim().toLowerCase();
        if (v === '2' || v.indexOf('propio') !== -1) return '2';
        return textoCeldaEsExtra(v) ? '1' : '0';
    }

    function syncModoCards(selector, valor) {
        const $wrap = $('[data-switch="' + selector + '"]');
        if (!$wrap.length) return;
        const v = String(valor ?? '0');
        $wrap.find('.inv-modo-card').removeClass('is-active');
        $wrap.find('.inv-modo-card[data-value="' + v + '"]').addClass('is-active');

        const sid = selector.replace(/^#/, '');
        const hint = v === '1' ? 'extra' : (v === '2' ? 'propio' : 'stock');
        $('[data-hint-for="' + sid + '"]').hide();
        $('[data-hint-for="' + sid + '"].' + hint).css('display', 'flex');
    }

    // Un equipo propio es del empleado: la empresa no le pone precio, folio, fecha de
    // compra ni mes de pago. Esos cuatro campos se ocultan por completo y se guardan
    // vacíos (ver guardarEquipo), no sólo dejan de ser obligatorios.
    const camposOpcionalesEquipoPropio = ['#editPrecio', '#editFolio', '#editFechaDeCompra', '#editMesDePagoEquipo'];

    function aplicarRequeridosEquipo(esPropio) {
        // Los contenedores marcados en el modal desaparecen; el required se quita
        // igual porque un input oculto y obligatorio bloquea la validación nativa.
        $('.equipo-solo-empresa').toggle(!esPropio);

        camposOpcionalesEquipoPropio.forEach(function(sel) {
            const $campo = $(sel);
            if (!$campo.length) return;

            // Se recuerda el estado del HTML para no volver obligatorio lo que nunca lo fue.
            if ($campo.data('requeridoOriginal') === undefined) {
                $campo.data('requeridoOriginal', $campo.prop('required'));
            }

            const requerido = !esPropio && $campo.data('requeridoOriginal') === true;
            $campo.prop('required', requerido);

            if (!requerido) {
                $campo.removeClass('is-invalid');
            }
        });

        // Folio oculto: no hay duplicado que revisar.
        if (esPropio) {
            folioValido = true;
            $('#editFolio').removeClass('is-invalid is-valid');
        }
    }

    // Fuente de verdad del modo: el hidden #<switchId>Valor. El checkbox se conserva
    // sólo por compatibilidad con el resto del formulario (etiqueta Si/No).
    function aplicarValorModo(selector, valor) {
        const v = presupuestadoForzado ? '1' : String(valor ?? '0');
        $(selector + 'Valor').val(v);
        $(selector).prop('checked', v === '1');
        $(selector + 'Label').text(v === '1' ? 'Si' : 'No');
        syncModoCards(selector, v);

        if (selector === '#editPresupuestadoEquipo') {
            aplicarRequeridosEquipo(v === '2');
        }
    }

    function setPresupuestado(selector, texto) {
        aplicarValorModo(selector, valorModoDesdeTexto(texto));
    }

    function getPresupuestado(selector) {
        return getModo(selector) === 1 ? 1 : 0;
    }

    // Valor completo (0/1/2) para equipos; insumos y líneas siguen usando getPresupuestado.
    function getModo(selector) {
        if (presupuestadoForzado) {
            return 1;
        }

        if (!permitePresupuestado) {
            return 0;
        }

        return parseInt($(selector + 'Valor').val() || '0', 10);
    }

    // Mantener la etiqueta del switch / cards en sync con su estado
    $(document).on('change', '.form-check-input[role="switch"]', function() {
        aplicarValorModo('#' + this.id, this.checked ? '1' : '0');
    });

    $(document).on('click', '.inv-modo-card:not(.is-locked)', function() {
        const $card = $(this);
        const selector = $card.closest('[data-switch]').data('switch');
        aplicarValorModo(selector, String($card.data('value')));
    });

    function bloquearAccionInventarioInactivo() {
        if (!empleadoInventarioActivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Empleado inactivo',
                text: 'No se pueden realizar acciones de inventario porque el empleado está dado de baja.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return true;
        }

        return false;
    }
</script>

<script>
    $(document).ready(function() {
        if ($('#equiposTable').length) {
        let table1_1 = $('#equiposTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,
        });
        }

        if ($('#insumosTable').length) {
        let table2_1 = $('#insumosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,
        });
        }

        if ($('#lineasTable').length) {
        let table3_1 = $('#lineasTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,

        });
        }

        let table = $('#equiposAsignadosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,
            "columnDefs": [
                { "visible": false, "targets": [3, 6, 7] } // características, fecha asignacion, fecha compra
            ]
        });

        let table2 = $('#insumosAsignadosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,

        });

        let table3 = $('#lineasAsignadosTable').DataTable({
            "responsive": true,
            "paging": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "searching": true,
            "ordering": true,
            "info": true,

        });

        inicializarFiltrosPresupuestado();
    });
</script>

<script>
    // Índice de la columna "Presupuestado" en cada tabla de asignados.
    // La columna sólo se pinta para FISICA/EXTRAORDINARIO.
    const columnaPresupuestado = {
        equiposAsignadosTable: 12,
        insumosAsignadosTable: 12,
        lineasAsignadosTable: 15,
    };

    // Filtro activo por tabla: todos | presupuestados | no_presupuestados
    const filtroPresupuestado = {
        equiposAsignadosTable: 'todos',
        insumosAsignadosTable: 'todos',
        lineasAsignadosTable: 'todos',
    };

    function esFilaPresupuestada(valorCelda) {
        const v = String(valorCelda ?? '').trim().toLowerCase();
        return v === 'si' || v.indexOf('extra') !== -1 || v.indexOf('presupuest') !== -1;
    }

    // Filtro global de DataTables: las tablas sin entrada en el mapa no se ven afectadas.
    $.fn.dataTable.ext.search.push(function(settings, data) {
        const tablaId = settings.nTable.id;
        const filtro = filtroPresupuestado[tablaId];

        if (!permitePresupuestado || !filtro || filtro === 'todos') {
            return true;
        }

        const presupuestada = esFilaPresupuestada(data[columnaPresupuestado[tablaId]]);

        return filtro === 'presupuestados' ? presupuestada : !presupuestada;
    });

    function actualizarConteos(tablaId) {
        if (!permitePresupuestado) {
            return;
        }

        const dt = $('#' + tablaId).DataTable();
        let si = 0;
        let no = 0;

        // {search:'none'} => cuenta sobre todas las filas, no sólo las visibles.
        dt.column(columnaPresupuestado[tablaId], { search: 'none' }).data().each(function(valor) {
            esFilaPresupuestada(valor) ? si++ : no++;
        });

        const barra = $('.inventario-filtros[data-tabla="' + tablaId + '"]');
        barra.find('.conteo-todos').text(si + no);
        barra.find('.conteo-si').text(si);
        barra.find('.conteo-no').text(no);

        const dual = $('.inv-dual[data-tabla="' + tablaId + '"]');
        dual.find('.conteo-si').text(si);
        dual.find('.conteo-no').text(no);
    }

    function inicializarFiltrosPresupuestado() {
        Object.keys(filtroPresupuestado).forEach(function(tablaId) {
            actualizarConteos(tablaId);

            // Recontar cada vez que se agrega, edita o elimina una fila.
            $('#' + tablaId).DataTable().on('draw', function() {
                actualizarConteos(tablaId);
            });
        });
    }

    // La columna "Presupuestado" sólo aporta información en "Todos"; en las otras
    // pestañas el valor ya está implícito en el filtro, igual que en el Excel.
    function aplicarVisibilidadPresupuestado(tablaId) {
        if (!permitePresupuestado) {
            return;
        }

        const mostrar = (filtroPresupuestado[tablaId] || 'todos') === 'todos';

        $('#' + tablaId).DataTable()
            .column(columnaPresupuestado[tablaId])
            .visible(mostrar, false);
    }

    $(document).on('click', '.pill-filtro', function() {
        const barra = $(this).closest('.inventario-filtros');
        const tablaId = barra.data('tabla');

        filtroPresupuestado[tablaId] = $(this).data('filtro');

        barra.find('.pill-filtro').removeClass('activo');
        $(this).addClass('activo');

        // Sync dual cards
        const dual = $('.inv-dual[data-tabla="' + tablaId + '"]');
        dual.find('.inv-dual-card').removeClass('is-active');
        dual.find('.inv-dual-card[data-filtro="' + filtroPresupuestado[tablaId] + '"]').addClass('is-active');
        if (filtroPresupuestado[tablaId] === 'todos') {
            dual.find('.inv-dual-card').removeClass('is-active');
        }

        aplicarVisibilidadPresupuestado(tablaId);
        $('#' + tablaId).DataTable().draw();
    });

    $(document).on('click', '.inv-dual-card', function() {
        const dual = $(this).closest('.inv-dual');
        const tablaId = dual.data('tabla');
        let filtro = $(this).data('filtro');

        // Segundo clic en la misma tarjeta = ver todos
        if ($(this).hasClass('is-active')) {
            filtro = 'todos';
            dual.find('.inv-dual-card').removeClass('is-active');
        } else {
            dual.find('.inv-dual-card').removeClass('is-active');
            $(this).addClass('is-active');
        }

        filtroPresupuestado[tablaId] = filtro;

        const barra = $('.inventario-filtros[data-tabla="' + tablaId + '"]');
        barra.find('.pill-filtro').removeClass('activo');
        const pill = barra.find('.pill-filtro[data-filtro="' + filtro + '"]');
        if (pill.length) pill.addClass('activo');
        else barra.find('.pill-filtro[data-filtro="todos"]').addClass('activo');

        aplicarVisibilidadPresupuestado(tablaId);
        $('#' + tablaId).DataTable().draw();
    });

    $(document).on('keyup', '.inv-table-search', function() {
        const tablaId = $(this).data('tabla');
        if ($.fn.DataTable.isDataTable('#' + tablaId)) {
            $('#' + tablaId).DataTable().search(this.value).draw();
        }
    });
</script>

<script>
    $(document).ready(function() {
        $('#myTab a').on('click', function(event) {
            event.preventDefault();
            var target = $(this).attr('href');

            $('#myTab a').removeClass('active');
            $('.tab-content > .tab-pane').removeClass('show active');

            $(this).addClass('active');
            $(target).addClass('show active');

            // Recalcular columnas DataTables al mostrar pestaña (evita tablas “vacías”)
            var $pane = $(target);
            $pane.find('table').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().columns.adjust();
                }
            });
        });
    });

    // Seccion equipo 
    // La tabla de equipos oculta las columnas 3, 6 y 7 (características, fecha de
    // asignación y de compra), y DataTables saca esos <td> del DOM: "td:eq(N)" deja de
    // coincidir con el número de columna. La API sí conserva las celdas ocultas, así
    // que el acceso por índice original pasa siempre por ella.
    function celdasEquipo($row) {
        const dt = $('#equiposAsignadosTable').DataTable();
        const indice = dt.row($row).index();
        const nodo = (columna) => $(dt.cell(indice, columna).node() || []);

        return {
            nodo: nodo,
            texto: (columna) => nodo(columna).text().trim(),
        };
    }

    // Editar equipo (abriendo el modal con los datos)
    $(document).on('click', '.edit-btn', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = $(this).closest('tr');
        let id = row.data('id');
        const celda = celdasEquipo(row);

        // Asignar valores al formulario
        document.getElementById('titulo').innerHTML = 'Editar Equipo';
        $('#editId').val(id);
        $('#editEmp').val('');
        $('#editCategoria').val(celda.texto(1));
        $('#editMarca').val(celda.texto(2));
        $('#editCaracteristicas').val(celda.texto(3));
        $('#editModelo').val(celda.texto(4));
        $('#editPrecio').val(celda.texto(5));
        $('#editFechaAsignacion').val(celda.texto(6));
        $('#editFechaDeCompra').val(celda.texto(7));
        $('#editNumSerie').val(celda.texto(8));
        $('#editFolio').val(celda.texto(9));
        $('#editGerenciaEquipo').val(celda.nodo(10).data('id')).trigger('change');
        $('#editComentarios').val(celda.texto(11));
        setPresupuestado('#editPresupuestadoEquipo', celda.texto(12));
        $('#editMesDePagoEquipo').val(celda.texto(13));

        $('#editModal').modal('show');
    });

    // Crear equipo (con valores vacíos para nuevo registro)
    $(document).on('click', '.crear-btn', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editForm')[0].reset();
        $('#editGerenciaEquipo').val(null).trigger('change');

        document.getElementById('titulo').innerHTML = 'Crear Equipo';
        let row = $(this).closest('tr');
        let categoria = row.find("td:eq(1)").text();
        let marca = row.find("td:eq(2)").text();
        let modelo = row.find("td:eq(3)").text();
        let caracteristicas = row.find("td:eq(4)").text();
        let precio = row.find("td:eq(5)").text();

        $('#editCategoria').val(categoria);
        $('#editMarca').val(marca);
        $('#editCaracteristicas').val(caracteristicas);
        $('#editModelo').val(modelo);
        $('#editPrecio').val(precio);
        $('#editId').val('');
        $('#editEmp').val(id_E);
        setPresupuestado('#editPresupuestadoEquipo', 'No');
        $('#editMesDePagoEquipo').val('');

        $('#editModal').modal('show');
    });

    // Validación en tiempo real del Folio (al escribir o al salir del campo)
    let folioTimer = null;
    let folioValido = true; // Estado de validez del folio actual

    // Función para cargar los últimos 3 folios
    function cargarUltimosFolios() {
        const excluirId = $('#editId').val();
        $.ajax({
            url: '/inventarios/verificar-folio',
            method: 'GET',
            data: { folio: '', excluir_id: excluirId },
            success: function(response) {
                if (response.ultimos_folios && response.ultimos_folios.length > 0) {
                    $('#ultimos-folios-lista').html(
                        '<ul class="mb-0 pl-3"><li>' +
                        response.ultimos_folios.join('</li><li>') +
                        '</li></ul>'
                    );  
                } else {
                    $('#ultimos-folios-lista').text('Ninguno registrado aún');
                }
            }
        });
    }

    // Mostrar últimos 3 folios registrados cuando el usuario hace focus al input
    $(document).on('focus', '#editFolio', function() {
        cargarUltimosFolios();
        $('#folio-Info').fadeIn(200);
    });

    // Ocultar la advertencia al perder el foco
    $(document).on('blur', '#editFolio', function() {
        $('#folio-Info').fadeOut(200);
    });

    $(document).on('input', '#editFolio', function() {
        clearTimeout(folioTimer);
        const folioInput = $(this);
        const folio = folioInput.val().trim();
        const excluirId = $('#editId').val();
        const feedbackEl = folioInput.siblings('.invalid-feedback');

        // Limpiar estado previo
        folioInput.removeClass('is-invalid is-valid');
        folioValido = true;

        if (!folio) return;

        // Esperar 500ms después de que el usuario deje de escribir
        folioTimer = setTimeout(function() {
            $.ajax({
                url: '/inventarios/verificar-folio',
                method: 'GET',
                data: { folio: folio, excluir_id: excluirId },
                success: function(response) {
                    if (response.disponible) {
                        folioInput.removeClass('is-invalid').addClass('is-valid');
                        feedbackEl.text('');
                        folioValido = true;
                    } else {
                        folioInput.removeClass('is-valid').addClass('is-invalid');
                        if (feedbackEl.length) {
                            feedbackEl.text(response.mensaje);
                        } else {
                            folioInput.after('<div class="invalid-feedback" style="display:block">' + response.mensaje + '</div>');
                        }
                        folioValido = false;
                    }
                    
                    // Actualizar también la lista si cambia
                    if (response.ultimos_folios && response.ultimos_folios.length > 0) {
                        $('#ultimos-folios-lista').html(
                            '<ul class="mb-0 pl-3"><li>' +
                            response.ultimos_folios.join('</li><li>') +
                            '</li></ul>'
                        );
                    }
                }
            });
        }, 500);
    });

    // Limpiar estado de validación del folio al abrir el modal
    $('#editModal').on('show.bs.modal', function() {
        folioValido = true;
        $('#editFolio').removeClass('is-invalid is-valid');
        $('#folio-Info').hide();
    });

    // Enviar formulario de edición o creación con AJAX
    $(document).on('click', '.submit_equipo', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        $('.error-message').remove();
        $('.is-invalid').removeClass('is-invalid');

        let isValid = true;

        // Validación de campos requeridos
        $('#editForm [required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos obligatorios',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        // Bloquear el envío si el folio ya fue detectado como duplicado
        if (!folioValido) {
            Swal.fire({
                icon: 'error',
                title: 'Folio duplicado',
                text: 'El folio ingresado ya está registrado. Por favor ingrese un folio único.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            $('#editFolio').addClass('is-invalid').focus();
            return;
        }

        let id = $('#editId').val();
        let id_E = $('#editEmp').val();
        // En equipo propio el folio está oculto: se ignora lo que traiga el input.
        let folio = getModo('#editPresupuestadoEquipo') === 2 ? '' : $('#editFolio').val().trim();
        let excluirId = id || null;
        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Sin folio no hay nada que verificar: se guarda directo.
        if (!folio) {
            guardarEquipo(id, id_E, folio, csrfToken);
            return;
        }

        // Verificación final de unicidad del folio antes de enviar
        $.ajax({
            url: '/inventarios/verificar-folio',
            method: 'GET',
            data: { folio: folio, excluir_id: excluirId },
            success: function(verifyResponse) {
                if (!verifyResponse.disponible) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Folio duplicado',
                        text: verifyResponse.mensaje,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                    $('#editFolio').addClass('is-invalid').focus();
                    folioValido = false;
                    return;
                }

                guardarEquipo(id, id_E, folio, csrfToken);
            },
            error: function() {
                // Si falla la verificación, dejar pasar y que el backend valide
                console.warn('No se pudo verificar el folio en tiempo real.');
            }
        });
    });

    // Mapa de campo del backend -> input del modal (no todos comparten nombre).
    const inputPorCampoEquipo = {
        CategoriaEquipo: '#editCategoria',
        GerenciaEquipoID: '#editGerenciaEquipo',
        MesDePago: '#editMesDePagoEquipo',
    };

    function marcarErroresEquipo(errores) {
        Object.keys(errores).forEach(function(campo) {
            const input = $(inputPorCampoEquipo[campo] || ('#edit' + campo));
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(errores[campo][0]);
        });

        const primero = Object.keys(errores)[0];

        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: errores[primero][0] || 'Por favor revise los campos marcados en rojo',
            customClass: {
                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
            }
        });
    }

    function guardarEquipo(id, id_E, folio, csrfToken) {
        let url = id ? '/inventarios/editar-equipo/' + id : '/inventarios/crear-equipo/' + id_E;
        let method = id ? 'PUT' : 'POST';

        const modo = getModo('#editPresupuestadoEquipo');
        // Campos ocultos en equipo propio: se envían vacíos para no arrastrar el valor
        // que quedó en el input al cambiar de modalidad.
        const soloEmpresa = (valor) => modo === 2 ? '' : valor;

        let formData = {
            CategoriaEquipo: $('#editCategoria').val(),
            GerenciaEquipoID: $('#editGerenciaEquipo').val(),
            Marca: $('#editMarca').val(),
            Caracteristicas: $('#editCaracteristicas').val(),
            Modelo: $('#editModelo').val(),
            Precio: soloEmpresa($('#editPrecio').val()),
            FechaAsignacion: $('#editFechaAsignacion').val(),
            NumSerie: $('#editNumSerie').val(),
            Folio: folio,
            FechaDeCompra: soloEmpresa($('#editFechaDeCompra').val()),
            Comentarios: $('#editComentarios').val(),
            FechaRenovacion: $('#editFechaDeRenovacion').val(),
            tipoEquipo: modo,
            MesDePago: soloEmpresa($('#editMesDePagoEquipo').val()),
        };

        $.ajax({
            url: url,
            method: method,
            data: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.errors) {
                    marcarErroresEquipo(response.errors);
                    return;
                }

                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Datos del equipo guardados correctamente",
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                });

                // Actualizar o agregar la fila en la tabla
                if (id) {
                    updateTableRow(response.equipo);
                } else {
                    addNewRow(response.equipo);
                }

                $('#editModal').modal('hide');
            },
            error: function(xhr) {
                const resp = xhr.responseJSON;

                if (xhr.status === 422 && resp && resp.errors) {
                    if (resp.errors.Folio) {
                        $('#editFolio').focus();
                        folioValido = false;
                    }

                    marcarErroresEquipo(resp.errors);
                    return;
                }

                console.error('Error:', xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al guardar los datos',
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                });
            }
        });
    }


    // Helper para formatear fechas a dd/mm/yyyy o 'Sin asignar'
    function formatFechaRenovacion(fecha) {
        if (!fecha || fecha === 'Sin asignar' || fecha === 'Sin asigna' || fecha === '0000-00-00' || fecha === 'null') {
            return 'Sin asignar';
        }
        let raw = fecha.toString().substring(0, 10);
        let parts = raw.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }
        return fecha;
    }

    // Helper para convertir dd/mm/yyyy a yyyy-mm-dd (para inputs type=date)
    function fechaDisplayToInput(fechaDisplay) {
        if (!fechaDisplay || fechaDisplay === 'Sin asignar' || fechaDisplay === 'Sin asigna' || fechaDisplay === '0000-00-00') {
            return '';
        }
        let parts = fechaDisplay.trim().split('/');
        if (parts.length === 3 && parts[2].length === 4) {
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        // Si ya está en yyyy-mm-dd, retornar como está
        return fechaDisplay.trim().substring(0, 10);
    }

    // Actualizar una fila en la tabla después de editar
    function updateTableRow(equipo) {
        let row = $(`tr[data-id=${equipo.InventarioID}]`);
        const celda = celdasEquipo(row);
        celda.nodo(1).text(equipo.CategoriaEquipo);
        celda.nodo(2).text(equipo.Marca);
        celda.nodo(3).text(equipo.Caracteristicas);
        celda.nodo(4).text(equipo.Modelo);
        celda.nodo(5).text(equipo.Precio);
        celda.nodo(6).text(equipo.FechaAsignacion);
        celda.nodo(7).text(equipo.FechaDeCompra);
        celda.nodo(8).text(equipo.NumSerie);
        celda.nodo(9).text(equipo.Folio);
        // El data-id de la gerencia es lo que relee el modal al reabrirlo.
        celda.nodo(10).attr('data-id', equipo.GerenciaEquipoID).text(equipo.GerenciaEquipo);
        celda.nodo(11).text(equipo.Comentarios);
        if (permitePresupuestado) {
            celda.nodo(12).html(htmlChipTipoEquipo(equipo.tipoEquipo));
            celda.nodo(13).text(equipo.MesDePago ?? '');
        }
        row.find('.edit-btn').data('id', equipo.InventarioID);

        // Refrescar la caché de DataTables para que el filtro y los conteos vean el cambio.
        $('#equiposAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }

    // Agregar una nueva fila en la tabla (para equipo creado)
    function addNewRow(equipo) {
        let newRow = `
        <tr data-id="${equipo.InventarioID}">
            <td>
                <div class="index-actions">
                    <button type="button" class="index-action index-action--edit edit-btn" data-id="${equipo.InventarioID}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" action="/inventarios/destroy/${equipo.InventarioID}" class="index-action-form">
                        <button type="submit" class="index-action index-action--delete delete-btn" data-id="${equipo.InventarioID}" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </td>
            <td>${equipo.CategoriaEquipo}</td>
            <td>${equipo.Marca}</td>
            <td>${equipo.Caracteristicas}</td>
            <td>${equipo.Modelo}</td>
            <td>${equipo.Precio}</td>
            <td>${equipo.FechaAsignacion}</td>
            <td>${equipo.FechaDeCompra}</td>
            <td>${equipo.NumSerie}</td>
            <td>${equipo.Folio}</td>
            <td data-id="${equipo.GerenciaEquipoID}">${equipo.GerenciaEquipo}</td>
            <td>${equipo.Comentarios}</td>
            ${permitePresupuestado ? `<td>${htmlChipTipoEquipo(equipo.tipoEquipo)}</td><td>${equipo.MesDePago ?? ''}</td>` : ''}
        </tr>
    `;
        $('#equiposAsignadosTable').DataTable().row.add($(newRow)).draw(false);
    }

    // Eliminar equipo con AJAX
    $(document).on('click', '.delete-btn', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var id = $(this).data('id'); // ✅ Obtener el ID del botón delete-btn

        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del equipo.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        Swal.fire({
            title: `Eliminar`,
            text: "¿Realmente desea eliminar este equipo asignado?",
            icon: "warning",
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cerrar',
            dangerMode: true,
            customClass: {
                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
            }
        }).then(function(willDelete) {
            if (willDelete.isConfirmed) {
                $.ajax({
                    url: `/inventarios/${id}`, // ✅ Se pasa el ID en la URL correctamente
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Eliminado!',
                                text: "El equipo fue eliminado correctamente.",
                                icon: 'success',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });

                            // Eliminar la fila de la tabla
                            $('#equiposAsignadosTable').DataTable().row(`tr[data-id=${id}]`).remove().draw(false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el equipo',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al eliminar el equipo.',
                            customClass: {
                                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                            }
                        });
                    }
                });
            }
        });
    });

    // Fin seccion equipo 

    // Seccion insumo

    $(document).on('click', '.edit-btn-insum', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = $(this).closest('tr');
        let id = row.data('id');

        // Asignar valores al formulario
        document.getElementById('tituloinsumo').innerHTML = 'Editar insumo';

        $('#editId_insumo').val(id);
        $('#editEmp_insumo').val('');
        $('#editCategoriaInsumo').val(row.find("td:eq(1)").text());
        $('#editNombreInsumo').val(row.find("td:eq(2)").text());
        $('#editCostoMensual').val(row.find("td:eq(3)").text());
        $('#editCostoAnual').val(row.find("td:eq(4)").text());
        $('#editFrecuenciaDePago').val(row.find("td:eq(5)").text());
        // Convertir dd/mm/yyyy del <td> a yyyy-mm-dd para el input date
        $('#editFechaDeRenovacion').val(fechaDisplayToInput(row.find("td:eq(6)").text()));
        $('#editobserv').val(row.find("td:eq(7)").text());
        $('#editFechaDeAsigna').val(fechaDisplayToInput(row.find("td:eq(8)").text()));
        $('#editNumSerieInsu').val(row.find("td:eq(9)").text());
        $('#editComentariosInsumo').val(row.find("td:eq(10)").text());
        $('#editMesDePago').val(row.find("td:eq(11)").text());
        setPresupuestado('#editPresupuestadoInsumo', row.find("td:eq(12)").text());

        $('#editModalInsumo').modal('show');
    });

    $(document).on('click', '.crear-btn-insumo', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editFormInsumo')[0].reset();

        document.getElementById('tituloinsumo').innerHTML = 'Crear insumo';
        let row = $(this).closest('tr');
        let categoria = row.find("td:eq(1)").text();
        let nombreinsumo = row.find("td:eq(2)").text();
        let costomensual = row.find("td:eq(3)").text();
        let costoanual = row.find("td:eq(4)").text();
        let frecuenciadepago = row.find("td:eq(5)").text();
        let fecharenovacion = row.find("td:eq(6)").text().trim();
        // Si la fecha trae hora (ej. 2026-04-29 00:00:00), tomamos solo los primeros 10 caracteres
        if (fecharenovacion.length > 10) {
            fecharenovacion = fecharenovacion.substring(0, 10);
        }
        // Si la fecha es un texto como 'Sin asignar', enviar vacío en vez del string
        if (fecharenovacion === 'Sin asignar' || fecharenovacion === 'Sin asigna' || fecharenovacion === '0000-00-00') {
            fecharenovacion = '';
        }
        let observaciones = row.find("td:eq(7)").text();

        $('#editCategoriaInsumo').val(categoria);
        $('#editNombreInsumo').val(nombreinsumo);
        $('#editCostoMensual').val(costomensual);
        $('#editCostoAnual').val(costoanual);
        $('#editFrecuenciaDePago').val(frecuenciadepago);
        $('#editFechaDeRenovacion').val(fecharenovacion);
        $('#editobserv').val(observaciones);
        $('#editId_insumo').val('');
        $('#editEmp_insumo').val(id_E);
        setPresupuestado('#editPresupuestadoInsumo', 'No');

        $('#editModalInsumo').modal('show');
    });


    $(document).on('click', '.submit_insumo', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        $('.error-message').remove();
        $('.is-invalid').removeClass('is-invalid');

        let isValid = true;

        $('#editFormInsumo [required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos obligatorios',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        let id = $('#editId_insumo').val();
        let id_E = $('#editEmp_insumo').val();
        let url = id ? '/inventarios/editar-insumo/' + id : '/inventarios/crear-insumo/' + id_E;
        let method = id ? 'PUT' : 'POST';

        // Limpiar FechaRenovacion: enviar vacío si tiene texto no-fecha
        let fechaRenovInsumo = $('#editFechaDeRenovacion').val();
        if (fechaRenovInsumo === 'Sin asignar' || fechaRenovInsumo === 'Sin asigna' || fechaRenovInsumo === '0000-00-00') {
            fechaRenovInsumo = '';
        }

        let formData = {
            CateogoriaInsumo: $('#editCategoriaInsumo').val(),
            NombreInsumo: $('#editNombreInsumo').val(),
            CostoMensual: $('#editCostoMensual').val(),
            CostoAnual: $('#editCostoAnual').val(),
            FrecuenciaDePago: $('#editFrecuenciaDePago').val(),
            FechaRenovacion: fechaRenovInsumo,
            Observaciones: $('#editobserv').val(),
            FechaAsignacion: $('#editFechaDeAsigna').val(),
            NumSerie: $('#editNumSerieInsu').val(),
            Comentarios: $('#editComentariosInsumo').val(),
            MesDePago: $('#editMesDePago').val(),
            Presupuestado: getPresupuestado('#editPresupuestadoInsumo'),
        };

        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.errors) {

                    Object.keys(response.errors).forEach(field => {
                        const input = $(`#edit${field}`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(response.errors[field][0]);
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: 'Por favor revise los campos marcados en rojo',
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                } else {

                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Datos del insumo guardado correctamente",
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });


                    if (id) {
                        updateisnumoTableRow(response.insumo);
                    } else {
                        addinsumoNewRow(response.insumo);
                    }

                    $('#editModalInsumo').modal('hide');
                }
            },
            error: function(error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al guardar los datos',
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                });
            }
        });
    });


    function updateisnumoTableRow(insumo) {
        let row = $(`tr[data-id=${insumo.InventarioID}]`);
        row.find('td:eq(1)').text(insumo.CateogoriaInsumo);
        row.find('td:eq(2)').text(insumo.NombreInsumo);
        row.find('td:eq(3)').text(insumo.CostoMensual);
        row.find('td:eq(4)').text(insumo.CostoAnual);
        row.find('td:eq(5)').text(insumo.FrecuenciaDePago);
        row.find('td:eq(6)').text(formatFechaRenovacion(insumo.FechaRenovacion));
        row.find('td:eq(7)').text(insumo.Observaciones);
        row.find('td:eq(8)').text(formatFechaRenovacion(insumo.FechaAsignacion));
        row.find('td:eq(9)').text(insumo.NumSerie);
        row.find('td:eq(10)').text(insumo.Comentarios);
        row.find('td:eq(11)').text(insumo.MesDePago);
        if (permitePresupuestado) {
            row.find('td:eq(12)').html(htmlChipPresupuestado(!!insumo.Presupuestado));
        }

        $('#insumosAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }


    function addinsumoNewRow(insumo) {
        let newRow = `
        <tr data-id="${insumo.InventarioID}">
            <td>
                <div class="index-actions">
                    <button type="button" class="index-action index-action--edit edit-btn-insum" data-id="${insumo.InventarioID}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" action="/inventarios/deleteInsumo/${insumo.InventarioID}" class="index-action-form">
                        <button type="submit" class="index-action index-action--delete delete-btn-insumo" data-id="${insumo.InventarioID}" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </td>
            <td>${insumo.CateogoriaInsumo}</td>
            <td>${insumo.NombreInsumo}</td>
            <td>${insumo.CostoMensual}</td>
            <td>${insumo.CostoAnual}</td>
            <td>${insumo.FrecuenciaDePago}</td>
            <td>${formatFechaRenovacion(insumo.FechaRenovacion)}</td>
            <td>${insumo.Observaciones}</td>
            <td>${formatFechaRenovacion(insumo.FechaAsignacion)}</td>
            <td>${insumo.NumSerie}</td>
            <td>${insumo.Comentarios}</td>
            <td>${insumo.MesDePago}</td>
            ${permitePresupuestado ? `<td>${htmlChipPresupuestado(!!insumo.Presupuestado)}</td>` : ''}
        </tr>
    `;
        $('#insumosAsignadosTable').DataTable().row.add($(newRow)).draw(false);
    }

    $(document).on('click', '.delete-btn-insumo', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var id = $(this).data('id');

        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del insumo.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        Swal.fire({
            title: `Eliminar`,
            text: "¿Realmente desea eliminar este insumo asignado?",
            icon: "warning",
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cerrar',
            dangerMode: true,
            customClass: {
                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
            }
        }).then(function(willDelete) {
            if (willDelete.isConfirmed) {
                $.ajax({
                    url: `/inventarios/deleteInsumo/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Eliminado!',
                                text: "El insumo fue eliminado correctamente.",
                                icon: 'success',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });

                            // Eliminar la fila de la tabla
                            $('#insumosAsignadosTable').DataTable().row(`tr[data-id=${id}]`).remove().draw(false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el insumo',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al eliminar el insumo.',
                            customClass: {
                                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                            }
                        });
                    }
                });
            }
        });
    });

    // Fin Seccion insumo

    // Seccion telefono

    $(document).on('click', '.edit-btn-linea', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = $(this).closest('tr');
        let id = row.data('id');

        document.getElementById('titulolinea').innerHTML = 'Editar Linea';

        // Asignar valores al formulario
        $('#editId_linea').val(id);
        $('#editId_linea2').val('');
        $('#editEmp_linea').val('');
        $('#editcomenl').val(row.find("td:eq(12)").text());
        $('#editfechalinea').val(fechaDisplayToInput(row.find("td:eq(11)").text()));
        $('#editMontoRenovacionFianza').val(row.find("td:eq(13)").text());
        // Convertir dd/mm/yyyy del <td> a yyyy-mm-dd para el hidden input
        $('#editFechaRenovacion').val(fechaDisplayToInput(row.find("td:eq(14)").text()));
        setPresupuestado('#editPresupuestadoLinea', row.find("td:eq(15)").text());

        $('#editModalLinea').modal('show');
    });
    $(document).on('click', '.crear-btn-linea', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editFormLinea')[0].reset();

        document.getElementById('titulolinea').innerHTML = 'Asignar Linea';
        let row = $(this).closest('tr');
        
        let boton = $(this);
        let id = boton.data('id');

        let monto = row.find("td:eq(10)").text();
        let fecha = row.find("td:eq(11)").text().trim();

        // Limpiar fecha si trae hora
        if (fecha.length > 10) {
            fecha = fecha.substring(0, 10);
        }

        // Si la fecha es un texto como 'Sin asignar', enviar vacío en vez del string
        if (fecha === 'Sin asignar' || fecha === 'Sin asigna' || fecha === '0000-00-00') {
            fecha = '';
        }

        $('#editId_linea').val('');
        $('#editId_linea2').val(id);
        $('#editEmp_linea').val(id_E);
        $('#editMontoRenovacionFianza').val(monto);
        $('#editFechaRenovacion').val(fecha);
        setPresupuestado('#editPresupuestadoLinea', 'No');

        $('#editModalLinea').modal('show');
    });


    $(document).on('click', '.submit_linea', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        $('.error-message').remove();
        $('.is-invalid').removeClass('is-invalid');

        let isValid = true;

        $('#editFormLinea [required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos obligatorios',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        let id = $('#editId_linea').val();
        let id2 = $('#editId_linea2').val();
        let id_E = $('#editEmp_linea').val();
        let url = id ? '/inventarios/editar-linea/' + id : '/inventarios/crear-linea/' + id_E + '/' + id2;
        let method = id ? 'PUT' : 'POST';

        // Limpiar FechaRenovacion: enviar vacío si tiene texto no-fecha
        let fechaRenov = $('#editFechaRenovacion').val();
        if (fechaRenov === 'Sin asignar' || fechaRenov === 'Sin asigna' || fechaRenov === '0000-00-00') {
            fechaRenov = '';
        }

        let formData = {
            FechaAsignacion: $('#editfechalinea').val(),
            Comentarios: $('#editcomenl').val(),
            MontoRenovacionFianza: $('#editMontoRenovacionFianza').val(),
            FechaRenovacion: fechaRenov,
            Presupuestado: getPresupuestado('#editPresupuestadoLinea')
        };

        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.errors) {

                    Object.keys(response.errors).forEach(field => {
                        const input = $(`#edit${field}`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(response.errors[field][0]);
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: 'Por favor revise los campos marcados en rojo',
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                } else {

                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Datos del telefonia guardado correctamente",
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });


                    if (id) {
                        updatetelefTableRow(response.telefono);
                    } else {
                        addtelefNewRow(response.telefono);
                    }

                    $('#editModalLinea').modal('hide');
                }
            },
            error: function(error) {
                console.error('Error:', error);
                let errorMessage = 'Ocurrió un error al guardar los datos';
                if (error.responseJSON && error.responseJSON.message) {
                    errorMessage = error.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    customClass: {
                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                    }
                });
            }
        });
    });


    function updatetelefTableRow(telefono) {
        let row = $(`tr[data-id=${telefono.InventarioID}]`);
        row.find('td:eq(11)').text(formatFechaRenovacion(telefono.FechaAsignacion));
        row.find('td:eq(12)').text(telefono.Comentarios);
        row.find('td:eq(13)').text(telefono.MontoRenovacionFianza);
        row.find('td:eq(14)').text(formatFechaRenovacion(telefono.FechaRenovacion));
        if (permitePresupuestado) {
            row.find('td:eq(15)').html(htmlChipPresupuestado(!!telefono.Presupuestado));
        }

        $('#lineasAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }


    function addtelefNewRow(telefono) {
        const table = $('#lineasAsignadosTable').DataTable();

        const newRow = [
            `<div class="index-actions">
                <button type="button" class="index-action index-action--edit edit-btn-linea" data-id="${telefono.InventarioID}" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <form method="POST" action="/inventarios/deleteL/${telefono.InventarioID}" class="index-action-form">
                    <button type="submit" class="index-action index-action--delete delete-btn-linea" data-id="${telefono.InventarioID}" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>`,
            telefono.NumTelefonico,
            telefono.Compania,
            telefono.PlanTel,
            telefono.CostoRentaMensual,
            telefono.CuentaPadre,
            telefono.CuentaHija,
            telefono.TipoLinea,
            telefono.Obra,
            formatFechaRenovacion(telefono.FechaFianza),
            telefono.CostoFianza,
            formatFechaRenovacion(telefono.FechaAsignacion),
            telefono.Comentarios,
            telefono.MontoRenovacionFianza,
            formatFechaRenovacion(telefono.FechaRenovacion)
        ];

        // La columna sólo existe para FISICA/EXTRAORDINARIO; DataTables exige que el
        // array tenga exactamente tantos elementos como columnas tenga la tabla.
        if (permitePresupuestado) {
            newRow.push(htmlChipPresupuestado(!!telefono.Presupuestado));
        }

        table.row.add(newRow).draw(false);
    }

    $(document).on('click', '.delete-btn-linea', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var id = $(this).data('id');

        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del telefono.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        Swal.fire({
            title: `Eliminar`,
            text: "¿Realmente desea eliminar este telefono asignado?",
            icon: "warning",
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cerrar',
            dangerMode: true,
            didOpen: () => {
                $('#editEmpleado').select2({
                    dropdownParent: $('.swal2-popup'),
                    width: '100%',
                    theme: 'classic'
                });

                $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                $('.swal2-title').addClass('dark:text-white');
            }
        }).then(function(willDelete) {
            if (willDelete.isConfirmed) {
                $.ajax({
                    url: `/inventarios/deleteL/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Eliminado!',
                                text: "El telefono fue eliminado correctamente.",
                                icon: 'success',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });

                            const table = $('#lineasAsignadosTable').DataTable();
                            table.row($(`.delete-btn-linea[data-id="${id}"]`).closest('tr')).remove().draw(false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el telefono',
                                customClass: {
                                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al eliminar el telefono.',
                            customClass: {
                                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                            }
                        });
                    }
                });
            }
        });
    });

    // Fin Seccion telefono
</script>



@endpush