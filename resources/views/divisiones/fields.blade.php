<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreDivision', 'Nombre división:') !!}
    {!! Form::text('NombreDivision', null, ['class' => 'form-control', 'maxlength' => 100, 'required' => true]) !!}
    <p class="mt-1 text-xs text-slate-500">En TI la unidad de negocio siempre pertenece a una división.</p>
</div>
