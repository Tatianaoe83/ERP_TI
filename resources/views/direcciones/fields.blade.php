<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreDireccion', 'Nombre dirección:') !!}
    {!! Form::text('NombreDireccion', null, ['class' => 'form-control', 'maxlength' => 100, 'required' => true]) !!}
</div>

<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('UnidadNegocioID', 'Unidad de negocio:') !!}
    {!! Form::select('UnidadNegocioID', App\Models\UnidadesDeNegocio::orderBy('NombreEmpresa')->pluck('NombreEmpresa', 'UnidadNegocioID'), null, ['placeholder' => 'Seleccionar', 'class' => 'jz form-control', 'required' => true]) !!}
    <p class="mt-1 text-xs text-slate-500">En TI la dirección siempre pertenece a una unidad. En Capital Humano ese nivel puede ir vacío.</p>
</div>

<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('estado', 'Activa:') !!}
    {!! Form::select('estado', [1 => 'Si', 0 => 'No'], null, ['class' => 'form-control']) !!}
</div>
