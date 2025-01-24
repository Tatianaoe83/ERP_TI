<!-- Nombregerencia Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombreGerencia', 'Nombre gerencia:') !!}
    {!! Form::text('NombreGerencia', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Unidadnegocioid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('UnidadNegocioID', 'Unidad Negocio:') !!}
    {!! Form::number('UnidadNegocioID', null, ['class' => 'form-control']) !!}
</div>

<!-- Nombregerente Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombreGerente', 'Nombre gerente:') !!}
    {!! Form::text('NombreGerente', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>