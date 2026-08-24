@php
    $empleadoActivo = $empleadoActivo ?? ($inventario->Estado == 1 || $inventario->Estado === true);
    $permitePresupuestado = $permitePresupuestado ?? false;
    $presupuestadoForzado = $presupuestadoForzado ?? false;

    // Los equipos guardan la modalidad en "tipoEquipo"; insumos y líneas en "Presupuestado".
    $equiposStock = collect($equiposAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnInventario($e->tipoEquipo ?? 0));
    $equiposExtra = collect($equiposAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnPresupuesto($e->tipoEquipo ?? 0));
    $insumosStock = collect($insumosAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnInventario($e->Presupuestado ?? 0));
    $insumosExtra = collect($insumosAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnPresupuesto($e->Presupuestado ?? 0));
    $lineasStock = collect($LineasAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnInventario($e->Presupuestado ?? 0));
    $lineasExtra = collect($LineasAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnPresupuesto($e->Presupuestado ?? 0));

    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 0);
@endphp

<div class="tab-content">




    <!-- TAB Empleado -->
    <div class="tab-pane fade show active" id="empleados">
        <div class="index-page__card crud-page__card inv-empleado-card">
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
                <div class="inv-kpi-sub">{{ $equiposStock->count() }} equipo(s) · sale en inventario</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Extra / Presupuesto</div>
                <div class="inv-kpi-value">{{ $fmtMoney($equiposExtra->sum('Precio')) }}</div>
                <div class="inv-kpi-sub">{{ $equiposExtra->count() }} equipo(s) · sale en presupuesto</div>
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
                        <div class="inv-dual-title stock"><i class="fas fa-cube"></i> Inventario (<span class="conteo-no">{{ $equiposStock->count() }}</span>)</div>
                        <div class="inv-dual-empty">Stock y compartidos</div>
                        <div class="inv-money">{{ $fmtMoney($equiposStock->sum('Precio')) }}</div>
                    </div>
                    <div class="inv-dual-card" data-filtro="presupuestados">
                        <div class="inv-dual-title extra"><i class="fas fa-calendar-alt"></i> Presupuesto (<span class="conteo-si">{{ $equiposExtra->count() }}</span>)</div>
                        <div class="inv-dual-empty">Extra y compartidos</div>
                        <div class="inv-money">{{ $fmtMoney($equiposExtra->sum('Precio')) }}</div>
                    </div>
                </div>
                @endif

            <!-- equiposAsignados Seleccionados -->

                <div class="index-page__table-wrap table-responsive">
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
                        <tr data-id="{{ $equiposAsignado->InventarioID }}" data-meses="{{ $equiposAsignado->MesDePago }}">
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
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($equiposAsignado->tipoEquipo) !!}</td>
                            <td>@if($equiposAsignado->MesDePago)@include('inventarios.partials.meses-pills', ['mesesValor' => $equiposAsignado->MesDePago])@endif</td>
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


                                    <button type="button" class="index-action index-action--success crear-btn" data-id="{{ $equipo->CategoriaID }}" title="Asignar">
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
                <div class="inv-kpi-sub">Sale en inventario</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Extra / Presupuesto</div>
                <div class="inv-kpi-value">{{ $insumosExtra->count() }}</div>
                <div class="inv-kpi-sub">Sale en presupuesto</div>
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
                        <div class="inv-dual-title stock"><i class="fas fa-cube"></i> Inventario (<span class="conteo-no">{{ $insumosStock->count() }}</span>)</div>
                        <div class="inv-dual-empty">Stock y compartidos</div>
                    </div>
                    <div class="inv-dual-card" data-filtro="presupuestados">
                        <div class="inv-dual-title extra"><i class="fas fa-calendar-alt"></i> Presupuesto (<span class="conteo-si">{{ $insumosExtra->count() }}</span>)</div>
                        <div class="inv-dual-empty">Extra y compartidos</div>
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
                            <th>Calendario</th>
                            <th>Fecha de Renovacion</th>
                            <th>Observaciones</th>
                            <th>Fecha de Asignacion</th>
                            <th>Num. Serie</th>
                            <th>Comentarios</th>
                            <th>Meses de pago</th>
                            @if($permitePresupuestado)
                            <th>Stock / Extra</th>
                            @endif
                            <th>Licencia</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumosAsignados as $insumosAsignado)
                        <tr data-id="{{ $insumosAsignado->InventarioID }}" data-meses="{{ $insumosAsignado->MesDePago }}">
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
                            <td>{{ \App\Helpers\PagoMeses::etiqueta($insumosAsignado->MesDePago, $insumosAsignado->FrecuenciaDePago) }}</td>
                            <td>{{ (empty($insumosAsignado->FechaRenovacion) || in_array($insumosAsignado->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? 'Sin asignar' : \Carbon\Carbon::parse($insumosAsignado->FechaRenovacion)->format('d/m/Y') }}</td>
                            <td>{{ $insumosAsignado->Observaciones }}</td>
<td>{{ $insumosAsignado->FechaAsignacion ? \Carbon\Carbon::parse($insumosAsignado->FechaAsignacion)->format('d/m/Y') : 'Sin asignar' }}</td>                            <td>{{ $insumosAsignado->NumSerie }}</td>
                            <td>{{ $insumosAsignado->Comentarios }}</td>
                            <td>@include('inventarios.partials.meses-pills', ['mesesValor' => $insumosAsignado->MesDePago, 'mesesFrecuencia' => $insumosAsignado->FrecuenciaDePago])</td>
                            @if($permitePresupuestado)
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($insumosAsignado->Presupuestado) !!}</td>
                            @endif
                            <td>@if($insumosAsignado->LicenciaPirata)<span class="inv-chip inv-chip-pirata">Pirata</span>@endif</td>
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
                                <th>Fecha de Renovacion</th>
                                <th>Observaciones</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insumos as $insumo)
                            <tr>
                                <td>


                                    <button type="button" class="index-action index-action--success crear-btn-insumo" data-id="{{ $insumo->CategoriaID }}" title="Asignar">
                                        <i class="fas fa-plus"></i>
                                    </button>

                                </td>

                                <td>{{ $insumo->categorias->Categoria }}</td>
                                <td>{{ $insumo->NombreInsumo }}</td>
                                <td>{{ $insumo->CostoMensual }}</td>
                                <td>{{ $insumo->CostoAnual }}</td>
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
                <div class="inv-kpi-label">Inventario</div>
                <div class="inv-kpi-value">{{ $lineasStock->count() }}</div>
                <div class="inv-kpi-sub">Stock y compartidos</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Presupuesto</div>
                <div class="inv-kpi-value">{{ $lineasExtra->count() }}</div>
                <div class="inv-kpi-sub">Extra y compartidos</div>
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
                        <div class="inv-dual-title stock"><i class="fas fa-cube"></i> Inventario (<span class="conteo-no">{{ $lineasStock->count() }}</span>)</div>
                        <div class="inv-dual-empty">Stock y compartidos</div>
                    </div>
                    <div class="inv-dual-card" data-filtro="presupuestados">
                        <div class="inv-dual-title extra"><i class="fas fa-calendar-alt"></i> Presupuesto (<span class="conteo-si">{{ $lineasExtra->count() }}</span>)</div>
                        <div class="inv-dual-empty">Extra y compartidos</div>
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
                            <th>Meses de renta</th>



                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($LineasAsignados as $LineasAsignado)
                        <tr data-id="{{ $LineasAsignado->InventarioID }}" data-meses="{{ $LineasAsignado->MesDePago }}">
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
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($LineasAsignado->Presupuestado) !!}</td>
                            @endif
                            <td>@include('inventarios.partials.meses-pills', ['mesesValor' => $LineasAsignado->MesDePago])</td>

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


                                    <button type="button" class="index-action index-action--success crear-btn-linea" data-id="{{ $Linea->LineaID }}" title="Asignar">
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
@include('layouts.datatables_css')

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
@include('layouts.datatables_js')
@include('layouts.partials.index-page-js')

