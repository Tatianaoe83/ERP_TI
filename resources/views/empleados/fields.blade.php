<!-- Nombreempleado Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombreEmpleado', 'Nombre empleado:') !!}
    {!! Form::text('NombreEmpleado', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Puestoid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('PuestoID', 'Puesto:') !!}
    {!! Form::number('PuestoID', null, ['class' => 'form-control']) !!}
</div>

<!-- Obraid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ObraID', 'Obra:') !!}
    {!! Form::number('ObraID', null, ['class' => 'form-control']) !!}
</div>

<!-- Numtelefono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NumTelefono', 'Num telefono:') !!}
    {!! Form::text('NumTelefono', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Correo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Correo', 'Correo:') !!}
    {!! Form::text('Correo', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Estado Field -->
<div class="form-group col-sm-6">
    <div class="form-check">
        {!! Form::hidden('Estado', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('Estado', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('Estado', 'Estado', ['class' => 'form-check-label']) !!}
    </div>
</div>
