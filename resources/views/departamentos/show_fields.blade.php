<!-- Nombredepartamento Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreDepartamento', 'Nombre departamento:') !!}
    <p>{{ $departamentos->NombreDepartamento }}</p>
</div>

<!-- Gerenciaid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('GerenciaID', 'Gerencia:') !!}
    <p>{{ $departamentos->gerencia->NombreGerencia }}</p>
</div>