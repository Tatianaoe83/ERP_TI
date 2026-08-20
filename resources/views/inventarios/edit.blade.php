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
    $iniciales = collect(preg_split('/\s+/', trim((string) $inventario->NombreEmpleado)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
@endphp

@include('inventarios.partials.tipo-persona-styles')
@include('inventarios.partials.asignar-ui-styles')

<div class="inv-assign-page">
    <div class="inv-hero">
        <div class="inv-hero-left">
            <div class="inv-avatar">{{ $iniciales ?: 'IN' }}</div>
            <div>
                <p class="inv-hero-label">Inventario de</p>
                <h1 class="inv-hero-name">{{ $inventario->NombreEmpleado }}</h1>
                <div class="mt-1 d-flex flex-wrap align-items-center gap-2">
                    <span class="inv-tipo-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                    <span class="text-muted small">{{ $meta['rules'] }}</span>
                </div>
            </div>
        </div>
        <div class="inv-hero-actions">
            <a href="{{ route('inventarios.index') }}" class="inv-btn-back">Regresar</a>
        </div>
    </div>

    @include('adminlte-templates::common.errors')

    @if(!$empleadoActivo)
    <div class="alert alert-warning">
        Este empleado está <strong>inactivo</strong>. Solo puede consultar su inventario; no es posible asignar, editar ni eliminar elementos.
    </div>
    @endif

    @include('inventarios.fields')
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

            @if($permitePresupuestado)
            <div class="row mt-3">
              <div class="col-md-12">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoEquipo',
                    'presupuestadoForzado' => $presupuestadoForzado,
                    'permitePropio' => true,
                ])
              </div>
              <div class="col-md-6 mt-3 equipo-solo-empresa">
                <div class="dark:text-white">
                  <label>Mes de pago <span class="text-muted small">(recomendado si es Extra)</span></label>
                  <select class="form-select" id="editMesDePagoEquipo" name="MesDePago">
                    <option value="">Seleccione mes</option>
                    <option value="ENERO">enero</option>
                    <option value="FEBRERO">febrero</option>
                    <option value="MARZO">marzo</option>
                    <option value="ABRIL">abril</option>
                    <option value="MAYO">mayo</option>
                    <option value="JUNIO">junio</option>
                    <option value="JULIO">julio</option>
                    <option value="AGOSTO">agosto</option>
                    <option value="SEPTIEMBRE">septiembre</option>
                    <option value="OCTUBRE">octubre</option>
                    <option value="NOVIEMBRE">noviembre</option>
                    <option value="DICIEMBRE">diciembre</option>
                  </select>
                </div>
              </div>
            </div>
            @endif
          </div>
        </form>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary submit_equipo">Guardar Cambios</button>
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
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Frecuencia de pago</label>
                  <input type="text" class="form-control" id="editFrecuenciaDePago" name="editFrecuenciaDePago" required readonly>

                </div>
              </div>
              <div class="col-md-6">
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
                  <label>Mes de pago</label>

                  <select class="form-select" id="editMesDePago" name="editMesDePago" required aria-label="Default select example">
                    <option value="">Seleccione mes</option>
                    <option value="N/A">N/A</option>
                    <option value="ENERO">enero </option>
                    <option value="FEBRERO">febrero</option>
                    <option value="MARZO">marzo </option>
                    <option value="ABRIL">abril </option>
                    <option value="MAYO">mayo</option>
                    <option value="JUNIO">junio</option>
                    <option value="JULIO">julio</option>
                    <option value="AGOSTO">agosto</option>
                    <option value="SEPTIEMBRE">septiembre</option>
                    <option value="OCTUBRE">octubre</option>
                    <option value="NOVIEMBRE">noviembre</option>
                    <option value="DICIEMBRE">diciembre</option>

                  </select>

                </div>
              </div>
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Comentarios</label>
                  <div class="form-floating">
                    <textarea class="form-control" id="editComentariosInsumo" name="editComentariosInsumo" style="height: 100px"></textarea>

                  </div>

                </div>
              </div>
            </div>

            <div class="row mt-3">
              @if($permitePresupuestado)
              <div class="col-md-8">
                @include('inventarios.partials.presupuestado-captura', [
                    'switchId' => 'editPresupuestadoInsumo',
                    'presupuestadoForzado' => $presupuestadoForzado,
                ])
              </div>
              @endif

              {{-- Sólo aplica al stock: una licencia pirata no se paga ni entra al presupuesto. --}}
              <div class="col-md-{{ $permitePresupuestado ? 4 : 12 }} insumo-solo-stock">
                <div class="dark:text-white">
                  <label class="inv-form-label d-block">Origen de la licencia</label>
                  <button type="button" class="inv-pirata-card" id="editLicenciaPirataCard"
                          aria-pressed="false" data-target="editLicenciaPirata">
                    <span class="inv-pirata-ico"><i class="fas fa-shield-halved"></i></span>
                    <span class="inv-pirata-body">
                      <span class="inv-pirata-title">Licencia pirata</span>
                      <span class="inv-pirata-desc">Sin respaldo legal · no genera costo</span>
                    </span>
                    <span class="inv-pirata-check"><i class="fas fa-check"></i></span>
                  </button>
                  <input type="checkbox" class="d-none" id="editLicenciaPirata">
                </div>
              </div>
            </div>

          </div>
        </form>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary submit_insumo">Guardar Cambios</button>
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
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary submit_linea">Guardar Cambios</button>
      </div>
    </div>
  </div>
</div>



@endsection