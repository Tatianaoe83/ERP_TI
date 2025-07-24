<!-- Imagen Field -->
<div class="col-sm-12">
    {!! Form::label('Imagen', 'Imagen:') !!}
    <p>{{ $facturas->Imagen }}</p>
</div>

<!-- Descripcion Field -->
<div class="col-sm-12">
    {!! Form::label('Descripcion', 'Descripcion:') !!}
    <p>{{ $facturas->Descripcion }}</p>
</div>

<!-- Importe Field -->
<div class="col-sm-12">
    {!! Form::label('Importe', 'Importe:') !!}
    <p>{{ $facturas->Importe }}</p>
</div>

<!-- Insumoid Field -->
<div class="col-sm-12">
    {!! Form::label('InsumoID', 'Insumoid:') !!}
    <p>{{ $facturas->InsumoID }}</p>
</div>

