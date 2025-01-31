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

            {!! Form::model($inventario, ['route' => ['inventarios.update', $inventario->EmpleadoID], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('inventarios.fields')
                </div>
            </div>

            <div class="card-footer">
            </div>

            {!! Form::close() !!}

        </div>
    </div>
    </section>
    
<!-- Modal de Edición -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Equipo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId">
                    <div class="form-group">
                        <label>Categoria Equipo</label>
                        <input type="text" class="form-control" id="editCategoria">
                    </div>
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" class="form-control" id="editMarca">
                    </div>
                    <div class="form-group">
                        <label>Caracteristicas</label>
                        <input type="text" class="form-control" id="editCaracteristicas">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


