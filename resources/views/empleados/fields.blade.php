<!-- Nombreempleado Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpleado', 'Nombre empleado:') !!}
    {!! Form::text('NombreEmpleado', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Puestoid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('PuestoID', 'Puesto:') !!}

    {!!Form::select('PuestoID',App\Models\Puestos::select(DB::raw("CONCAT(puestos.NombrePuesto,' - ', gerencia.NombreGerencia) AS NombrePuesto, puestos.PuestoID"))
    ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
    ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
    ->pluck('NombrePuesto','PuestoID'),null,[ 'style' => 'width: 100%','placeholder' => 'SELECCIONAR','class'=>'form-control jz'])!!}


</div>

<!-- Obraid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('ObraID', 'Obra:') !!}

    {!!Form::select('ObraID',App\Models\Obras::all()->
    pluck('NombreObra','ObraID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}


</div>

<!-- Numtelefono Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefono', 'Num telefono:') !!}
    {!! Form::text('NumTelefono', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Correo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Correo', 'Correo:') !!}
    {!! Form::text('Correo', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Estado Field -->
<div class="mt-4 col-sm-6 text-[#101D49] dark:text-white">
    <div class="form-check">
        {!! Form::hidden('Estado', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('Estado', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('Estado', 'Activo', ['class' => 'form-check-label']) !!}
    </div>
</div>