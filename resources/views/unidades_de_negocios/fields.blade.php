<!-- Nombreempresa Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpresa', 'Nombre empresa:') !!}
    {!! Form::text('NombreEmpresa', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Rfc Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('RFC', 'Rfc:') !!}
    {!! Form::text('RFC', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Direccion Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Direccion', 'Direccion:') !!}
    {!! Form::text('Direccion', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Numtelefono Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefono', 'Num. telefono:') !!}
    {!! Form::text('NumTelefono', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>