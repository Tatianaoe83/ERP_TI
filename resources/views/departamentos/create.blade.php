@extends('layouts.app')

@section('content')
<h3 class="row text-[#101D49] dark:text-white">Crear Departamento</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    <div class="">

        {!! Form::open(['route' => 'departamentos.store']) !!}


        <div class="flex flex-col gap-2">
            <div class="row">
                @include('departamentos.fields')
            </div>


            <div>
                {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('departamentos.index') }}" class="btn btn-danger">Cancelar</a>
            </div>
        </div>

        {!! Form::close() !!}

    </div>
</div>
@endsection