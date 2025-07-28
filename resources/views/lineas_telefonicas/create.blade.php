@extends('layouts.app')

@section('content')

<h3 class="text-[#101D49] dark:text-white">Crear Lineas Telefonicas</h3>
<div class="content px-3">

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


    <div class="flex flex-col gap-2">
        <div class="row">
            @include('lineas_telefonicas.fields')
        </div>

        <div class="">
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('lineasTelefonicas.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection