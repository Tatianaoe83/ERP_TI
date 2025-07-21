<!-- Nombrepuesto Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombrePuesto', 'Nombre puesto:') !!}
    {!! Form::text('NombrePuesto', null, ['class' => 'form-control','maxlength' => 75,'maxlength' => 75]) !!}
</div>

<!-- Departamentoid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('DepartamentoID', 'Departamento:') !!}

    {!!Form::select('DepartamentoID',App\Models\Departamentos::select(DB::raw("CONCAT(departamentos.NombreDepartamento,' - ', gerencia.NombreGerencia) AS NombreDepartamento, departamentos.DepartamentoID"))
    ->join('gerencia', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
    ->pluck('NombreDepartamento','DepartamentoID'),null,[ 'style' => 'width: 100%','placeholder' => 'SELECCIONAR','class'=>'form-control jz'])!!}
</div>