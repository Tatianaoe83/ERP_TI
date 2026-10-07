<!-- Nombregerencia Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreGerencia', 'Nombre gerencia:') !!}
    {!! Form::text('NombreGerencia', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Direccion Field. La unidad se toma de aquí al guardar. -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('DireccionID', 'Dirección:') !!}
    {!! Form::select(
        'DireccionID',
        App\Models\Direccion::with('unidadesdenegocio')->orderBy('NombreDireccion')->get()->mapWithKeys(function ($d) {
            $unidad = $d->unidadesdenegocio->NombreEmpresa ?? '';
            return [$d->DireccionID => trim($d->NombreDireccion.' — '.$unidad)];
        }),
        null,
        ['placeholder' => 'Seleccionar', 'class' => 'jz form-control', 'required' => true]
    ) !!}
    <p class="mt-1 text-xs text-slate-500">En TI la gerencia siempre cuelga de una dirección. En Capital Humano ese nivel puede ir vacío. El centro de costos no se captura aquí.</p>
</div>

<!-- Nombregerente Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreGerente', 'Nombre gerente:') !!}
    {!! Form::text('NombreGerente', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>
