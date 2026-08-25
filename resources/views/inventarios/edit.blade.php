@extends('layouts.app')

@section('content')
@php
    $tipoPersona = strtoupper((string) ($inventario->tipo_persona ?? 'FISICA'));
    $tipoMeta = [
        'FISICA' => [
            'label' => 'Física',
            'class' => 'inv-tipo-fisica',
            'rules' => 'Puede tener Stock (asignado actual) y Extra (presupuesto futuro).',
        ],
        'REFERENCIADO' => [
            'label' => 'Referenciado',
            'class' => 'inv-tipo-referenciado',
            'rules' => 'Gerencia / control de almacén: solo Stock (inventario actual). Sin extras de presupuesto.',
        ],
        'EXTRAORDINARIO' => [
            'label' => 'Extraordinario',
            'class' => 'inv-tipo-extraordinario',
            'rules' => 'Todo lo asignado es Extra (presupuesto futuro). No aplica stock operativo.',
        ],
    ];
    $meta = $tipoMeta[$tipoPersona] ?? $tipoMeta['FISICA'];
@endphp

@include('inventarios.partials.tipo-persona-styles')
@include('inventarios.partials.asignar-ui-styles')

@include('flash::message')

<div data-app-tabset class="inv-assign-page">
<x-index-page
    title="Asignar inventario"
    icon="fa-laptop"
    :subtitle="$inventario->NombreEmpleado"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        <span class="inv-tipo-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
        <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Volver</a>
    </x-slot>

    <x-slot name="tabs">
        <div class="app-tabs" id="myTab" role="tablist">
            <button type="button" class="app-tabs__btn is-active" data-inv-tab="#empleados">Empleado</button>
            <button type="button" class="app-tabs__btn" data-inv-tab="#equipo">Equipo de cómputo</button>
            <button type="button" class="app-tabs__btn" data-inv-tab="#insumo">Insumo</button>
            <button type="button" class="app-tabs__btn" data-inv-tab="#linea">Línea de telefonía</button>
        </div>
    </x-slot>

    <p class="inv-assign-note">{{ $meta['rules'] }}</p>

    @include('adminlte-templates::common.errors')

    @if(!$empleadoActivo)
    <div class="alert alert-warning">
        Este empleado está <strong>inactivo</strong>. Solo puede consultar su inventario; no es posible asignar, editar ni eliminar elementos.
    </div>
    @endif

    @include('inventarios.fields')
</x-index-page>
</div>

<!-- Modal de Edición -->
<div class="modal" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content inv-modal-content dark:bg-[#101010]">
      <div class="modal-header inv-modal-header">
        <h5 class="modal-title inv-modal-title" id="titulo"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" id="editId">
          <input type="hidden" id="editEmp">

          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label for="editCategoria">Categoría del Equipo </label>
                  <input type="text" class="form-control" id="editCategoria" name="editCategoria" required readonly>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Marca </label>
                  <input type="text" class="form-control" id="editMarca" name="Marca" required readonly>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Caracteristicas</label>
                  <textarea class="form-control" rows="3" id="editCaracteristicas" required readonly></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Modelo </label>
                  <input type="text" class="form-control" id="editModelo" name="Modelo" required readonly>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 equipo-solo-empresa">
                <div class="dark:text-white">
                  <label>Precio</label>
                  <input type="number" class="form-control" id="editPrecio" required min="1" step="1" pattern="\d*" readonly>

                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Fecha Asignacion</label>
                  <input type="date" class="form-control" id="editFechaAsignacion" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 equipo-solo-empresa">
                <div class="dark:text-white">
                  <label>Fecha de Compra</label>
                  <input type="date" class="form-control" id="editFechaDeCompra" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Num. Serie</label>
                  <input type="text" class="form-control" id="editNumSerie" required>
                </div>
              </div>
            </div>

            <div class="row equipo-solo-empresa">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Folio</label>
                  <input type="text" class="form-control" id="editFolio" required="required">
                  <div class="invalid-feedback rounded ">Este folio ya está registrado. Debe ser único e irrepetible.</div>
                  <div id="folio-Info" class="mt-2 px-2 py-2 bg-gray-100 text-gray-900 dark:bg-[#101010] dark:text-white border-gray-300 dark:border-gray-700 rounded" style="display:none;">
                    Últimos 3 folios registrados: <strong id="ultimos-folios-lista">Cargando...</strong>
                  </div>
                </div>
              </div>
              <div class="col-md-6">

              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Comentarios</label>

                  <div class="form-floating">
                    <textarea class="form-control" id="editComentarios" name="editComentarios" style="height: 100px"></textarea>

                  </div>

                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Gerencia </label>
                  <select class="jz1 form-control" id="editGerenciaEquipo" name="GerenciaEquipoID" required>
                    <option value="">Seleccione una gerencia</option>
                    @foreach(App\Models\Gerencia::all() as $gerencia)
                    <option value="{{ $gerencia->GerenciaID }}">{{ $gerencia->NombreGerencia }}</option>
                    @endforeach
                  </select>
                  <div class="invalid-feedback">Debe seleccionar una gerencia</div>
                </div>
              </div>

            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                @include('inventarios.partials.meses-pago', [
                    'mesesPagoId' => 'editMesDePagoEquipo',
                    'mesesPagoLabel' => 'Meses de pago',
                    'mesesPagoAyuda' => 'Pago único: un mes. Parcialidad: varios meses. El importe se reparte entre los meses marcados.',
                ])
              </div>
            </div>

            @if($permitePresupuestado)
            <div class="row mb-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoEquipo',
                    'presupuestadoForzado' => $presupuestadoForzado,
                    'permitePropio' => true,
                ])
              </div>
            </div>
            @endif

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label for="editCategoria">Categoría del Equipo</label>
                  <input type="text" class="form-control inv-locked" id="editCategoria" name="editCategoria" required readonly tabindex="-1">
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Marca</label>
                  <input type="text" class="form-control inv-locked" id="editMarca" name="Marca" required readonly tabindex="-1">
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Características</label>
                  <textarea class="form-control inv-locked" rows="3" id="editCaracteristicas" required readonly tabindex="-1"></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Modelo</label>
                  <input type="text" class="form-control inv-locked" id="editModelo" name="Modelo" required readonly tabindex="-1">
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Precio <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="number" class="form-control inv-locked" id="editPrecio" min="1" step="1" pattern="\d*" readonly tabindex="-1" data-req-stock="1">
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Fecha Asignación <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="date" class="form-control" id="editFechaAsignacion" data-req-stock="1">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Fecha de Compra <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="date" class="form-control" id="editFechaDeCompra" data-req-stock="1">
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Num. Serie <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="text" class="form-control" id="editNumSerie" data-req-stock="1">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Folio <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="text" class="form-control" id="editFolio" data-req-stock="1">
                  <div class="invalid-feedback rounded ">Este folio ya está registrado. Debe ser único e irrepetible.</div>
                  <div id="folio-Info" class="mt-2 px-2 py-2 bg-gray-100 text-gray-900 dark:bg-[#101010] dark:text-white border-gray-300 dark:border-gray-700 rounded" style="display:none;">
                    Últimos 3 folios registrados: <strong id="ultimos-folios-lista">Cargando...</strong>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Gerencia <span class="inv-opt-tag">Opcional en extra</span></label>
                  <select class="jz1 form-control" id="editGerenciaEquipo" name="GerenciaEquipoID" data-req-stock="1">
                    <option value="">Seleccione una gerencia</option>
                    @foreach(App\Models\Gerencia::all() as $gerencia)
                    <option value="{{ $gerencia->GerenciaID }}">{{ $gerencia->NombreGerencia }}</option>
                    @endforeach
                  </select>
                  <div class="invalid-feedback">Debe seleccionar una gerencia</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Comentarios</label>
                  <div class="form-floating">
                    <textarea class="form-control" id="editComentarios" name="editComentarios" style="height: 100px"></textarea>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                @include('inventarios.partials.meses-pago', [
                    'mesesPagoId' => 'editMesDePagoEquipo',
                    'mesesPagoLabel' => 'Meses de pago',
                    'mesesPagoAyuda' => 'Pago único: un mes. Parcialidad: varios meses. El importe se reparte entre los meses marcados.',
                ])
              </div>
            </div>
          </div>
        </form>
        <div class="modal-footer">
          <button type="button" class="index-page__btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="index-page__btn-primary submit_equipo">Guardar cambios</button>
        </div>

      </div>

    </div>
  </div>
</div>


<!-- Modal de Edición insumo-->
<div class="modal" id="editModalInsumo" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content inv-modal-content dark:bg-[#101010]">
      <div class="modal-header inv-modal-header">
        <h5 class="modal-title inv-modal-title" id="tituloinsumo"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="editFormInsumo">
          <input type="hidden" id="editId_insumo">
          <input type="hidden" id="editEmp_insumo">

          <div class="container-fluid">
            @if($permitePresupuestado)
            <div class="row mb-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoInsumo',
                    'presupuestadoForzado' => $presupuestadoForzado,
                ])
              </div>
            </div>
            @endif

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label for="editCategoriaInsumo">Categoría del Insumo</label>
                  <input type="text" class="form-control inv-locked" id="editCategoriaInsumo" name="editCategoriaInsumo" required readonly tabindex="-1">
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Nombre Insumo</label>
                  <input type="text" class="form-control inv-locked" id="editNombreInsumo" name="editNombreInsumo" required readonly tabindex="-1">
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Costo Mensual</label>
                  <input type="text" class="form-control inv-locked" id="editCostoMensual" name="editCostoMensual" required readonly tabindex="-1">
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Costo Anual</label>
                  <input type="text" class="form-control inv-locked" id="editCostoAnual" name="editCostoAnual" required readonly tabindex="-1">
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="dark:text-white">
                  <label>Observaciones</label>
                  <input type="text" class="form-control inv-locked" id="editobserv" name="editobserv" readonly tabindex="-1">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Fecha de asignación <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="date" class="form-control" id="editFechaDeAsigna" name="editFechaDeAsigna" data-req-stock="1">
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Num. Serie <span class="inv-opt-tag">Opcional en extra</span></label>
                  <input type="text" class="form-control" id="editNumSerieInsu" data-req-stock="1">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Comentarios</label>
                  <div class="form-floating">
                    <textarea class="form-control" id="editComentariosInsumo" name="editComentariosInsumo" style="height: 100px"></textarea>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                @include('inventarios.partials.meses-pago', [
                    'mesesPagoId' => 'editMesDePago',
                    'mesesPagoLabel' => 'Meses de pago',
                    'mesesPagoAyuda' => 'Un mes = ese mes. Varios = parcialidad. Los 12 = anual. El calendario de presupuesto usa estos meses.',
                ])
              </div>
            </div>
          </div>
        </form>
        <div class="modal-footer">
          <button type="button" class="index-page__btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="index-page__btn-primary submit_insumo">Guardar cambios</button>
        </div>

      </div>

    </div>
  </div>
</div>


