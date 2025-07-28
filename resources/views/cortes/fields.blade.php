<!-- Mes Field -->
<div class="form-group col-sm-6">
    {!! Form::label('Mes', 'Mes:') !!}
    {!! Form::text('Mes', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Gerenciaid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('GerenciaID', 'Gerenciaid:') !!}
    {!! Form::number('GerenciaID', null, ['class' => 'form-control']) !!}
</div>

<!-- Insumoid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('InsumoID', 'Insumoid:') !!}
    {!! Form::number('InsumoID', null, ['class' => 'form-control']) !!}
</div>