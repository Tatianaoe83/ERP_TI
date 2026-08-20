@extends('layouts.app')

@section('content')
<x-crud-page title="Nueva línea telefónica" icon="fa-phone-alt" subtitle="Completa los datos" :back-url="route('lineasTelefonicas.index')">
    @include('adminlte-templates::common.errors')

    {!! Form::open(['route' => 'lineasTelefonicas.store']) !!}

    @if(session('swal'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: '{{ session('swal.icon') }}',
            title: '{{ session('swal.title') }}',
            text: '{{ session('swal.text') }}',
            confirmButtonText: 'Aceptar'
        });
    </script>
    @endif

    <div class="row crud-form">
        @include('lineas_telefonicas.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
