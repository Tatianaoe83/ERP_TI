<!-- Nombregerencia Field -->
<div class="col-sm-12">
    {!! Form::label('NombreGerencia', 'Nombre gerencia:') !!}
    <p>{{ $gerencia->NombreGerencia }}</p>
</div>

<!-- Unidadnegocioid Field -->
<div class="col-sm-12">
    {!! Form::label('UnidadNegocioID', 'Unidad negocio:') !!}
    <p>{{ $gerencia->unidadesdenegocio->NombreEmpresa}}</p>
</div>

<!-- Nombregerente Field -->
<div class="col-sm-12">
    {!! Form::label('NombreGerente', 'Nombre gerente:') !!}
    <p>{{ $gerencia->NombreGerente }}</p>
</div>