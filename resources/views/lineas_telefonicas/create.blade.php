@extends('layouts.app')

@section('content')

<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Crear Lineas Telefonicas</h3>
    </div>


    <div class="section-body">

        <div class="content px-3">

            @include('adminlte-templates::common.errors')

            <div class="card">

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

                <div class="card-body">

                    <div class="row">
                        @include('lineas_telefonicas.fields')
                    </div>

                </div>

                <div class="card-footer">
                    {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                    <a href="{{ route('lineasTelefonicas.index') }}" class="btn btn-danger">Cancelar</a>
                </div>

                {!! Form::close() !!}

            </div>
        </div>
</section>
@endsection