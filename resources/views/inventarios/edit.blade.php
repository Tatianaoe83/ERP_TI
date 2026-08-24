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
            <div class="row mt-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoEquipo',
                    'presupuestadoForzado' => $presupuestadoForzado,
                    'permitePropio' => true,
                ])
              </div>
            </div>
            @endif
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
    <div class="modal-content dark:bg-[#101010]">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloinsumo"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="editFormInsumo">
          <input type="hidden" id="editId_insumo">
          <input type="hidden" id="editEmp_insumo">

          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label for="editCategoriaInsumo">Categoría del Insumo </label>
                  <input type="text" class="form-control" id="editCategoriaInsumo" name="editCategoriaInsumo" required readonly>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Nombre Insumo </label>
                  <input type="text" class="form-control" id="editNombreInsumo" name="editNombreInsumo" required readonly>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Costo Mensual</label>
                  <input type="text" class="form-control" id="editCostoMensual" name="editCostoMensual" required readonly>

                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Costo Anual </label>
                  <input type="text" class="form-control" id="editCostoAnual" name="editCostoAnual" required readonly>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="dark:text-white">
                  <label>Observaciones</label>
                  <input type="text" class="form-control" id="editobserv" name="editobserv" readonly>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Fecha de asignacion</label>
                  <input type="date" class="form-control" id="editFechaDeAsigna" name="editFechaDeAsigna" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Num. Serie</label>
                  <input type="text" class="form-control" id="editNumSerieInsu" id="editNumSerieInsu" required>
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

            @if($permitePresupuestado)
            <div class="row mt-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoInsumo',
                    'presupuestadoForzado' => $presupuestadoForzado,
                ])
              </div>
            </div>
            @endif

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
    <div class="modal-content dark:bg-[#101010]">
      <div class="modal-header">
        <h5 class="modal-title" id="titulolinea"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editFormLinea">
          <input type="hidden" id="editId_linea">
          <input type="hidden" id="editId_linea2">
          <input type="hidden" id="editEmp_linea">


          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label for="editfechalinea">Fecha de asignacion</label>
                  <input type="date" class="form-control" id="editfechalinea" name="editfechalinea" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Comentarios </label>

                  <div class="form-floating">
                    <textarea class="form-control" id="editcomenl" name="editcomenl" style="height: 100px"></textarea>

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

            @if($permitePresupuestado)
            <div class="row mt-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoLinea',
                    'presupuestadoForzado' => $presupuestadoForzado,
                ])
              </div>
            </div>
            @endif

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