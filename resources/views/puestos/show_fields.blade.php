<!-- Nombrepuesto Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombrePuesto', 'Nombre puesto:') !!}
    <p>{{ $puestos->NombrePuesto }}</p>
</div>

<!-- Departamentoid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('DepartamentoID', 'Departamento:') !!}
    <p>{{ $puestos->departamentos->NombreDepartamento }}</p>
</div>