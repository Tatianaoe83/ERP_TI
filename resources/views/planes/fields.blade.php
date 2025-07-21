<!-- Companiaid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CompaniaID', 'Compania:') !!}

    {!!Form::select('CompaniaID',App\Models\CompaniasLineasTelefonicas::all()->
    pluck('Compania','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>

<!-- Nombreplan Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombrePlan', 'Nombre plan:') !!}
    {!! Form::text('NombrePlan', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Precioplan Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('PrecioPlan', 'Precio plan:') !!}
    {!! Form::number('PrecioPlan', null, ['class' => 'form-control']) !!}
</div>