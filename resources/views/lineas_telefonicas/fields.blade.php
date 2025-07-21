<!-- Numtelefonico Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefonico', 'Num telefonico:') !!}
    {!! Form::text('NumTelefonico', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Planid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('PlanID', 'Plan:') !!}

    {!!Form::select('PlanID',App\Models\Planes::all()->
    pluck('NombrePlan','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>

<!-- Cuentapadre Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CuentaPadre', 'Cuenta padre:') !!}
    {!! Form::text('CuentaPadre', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Cuentahija Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CuentaHija', 'Cuenta hija:') !!}
    {!! Form::text('CuentaHija', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Tipolinea Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('TipoLinea', 'Tipo linea:') !!}
    {!! Form::text('TipoLinea', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Obraid Field -->
<div class="col-sm-6">
    {!! Form::label('ObraID', 'Obra:') !!}

    {!!Form::select('ObraID',App\Models\Obras::all()->
    pluck('NombreObra','ObraID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>

<!-- Fechafianza Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('FechaFianza', 'Fecha fianza:') !!}
    {!! Form::date('FechaFianza', null, ['class' => 'form-control']) !!}
</div>



<!-- Costofianza Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CostoFianza', 'Costo fianza:') !!}
    {!! Form::number('CostoFianza', null, ['class' => 'form-control']) !!}
</div>

<!-- Activo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white mt-3">
    <div class="form-check">
        {!! Form::hidden('Activo', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('Activo', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('Activo', 'Activo', ['class' => 'form-check-label']) !!}
    </div>
</div>


<!-- Disponible Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white mt-3">
    <div class="form-check">
        {!! Form::hidden('Disponible', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('Disponible', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('Disponible', 'Disponible', ['class' => 'form-check-label']) !!}
    </div>
</div>


<!-- Montorenovacionfianza Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('MontoRenovacionFianza', 'Monto renovacion fianza:') !!}
    {!! Form::number('MontoRenovacionFianza', null, ['class' => 'form-control']) !!}
</div>