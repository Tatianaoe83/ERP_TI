@extends('layouts.app')

@section('content')
<section class="section">
        <div class="section-header">
            <h3 class="page__heading">Inventario de:</h3> <h5 style="margin-bottom: 6px;padding-left: 5px;">{{$inventario->NombreEmpleado}}</h5>
        </div>

    
    <div class="section-body">

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">


            <div class="card-body">
                <div class="row">
                  <p>tranferir</p>
                </div>
            </div>

            <div class="card-footer">
            </div>
   

        </div>
    </div>
    </section>
    
<!-- Modal de Edición -->
<div class="modal" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
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
                <div class="form-group">
                  <label for="editCategoria">Categoría del Equipo </label>
                  <input type="text" class="form-control" id="editCategoria" name="editCategoria" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Marca </label>
                  <input type="text" class="form-control" id="editMarca" name="Marca" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Caracteristicas</label>
                  <textarea class="form-control"  rows="3" id="editCaracteristicas" required ></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Modelo </label>
                  <input type="text" class="form-control" id="editModelo" name="Modelo" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Precio</label>
                  <input type="number" class="form-control" id="editPrecio" required  min="1" step="1" pattern="\d*">
                  
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Fecha Asignacion</label>
                  <input type="date" class="form-control" id="editFechaAsignacion" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Fecha de Compra</label>
                  <input type="date" class="form-control" id="editFechaDeCompra" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Num. Serie</label>
                  <input type="text" class="form-control" id="editNumSerie" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Folio</label>
                  <input type="text" class="form-control" id="editFolio" required="required">
                </div>
              </div>
              <div class="col-md-6">
               
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Comentarios</label>
                  <input type="text" class="form-control" id="editComentarios">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
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
            <button  type="button" class="btn btn-primary submit_equipo">Guardar Cambios</button>
          </div>
        
      </div>
      
    </div>
  </div>
</div>

   
<!-- Modal de Edición insumo-->
<div class="modal" id="editModalInsumo" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
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
                <div class="form-group">
                  <label for="editCategoriaInsumo">Categoría del Insumo </label>
                  <input type="text" class="form-control" id="editCategoriaInsumo" name="editCategoriaInsumo" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nombre Insumo </label>
                  <input type="text" class="form-control" id="editNombreInsumo" name="editNombreInsumo" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Costo Mensual</label>
                  <input type="text" class="form-control" id="editCostoMensual" name="editCostoMensual" required>
                
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Costo Anual </label>
                  <input type="text" class="form-control" id="editCostoAnual" name="editCostoAnual" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Frecuencia de pago</label>
                  <input type="text" class="form-control" id="editFrecuenciaDePago" name="editFrecuenciaDePago" required>
                  
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Observaciones</label>
                  <input type="text" class="form-control" id="editobserv" name="editobserv">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Fecha de asignacion</label>
                  <input type="date" class="form-control" id="editFechaDeAsigna" name="editFechaDeAsigna" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Num. Serie</label>
                  <input type="text" class="form-control" id="editNumSerieInsu" id="editNumSerieInsu" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Mes de pago</label>
                
                  <select class="form-select"  id="editMesDePago" name="editMesDePago" required aria-label="Default select example">
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
              <div class="form-group">
                  <label>Comentarios</label>
                  <input type="text" class="form-control" id="editComentariosInsumo" name="editComentariosInsumo">
                </div>
              </div>
            </div>

          
          </div>
          </form>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button  type="button" class="btn btn-primary submit_insumo">Guardar Cambios</button>
          </div>
        
      </div>
      
    </div>
  </div>
  </div>


  <!-- Modal de Edición linea-->
<div class="modal" id="editModalLinea" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
      <h5 class="modal-title" id="titulolinea"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <form id="editFormLinea">
          <input type="hidden" id="editId_linea">
          <input type="hidden" id="editEmp_linea">


          <input type="hidden" id="lineaid">

          

          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label for="editfechalinea">Fecha de asignacion</label>
                  <input type="date" class="form-control" id="editfechalinea" name="editfechalinea" required>
                  <div class="invalid-feedback">Este campo es requerido</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Comentarios </label>
                  <input type="text" class="form-control" id="editcomenl" name="editcomenl" >
            
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


