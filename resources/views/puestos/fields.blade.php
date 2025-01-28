<!-- Nombrepuesto Field -->
<div class="form-group col-sm-6">
    {!! Form::label('NombrePuesto', 'Nombre puesto:') !!}
    {!! Form::text('NombrePuesto', null, ['class' => 'form-control','maxlength' => 75,'maxlength' => 75]) !!}
</div>

<!-- Departamentoid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('DepartamentoID', 'Departamento:') !!}

    {!!Form::select('DepartamentoID',App\Models\Departamentos::all()->
        pluck('NombreDepartamento','DepartamentoID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

   
</div>