<!-- Nombreinsumo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreInsumo', 'Nombre insumo:') !!}
    {!! Form::text('NombreInsumo', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Categoriaid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CategoriaID', 'Categoria:') !!}

    {!!Form::select('CategoriaID',App\Models\Categorias::all()-> where ("TipoID", 1)->
    pluck('Categoria','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

</div>

<!-- Costomensual Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CostoMensual', 'Costo mensual:') !!}
    {!! Form::number('CostoMensual', null, ['class' => 'form-control']) !!}
</div>

<!-- Costoanual Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CostoAnual', 'Costo anual:') !!}
    {!! Form::number('CostoAnual', null, ['class' => 'form-control']) !!}
</div>

<!-- Frecuenciadepago Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('FrecuenciaDePago', 'Frecuencia de pago:') !!}
    {!! Form::text('FrecuenciaDePago', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Observaciones Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Observaciones', 'Observaciones:') !!}
    {!! Form::text('Observaciones', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>