<!-- Categoriaid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CategoriaID', 'Categoria:') !!}

    {!!Form::select('CategoriaID',App\Models\Categorias::all()-> where ("TipoID", 2)->
    pluck('Categoria','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'style' => 'width: 100%'])!!}


</div>

<!-- Marca Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Marca', 'Marca:') !!}
    {!! Form::text('Marca', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Caracteristicas Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Caracteristicas', 'Caracteristicas:') !!}
    {!! Form::text('Caracteristicas', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Modelo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Modelo', 'Modelo:') !!}
    {!! Form::text('Modelo', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Precio Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Precio', 'Precio:') !!}
    {!! Form::number('Precio', null, ['class' => 'form-control','min' => '0','placeholder' => '0']) !!}
</div>