<script>
    var empleadoInventarioActivo = @json($empleadoActivo);
    var permitePresupuestado = @json($permitePresupuestado);
    var presupuestadoForzado = @json($presupuestadoForzado);

    $(document).off('.invAssign');

    // El switch sólo existe en el DOM para FISICA; en EXTRAORDINARIO todo lo
    // asignado es presupuestado y para el resto el campo viaja siempre en 0.
    // (El servidor vuelve a aplicar la regla, esto es sólo para la UI.)
    // Modalidades: 0 stock, 1 extra, 2 compartido, 3 propio (esta última sólo equipos).
    const MODO_STOCK = 0;
    const MODO_EXTRA = 1;
    const MODO_COMPARTIDO = 2;
    const MODO_PROPIO = 3;

    const MODOS = {
        0: { hint: 'stock',  etiqueta: 'Stock',      chip: 'inv-chip-stock'  },
        1: { hint: 'extra',  etiqueta: 'Extra',      chip: 'inv-chip-extra'  },
        2: { hint: 'share',  etiqueta: 'Compartido', chip: 'inv-chip-share'  },
        3: { hint: 'propio', etiqueta: 'Propio',     chip: 'inv-chip-propio' },
    };

    function tipoAsignacionDesdeTexto(texto) {
        const v = String(texto ?? '').trim().toLowerCase();
        if (v.indexOf('propio') !== -1) return MODO_PROPIO;
        if (v.indexOf('compart') !== -1) return MODO_COMPARTIDO;
        if (v === 'si' || v === '1' || v.indexOf('extra') !== -1 || v.indexOf('presupuest') !== -1) return MODO_EXTRA;
        return MODO_STOCK;
    }

    function htmlChipPresupuestado(valor) {
        const tipo = (valor === true || valor === '1') ? MODO_EXTRA : (parseInt(valor, 10) || MODO_STOCK);
        const modo = MODOS[tipo] || MODOS[MODO_STOCK];
        return '<span class="inv-chip ' + modo.chip + '">' + modo.etiqueta + '</span>';
    }

    function syncModoCards(selector, valor) {
        const $wrap = $('[data-switch="' + selector + '"]');
        if (!$wrap.length) return;

        const tipo = parseInt(valor, 10) || MODO_STOCK;
        const modo = MODOS[tipo] || MODOS[MODO_STOCK];

        $wrap.find('.inv-modo-card').removeClass('is-active');
        $wrap.find('.inv-modo-card[data-value="' + tipo + '"]').addClass('is-active');

        const sid = selector.replace(/^#/, '');
        $('[data-hint-for="' + sid + '"]').hide();
        $('[data-hint-for="' + sid + '"].' + modo.hint).css('display', 'flex');
        $(selector + 'Label').text(modo.etiqueta);

        if (selector === '#editPresupuestadoEquipo') {
            aplicarRequeridosEquipo(tipo === MODO_PROPIO);
        }
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

    // Sólo los insumos de categoría LICENCIA pueden ser piratas, y sólo en stock:
    // una licencia pirata no se paga, así que nunca se proyecta como gasto.
    function esCategoriaLicencia() {
        return String($('#editCategoriaInsumo').val() ?? '').toLowerCase().indexOf('licencia') !== -1;
    }

    // El modal se rellena por pasos; al terminar de abrirse ya está todo puesto.
    $(document).on('shown.bs.modal', '#editModalInsumo', refrescarLicenciaPirata);

    function refrescarLicenciaPirata() {
        const esStock = getPresupuestado('#editPresupuestadoInsumo') !== 1;
        const aplica = esStock && esCategoriaLicencia();

        $('.insumo-solo-stock').toggle(aplica);

        if (!aplica) {
            setLicenciaPirata(false);
        }
    }

    // El checkbox real queda oculto; la card es la que se ve y se pulsa.
    function setLicenciaPirata(activa) {
        const marcada = !!activa;
        $('#editLicenciaPirata').prop('checked', marcada);
        $('#editLicenciaPirataCard')
            .toggleClass('is-active', marcada)
            .attr('aria-pressed', marcada ? 'true' : 'false');
    }

    $(document).on('click', '.inv-pirata-card', function() {
        setLicenciaPirata(!$('#' + $(this).data('target')).is(':checked'));
    });

    function setPresupuestado(selector, texto) {
        const tipo = presupuestadoForzado ? 1 : tipoAsignacionDesdeTexto(texto);
        $(selector).val(tipo);
        syncModoCards(selector, tipo);
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

        return parseInt($(selector).val(), 10) || 0;
    }

    $(document).on('click.invAssign', '.inv-modo-card:not(.is-locked)', function() {
        const $card = $(this);
        const selector = $card.closest('[data-switch]').data('switch');
        const value = parseInt($card.data('value'), 10) || 0;
        $(selector).val(value);
        syncModoCards(selector, value);
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
        var invDtLang = {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_',
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
        var invDtDom = "<'index-page__dt-toolbar'f>t<'index-page__dt-footer'ip>";
        var invDtBase = {
            responsive: true,
            paging: true,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            dom: invDtDom,
            language: invDtLang
        };

        function initInvDt(selector, extra) {
            if (!$(selector).length) return;
            if ($.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().destroy();
            }
            $(selector).DataTable($.extend(true, {}, invDtBase, extra || {}));
        }

        initInvDt('#equiposTable');
        initInvDt('#insumosTable');
        initInvDt('#lineasTable');
        initInvDt('#equiposAsignadosTable', {
            columnDefs: [
                { visible: false, targets: [3, 6, 7] }
            ]
        });
        initInvDt('#insumosAsignadosTable');
        initInvDt('#lineasAsignadosTable');

        inicializarFiltrosPresupuestado();
    });
</script>

<script>
    // Índice de la columna "Presupuestado" en cada tabla de asignados.
    // La columna sólo se pinta para FISICA/EXTRAORDINARIO.
    var columnaPresupuestado = {
        equiposAsignadosTable: 12,
        insumosAsignadosTable: 12,
        lineasAsignadosTable: 15,
    };

    // Filtro activo por tabla: todos | presupuestados | no_presupuestados
    var filtroPresupuestado = {
        equiposAsignadosTable: 'todos',
        insumosAsignadosTable: 'todos',
        lineasAsignadosTable: 'todos',
    };

    var mesesPagoTodos = {!! json_encode(\App\Helpers\PagoMeses::MESES) !!};
    var mesesPagoTodosStr = mesesPagoTodos.join(',');

    function etiquetaMesesPago(valor) {
        var sel = String(valor || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        if (sel.length === 0) return 'Sin meses';
        if (sel.length === 12) return 'Anual (12 meses)';
        if (sel.length === 1) return sel[0];
        return 'Parcial (' + sel.length + ' meses)';
    }

    function htmlPillsMeses(valor) {
        var sel = String(valor || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        if (!sel.length) return '<span class="text-muted">—</span>';
        if (sel.length === 12) return '<span class="inv-mes-pill">Anual</span>';
        return '<span class="inv-meses-pills">' + sel.map(function(m) {
            return '<span class="inv-mes-pill">' + m.substring(0, 3) + '</span>';
        }).join('') + '</span>';
    }

    function syncPagoMeses($root) {
        var values = [];
        $root.find('input[type=checkbox]').each(function() {
            if (this.checked) values.push(this.value);
            $(this).closest('.pago-meses__chip').toggleClass('is-on', this.checked);
        });
        var hiddenId = $root.data('pago-meses');
        $('#' + hiddenId).val(values.join(','));
        var n = values.length;
        var hint = 'Sin meses seleccionados.';
        if (n === 12) hint = 'Anual: el costo entra los 12 meses.';
        else if (n === 1) hint = 'Un mes: ' + values[0] + '.';
        else if (n > 1) hint = 'Parcialidad: ' + n + ' meses.';
        $root.find('[data-meses-hint]').text(hint);
    }

    function setPagoMeses(hiddenId, valor) {
        var id = String(hiddenId || '').replace('#', '');
        var $root = $('[data-pago-meses="' + id + '"]');
        var set = String(valor || '').split(',').map(function(s) {
            return s.trim().toUpperCase();
        }).filter(Boolean);
        $root.find('input[type=checkbox]').each(function() {
            this.checked = set.indexOf(String(this.value).toUpperCase()) !== -1;
        });
        syncPagoMeses($root);
    }

    $(document).off('.pagoMeses');
    $(document).on('change.pagoMeses', '.pago-meses input[type=checkbox]', function() {
        syncPagoMeses($(this).closest('.pago-meses'));
    });
    $(document).on('click.pagoMeses', '.pago-meses [data-meses-accion]', function() {
        var $root = $(this).closest('.pago-meses');
        var anual = $(this).data('meses-accion') === 'anual';
        $root.find('input[type=checkbox]').prop('checked', anual);
        syncPagoMeses($root);
    });

    function tipoFilaAsignacion(valorCelda) {
        return tipoAsignacionDesdeTexto(valorCelda);
    }

    window.__invFiltroPresupuestadoFn = function(settings, data) {
        var tablaId = settings.nTable.id;
        var filtro = filtroPresupuestado[tablaId];

        if (!permitePresupuestado || !filtro || filtro === 'todos') {
            return true;
        }

        var tipo = tipoFilaAsignacion(data[columnaPresupuestado[tablaId]]);
        if (filtro === 'presupuestados') {
            return tipo === MODO_EXTRA || tipo === MODO_COMPARTIDO;
        }
        if (filtro === 'no_presupuestados') {
            return tipo === MODO_STOCK || tipo === MODO_COMPARTIDO || tipo === MODO_PROPIO;
        }
        if (filtro === 'compartidos') {
            return tipo === MODO_COMPARTIDO;
        }

        return true;
    };

    if (!window.__invFiltroPresupuestadoDt) {
        window.__invFiltroPresupuestadoDt = true;
        $.fn.dataTable.ext.search.push(function(settings, data) {
            return window.__invFiltroPresupuestadoFn(settings, data);
        });
    }

    function actualizarConteos(tablaId) {
        if (!permitePresupuestado) {
            return;
        }

        const dt = $('#' + tablaId).DataTable();
        let inventario = 0;
        let presupuesto = 0;
        let total = 0;

        dt.column(columnaPresupuestado[tablaId], { search: 'none' }).data().each(function(valor) {
            const tipo = tipoFilaAsignacion(valor);
            total++;
            if (tipo !== MODO_EXTRA) inventario++;
            if (tipo === MODO_EXTRA || tipo === MODO_COMPARTIDO) presupuesto++;
        });

        const barra = $('.inventario-filtros[data-tabla="' + tablaId + '"]');
        barra.find('.conteo-todos').text(total);
        barra.find('.conteo-si').text(presupuesto);
        barra.find('.conteo-no').text(inventario);

        const dual = $('.inv-dual[data-tabla="' + tablaId + '"]');
        dual.find('.conteo-si').text(presupuesto);
        dual.find('.conteo-no').text(inventario);
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

    $(document).on('click.invAssign', '.pill-filtro', function() {
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

    $(document).on('click.invAssign', '.inv-dual-card', function() {
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

    $(document).on('keyup.invAssign', '.inv-table-search', function() {
        const tablaId = $(this).data('tabla');
        if ($.fn.DataTable.isDataTable('#' + tablaId)) {
            $('#' + tablaId).DataTable().search(this.value).draw();
        }
    });
</script>

<script>
    $(document).ready(function() {
        $('#myTab [data-inv-tab]').on('click', function(event) {
            event.preventDefault();
            var target = $(this).attr('data-inv-tab');

            $('#myTab [data-inv-tab]').removeClass('is-active active');
            $('.tab-content > .tab-pane').removeClass('show active');

            $(this).addClass('is-active active');
            $(target).addClass('show active');

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
    $(document).on('click.invAssign', '.edit-btn', function() {
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
        $('#editCategoria').val(row.find("td:eq(1)").text());
        $('#editMarca').val(row.find("td:eq(2)").text());
        $('#editCaracteristicas').val(row.find("td:eq(3)").text());
        $('#editModelo').val(row.find("td:eq(4)").text());
        $('#editPrecio').val(row.find("td:eq(5)").text());
        $('#editFechaAsignacion').val(row.find("td:eq(6)").text());
        $('#editFechaDeCompra').val(row.find("td:eq(7)").text());
        $('#editNumSerie').val(row.find("td:eq(8)").text());
        $('#editFolio').val(row.find("td:eq(9)").text());
        $('#editGerenciaEquipo').val(row.find("td:eq(10)").data('id')).trigger('change');
        $('#editComentarios').val(row.find("td:eq(11)").text());
        setPresupuestado('#editPresupuestadoEquipo', row.find("td:eq(12)").text());
        setPagoMeses('editMesDePagoEquipo', row.attr('data-meses') || '');

        $('#editModal').modal('show');
    });

    // Crear equipo (con valores vacíos para nuevo registro)
    $(document).on('click.invAssign', '.crear-btn', function() {
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
        setPagoMeses('editMesDePagoEquipo', '');

        $('#editModal').modal('show');
    });

    // Validación en tiempo real del Folio (al escribir o al salir del campo)
    var folioTimer = null;
    var folioValido = true; // Estado de validez del folio actual

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
    $(document).on('focus.invAssign', '#editFolio', function() {
        cargarUltimosFolios();
        $('#folio-Info').fadeIn(200);
    });

    // Ocultar la advertencia al perder el foco
    $(document).on('blur.invAssign', '#editFolio', function() {
        $('#folio-Info').fadeOut(200);
    });

    $(document).on('input.invAssign', '#editFolio', function() {
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
    $(document).on('click.invAssign', '.submit_equipo', function(event) {
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
        let folio = getModo('#editPresupuestadoEquipo') === MODO_PROPIO ? '' : $('#editFolio').val().trim();
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
        const soloEmpresa = (valor) => modo === MODO_PROPIO ? '' : valor;

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
            row.find('td:eq(12)').html(htmlChipPresupuestado(equipo.Presupuestado));
            row.find('td:eq(13)').html(htmlPillsMeses(equipo.MesDePago ?? ''));
        }
        row.attr('data-meses', equipo.MesDePago ?? '');
        row.find('.edit-btn').data('id', equipo.InventarioID);

        // Refrescar la caché de DataTables para que el filtro y los conteos vean el cambio.
        $('#equiposAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }

    // Agregar una nueva fila en la tabla (para equipo creado)
    function addNewRow(equipo) {
        let newRow = `
        <tr data-id="${equipo.InventarioID}" data-meses="${equipo.MesDePago ?? ''}">
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
            ${permitePresupuestado ? `<td>${htmlChipPresupuestado(equipo.Presupuestado)}</td><td>${htmlPillsMeses(equipo.MesDePago ?? '')}</td>` : ''}
        </tr>
    `;
        $('#equiposAsignadosTable').DataTable().row.add($(newRow)).draw(false);
    }

    // Eliminar equipo con AJAX
    $(document).on('click.invAssign', '.delete-btn', function(event) {
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

    $(document).on('click.invAssign', '.edit-btn-insum', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = $(this).closest('tr');
        let id = row.data('id');

        // Asignar valores al formulario
        document.getElementById('tituloinsumo').innerHTML = 'Editar insumo';

        $('#editId_insumo').val(id);
        $('#editEmp_insumo').val('');
        // data-categoria es la fuente confiable; el <td> se corre si DataTables colapsa columnas.
        $('#editCategoriaInsumo').val(row.data('categoria') ?? row.find("td:eq(1)").text());
        $('#editNombreInsumo').val(row.find("td:eq(2)").text());
        $('#editCostoMensual').val(row.find("td:eq(3)").text());
        $('#editCostoAnual').val(row.find("td:eq(4)").text());
        $('#editFechaDeRenovacion').val(fechaDisplayToInput(row.find("td:eq(6)").text()));
        $('#editobserv').val(row.find("td:eq(7)").text());
        $('#editFechaDeAsigna').val(fechaDisplayToInput(row.find("td:eq(8)").text()));
        $('#editNumSerieInsu').val(row.find("td:eq(9)").text());
        $('#editComentariosInsumo').val(row.find("td:eq(10)").text());
        setPagoMeses('editMesDePago', row.attr('data-meses') || '');
        setPresupuestado('#editPresupuestadoInsumo', row.find("td:eq(12)").text());
        setLicenciaPirata(String(row.data('pirata')) === '1');
        // Va al final: apaga la bandera si el insumo no es licencia o es presupuestado.
        refrescarLicenciaPirata();

        $('#editModalInsumo').modal('show');
    });

    $(document).on('click.invAssign', '.crear-btn-insumo', function() {
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
        let fecharenovacion = row.find("td:eq(5)").text().trim();
        // Si la fecha trae hora (ej. 2026-04-29 00:00:00), tomamos solo los primeros 10 caracteres
        if (fecharenovacion.length > 10) {
            fecharenovacion = fecharenovacion.substring(0, 10);
        }
        // Si la fecha es un texto como 'Sin asignar', enviar vacío en vez del string
        if (fecharenovacion === 'Sin asignar' || fecharenovacion === 'Sin asigna' || fecharenovacion === '0000-00-00') {
            fecharenovacion = '';
        }
        let observaciones = row.find("td:eq(6)").text();

        $('#editCategoriaInsumo').val(categoria);
        $('#editNombreInsumo').val(nombreinsumo);
        $('#editCostoMensual').val(costomensual);
        $('#editCostoAnual').val(costoanual);
        $('#editFechaDeRenovacion').val(fecharenovacion);
        $('#editobserv').val(observaciones);
        $('#editId_insumo').val('');
        $('#editEmp_insumo').val(id_E);
        setPresupuestado('#editPresupuestadoInsumo', 'No');
        setPagoMeses('editMesDePago', mesesPagoTodosStr);

        $('#editModalInsumo').modal('show');
    });


    $(document).on('click.invAssign', '.submit_insumo', function(event) {
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

        if (!$('#editMesDePago').val()) {
            isValid = false;
        }

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Seleccione al menos un mes de pago y complete los campos obligatorios',
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
            FechaRenovacion: fechaRenovInsumo,
            Observaciones: $('#editobserv').val(),
            FechaAsignacion: $('#editFechaDeAsigna').val(),
            NumSerie: $('#editNumSerieInsu').val(),
            Comentarios: $('#editComentariosInsumo').val(),
            MesDePago: $('#editMesDePago').val(),
            Presupuestado: getPresupuestado('#editPresupuestadoInsumo'),
            LicenciaPirata: $('#editLicenciaPirata').is(':checked') ? 1 : 0,
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
        row.find('td:eq(5)').text(etiquetaMesesPago(insumo.MesDePago));
        row.find('td:eq(6)').text(formatFechaRenovacion(insumo.FechaRenovacion));
        row.find('td:eq(7)').text(insumo.Observaciones);
        row.find('td:eq(8)').text(formatFechaRenovacion(insumo.FechaAsignacion));
        row.find('td:eq(9)').text(insumo.NumSerie);
        row.find('td:eq(10)').text(insumo.Comentarios);
        row.find('td:eq(11)').html(htmlPillsMeses(insumo.MesDePago));
        row.attr('data-meses', insumo.MesDePago ?? '');
        if (permitePresupuestado) {
            row.find('td:eq(12)').html(htmlChipPresupuestado(insumo.Presupuestado));
        }
        // .data() cachea, así que hay que actualizar ambos para que el modal relea bien.
        row.attr('data-pirata', insumo.LicenciaPirata ? 1 : 0).data('pirata', insumo.LicenciaPirata ? 1 : 0);
        row.attr('data-categoria', insumo.CateogoriaInsumo).data('categoria', insumo.CateogoriaInsumo);
        row.find('td').last().html(htmlChipPirata(insumo.LicenciaPirata));

        $('#insumosAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }


    function addinsumoNewRow(insumo) {
        let newRow = `
        <tr data-id="${insumo.InventarioID}" data-meses="${insumo.MesDePago ?? ''}">
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
            <td>${etiquetaMesesPago(insumo.MesDePago)}</td>
            <td>${formatFechaRenovacion(insumo.FechaRenovacion)}</td>
            <td>${insumo.Observaciones}</td>
            <td>${formatFechaRenovacion(insumo.FechaAsignacion)}</td>
            <td>${insumo.NumSerie}</td>
            <td>${insumo.Comentarios}</td>
            <td>${htmlPillsMeses(insumo.MesDePago)}</td>
            ${permitePresupuestado ? `<td>${htmlChipPresupuestado(insumo.Presupuestado)}</td>` : ''}
        </tr>
    `;
        $('#insumosAsignadosTable').DataTable().row.add($(newRow)).draw(false);
    }

    $(document).on('click.invAssign', '.delete-btn-insumo', function(event) {
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

    $(document).on('click.invAssign', '.edit-btn-linea', function() {
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
        setPagoMeses('editMesDePagoLinea', row.attr('data-meses') || '');

        $('#editModalLinea').modal('show');
    });
    $(document).on('click.invAssign', '.crear-btn-linea', function() {
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
        setPagoMeses('editMesDePagoLinea', mesesPagoTodosStr);

        $('#editModalLinea').modal('show');
    });


    $(document).on('click.invAssign', '.submit_linea', function(event) {
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

        if (!$('#editMesDePagoLinea').val()) {
            isValid = false;
        }

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Seleccione al menos un mes de renta y complete los campos obligatorios',
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
            Presupuestado: getPresupuestado('#editPresupuestadoLinea'),
            MesDePago: $('#editMesDePagoLinea').val()
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
            row.find('td:eq(15)').html(htmlChipPresupuestado(telefono.Presupuestado));
            row.find('td:eq(16)').html(htmlPillsMeses(telefono.MesDePago));
        } else {
            row.find('td:eq(15)').html(htmlPillsMeses(telefono.MesDePago));
        }
        row.attr('data-meses', telefono.MesDePago ?? '');

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
            newRow.push(htmlChipPresupuestado(telefono.Presupuestado));
        }
        newRow.push(htmlPillsMeses(telefono.MesDePago));

        var dtRow = $('#lineasAsignadosTable').DataTable().row.add(newRow);
        $(dtRow.node()).attr('data-id', telefono.InventarioID).attr('data-meses', telefono.MesDePago || '');
        dtRow.draw(false);
    }

    $(document).on('click.invAssign', '.delete-btn-linea', function(event) {
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