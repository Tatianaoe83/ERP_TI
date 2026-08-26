@php
    $empleadoActivo = $empleadoActivo ?? ($inventario->Estado == 1 || $inventario->Estado === true);
    $permitePresupuestado = $permitePresupuestado ?? false;
    $presupuestadoForzado = $presupuestadoForzado ?? false;

    $equiposStock = collect($equiposAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnInventario($e->Presupuestado ?? 0));
    $equiposExtra = collect($equiposAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnPresupuesto($e->Presupuestado ?? 0));
    $insumosStock = collect($insumosAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnInventario($e->Presupuestado ?? 0));
    $insumosExtra = collect($insumosAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnPresupuesto($e->Presupuestado ?? 0));
    $lineasStock = collect($LineasAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnInventario($e->Presupuestado ?? 0));
    $lineasExtra = collect($LineasAsignados)->filter(fn ($e) => \App\Helpers\PresupuestoAsignacion::entraEnPresupuesto($e->Presupuestado ?? 0));

    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 0);

    $esVacioInv = function ($valor) {
        if ($valor === null || $valor === '') {
            return true;
        }
        $v = mb_strtolower(trim((string) $valor), 'UTF-8');

        return in_array($v, ['null', 'sin asignar', 'sin asigna', 'pendiente'], true);
    };

    $celdaPendiente = function ($valor, $esExtra) use ($esVacioInv) {
        if ($esVacioInv($valor)) {
            return $esExtra ? '<span class="inv-pendiente">Pendiente</span>' : '';
        }

        return e($valor);
    };

    $fechaPendiente = function ($fecha, $esExtra) {
        $vacia = empty($fecha) || in_array($fecha, ['Sin asignar', 'Sin asigna', '0000-00-00', 'null'], true);
        if ($vacia) {
            return $esExtra ? '<span class="inv-pendiente">Pendiente</span>' : 'Sin asignar';
        }

        return e(\Carbon\Carbon::parse($fecha)->format('d/m/Y'));
    };
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
                <div class="inv-kpi-value" id="kpi-equipos-total" data-kpi="total">{{ $equiposAsignados->count() }}</div>
                <div class="inv-kpi-sub">En resguardo / proyección</div>
            </div>
            @if($permitePresupuestado)
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Stock</div>
                <div class="inv-kpi-value" data-kpi="stock-money">{{ $fmtMoney($equiposStock->sum('Precio')) }}</div>
                <div class="inv-kpi-sub" data-kpi="stock-sub">{{ $equiposStock->count() }} equipo(s) · sale en inventario</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Extra / Presupuesto</div>
                <div class="inv-kpi-value" data-kpi="extra-money">{{ $fmtMoney($equiposExtra->sum('Precio')) }}</div>
                <div class="inv-kpi-sub" data-kpi="extra-sub">{{ $equiposExtra->count() }} equipo(s) · sale en presupuesto</div>
            </div>
            @else
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Solo stock</div>
                <div class="inv-kpi-value" data-kpi="solo-stock-money">{{ $fmtMoney(collect($equiposAsignados)->sum('Precio')) }}</div>
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
                @include('inventarios.partials.bulk-asignacion', [
                    'tabla' => 'equiposAsignadosTable',
                    'tipo' => 'equipo',
                ])

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
                            <th>Estatus</th>
                            <th>Mes de pago</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equiposAsignados as $equiposAsignado)
                        @php $esExtraEquipo = (int) $equiposAsignado->Presupuestado === \App\Helpers\PresupuestoAsignacion::EXTRA; @endphp
                        <tr data-id="{{ $equiposAsignado->InventarioID }}"
                            data-meses="{{ $equiposAsignado->MesDePago }}"
                            data-presupuestado="{{ $equiposAsignado->Presupuestado }}"
                            data-categoria="{{ $equiposAsignado->CategoriaEquipo }}"
                            data-marca="{{ $equiposAsignado->Marca }}"
                            data-caracteristicas="{{ $equiposAsignado->Caracteristicas }}"
                            data-modelo="{{ $equiposAsignado->Modelo }}"
                            data-precio="{{ $equiposAsignado->Precio }}"
                            data-fecha-asignacion="{{ $equiposAsignado->FechaAsignacion ? \Carbon\Carbon::parse($equiposAsignado->FechaAsignacion)->toDateString() : '' }}"
                            data-fecha-compra="{{ $equiposAsignado->FechaDeCompra ? \Carbon\Carbon::parse($equiposAsignado->FechaDeCompra)->toDateString() : '' }}"
                            data-num-serie="{{ $equiposAsignado->NumSerie }}"
                            data-folio="{{ $equiposAsignado->Folio }}"
                            data-gerencia-id="{{ $equiposAsignado->GerenciaEquipoID }}"
                            data-comentarios="{{ $equiposAsignado->Comentarios }}">
                            <td>
                                @if($empleadoActivo)
                                <div class="index-actions">
                                    @if($permitePresupuestado && !$presupuestadoForzado)
                                        @if((int) $equiposAsignado->Presupuestado === 1)
                                        <span class="inv-check inv-check--off" title="Extra: ábralo para cambiar el tipo"></span>
                                        @else
                                        <label class="inv-check" title="Seleccionar">
                                            <input type="checkbox" class="inv-bulk-check" data-tipo="equipo" data-id="{{ $equiposAsignado->InventarioID }}" data-modo="{{ (int) $equiposAsignado->Presupuestado }}">
                                        </label>
                                        @endif
                                    @endif
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
                            <td>{!! $celdaPendiente($equiposAsignado->Precio, $esExtraEquipo) !!}</td>
                            <td>{!! $fechaPendiente($equiposAsignado->FechaAsignacion, $esExtraEquipo) !!}</td>
                            <td>{!! $fechaPendiente($equiposAsignado->FechaDeCompra, $esExtraEquipo) !!}</td>
                            <td>{!! $celdaPendiente($equiposAsignado->NumSerie, $esExtraEquipo) !!}</td>
                            <td>{!! $celdaPendiente($equiposAsignado->Folio, $esExtraEquipo) !!}</td>
                            <td data-id="{{ $equiposAsignado->GerenciaEquipoID }}">{!! $celdaPendiente($equiposAsignado->GerenciaEquipo, $esExtraEquipo) !!}</td>
                            <td>{!! $celdaPendiente($equiposAsignado->Comentarios, $esExtraEquipo) !!}</td>
                            @if($permitePresupuestado)
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($equiposAsignado->Presupuestado) !!}</td>
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
                <div class="inv-kpi-value" data-kpi="total">{{ $insumosAsignados->count() }}</div>
                <div class="inv-kpi-sub">En resguardo / proyección</div>
            </div>
            @if($permitePresupuestado)
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Stock</div>
                <div class="inv-kpi-value" data-kpi="stock-count">{{ $insumosStock->count() }}</div>
                <div class="inv-kpi-sub">Sale en inventario</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Extra / Presupuesto</div>
                <div class="inv-kpi-value" data-kpi="extra-count">{{ $insumosExtra->count() }}</div>
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

                @include('inventarios.partials.bulk-asignacion', [
                    'tabla' => 'insumosAsignadosTable',
                    'tipo' => 'insumo',
                ])

            <div class="table-responsive">
                <table id="insumosAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Categoria Insumo</th>
                            <th>Nombre Insumo</th>
                            <th>Costo Mensual</th>
                            <th>Costo Anual</th>
                            <th>Fecha de Renovacion</th>
                            <th>Observaciones</th>
                            <th>Fecha de Asignacion</th>
                            <th>Num. Serie</th>
                            <th>Comentarios</th>
                            @if($permitePresupuestado)
                            <th>Estatus</th>
                            @endif
                            <th>Mes de pago</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumosAsignados as $insumosAsignado)
                        @php $esExtraInsumo = (int) $insumosAsignado->Presupuestado === \App\Helpers\PresupuestoAsignacion::EXTRA; @endphp
                        <tr data-id="{{ $insumosAsignado->InventarioID }}" data-meses="{{ $insumosAsignado->MesDePago }}" data-presupuestado="{{ $insumosAsignado->Presupuestado }}" data-categoria="{{ $insumosAsignado->CateogoriaInsumo }}" data-nombre="{{ $insumosAsignado->NombreInsumo }}" data-costo-mensual="{{ $insumosAsignado->CostoMensual }}" data-costo-anual="{{ $insumosAsignado->CostoAnual }}">
                            <td>
                                @if($empleadoActivo)
                                <div class="index-actions">
                                    @if($permitePresupuestado && !$presupuestadoForzado)
                                        @if((int) $insumosAsignado->Presupuestado === 1)
                                        <span class="inv-check inv-check--off" title="Extra: ábralo para cambiar el tipo"></span>
                                        @else
                                        <label class="inv-check" title="Seleccionar">
                                            <input type="checkbox" class="inv-bulk-check" data-tipo="insumo" data-id="{{ $insumosAsignado->InventarioID }}" data-modo="{{ (int) $insumosAsignado->Presupuestado }}">
                                        </label>
                                        @endif
                                    @endif
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
                            <td>{!! $fechaPendiente($insumosAsignado->FechaRenovacion, false) !!}</td>
                            <td>{{ $insumosAsignado->Observaciones }}</td>
                            <td>{!! $fechaPendiente($insumosAsignado->FechaAsignacion, $esExtraInsumo) !!}</td>
                            <td>{!! $celdaPendiente($insumosAsignado->NumSerie, $esExtraInsumo) !!}</td>
                            <td>{!! $celdaPendiente($insumosAsignado->Comentarios, $esExtraInsumo) !!}</td>
                            @if($permitePresupuestado)
                            <td>{!! \App\Helpers\PresupuestoAsignacion::chipHtml($insumosAsignado->Presupuestado) !!}</td>
                            @endif
                            <td>@include('inventarios.partials.meses-pills', ['mesesValor' => $insumosAsignado->MesDePago, 'mesesFrecuencia' => $insumosAsignado->FrecuenciaDePago])</td>
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
                            <tr data-insumo-id="{{ $insumo->ID }}"
                                data-categoria="{{ optional($insumo->categorias)->Categoria }}"
                                data-nombre="{{ $insumo->NombreInsumo }}"
                                data-costo-mensual="{{ $insumo->CostoMensual }}"
                                data-costo-anual="{{ $insumo->CostoAnual }}">
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
                <div class="inv-kpi-value" data-kpi="total">{{ $LineasAsignados->count() }}</div>
                <div class="inv-kpi-sub">En uso / proyección</div>
            </div>
            @if($permitePresupuestado)
            <div class="inv-kpi inv-kpi-stock">
                <div class="inv-kpi-label">Inventario</div>
                <div class="inv-kpi-value" data-kpi="stock-count">{{ $lineasStock->count() }}</div>
                <div class="inv-kpi-sub">Stock y compartidos</div>
            </div>
            <div class="inv-kpi inv-kpi-extra">
                <div class="inv-kpi-label">Presupuesto</div>
                <div class="inv-kpi-value" data-kpi="extra-count">{{ $lineasExtra->count() }}</div>
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
                @if($empleadoActivo && $permitePresupuestado)
                <button type="button" class="crear-btn-linea-extra" title="Proyección extra: solo plan, sin reservar del catálogo">
                    <i class="fas fa-calendar-plus"></i> Agregar proyección extra
                </button>
                @endif
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

                @include('inventarios.partials.bulk-asignacion', [
                    'tabla' => 'lineasAsignadosTable',
                    'tipo' => 'linea',
                ])

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
                            <th>Estatus</th>
                            @endif
                            <th>Mes de pago</th>



                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($LineasAsignados as $LineasAsignado)
                        @php
                            $esProyLinea = empty($LineasAsignado->LineaID) && (int) $LineasAsignado->Presupuestado === \App\Helpers\PresupuestoAsignacion::EXTRA;
                            $obraLinea = $LineasAsignado->Obra ?: ($LineasAsignado->lineastelefonicas->obras->NombreObra ?? 'Sin asignar');
                            $pendiente = '<span class="inv-pendiente">Pendiente</span>';
                        @endphp
                        <tr
                            data-id="{{ $LineasAsignado->InventarioID }}"
                            data-meses="{{ $LineasAsignado->MesDePago }}"
                            data-linea-id="{{ $LineasAsignado->LineaID }}"
                            data-plan-id="{{ $LineasAsignado->PlanID }}"
                            data-tipo="{{ $LineasAsignado->TipoLinea }}"
                            data-obra-id="{{ $LineasAsignado->ObraID }}"
                            data-renta="{{ $LineasAsignado->CostoRentaMensual }}"
                            data-compania="{{ $LineasAsignado->Compania }}"
                            data-fianza="{{ $LineasAsignado->CostoFianza }}"
                            data-num="{{ $LineasAsignado->NumTelefonico }}"
                            data-cuenta-padre="{{ $LineasAsignado->CuentaPadre }}"
                            data-cuenta-hija="{{ $LineasAsignado->CuentaHija }}"
                            data-fecha-fianza="{{ $LineasAsignado->FechaFianza ? \Carbon\Carbon::parse($LineasAsignado->FechaFianza)->format('Y-m-d') : '' }}"
                            data-fecha-asig="{{ $LineasAsignado->FechaAsignacion ? \Carbon\Carbon::parse($LineasAsignado->FechaAsignacion)->format('Y-m-d') : '' }}"
                            data-comentarios="{{ $LineasAsignado->Comentarios }}"
                            data-monto-renov="{{ $LineasAsignado->MontoRenovacionFianza }}"
                            data-fecha-renov="{{ (empty($LineasAsignado->FechaRenovacion) || in_array($LineasAsignado->FechaRenovacion, ['Sin asignar', 'Sin asigna', '0000-00-00'])) ? '' : \Carbon\Carbon::parse($LineasAsignado->FechaRenovacion)->format('Y-m-d') }}"
                            data-presupuestado="{{ $LineasAsignado->Presupuestado }}"
                        >
                            <td>
                                @if($empleadoActivo)
                                <div class="index-actions">
                                    @if($permitePresupuestado && !$presupuestadoForzado)
                                        @if((int) $LineasAsignado->Presupuestado === 1)
                                        <span class="inv-check inv-check--off" title="Extra: ábralo para cambiar el tipo"></span>
                                        @else
                                        <label class="inv-check" title="Seleccionar">
                                            <input type="checkbox" class="inv-bulk-check" data-tipo="linea" data-id="{{ $LineasAsignado->InventarioID }}" data-modo="{{ (int) $LineasAsignado->Presupuestado }}">
                                        </label>
                                        @endif
                                    @endif
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


                            <td>{!! $esProyLinea && empty($LineasAsignado->NumTelefonico) ? $pendiente : e($LineasAsignado->NumTelefonico) !!}</td>
                            <td>{{ $LineasAsignado->Compania}}</td>
                            <td>{{ $LineasAsignado->PlanTel}}</td>
                            <td>{{ $LineasAsignado->CostoRentaMensual}}</td>
                            <td>{!! $esProyLinea && empty($LineasAsignado->CuentaPadre) ? $pendiente : e($LineasAsignado->CuentaPadre) !!}</td>
                            <td>{!! $esProyLinea && empty($LineasAsignado->CuentaHija) ? $pendiente : e($LineasAsignado->CuentaHija) !!}</td>
                            <td>{{ $LineasAsignado->TipoLinea}}</td>  
                            <td>{{ $obraLinea }}</td>
                            <td>{!! $esProyLinea && empty($LineasAsignado->FechaFianza) ? $pendiente : ($LineasAsignado->FechaFianza ? e(\Carbon\Carbon::parse($LineasAsignado->FechaFianza)->format('d/m/Y')) : '') !!}</td>
                            <td>{{ $LineasAsignado->CostoFianza}}</td>
                            <td>{!! $esProyLinea && empty($LineasAsignado->FechaAsignacion) ? $pendiente : ($LineasAsignado->FechaAsignacion ? e(\Carbon\Carbon::parse($LineasAsignado->FechaAsignacion)->format('d/m/Y')) : '') !!}</td>
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
                <span><i class="fas fa-plus-circle mr-1"></i> Líneas disponibles (stock)</span>
            </div>
            <div class="inv-panel-body">
            @if($permitePresupuestado)
            <p class="inv-linea-hint mb-3">Para presupuesto extra no reserve una línea de aquí. Use <strong>Agregar proyección extra</strong>: solo plan y costos. El número se crea en el catálogo cuando pase a stock o compartido.</p>
            @endif
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
    function tipoAsignacionDesdeTexto(texto) {
        const v = String(texto ?? '').trim().toLowerCase();
        if (v === '2' || v.indexOf('compart') !== -1) return 2;
        if (v === 'si' || v === '1' || v.indexOf('extra') !== -1 || v.indexOf('presupuest') !== -1) return 1;
        return 0;
    }

    function htmlChipPresupuestado(valor) {
        const tipo = (valor === true || valor === '1') ? 1 : parseInt(valor, 10);
        if (tipo === 2) return '<span class="inv-chip inv-chip-share">Compartido</span>';
        if (tipo === 1) return '<span class="inv-chip inv-chip-extra">Extra</span>';
        return '<span class="inv-chip inv-chip-stock">Stock</span>';
    }

    function htmlCheckBulk(tipo, id, modo) {
        if (!empleadoInventarioActivo || !permitePresupuestado || presupuestadoForzado) {
            return '';
        }
        modo = parseInt(modo, 10) || 0;
        if (modo === 1) {
            return '<span class="inv-check inv-check--off" title="Extra: ábralo para cambiar el tipo"></span>';
        }
        return '<label class="inv-check" title="Seleccionar"><input type="checkbox" class="inv-bulk-check" data-tipo="' + tipo + '" data-id="' + id + '" data-modo="' + modo + '"></label>';
    }

    function syncCheckFila($row, tipo, id, modo) {
        var $cell = $row.find('.index-actions');
        if (!$cell.length) {
            return;
        }
        $cell.find('.inv-check').remove();
        $cell.prepend(htmlCheckBulk(tipo, id, modo));
        $row.attr('data-presupuestado', modo);
    }

    function checksBulk($bar) {
        var tablaId = $bar.data('bulk-table');
        return $bar.closest('.inv-panel-body').find('#' + tablaId + ' tbody tr:visible .inv-bulk-check');
    }

    function actualizarConteoBulk($bar) {
        var n = checksBulk($bar).filter(':checked').length;
        $bar.find('.inv-bulk-count').text(n + (n === 1 ? ' seleccionado' : ' seleccionados'));
        $bar.find('.inv-bulk-btn').prop('disabled', n === 0);
    }

    function syncModoCards(selector, valor) {
        const tipo = parseInt(valor, 10) || 0;
        const $wrap = $('[data-switch="' + selector + '"]');
        if (!$wrap.length) return;
        $wrap.find('.inv-modo-card').removeClass('is-active');
        $wrap.find('.inv-modo-card[data-value="' + tipo + '"]').addClass('is-active');

        const sid = selector.replace(/^#/, '');
        const hint = tipo === 2 ? 'share' : (tipo === 1 ? 'extra' : 'stock');
        $('[data-hint-for="' + sid + '"]').hide();
        $('[data-hint-for="' + sid + '"].' + hint).css('display', 'flex');
        $(selector + 'Label').text(tipo === 2 ? 'Compartido' : (tipo === 1 ? 'Extra' : 'Stock'));
    }

    function setPresupuestado(selector, texto) {
        const tipo = presupuestadoForzado ? 1 : tipoAsignacionDesdeTexto(texto);
        $(selector).val(tipo);
        syncModoCards(selector, tipo);
        const $form = $(selector).closest('form');
        if ($form.length) {
            syncRequeridosModo($form, selector);
        }
    }

    function getPresupuestado(selector) {
        if (presupuestadoForzado) {
            return 1;
        }
        if (!permitePresupuestado) {
            return 0;
        }

        return parseInt($(selector).val(), 10) || 0;
    }

    function esModoExtra(selector) {
        return getPresupuestado(selector) === 1;
    }

    function esProyeccionLinea() {
        return $('#editEsProyeccion').val() === '1' || (
            !$('#editId_linea2').val() && !$('#editLineaCatalogoId').val()
        );
    }

    function syncLineaModalModo() {
        const $form = $('#editFormLinea');
        if (!$form.length) {
            return;
        }

        const extra = esModoExtra('#editPresupuestadoLinea');
        const proyeccion = esProyeccionLinea();
        const nuevaProyeccion = $('#editEsProyeccion').val() === '1' && !$('#editId_linea').val();

        $form.find('.js-linea-plan').toggle(proyeccion);
        $form.find('.js-linea-real').toggle(!(extra && proyeccion));
        $form.find('.js-linea-proyeccion-hint').toggle(proyeccion);

        $form.find('#editPlanLinea, #editTipoLinea, #editObraLinea').each(function() {
            if (proyeccion) {
                this.setAttribute('required', 'required');
            } else {
                this.removeAttribute('required');
            }
        });

        if (!presupuestadoForzado) {
            $form.find('.inv-modo-card').toggleClass('is-locked', nuevaProyeccion);
        }
    }

    function aplicarPlanLineaSeleccionado() {
        const $opt = $('#editPlanLinea option:selected');
        $('#editCompaniaLinea').val($opt.data('compania') || '');
        $('#editRentaLinea').val($opt.data('renta') || '');
    }

    function valorVacioInv(valor) {
        if (valor === null || valor === undefined) {
            return true;
        }
        var v = String(valor).trim().toLowerCase();
        return v === '' || v === 'null' || v === 'sin asignar' || v === 'sin asigna' || v === 'pendiente' || v === '0000-00-00';
    }

    function esExtraAsignacion(valor) {
        return parseInt(valor, 10) === 1;
    }

    function celdaPendiente(valor, esProy) {
        if (esProy && valorVacioInv(valor)) {
            return '<span class="inv-pendiente">Pendiente</span>';
        }
        return valorVacioInv(valor) ? '' : valor;
    }

    function celdaFechaPendiente(valor, esProy) {
        if (valorVacioInv(valor)) {
            return esProy ? '<span class="inv-pendiente">Pendiente</span>' : 'Sin asignar';
        }
        return formatFechaRenovacion(valor);
    }

    function filaPadre($el) {
        var $row = $($el).closest('tr');
        if ($row.hasClass('child') || $row.hasClass('dtr-details')) {
            $row = $row.prev();
        }
        return $row;
    }

    function attrFila($row, clave) {
        var v = $row.attr('data-' + clave);
        if (v === undefined || v === null) {
            return '';
        }
        v = String(v).trim();
        return valorVacioInv(v) ? '' : v;
    }

    function setAttrFila($row, clave, valor) {
        $row.attr('data-' + clave, valor == null ? '' : valor);
    }

    function escAttr(valor) {
        return String(valor == null ? '' : valor)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function textoDeCelda($td) {
        var v = String(($td && $td.text) ? $td.text() : ($td || '')).trim();
        return valorVacioInv(v) ? '' : v;
    }

    function celdaLineaPendiente(valor, esProy) {
        return celdaPendiente(valor, esProy);
    }

    function esProyeccionTel(telefono) {
        return !telefono.LineaID && parseInt(telefono.Presupuestado, 10) === 1;
    }

    function syncRequeridosModo($form, selectorModo) {
        const extra = esModoExtra(selectorModo);
        $form.toggleClass('is-modo-extra', extra);
        $form.find('[data-req-stock]').each(function() {
            if (extra) {
                this.removeAttribute('required');
            } else {
                this.setAttribute('required', 'required');
            }
        });
        if ($form.is('#editFormLinea')) {
            syncLineaModalModo();
        }
    }

    function validarCamposRequeridos($form) {
        let ok = true;
        $form.find('[required]').each(function() {
            if ($(this).is('[readonly],:disabled')) {
                return;
            }
            if (!$(this).val()) {
                ok = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        return ok;
    }

    $(document).on('click.invAssign', '.inv-modo-card:not(.is-locked)', function() {
        const $card = $(this);
        const selector = $card.closest('[data-switch]').data('switch');
        const value = parseInt($card.data('value'), 10) || 0;
        $(selector).val(value);
        syncModoCards(selector, value);
        const $form = $card.closest('form');
        if ($form.length) {
            syncRequeridosModo($form, selector);
        }
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
        initInvDt('#insumosAsignadosTable', {
            columnDefs: (function () {
                var defs = [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: [1, 2] },
                    { responsivePriority: 10000, targets: [5, 6, 7, 8, 9] }
                ];
                if (typeof permitePresupuestado !== 'undefined' && permitePresupuestado) {
                    defs.push({ responsivePriority: 3, targets: [10, 11] });
                } else {
                    defs.push({ responsivePriority: 3, targets: [10] });
                }
                return defs;
            })()
        });
        initInvDt('#lineasAsignadosTable', {
            columnDefs: (function () {
                var defs = [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: [1, 2, 3] },
                    { responsivePriority: 10000, targets: [5, 6, 9, 10, 12, 13, 14] }
                ];
                if (typeof permitePresupuestado !== 'undefined' && permitePresupuestado) {
                    defs.push({ responsivePriority: 3, targets: [15, 16] });
                } else {
                    defs.push({ responsivePriority: 3, targets: [15] });
                }
                return defs;
            })()
        });

        inicializarFiltrosPresupuestado();
    });
</script>

<script>
    // Índice de la columna "Presupuestado" en cada tabla de asignados.
    // La columna sólo se pinta para FISICA/EXTRAORDINARIO.
    var columnaPresupuestado = {
        equiposAsignadosTable: 12,
        insumosAsignadosTable: 10,
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
            return tipo === 1 || tipo === 2;
        }
        if (filtro === 'no_presupuestados') {
            return tipo === 0 || tipo === 2;
        }
        if (filtro === 'compartidos') {
            return tipo === 2;
        }

        return true;
    };

    if (!window.__invFiltroPresupuestadoDt) {
        window.__invFiltroPresupuestadoDt = true;
        $.fn.dataTable.ext.search.push(function(settings, data) {
            return window.__invFiltroPresupuestadoFn(settings, data);
        });
    }

    function fmtMoneyInv(n) {
        n = Math.round(Number(n) || 0);
        return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function montoFilaAsignada($row, tablaId) {
        if (tablaId === 'equiposAsignadosTable') {
            return parseFloat($row.attr('data-precio')) || 0;
        }
        if (tablaId === 'insumosAsignadosTable') {
            return parseFloat($row.attr('data-costo-mensual')) || parseFloat($row.find('td:eq(3)').text()) || 0;
        }
        if (tablaId === 'lineasAsignadosTable') {
            return parseFloat($row.attr('data-renta')) || 0;
        }
        return 0;
    }

    function tipoDesdeFila($row, tablaId) {
        var raw = $row.attr('data-presupuestado');
        if (raw !== undefined && raw !== null && String(raw).trim() !== '') {
            var n = parseInt(raw, 10);
            if (!isNaN(n)) {
                return n;
            }
        }
        var col = columnaPresupuestado[tablaId];
        if (col === undefined) {
            return 0;
        }
        return tipoFilaAsignacion($row.find('td').eq(col).text());
    }

    function actualizarConteos(tablaId) {
        if (!$('#' + tablaId).length || !$.fn.DataTable.isDataTable('#' + tablaId)) {
            return;
        }

        const dt = $('#' + tablaId).DataTable();
        let inventario = 0;
        let presupuesto = 0;
        let total = 0;
        let montoInv = 0;
        let montoPre = 0;
        let montoTotal = 0;

        dt.rows({ search: 'none' }).every(function() {
            const $n = $(this.node());
            const tipo = tipoDesdeFila($n, tablaId);
            const monto = montoFilaAsignada($n, tablaId);
            total++;
            montoTotal += monto;
            if (tipo === 0 || tipo === 2) {
                inventario++;
                montoInv += monto;
            }
            if (tipo === 1 || tipo === 2) {
                presupuesto++;
                montoPre += monto;
            }
        });

        const barra = $('.inventario-filtros[data-tabla="' + tablaId + '"]');
        barra.find('.conteo-todos').text(total);
        barra.find('.conteo-si').text(presupuesto);
        barra.find('.conteo-no').text(inventario);

        const dual = $('.inv-dual[data-tabla="' + tablaId + '"]');
        dual.find('.conteo-si').text(presupuesto);
        dual.find('.conteo-no').text(inventario);
        dual.find('[data-filtro="no_presupuestados"] .inv-money').text(fmtMoneyInv(montoInv));
        dual.find('[data-filtro="presupuestados"] .inv-money').text(fmtMoneyInv(montoPre));

        const $pane = $('#' + tablaId).closest('.tab-pane');
        $pane.find('[data-kpi="total"]').text(total);
        $pane.find('[data-kpi="stock-count"]').text(inventario);
        $pane.find('[data-kpi="extra-count"]').text(presupuesto);
        $pane.find('[data-kpi="stock-money"]').text(fmtMoneyInv(montoInv));
        $pane.find('[data-kpi="extra-money"]').text(fmtMoneyInv(montoPre));
        $pane.find('[data-kpi="solo-stock-money"]').text(fmtMoneyInv(montoTotal));
        if (tablaId === 'equiposAsignadosTable') {
            $pane.find('[data-kpi="stock-sub"]').text(inventario + ' equipo(s) · sale en inventario');
            $pane.find('[data-kpi="extra-sub"]').text(presupuesto + ' equipo(s) · sale en presupuesto');
        }
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
    // En equipo el chip se oculta al filtrar (el tipo ya va implícito).
    // En insumo y línea Status y Mes de pago se quedan siempre visibles.
    function aplicarVisibilidadPresupuestado(tablaId) {
        if (!permitePresupuestado) {
            return;
        }
        if (tablaId === 'insumosAsignadosTable' || tablaId === 'lineasAsignadosTable') {
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
    // Editar equipo (abriendo el modal con los datos)
    $(document).on('click.invAssign', '.edit-btn', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = filaPadre(this);
        let id = row.data('id');

        document.getElementById('titulo').innerHTML = 'Editar Equipo';
        $('#editId').val(id);
        $('#editEmp').val('');
        $('#editCategoria').val(attrFila(row, 'categoria'));
        $('#editMarca').val(attrFila(row, 'marca'));
        $('#editCaracteristicas').val(attrFila(row, 'caracteristicas'));
        $('#editModelo').val(attrFila(row, 'modelo'));
        $('#editPrecio').val(attrFila(row, 'precio'));
        $('#editFechaAsignacion').val(attrFila(row, 'fecha-asignacion'));
        $('#editFechaDeCompra').val(attrFila(row, 'fecha-compra'));
        $('#editNumSerie').val(attrFila(row, 'num-serie'));
        $('#editFolio').val(attrFila(row, 'folio'));
        $('#editGerenciaEquipo').val(attrFila(row, 'gerencia-id') || row.find("td:eq(10)").data('id')).trigger('change');
        $('#editComentarios').val(attrFila(row, 'comentarios'));
        setPresupuestado('#editPresupuestadoEquipo', row.attr('data-presupuestado') || row.find("td:eq(12)").text());
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

        syncRequeridosModo($('#editForm'), '#editPresupuestadoEquipo');
        let isValid = validarCamposRequeridos($('#editForm'));

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: esModoExtra('#editPresupuestadoEquipo')
                    ? 'En extra puede dejar vacíos folio, fechas y serie. Complete los datos del catálogo.'
                    : 'Para pasar a stock o compartido complete folio, fechas, serie y gerencia.',
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        const folioVacio = !$('#editFolio').val().trim();
        if (esModoExtra('#editPresupuestadoEquipo') && folioVacio) {
            folioValido = true;
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
        let folio = $('#editFolio').val().trim();
        let excluirId = id || null;
        let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function enviarEquipo() {
            let url = id ? '/inventarios/editar-equipo/' + id : '/inventarios/crear-equipo/' + id_E;
            let method = id ? 'PUT' : 'POST';
            let formData = {
                CategoriaEquipo: $('#editCategoria').val(),
                GerenciaEquipoID: $('#editGerenciaEquipo').val(),
                Marca: $('#editMarca').val(),
                Caracteristicas: $('#editCaracteristicas').val(),
                Modelo: $('#editModelo').val(),
                Precio: $('#editPrecio').val(),
                FechaAsignacion: $('#editFechaAsignacion').val(),
                NumSerie: $('#editNumSerie').val(),
                Folio: folio,
                FechaDeCompra: $('#editFechaDeCompra').val(),
                Comentarios: $('#editComentarios').val(),
                FechaRenovacion: $('#editFechaDeRenovacion').val(),
                Presupuestado: getPresupuestado('#editPresupuestadoEquipo'),
                MesDePago: $('#editMesDePagoEquipo').val(),
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
                            // Mostrar errores de validación
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
                            // Si la solicitud fue exitosa, actualizar la fila correspondiente o agregar una nueva
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
                        }
                    },
                    error: function(xhr) {
                        // Manejar error 422 del backend (folio duplicado)
                        if (xhr.status === 422) {
                            let resp = xhr.responseJSON;
                            if (resp && resp.errors && resp.errors.Folio) {
                                $('#editFolio').addClass('is-invalid').focus();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Folio duplicado',
                                    text: resp.errors.Folio[0],
                                    customClass: {
                                        popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                                    }
                                });
                            }
                        } else {
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
                    }
                });
        }

        if (!folio) {
            enviarEquipo();
            return;
        }

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
                enviarEquipo();
            },
            error: function() {
                enviarEquipo();
            }
        });
    });


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
        if (!fechaDisplay || fechaDisplay === 'Sin asignar' || fechaDisplay === 'Sin asigna' || fechaDisplay === '0000-00-00' || fechaDisplay === 'null' || fechaDisplay === 'Pendiente') {
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
        const extra = esExtraAsignacion(equipo.Presupuestado);
        row.find('td:eq(1)').text(equipo.CategoriaEquipo);
        row.find('td:eq(2)').text(equipo.Marca);
        row.find('td:eq(3)').text(equipo.Caracteristicas);
        row.find('td:eq(4)').text(equipo.Modelo);
        row.find('td:eq(5)').html(celdaPendiente(equipo.Precio, extra));
        row.find('td:eq(6)').html(celdaFechaPendiente(equipo.FechaAsignacion, extra));
        row.find('td:eq(7)').html(celdaFechaPendiente(equipo.FechaDeCompra, extra));
        row.find('td:eq(8)').html(celdaPendiente(equipo.NumSerie, extra));
        row.find('td:eq(9)').html(celdaPendiente(equipo.Folio, extra));
        row.find('td:eq(10)').attr('data-id', equipo.GerenciaEquipoID || '').html(celdaPendiente(equipo.GerenciaEquipo, extra));
        row.find('td:eq(11)').html(celdaPendiente(equipo.Comentarios, extra));
        if (permitePresupuestado) {
            row.find('td:eq(12)').html(htmlChipPresupuestado(equipo.Presupuestado));
            row.find('td:eq(13)').html(htmlPillsMeses(equipo.MesDePago ?? ''));
        }
        row.attr('data-meses', equipo.MesDePago ?? '');
        row.find('.edit-btn').data('id', equipo.InventarioID);
        setAttrFila(row, 'categoria', equipo.CategoriaEquipo);
        setAttrFila(row, 'marca', equipo.Marca);
        setAttrFila(row, 'caracteristicas', equipo.Caracteristicas);
        setAttrFila(row, 'modelo', equipo.Modelo);
        setAttrFila(row, 'precio', equipo.Precio);
        setAttrFila(row, 'fecha-asignacion', equipo.FechaAsignacion ? String(equipo.FechaAsignacion).substring(0, 10) : '');
        setAttrFila(row, 'fecha-compra', equipo.FechaDeCompra ? String(equipo.FechaDeCompra).substring(0, 10) : '');
        setAttrFila(row, 'num-serie', equipo.NumSerie);
        setAttrFila(row, 'folio', equipo.Folio);
        setAttrFila(row, 'gerencia-id', equipo.GerenciaEquipoID);
        setAttrFila(row, 'comentarios', equipo.Comentarios);
        setAttrFila(row, 'presupuestado', equipo.Presupuestado);
        syncCheckFila(row, 'equipo', equipo.InventarioID, equipo.Presupuestado);

        // Refrescar la caché de DataTables para que el filtro y los conteos vean el cambio.
        $('#equiposAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }

    // Agregar una nueva fila en la tabla (para equipo creado)
    function addNewRow(equipo) {
        const extra = esExtraAsignacion(equipo.Presupuestado);
        let newRow = `
        <tr data-id="${equipo.InventarioID}" data-meses="${equipo.MesDePago ?? ''}" data-presupuestado="${equipo.Presupuestado ?? 0}"
            data-categoria="${escAttr(equipo.CategoriaEquipo)}" data-marca="${escAttr(equipo.Marca)}"
            data-caracteristicas="${escAttr(equipo.Caracteristicas)}" data-modelo="${escAttr(equipo.Modelo)}"
            data-precio="${escAttr(equipo.Precio)}" data-fecha-asignacion="${escAttr(equipo.FechaAsignacion ? String(equipo.FechaAsignacion).substring(0, 10) : '')}"
            data-fecha-compra="${escAttr(equipo.FechaDeCompra ? String(equipo.FechaDeCompra).substring(0, 10) : '')}"
            data-num-serie="${escAttr(equipo.NumSerie)}" data-folio="${escAttr(equipo.Folio)}"
            data-gerencia-id="${escAttr(equipo.GerenciaEquipoID)}" data-comentarios="${escAttr(equipo.Comentarios)}">
            <td>
                <div class="index-actions">
                    ${htmlCheckBulk('equipo', equipo.InventarioID, equipo.Presupuestado)}
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
            <td>${celdaPendiente(equipo.Precio, extra)}</td>
            <td>${celdaFechaPendiente(equipo.FechaAsignacion, extra)}</td>
            <td>${celdaFechaPendiente(equipo.FechaDeCompra, extra)}</td>
            <td>${celdaPendiente(equipo.NumSerie, extra)}</td>
            <td>${celdaPendiente(equipo.Folio, extra)}</td>
            <td data-id="${equipo.GerenciaEquipoID || ''}">${celdaPendiente(equipo.GerenciaEquipo, extra)}</td>
            <td>${celdaPendiente(equipo.Comentarios, extra)}</td>
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

        let row = filaPadre(this);
        let id = row.data('id');

        // Asignar valores al formulario
        document.getElementById('tituloinsumo').innerHTML = 'Editar insumo';

        $('#editId_insumo').val(id);
        $('#editEmp_insumo').val('');
        $('#editCategoriaInsumo').val(attrFila(row, 'categoria') || row.find("td:eq(1)").text());
        $('#editNombreInsumo').val(attrFila(row, 'nombre') || row.find("td:eq(2)").text());
        $('#editCostoMensual').val(attrFila(row, 'costo-mensual') || row.find("td:eq(3)").text());
        $('#editCostoAnual').val(attrFila(row, 'costo-anual') || row.find("td:eq(4)").text());
        $('#editFechaDeRenovacion').val(fechaDisplayToInput(textoDeCelda(row.find("td:eq(5)"))));
        $('#editobserv').val(row.find("td:eq(6)").text());
        $('#editFechaDeAsigna').val(fechaDisplayToInput(textoDeCelda(row.find("td:eq(7)"))));
        $('#editNumSerieInsu').val(textoDeCelda(row.find("td:eq(8)")));
        $('#editComentariosInsumo').val(textoDeCelda(row.find("td:eq(9)")));
        setPagoMeses('editMesDePago', row.attr('data-meses') || '');
        setPresupuestado('#editPresupuestadoInsumo', row.attr('data-presupuestado') || row.find("td:eq(10)").text());

        $('#editModalInsumo').modal('show');
    });

    $(document).on('click.invAssign', '.crear-btn-insumo', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editFormInsumo')[0].reset();

        document.getElementById('tituloinsumo').innerHTML = 'Crear insumo';
        let row = filaPadre(this);
        let categoria = attrFila(row, 'categoria') || row.find("td:eq(1)").text();
        let nombreinsumo = attrFila(row, 'nombre') || row.find("td:eq(2)").text();
        let costomensual = attrFila(row, 'costo-mensual') || row.find("td:eq(3)").text();
        let costoanual = attrFila(row, 'costo-anual') || row.find("td:eq(4)").text();
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

        syncRequeridosModo($('#editFormInsumo'), '#editPresupuestadoInsumo');
        let isValid = validarCamposRequeridos($('#editFormInsumo'));

        if (!$('#editMesDePago').val()) {
            isValid = false;
        }

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: esModoExtra('#editPresupuestadoInsumo')
                    ? 'Seleccione los meses de pago. Fecha y serie pueden quedar vacíos en extra.'
                    : 'Seleccione los meses de pago y complete fecha de asignación y número de serie.',
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
        const extra = esExtraAsignacion(insumo.Presupuestado);
        row.find('td:eq(1)').text(insumo.CateogoriaInsumo);
        row.find('td:eq(2)').text(insumo.NombreInsumo);
        row.find('td:eq(3)').text(insumo.CostoMensual);
        row.find('td:eq(4)').text(insumo.CostoAnual);
        row.find('td:eq(5)').text(formatFechaRenovacion(insumo.FechaRenovacion));
        row.find('td:eq(6)').text(insumo.Observaciones);
        row.find('td:eq(7)').html(celdaFechaPendiente(insumo.FechaAsignacion, extra));
        row.find('td:eq(8)').html(celdaPendiente(insumo.NumSerie, extra));
        row.find('td:eq(9)').html(celdaPendiente(insumo.Comentarios, extra));
        row.attr('data-meses', insumo.MesDePago ?? '');
        row.attr('data-presupuestado', insumo.Presupuestado ?? 0);
        setAttrFila(row, 'categoria', insumo.CateogoriaInsumo);
        setAttrFila(row, 'nombre', insumo.NombreInsumo);
        setAttrFila(row, 'costo-mensual', insumo.CostoMensual);
        setAttrFila(row, 'costo-anual', insumo.CostoAnual);
        if (permitePresupuestado) {
            row.find('td:eq(10)').html(htmlChipPresupuestado(insumo.Presupuestado));
            row.find('td:eq(11)').html(htmlPillsMeses(insumo.MesDePago));
        } else {
            row.find('td:eq(10)').html(htmlPillsMeses(insumo.MesDePago));
        }
        syncCheckFila(row, 'insumo', insumo.InventarioID, insumo.Presupuestado);

        $('#insumosAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }


    function addinsumoNewRow(insumo) {
        const extra = esExtraAsignacion(insumo.Presupuestado);
        let newRow = `
        <tr data-id="${insumo.InventarioID}" data-meses="${insumo.MesDePago ?? ''}" data-presupuestado="${insumo.Presupuestado ?? 0}" data-categoria="${escAttr(insumo.CateogoriaInsumo)}" data-nombre="${escAttr(insumo.NombreInsumo)}" data-costo-mensual="${escAttr(insumo.CostoMensual)}" data-costo-anual="${escAttr(insumo.CostoAnual)}">
            <td>
                <div class="index-actions">
                    ${htmlCheckBulk('insumo', insumo.InventarioID, insumo.Presupuestado)}
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
            <td>${formatFechaRenovacion(insumo.FechaRenovacion)}</td>
            <td>${insumo.Observaciones || ''}</td>
            <td>${celdaFechaPendiente(insumo.FechaAsignacion, extra)}</td>
            <td>${celdaPendiente(insumo.NumSerie, extra)}</td>
            <td>${celdaPendiente(insumo.Comentarios, extra)}</td>
            ${permitePresupuestado ? `<td>${htmlChipPresupuestado(insumo.Presupuestado)}</td>` : ''}
            <td>${htmlPillsMeses(insumo.MesDePago)}</td>
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

    $(document).on('change.invAssign', '#editPlanLinea', aplicarPlanLineaSeleccionado);

    $(document).on('click.invAssign', '.edit-btn-linea', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let row = $(this).closest('tr');
        let id = row.data('id');
        let lineaId = row.attr('data-linea-id') || '';

        document.getElementById('titulolinea').innerHTML = lineaId ? 'Editar línea' : 'Editar proyección extra';

        $('#editFormLinea')[0].reset();
        $('#editId_linea').val(id);
        $('#editId_linea2').val('');
        $('#editEmp_linea').val('');
        $('#editLineaCatalogoId').val(lineaId);
        $('#editEsProyeccion').val(lineaId ? '0' : '1');
        $('#editPlanLinea').val(row.attr('data-plan-id') || '');
        aplicarPlanLineaSeleccionado();
        if (!$('#editCompaniaLinea').val()) {
            $('#editCompaniaLinea').val(row.attr('data-compania') || '');
        }
        if (!$('#editRentaLinea').val()) {
            $('#editRentaLinea').val(row.attr('data-renta') || '');
        }
        $('#editTipoLinea').val(row.attr('data-tipo') || '');
        $('#editObraLinea').val(row.attr('data-obra-id') || '');
        $('#editCostoFianzaLinea').val(row.attr('data-fianza') || '');
        $('#editNumTelLinea').val(row.attr('data-num') || '');
        $('#editCuentaPadreLinea').val(row.attr('data-cuenta-padre') || '');
        $('#editCuentaHijaLinea').val(row.attr('data-cuenta-hija') || '');
        $('#editFechaFianzaLinea').val(row.attr('data-fecha-fianza') || '');
        $('#editfechalinea').val(row.attr('data-fecha-asig') || '');
        $('#editcomenl').val(row.attr('data-comentarios') || '');
        $('#editMontoRenovacionFianza').val(row.attr('data-monto-renov') || '');
        $('#editFechaRenovacion').val(row.attr('data-fecha-renov') || '');
        setPresupuestado('#editPresupuestadoLinea', row.attr('data-presupuestado') || row.find('td:eq(15)').text());
        setPagoMeses('editMesDePagoLinea', row.attr('data-meses') || '');
        syncLineaModalModo();

        $('#editModalLinea').modal('show');
    });
    $(document).on('click.invAssign', '.crear-btn-linea', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        let id_E = '{{ $inventario->EmpleadoID }}';

        $('#editFormLinea')[0].reset();

        document.getElementById('titulolinea').innerHTML = 'Asignar línea del catálogo';
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
        $('#editLineaCatalogoId').val(id);
        $('#editEsProyeccion').val('0');
        $('#editMontoRenovacionFianza').val(monto);
        $('#editFechaRenovacion').val(fecha);
        setPresupuestado('#editPresupuestadoLinea', 'No');
        setPagoMeses('editMesDePagoLinea', mesesPagoTodosStr);
        syncLineaModalModo();

        $('#editModalLinea').modal('show');
    });

    $(document).on('click.invAssign', '.crear-btn-linea-extra', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        $('#editFormLinea')[0].reset();
        document.getElementById('titulolinea').innerHTML = 'Proyección extra de línea';
        $('#editId_linea').val('');
        $('#editId_linea2').val('');
        $('#editEmp_linea').val('{{ $inventario->EmpleadoID }}');
        $('#editLineaCatalogoId').val('');
        $('#editEsProyeccion').val('1');
        setPresupuestado('#editPresupuestadoLinea', '1');
        setPagoMeses('editMesDePagoLinea', mesesPagoTodosStr);
        syncLineaModalModo();

        $('#editModalLinea').modal('show');
    });


    $(document).on('click.invAssign', '.submit_linea', function(event) {
        event.preventDefault();

        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        $('.error-message').remove();
        $('.is-invalid').removeClass('is-invalid');

        syncRequeridosModo($('#editFormLinea'), '#editPresupuestadoLinea');
        let isValid = validarCamposRequeridos($('#editFormLinea'));

        if (!$('#editMesDePagoLinea').val()) {
            isValid = false;
        }

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: esProyeccionLinea() && esModoExtra('#editPresupuestadoLinea')
                    ? 'La proyección extra requiere plan, tipo, obra y meses de renta. El número se captura al pasarla a stock.'
                    : (esModoExtra('#editPresupuestadoLinea')
                        ? 'Seleccione los meses de renta. La fecha de asignación puede quedar vacía en extra.'
                        : 'Capture número, cuentas, plan/tipo/obra si aplica, meses de renta y fecha de asignación.'),
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                }
            });
            return;
        }

        let id = $('#editId_linea').val();
        let id2 = $('#editId_linea2').val();
        let id_E = $('#editEmp_linea').val() || '{{ $inventario->EmpleadoID }}';
        let url;
        if (id) {
            url = '/inventarios/editar-linea/' + id;
        } else if (esProyeccionLinea()) {
            url = '/inventarios/crear-linea-extra/' + id_E;
        } else {
            url = '/inventarios/crear-linea/' + id_E + '/' + id2;
        }
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
            MesDePago: $('#editMesDePagoLinea').val(),
            PlanID: $('#editPlanLinea').val(),
            TipoLinea: $('#editTipoLinea').val(),
            ObraID: $('#editObraLinea').val(),
            CostoFianza: $('#editCostoFianzaLinea').val(),
            Compania: $('#editCompaniaLinea').val(),
            CostoRentaMensual: $('#editRentaLinea').val(),
            NumTelefonico: $('#editNumTelLinea').val(),
            CuentaPadre: $('#editCuentaPadreLinea').val(),
            CuentaHija: $('#editCuentaHijaLinea').val(),
            FechaFianza: $('#editFechaFianzaLinea').val()
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


    function attrsFilaLinea(telefono) {
        var fechaFianza = telefono.FechaFianza ? String(telefono.FechaFianza).substring(0, 10) : '';
        var fechaAsig = telefono.FechaAsignacion ? String(telefono.FechaAsignacion).substring(0, 10) : '';
        var fechaRenov = telefono.FechaRenovacion ? String(telefono.FechaRenovacion).substring(0, 10) : '';
        return {
            'data-id': telefono.InventarioID,
            'data-meses': telefono.MesDePago || '',
            'data-linea-id': telefono.LineaID || '',
            'data-plan-id': telefono.PlanID || '',
            'data-tipo': telefono.TipoLinea || '',
            'data-obra-id': telefono.ObraID || '',
            'data-renta': telefono.CostoRentaMensual || '',
            'data-compania': telefono.Compania || '',
            'data-fianza': telefono.CostoFianza || '',
            'data-num': telefono.NumTelefonico || '',
            'data-cuenta-padre': telefono.CuentaPadre || '',
            'data-cuenta-hija': telefono.CuentaHija || '',
            'data-fecha-fianza': fechaFianza,
            'data-fecha-asig': fechaAsig,
            'data-comentarios': telefono.Comentarios || '',
            'data-monto-renov': telefono.MontoRenovacionFianza || '',
            'data-fecha-renov': fechaRenov,
            'data-presupuestado': telefono.Presupuestado
        };
    }

    function aplicarAttrsFilaLinea($row, telefono) {
        var attrs = attrsFilaLinea(telefono);
        Object.keys(attrs).forEach(function(k) {
            $row.attr(k, attrs[k]);
        });
    }

    function obraLineaTexto(telefono) {
        if (telefono.Obra) return telefono.Obra;
        if (telefono.lineastelefonicas && telefono.lineastelefonicas.obras) {
            return telefono.lineastelefonicas.obras.NombreObra;
        }
        return 'Sin asignar';
    }

    function updatetelefTableRow(telefono) {
        let row = $(`tr[data-id=${telefono.InventarioID}]`);
        const proy = esProyeccionTel(telefono);
        row.find('td:eq(1)').html(celdaLineaPendiente(telefono.NumTelefonico, proy));
        row.find('td:eq(2)').text(telefono.Compania || '');
        row.find('td:eq(3)').text(telefono.PlanTel || '');
        row.find('td:eq(4)').text(telefono.CostoRentaMensual || '');
        row.find('td:eq(5)').html(celdaLineaPendiente(telefono.CuentaPadre, proy));
        row.find('td:eq(6)').html(celdaLineaPendiente(telefono.CuentaHija, proy));
        row.find('td:eq(7)').text(telefono.TipoLinea || '');
        row.find('td:eq(8)').text(obraLineaTexto(telefono));
        row.find('td:eq(9)').html(celdaLineaPendiente(formatFechaRenovacion(telefono.FechaFianza), proy));
        row.find('td:eq(10)').text(telefono.CostoFianza || '');
        row.find('td:eq(11)').html(celdaLineaPendiente(formatFechaRenovacion(telefono.FechaAsignacion), proy));
        row.find('td:eq(12)').text(telefono.Comentarios || '');
        row.find('td:eq(13)').text(telefono.MontoRenovacionFianza || '');
        row.find('td:eq(14)').text(formatFechaRenovacion(telefono.FechaRenovacion));
        if (permitePresupuestado) {
            row.find('td:eq(15)').html(htmlChipPresupuestado(telefono.Presupuestado));
            row.find('td:eq(16)').html(htmlPillsMeses(telefono.MesDePago));
        } else {
            row.find('td:eq(15)').html(htmlPillsMeses(telefono.MesDePago));
        }
        aplicarAttrsFilaLinea(row, telefono);
        syncCheckFila(row, 'linea', telefono.InventarioID, telefono.Presupuestado);

        $('#lineasAsignadosTable').DataTable().row(row).invalidate().draw(false);
    }


    function addtelefNewRow(telefono) {
        const table = $('#lineasAsignadosTable').DataTable();
        const proy = esProyeccionTel(telefono);

        const newRow = [
            `<div class="index-actions">
                ${htmlCheckBulk('linea', telefono.InventarioID, telefono.Presupuestado)}
                <button type="button" class="index-action index-action--edit edit-btn-linea" data-id="${telefono.InventarioID}" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <form method="POST" action="/inventarios/deleteL/${telefono.InventarioID}" class="index-action-form">
                    <button type="submit" class="index-action index-action--delete delete-btn-linea" data-id="${telefono.InventarioID}" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>`,
            celdaLineaPendiente(telefono.NumTelefonico, proy),
            telefono.Compania,
            telefono.PlanTel,
            telefono.CostoRentaMensual,
            celdaLineaPendiente(telefono.CuentaPadre, proy),
            celdaLineaPendiente(telefono.CuentaHija, proy),
            telefono.TipoLinea,
            obraLineaTexto(telefono),
            celdaLineaPendiente(formatFechaRenovacion(telefono.FechaFianza), proy),
            telefono.CostoFianza,
            celdaLineaPendiente(formatFechaRenovacion(telefono.FechaAsignacion), proy),
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
        aplicarAttrsFilaLinea($(dtRow.node()), telefono);
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

    $(document).on('change.invAssign', '.inv-bulk-check, .inv-bulk-all', function() {
        var $bar = $(this).closest('.inv-panel-body').find('.inv-bulk');
        if ($(this).hasClass('inv-bulk-all')) {
            checksBulk($bar).prop('checked', this.checked);
        }
        actualizarConteoBulk($bar);
    });

    $(document).on('click.invAssign', '.inv-bulk-btn', function() {
        if (bloquearAccionInventarioInactivo()) {
            return;
        }

        var $btn = $(this);
        var $bar = $btn.closest('.inv-bulk');
        var tipo = $bar.data('bulk-tipo');
        var tablaId = $bar.data('bulk-table');
        var modo = parseInt($btn.data('modo'), 10);
        var ids = [];
        checksBulk($bar).filter(':checked').each(function() {
            ids.push($(this).data('id'));
        });

        if (!ids.length) {
            return;
        }

        var etiqueta = modo === 2 ? 'Compartido' : 'Stock';
        Swal.fire({
            title: 'Cambiar tipo',
            text: 'Pasar ' + ids.length + ' registro(s) a ' + etiqueta + '.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
            }
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/inventarios/cambiar-asignacion-masiva',
                method: 'PUT',
                data: {
                    tipo: tipo,
                    ids: ids,
                    Presupuestado: modo,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    (response.actualizados || []).forEach(function(id) {
                        var $row = $('#' + tablaId + ' tr[data-id="' + id + '"]');
                        if (permitePresupuestado) {
                            $row.find('td:eq(' + columnaPresupuestado[tablaId] + ')').html(htmlChipPresupuestado(modo));
                        }
                        syncCheckFila($row, tipo, id, modo);
                        if ($.fn.DataTable.isDataTable('#' + tablaId)) {
                            $('#' + tablaId).DataTable().row($row).invalidate();
                        }
                    });
                    if ($.fn.DataTable.isDataTable('#' + tablaId)) {
                        $('#' + tablaId).DataTable().draw(false);
                    }
                    $bar.find('.inv-bulk-all').prop('checked', false);
                    actualizarConteoBulk($bar);

                    var extra = '';
                    if (response.omitidos) {
                        extra = ' Se omitieron ' + response.omitidos + ' extra(s).';
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: (response.actualizados || []).length + ' registro(s) pasaron a ' + etiqueta + '.' + extra,
                        timer: 1800,
                        showConfirmButton: false,
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                },
                error: function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (error.responseJSON && error.responseJSON.message) ? error.responseJSON.message : 'No se pudo cambiar el tipo.',
                        customClass: {
                            popup: document.documentElement.classList.contains('dark') ? 'bg-[#101010] text-white' : 'bg-white text-black'
                        }
                    });
                }
            });
        });
    });

    // Fin Seccion telefono
</script>



@endpush