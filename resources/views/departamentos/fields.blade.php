<!-- Nombredepartamento Field -->
<div class="col-sm-6 row text-[#101D49] dark:text-white">
    {!! Form::label('NombreDepartamento', 'Nombre área:') !!}
    {!! Form::text('NombreDepartamento', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Gerenciaid Field -->
<div class="col-sm-6 row text-[#101D49] dark:text-white">
    {!! Form::label('GerenciaID', 'Gerencia:') !!}

    {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
    pluck('NombreGerencia','GerenciaID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control','required' => true])!!}
    <p class="mt-1 text-xs text-slate-500">En TI el área siempre tiene gerencia.</p>
</div>