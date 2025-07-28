<!-- Imagen Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Imagen', 'Imagen:') !!}
    {!! Form::text('Imagen', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Descripcion Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('Descripcion', 'Descripcion:') !!}
    {!! Form::textarea('Descripcion', null, ['class' => 'form-control']) !!}
</div>

<!-- Importe Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Importe', 'Importe:') !!}
    {!! Form::number('Importe', null, ['class' => 'form-control']) !!}
</div>

<!-- Insumoid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('InsumoID', 'Insumoid:') !!}
    {!! Form::number('InsumoID', null, ['class' => 'form-control']) !!}
</div>