<!-- Modal de Edición linea-->
<div class="modal" id="editModalLinea" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content inv-modal-content dark:bg-[#101010]">
      <div class="modal-header inv-modal-header">
        <h5 class="modal-title inv-modal-title" id="titulolinea"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editFormLinea">
          <input type="hidden" id="editId_linea">
          <input type="hidden" id="editId_linea2">
          <input type="hidden" id="editEmp_linea">
          <input type="hidden" id="editLineaCatalogoId">
          <input type="hidden" id="editEsProyeccion" value="0">

          <div class="container-fluid">
            @if($permitePresupuestado)
            <div class="row mb-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoLinea',
                    'presupuestadoForzado' => $presupuestadoForzado,
                ])
              </div>
            </div>
            @endif

            <p class="inv-linea-hint js-linea-proyeccion-hint" style="display:none;">
              Proyección extra: solo plan, tipo, obra y costo. El número, las cuentas y las fechas se capturan al pasarla a Stock o Compartido; ahí se crea la línea en el catálogo.
            </p>

            <div class="js-linea-plan">
              <div class="row">
                <div class="col-md-6">
                  <div class="dark:text-white mb-3">
                    <label for="editPlanLinea">Plan</label>
                    <select class="form-control" id="editPlanLinea" name="PlanID">
                      <option value="">Seleccione un plan</option>
                      @foreach(($planesLinea ?? collect()) as $plan)
                      <option
                        value="{{ $plan->ID }}"
                        data-renta="{{ $plan->PrecioPlan }}"
                        data-compania="{{ $plan->companiaslineastelefonicas->Compania ?? '' }}"
                      >{{ $plan->companiaslineastelefonicas->Compania ?? '' }} — {{ $plan->NombrePlan }}</option>
                      @endforeach
                    </select>
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="dark:text-white mb-3">
                    <label>Compañía</label>
                    <input type="text" class="form-control inv-locked" id="editCompaniaLinea" readonly tabindex="-1">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="dark:text-white mb-3">
                    <label>Costo renta mensual</label>
                    <input type="text" class="form-control inv-locked" id="editRentaLinea" readonly tabindex="-1">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="dark:text-white mb-3">
                    <label for="editTipoLinea">Tipo de línea</label>
                    <select class="form-control" id="editTipoLinea" name="TipoLinea">
                      <option value="">Seleccione</option>
                      @foreach(($tiposLinea ?? collect(['VOZ', 'DATOS', 'GPS'])) as $tipo)
                      <option value="{{ $tipo }}">{{ $tipo }}</option>
                      @endforeach
                    </select>
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="dark:text-white mb-3">
                    <label for="editObraLinea">Obra</label>
                    <select class="form-control" id="editObraLinea" name="ObraID">
                      <option value="">Seleccione</option>
                      @foreach(($obrasLinea ?? collect()) as $obra)
                      <option value="{{ $obra->ObraID }}">{{ $obra->NombreObra }}</option>
                      @endforeach
                    </select>
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="dark:text-white mb-3">
                    <label for="editCostoFianzaLinea">Costo fianza</label>
                    <input type="number" min="0" step="1" class="form-control" id="editCostoFianzaLinea" name="CostoFianza">
                  </div>
                </div>
              </div>
            </div>

            <div class="js-linea-real">
              <div class="row">
                <div class="col-md-4">
                  <div class="dark:text-white mb-3">
                    <label for="editNumTelLinea">Número telefónico <span class="inv-opt-tag">Al pasar a stock</span></label>
                    <input type="text" class="form-control" id="editNumTelLinea" name="NumTelefonico" maxlength="50" data-req-stock="1">
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="dark:text-white mb-3">
                    <label for="editCuentaPadreLinea">Cuenta padre <span class="inv-opt-tag">Al pasar a stock</span></label>
                    <input type="text" class="form-control" id="editCuentaPadreLinea" name="CuentaPadre" maxlength="100" data-req-stock="1">
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="dark:text-white mb-3">
                    <label for="editCuentaHijaLinea">Cuenta hija <span class="inv-opt-tag">Al pasar a stock</span></label>
                    <input type="text" class="form-control" id="editCuentaHijaLinea" name="CuentaHija" maxlength="100" data-req-stock="1">
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="dark:text-white mb-3">
                    <label for="editFechaFianzaLinea">Fecha de fianza <span class="inv-opt-tag">Opcional</span></label>
                    <input type="date" class="form-control" id="editFechaFianzaLinea" name="FechaFianza">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="dark:text-white mb-3">
                    <label for="editfechalinea">Fecha de asignación <span class="inv-opt-tag">Opcional en extra</span></label>
                    <input type="date" class="form-control" id="editfechalinea" name="editfechalinea" data-req-stock="1">
                    <div class="invalid-feedback">Este campo es requerido</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="dark:text-white">
                  <label>Comentarios </label>
                  <div class="form-floating">
                    <textarea class="form-control" id="editcomenl" name="editcomenl" style="height: 80px"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                @include('inventarios.partials.meses-pago', [
                    'mesesPagoId' => 'editMesDePagoLinea',
                    'mesesPagoLabel' => 'Meses de renta',
                    'mesesPagoAyuda' => 'La renta entra al calendario sólo en los meses marcados. Un mes, parcial o anual (12).',
                ])
              </div>
            </div>

            <!-- Campos ocultos para capturar datos de abajo automáticamente -->
            <input type="hidden" id="editMontoRenovacionFianza" name="MontoRenovacionFianza">
            <input type="hidden" id="editFechaRenovacion" name="FechaRenovacion">

          </div>
        </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="index-page__btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="index-page__btn-primary submit_linea">Guardar cambios</button>
      </div>
    </div>
  </div>
</div>



@endsection