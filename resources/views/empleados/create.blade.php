@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo empleado" icon="fa-user" subtitle="Completa los datos" :back-url="route('empleados.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'empleados.store']) !!}
    <div class="row crud-form">
        @include('empleados.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>

<script>
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
