<!-- Nombreempleado Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpleado', 'Nombre empleado:') !!}
    <p>{{ $empleados->NombreEmpleado }}</p>
</div>

<!-- Puestoid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('PuestoID', 'Puesto:') !!}
    <p>{{ optional($empleados->puestos)->NombrePuesto ?: '—' }}</p>
</div>

<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('JefeDirectoID', 'Jefe inmediato:') !!}
    <p>{{ $empleados->jefeDirecto->NombreEmpleado ?? '—' }}</p>
</div>

<!-- Obraid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('ObraID', 'Ubicación:') !!}
    <p>{{ optional($empleados->obras)->NombreObra ?: '—' }}</p>
</div>

<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CentroCostoID', 'Centro de costos:') !!}
    <p>{{ optional($empleados->centroCosto)->NombreCentro ?: '—' }}</p>
</div>

<!-- Numtelefono Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefono', 'Num telefono:') !!}
    <p>{{ $empleados->NumTelefono }}</p>
</div>

<!-- Correo Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Correo', 'Correo:') !!}
    <p>{{ $empleados->Correo }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Estado', 'Estado:') !!}

    <p>{{ $empleados->Estado == '1' ? 'Activo' : ($empleados->Estado == '0' ? 'No Activo ' : '') }}</p>

</div>