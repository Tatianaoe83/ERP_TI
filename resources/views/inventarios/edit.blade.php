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
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Equipo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                <input type="hidden" id="editId">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-4"> 
                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" class="form-control" id="editCategoria">
                            </div>
                        </div>
                        <div class="col-md-4 ms-auto">
                             <div class="form-group">
                             <label>Marca</label>
                             <input type="text" class="form-control" id="editMarca">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4"> 
                            <div class="form-group">
                                <label>Caracteristicas</label>
                                <input type="text" class="form-control" id="editCaracteristicas">
                            </div>
                        </div>
                        <div class="col-md-4 ms-auto">
                             <div class="form-group">
                                <label>Modelo</label>
                                <input type="text" class="form-control" id="editModelo">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4"> 
                            <div class="form-group">
                                <label>Precio</label>
                                <input type="text" class="form-control" id="editPrecio">
                            </div>
                        </div>
                        <div class="col-md-4 ms-auto">
                             <div class="form-group">
                             <label>Fecha Asignacion</label>
                             <input type="text" class="form-control" id="editFechaAsignacion">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4"> 
                            <div class="form-group">
                                <label>Fecha de Compra</label>
                                <input type="text" class="form-control" id="editFechaDeCompra">
                            </div>
                        </div>
                        <div class="col-md-4 ms-auto">
                             <div class="form-group">
                             <label>Num. Serie</label>
                             <input type="text" class="form-control" id="editNumSerie">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4"> 
                            <div class="form-group">
                                <label>Folio</label>
                                <input type="text" class="form-control" id="editFolio">
                            </div>
                        </div>
                        <div class="col-md-4 ms-auto">
                             <div class="form-group">
                                <label>Gerencia Equipo</label>
                                 <input type="text" class="form-control" id="editGerenciaEquipo">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4"> 
                            <div class="form-group">
                                <label>Comentarios</label>
                                <input type="text" class="form-control" id="editComentarios">
                            </div>
                        </div>
                        
                    </div>
                
                </div>

                    <button class="btn btn-primary submit_equipo">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


