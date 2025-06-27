<!-- Nombreinsumo Field -->
<div class="col-sm-12">
    {!! Form::label('NombreInsumo', 'Nombre insumo:') !!}
    <p>{{ $insumos->NombreInsumo }}</p>
</div>

<!-- Categoriaid Field -->
<div class="col-sm-12">
    {!! Form::label('CategoriaID', 'Categoria:') !!}
    <p>{{ $insumos->categorias->Categoria }}</p>
</div>

<!-- Costomensual Field -->
<div class="col-sm-12">
    {!! Form::label('CostoMensual', 'Costo mensual:') !!}
    <p>{{ $insumos->CostoMensual }}</p>
</div>

<!-- Costoanual Field -->
<div class="col-sm-12">
    {!! Form::label('CostoAnual', 'Costo anual:') !!}
    <p>{{ $insumos->CostoAnual }}</p>
</div>

<!-- Frecuenciadepago Field -->
<div class="col-sm-12">
    {!! Form::label('FrecuenciaDePago', 'Frecuencia de pago:') !!}
    <p>{{ $insumos->FrecuenciaDePago }}</p>
</div>

<!-- Observaciones Field -->
<div class="col-sm-12">
    {!! Form::label('Observaciones', 'Observaciones:') !!}
    <p>{{ $insumos->Observaciones }}</p>
</div>

