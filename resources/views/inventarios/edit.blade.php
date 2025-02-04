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
                    @include('inventarios.fields')
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
                  <label>Categoria</label>
                  <input type="text" class="form-control" id="editCategoria" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Marca</label>
                  <input type="text" class="form-control" id="editMarca" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Caracteristicas</label>
                  <textarea class="form-control"  rows="3" id="editCaracteristicas" required></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Modelo</label>
                  <input type="text" class="form-control" id="editModelo" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6"> 
                <div class="form-group">
                  <label>Precio</label>
                  <input type="text" class="form-control" id="editPrecio" required>
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
                  <input type="text" class="form-control" id="editFolio" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Unidad de Negocio</label>
                  <input type="text" class="form-control" id="editNegocio">
                </div>
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
                  <label>Gerencia Equipo</label>
                  <input type="text" class="form-control" id="editGerenciaEquipo">
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection


