@extends('layouts.app')

@section('content')
<!--- <h3 class="text-[#101D49] dark:text-white">Presupuestos</h3> --->
<div class="row">

  <div class="col-12 col-md-12 col-lg-12">

    <h4 class="text-[#101D49] dark:text-white">Generar reportes de presupuestos</h4>
    <form enctype="multipart/form-data" action="presupuesto/descargar" method="POST" target="_blank">
      {{ csrf_field() }}

      <div class="flex flex-col gap-2">
        {!! Form::label('tipo', 'Tipo:', ['class' => 'text-[#101D49] dark:text-white']) !!}
        <select name="tipo" id="semestre" class="form-control" required>
          <option value="mens">Mensual</option>
          <option value="anual">Anual</option>

        </select>


        {!! Form::label('GerenciaID', 'Gerencia:', ['class' => 'text-[#101D49] dark:text-white']) !!}
        {!! Form::select('GerenciaID', $genusuarios->pluck('NombreGerencia','GerenciaID'), null, ['placeholder' => 'Seleccionar', 'class'=>'jz form-control', 'required']) !!}

        <div>
          <button type="submit" class="btn btn-success" name="submitbutton" value="pdf">Generar PDF</button>
          <button type="submit" class="btn btn-primary" name="submitbutton" value="excel">Generar Excel</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection