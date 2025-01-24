<!-- Nombreempleado Field -->
<div class="col-sm-12">
    {!! Form::label('NombreEmpleado', 'Nombre empleado:') !!}
    <p>{{ $empleados->NombreEmpleado }}</p>
</div>

<!-- Puestoid Field -->
<div class="col-sm-12">
    {!! Form::label('PuestoID', 'Puesto:') !!}
    <p>{{ $empleados->PuestoID }}</p>
</div>

<!-- Obraid Field -->
<div class="col-sm-12">
    {!! Form::label('ObraID', 'Obra:') !!}
    <p>{{ $empleados->ObraID }}</p>
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
    <p>{{ $empleados->Estado }}</p>
</div>

