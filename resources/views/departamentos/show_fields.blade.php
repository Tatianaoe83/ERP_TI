<!-- Nombredepartamento Field -->
<div class="col-sm-12">
    {!! Form::label('NombreDepartamento', 'Nombre departamento:') !!}
    <p>{{ $departamentos->NombreDepartamento }}</p>
</div>

<!-- Gerenciaid Field -->
<div class="col-sm-12">
    {!! Form::label('GerenciaID', 'Gerencia:') !!}
    <p>{{ $departamentos->gerencia->NombreGerencia }}</p>
</div>

