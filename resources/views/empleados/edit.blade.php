@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Empleado</h3>
<div class="content px-3">

    @include('adminlte-templates::common.errors')


    {!! Form::model($empleados, ['route' => ['empleados.update', $empleados->EmpleadoID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-2">
        <div class="row">
            @include('empleados.fields')
        </div>

        <div class="">
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('empleados.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ubicamos el valor original del tipo de persona
    const tipoOriginal = "{{ $empleados->tipo_persona }}".trim().toUpperCase();

    // Al cambiar el tipo de persona
    $('#tipo_persona').on('change', function () {

        // obtenemos el nuevo valor seleccionado
        let nuevoTipo = ($(this).val() || '').trim().toUpperCase();

        // Validamos cambios no permitidos
        if (
            (tipoOriginal === 'FISICA' && nuevoTipo === 'EXTRAORDINARIO')
        ) {

            Swal.fire({
                title: 'No se puede realizar esta acción',
                text: 'No está permitido cambiar entre físico y extraordinario.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });

            // regresar al valor original
            $(this).val(tipoOriginal).trigger('change');
        }
    });
});


function EvitarCamposTelefonoCorreo() {

    const tipoPersona = ($('#tipo_persona').val() || '')
        .trim()
        .toUpperCase();

    if (tipoPersona === 'EXTRAORDINARIO' || tipoPersona === 'REFERENCIADO') {

        $('#NumTelefono').val('').prop('disabled', true);
        $('#Correo').val('').prop('disabled', true);

    } 
    else {

        $('#NumTelefono').prop('disabled', false);
        $('#Correo').prop('disabled', false);

    }
}

document.addEventListener('DOMContentLoaded', function () {

    // Ejecutar al cargar la página
    EvitarCamposTelefonoCorreo();

    // Ejecutar cada vez que cambie el select
    $('#tipo_persona').on('change', function () {

        EvitarCamposTelefonoCorreo();

    });

});
</script>
@endsection