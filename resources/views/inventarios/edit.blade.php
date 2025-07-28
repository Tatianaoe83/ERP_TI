@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Inventario de:</h3>
<h5 style="margin-bottom: 6px;padding-left: 5px;">{{$inventario->NombreEmpleado}}</h5>

<div class="content px-3">

  @include('adminlte-templates::common.errors')

  <div class="row">

    <div class="row">
      <div class="col-md-6">
      </div>
      <div class="col-md-6" style="text-align: end;">

        <a href="{{ route('inventarios.index') }}" class="btn btn-danger">Regresar</a>


      </div>
    </div>


    @include('inventarios.fields')
  </div>
</div>
</section>

<!-- Modal de Edición -->
<div class="modal" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content dark:bg-[#101010]">
      <div class="modal-header">
        <h5 class="modal-title" id="titulo"></h5>
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
              <div class="col-md-6">
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
              <div class="col-md-6">
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

            <div class="row">
              <div class="col-md-6">
                <div class="dark:text-white">
                  <label>Folio</label>
                  <input type="text" class="form-control" id="editFolio" required="required">
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