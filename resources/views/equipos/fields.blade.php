<!-- Categoriaid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('CategoriaID', 'Categoria:') !!}

    {!!Form::select('CategoriaID',App\Models\Categorias::all()->
        pluck('Categoria','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

   
</div>

<!-- Marca Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Marca', 'Marca:') !!}
    {!! Form::text('Marca', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Caracteristicas Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Caracteristicas', 'Caracteristicas:') !!}
    {!! Form::text('Caracteristicas', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Modelo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Modelo', 'Modelo:') !!}
    {!! Form::text('Modelo', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Precio Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Precio', 'Precio:') !!}
    {!! Form::number('Precio', null, ['class' => 'form-control']) !!}
</div>