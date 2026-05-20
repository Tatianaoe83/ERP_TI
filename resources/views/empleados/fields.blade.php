<!-- tipo_persona Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('tipo_persona', 'Tipo de persona:') !!}
    {!! Form::select('tipo_persona', ['FISICA' => 'FISICA', 'REFERENCIADO' => 'REFERENCIADO', 'EXTRAORDINARIO' => 'EXTRAORDINARIO'], null, ['class' => 'form-control', 'id' => 'tipo_persona', 'placeholder' => 'Seleccionar tipo de persona', 'required' => true]) !!}
</div>

<!-- Nombreempleado Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpleado', 'Nombre empleado:') !!}
    {!! Form::text('NombreEmpleado', null, [
        'class' => 'form-control',
        'maxlength' => 100,
        'id' => 'NombreEmpleado',
        'placeholder' => 'Seleccione primero el tipo de persona',
        'required' => true,
    ]) !!}
    <small id="nombre-empleado-ayuda" class="form-text text-muted"></small>
</div>

<!-- Puestoid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('PuestoID', 'Puesto:') !!}

    {!!Form::select('PuestoID',App\Models\Puestos::select(DB::raw("CONCAT(puestos.NombrePuesto,' - ', gerencia.NombreGerencia) AS NombrePuesto, puestos.PuestoID"))
    ->join('departamentos', 'puestos.DepartamentoID', '=', 'departamentos.DepartamentoID')
    ->join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
    ->pluck('NombrePuesto','PuestoID'),null,[ 'style' => 'width: 100%','placeholder' => 'SELECCIONAR','class'=>'form-control jz', 'required' => true])!!}


</div>

<!-- Obraid Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('ObraID', 'Obra:') !!}

    {!!Form::select('ObraID',App\Models\Obras::all()->
    pluck('NombreObra','ObraID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control', 'required' => true])!!}


</div>

<!-- Numtelefono Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefono', 'Num telefono:') !!}
    {!! Form::text('NumTelefono', null, [
        'class' => 'form-control',
        'id' => 'NumTelefono',
        'maxlength' => 10,
    ]) !!}
    <small id="telefono-ayuda" class="form-text text-muted"></small>
</div>

<!-- Correo Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Correo', 'Correo:') !!}
    {!! Form::text('Correo', null, [
        'class' => 'form-control',
        'id' => 'Correo',
        'maxlength' => 150,
    ]) !!}
    <small id="correo-ayuda" class="form-text text-muted"></small>
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

<script>
window.addEventListener('load', function () {
    var formatosNombre = {
        FISICA: {
            placeholder: 'Ej: PEREZ GOMEZ JUAN CARLOS',
            ayuda: 'Formato: apellidos primero y nombre completo.'
        },
        REFERENCIADO: {
            placeholder: 'Ej: ALMACEN TI',
            ayuda: 'Use un nombre descriptivo del área o recurso (no persona física).'
        },
        EXTRAORDINARIO: {
            placeholder: 'Ej: VACANTE 1',
            ayuda: 'Use el formato VACANTE seguido de un número o identificador.'
        }
    };

    function actualizarCamposPorTipo() {
        var tipo = $('#tipo_persona').val();
        var esFisica = tipo === 'FISICA';
        var $nombre = $('#NombreEmpleado');
        var $nombreAyuda = $('#nombre-empleado-ayuda');
        var $telefono = $('#NumTelefono');
        var $correo = $('#Correo');
        var formato = formatosNombre[tipo];

        if (!formato) {
            $nombre.attr('placeholder', 'Seleccione primero el tipo de persona');
            $nombreAyuda.text('');
        } else {
            $nombre.attr('placeholder', formato.placeholder);
            $nombreAyuda.text(formato.ayuda);
        }

        $telefono.prop('required', esFisica);
        $correo.prop('required', esFisica);

        if (esFisica) {
            $telefono.attr('pattern', '[0-9]{10}');
            $telefono.attr('minlength', 10);
            $telefono.attr('maxlength', 10);
            $telefono.attr('title', 'Debe contener exactamente 10 dígitos');
            $('#telefono-ayuda').text('Requerido para persona física (10 dígitos).');
            $('#correo-ayuda').text('Requerido para persona física. Debe ser único entre empleados activos.');
        } else {
            $telefono.removeAttr('pattern');
            $telefono.removeAttr('minlength');
            $telefono.removeAttr('title');
            $telefono.attr('maxlength', 50);
            $('#telefono-ayuda').text('Opcional para referenciado y extraordinario.');
            $('#correo-ayuda').text('Opcional. Si lo captura, debe ser único entre empleados activos.');
        }
    }

    $('#tipo_persona').on('change', actualizarCamposPorTipo);
    actualizarCamposPorTipo();
});
</script>