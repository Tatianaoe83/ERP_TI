<!-- Companiaid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('CompaniaID', 'Compania:') !!}

    {!!Form::select('CompaniaID',App\Models\CompaniasLineasTelefonicas::all()->
        pluck('Compania','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>

<!-- Nombreplan Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombrePlan', 'Nombre plan:') !!}
    {!! Form::text('NombrePlan', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Precioplan Field -->
<div class="form-group col-sm-6">
    {!! Form::label('PrecioPlan', 'Precio plan:') !!}
    {!! Form::number('PrecioPlan', null, ['class' => 'form-control']) !!}
</div>