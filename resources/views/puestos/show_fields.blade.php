<!-- Nombrepuesto Field -->
<div class="col-sm-12">
    {!! Form::label('NombrePuesto', 'Nombre puesto:') !!}
    <p>{{ $puestos->NombrePuesto }}</p>
</div>

<!-- Departamentoid Field -->
<div class="col-sm-12">
    {!! Form::label('DepartamentoID', 'Departamento:') !!}
    <p>{{ $puestos->departamentoid->NombreDepartamento }}</p>
</div>

