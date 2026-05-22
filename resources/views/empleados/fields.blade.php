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
    {!! Form::text('NumTelefono', null, [
    'class' => 'form-control',
    'maxlength' => 10,
    'minlength' => 10,
    'pattern' => '[0-9]{10}',
    'title' => 'Debe contener exactamente 10 dígitos'
]) !!}
</div>

<!-- Correo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Correo', 'Correo:') !!}
    {!! Form::text('Correo', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- tipo_persona Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('tipo_persona', 'Tipo de persona:') !!}
    {!! Form::select('tipo_persona', ['FISICA' => 'FISICA', 'REFERENCIADO' => 'REFERENCIADO' ,'EXTRAORDINARIO' => 'EXTRAORDINARIO'], null, ['class' => 'form-control', 'placeholder' => 'Seleccionar tipo de persona']) !!}
</div>

<!-- Leyenda informativa Tipos de Persona -->
<div class="col-sm-12 mt-3 mb-4">
    <div class="p-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/10">
        <div class="flex items-start gap-2">
            <div class="text-sm">
                <p class="font-semibold text-blue-700 dark:text-blue-300 mb-2">Tipos de Persona:</p>
                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <span><strong class="text-green-700 dark:text-green-400">FÍSICA:</strong> Empleado regular activo en nómina con funciones operativas o administrativas.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span><strong class="text-orange-700 dark:text-orange-400">EXTRAORDINARIO:</strong> Puesto vacante o plaza sin asignar que requiere ser cubierta.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span><strong class="text-purple-700 dark:text-purple-400">REFERENCIADO:</strong> Registro de control para almacenes, inventarios o recursos de gerencia (no es personal).</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- Estado Field -->
@php
    $estadoEmpleado = 1;
    if (isset($empleados)) {
        $estadoEmpleado = (int) ($empleados->getAttributes()['Estado'] ?? ($empleados->Estado ? 1 : 0));
    }
    $estadoEmpleado = (int) old('Estado', $estadoEmpleado);
@endphp
<input type="hidden" name="Estado" value="{{ $estadoEmpleado }}">