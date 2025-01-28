<!-- Nombreempleado Field -->
<div class="col-sm-12">
    {!! Form::label('NombreEmpleado', 'Nombre empleado:') !!}
    <p>{{ $empleados->NombreEmpleado }}</p>
</div>

<!-- Puestoid Field -->
<div class="col-sm-12">
    {!! Form::label('PuestoID', 'Puesto:') !!}
    <p>{{ $empleados->puestoid->NombrePuesto }}</p>
</div>

<!-- Obraid Field -->
<div class="col-sm-12">
    {!! Form::label('ObraID', 'Obra:') !!}
    <p>{{ $empleados->obraid->NombreObra }}</p>
</div>

<!-- Numtelefono Field -->
<div class="col-sm-12">
    {!! Form::label('NumTelefono', 'Num telefono:') !!}
    <p>{{ $empleados->NumTelefono }}</p>
</div>

<!-- Correo Field -->
<div class="col-sm-12">
    {!! Form::label('Correo', 'Correo:') !!}
    <p>{{ $empleados->Correo }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-12">
    {!! Form::label('Estado', 'Estado:') !!}

    <p>{{ $empleados->Estado == '1' ? 'Activo' : ($empleados->Estado == '0' ? 'No Activo ' : '') }}</p>

</div>

