<!-- Nombreinsumo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreInsumo', 'Nombre insumo:') !!}
    {!! Form::text('NombreInsumo', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Categoriaid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CategoriaID', 'Categoria:') !!}

    {!!Form::select('CategoriaID',App\Models\Categorias::all()-> where ("TipoID", 1)->
    pluck('Categoria','ID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control','style' => 'width: 100%'])!!}

</div>

<!-- Costomensual Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CostoMensual_fields', 'Costo mensual:') !!}
    {!! Form::number('CostoMensual_fields', $costoMensual_fields, ['class' => 'form-control', 'step' => '0.01', 'id' => 'CostoMensual_fields']) !!}
</div>

<!-- Costoanual Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('CostoAnual_fields', 'Costo anual:') !!}
    {!! Form::number('CostoAnual_fields', $costoAnual_fields, ['class' => 'form-control', 'step' => '0.01', 'id' => 'CostoAnual_fields']) !!}
</div>

<!-- Importe Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Importe', 'IVA (%):') !!}
    {!! Form::number('Importe', null, ['class' => 'form-control', 'step' => '0.01', 'id' => 'Importe_fields', 'min' => '0', 'max' => '100']) !!}
    <small class="form-text text-muted">Porcentaje de IVA que se aplica al costo</small>
</div>

<!-- Costos con IVA -->
<div class="col-sm-12">
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="card-title mb-0 text-[#101D49] dark:text-white">
                <i class="fas fa-calculator me-2"></i>Costos con ajustes aplicados
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <label class="text-[#101D49] dark:text-white">Costo Mensual:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="CostoMensual" name="CostoMensual" readonly style="background-color: #f8f9fa;">
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="text-[#101D49] dark:text-white">Costo Anual:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="CostoAnual" name="CostoAnual" readonly style="background-color: #f8f9fa;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Frecuenciadepago Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('FrecuenciaDePago', 'Frecuencia de pago:') !!}
    {!! Form::select('FrecuenciaDePago', ['Mensual' => 'Mensual', 'Pago único' => 'Pago único', 'Anual' => 'Anual'], null, ['class' => 'form-control', 'id' => 'FrecuenciaDePago']) !!}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {  
    const costoMensual = document.getElementById('CostoMensual_fields');
    const costoAnual = document.getElementById('CostoAnual_fields');
    const importe = document.getElementById('Importe_fields');
    
    let isCalculating = false; 
    
    function calcularConIVA(campoModificado) {
        if (isCalculating) return;
        isCalculating = true;
        
        const mensual = parseFloat(costoMensual.value) || 0;
        const anual = parseFloat(costoAnual.value) || 0;
        const ivaPorcentaje = parseFloat(importe.value) || 0;
        
        if (campoModificado === 'mensual') {
        
            if (mensual > 0) {
                const nuevoAnual = mensual * 12;
                costoAnual.value = nuevoAnual.toFixed(2);
            } else {
                costoAnual.value = '';
            }
        } 
        else if (campoModificado === 'anual') {
           
            if (anual > 0) {
                const nuevoMensual = anual / 12;
                costoMensual.value = nuevoMensual.toFixed(2);
            } else {
                costoMensual.value = '';
            }
        }

        
        isCalculating = false;
    }
    
    function calcularCostoConIVA(base, ivaPorcentaje) {
        if (base <= 0 || ivaPorcentaje <= 0) return base;
        return base * (1 + ivaPorcentaje / 100);
    }

    
    if (costoMensual) {
        costoMensual.addEventListener('input', function() {
            calcularConIVA('mensual');
        });
    }
    
    if (costoAnual) {
        costoAnual.addEventListener('input', function() {
            calcularConIVA('anual');
        });
    }
    
    
    if (importe) {
        importe.addEventListener('input', function() {
            actualizarCostosConIVA();
        });
    }
    
    function actualizarCostosConIVA() {
        const mensual = parseFloat(costoMensual.value) || 0;
        const anual = parseFloat(costoAnual.value) || 0;
        const ivaPorcentaje = parseFloat(importe.value) || 0;
        
        let mensualConIVA, anualConIVA;
        
        
        if (ivaPorcentaje > 0) {
            mensualConIVA = calcularCostoConIVA(mensual, ivaPorcentaje);
            anualConIVA = Math.round(mensualConIVA) * 12;
        } else {
            
            mensualConIVA = mensual;
            anualConIVA = Math.round(mensual) * 12;
        }
        
        
        document.getElementById('CostoMensual').value = Math.round(mensualConIVA);
        document.getElementById('CostoAnual').value = Math.round(anualConIVA);
    }
    
    function recalcularConIVA(campoModificado) {
        if (isCalculating) return;
        isCalculating = true;
        
        const mensual = parseFloat(costoMensual.value) || 0;
        const anual = parseFloat(costoAnual.value) || 0;
        const ivaPorcentaje = parseFloat(importe.value) || 0;
        
        if (campoModificado === 'mensual') {

            if (mensual > 0) {
                const nuevoAnual = mensual * 12;
                costoAnual.value = nuevoAnual.toFixed(2);
            } else {
                costoAnual.value = '';
            }
        } 
        else if (campoModificado === 'anual') {
           
            if (anual > 0) {
                const nuevoMensual = anual / 12;
                costoMensual.value = nuevoMensual.toFixed(2);
            } else {
                costoMensual.value = '';
            }
        }
        
        
        actualizarCostosConIVA();
        isCalculating = false;
    }
    
    
    if (costoMensual) {
        costoMensual.addEventListener('input', function() {
            recalcularConIVA('mensual');
        });
    }
    
    if (costoAnual) {
        costoAnual.addEventListener('input', function() {
            recalcularConIVA('anual');
        });
    }
    
    
    const mensualInicial = parseFloat(costoMensual.value) || 0;
    const anualInicial = parseFloat(costoAnual.value) || 0;
    
    if (mensualInicial > 0) {
        recalcularConIVA('mensual');
    } else if (anualInicial > 0) {
        recalcularConIVA('anual');
    } else {
        actualizarCostosConIVA(); 
    }
});
</script>

<!-- Observaciones Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Observaciones', 'Observaciones:') !!}
    {!! Form::textarea('Observaciones', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255,'rows' => 3]) !!}
</div>