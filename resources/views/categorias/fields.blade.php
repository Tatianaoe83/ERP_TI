<!-- Tipoid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('TipoID', 'Tipo:') !!}

    {!!Form::select('TipoID',App\Models\TiposDeCategorias::all()->
        pluck('Categoria','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>

<!-- Categoria Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Categoria', 'Categoria:') !!}
    {!! Form::text('Categoria', null, ['class' => 'form-control','maxlength' => 75,'maxlength' => 75]) !!}
</div>