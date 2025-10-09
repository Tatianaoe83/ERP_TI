<!-- Nombreinsumo Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreInsumo', 'Nombre insumo:') !!}
    <p>{{ $insumos->NombreInsumo }}</p>
</div>

<!-- Categoriaid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CategoriaID', 'Categoria:') !!}
    <p>{{ $insumos->categoriaid->Categoria }}</p>
</div>

<!-- Costomensual Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CostoMensual', 'Costo mensual:') !!}
    <p>{{ $insumos->CostoMensual }}</p>
</div>

<!-- Costoanual Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CostoAnual', 'Costo anual:') !!}
    <p>{{ $insumos->CostoAnual }}</p>
</div>

<!-- Importe Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Importe', 'Inflación (%):') !!}
    <p>{{ number_format($insumos->Importe, 2) }}%</p>
</div>

<!-- Costos con IVA -->
<div class="col-sm-12">
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="card-title mb-0 text-[#101D49] dark:text-white">
                <i class="fas fa-calculator me-2"></i>Costos con Inflación Aplicado
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <label class="text-[#101D49] dark:text-white">Costo Mensual con Inflación:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" value="{{ $insumos->Importe > 0 ? number_format(round($insumos->CostoMensual * (1 + $insumos->Importe/100))) : number_format(round($insumos->CostoMensual)) }}" readonly style="background-color: #f8f9fa;">
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="text-[#101D49] dark:text-white">Costo Anual con Inflación:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" value="{{ $insumos->Importe > 0 ? number_format(round($insumos->CostoAnual * (1 + $insumos->Importe/100))) : number_format(round($insumos->CostoAnual)) }}" readonly style="background-color: #f8f9fa;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Frecuenciadepago Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('FrecuenciaDePago', 'Frecuencia de pago:') !!}
    <p>{{ $insumos->FrecuenciaDePago }}</p>
</div>

<!-- Observaciones Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Observaciones', 'Observaciones:') !!}
    <p>{{ $insumos->Observaciones }}</p>
</div>