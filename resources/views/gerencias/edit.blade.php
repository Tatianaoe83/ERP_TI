@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Agencia</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')


    {!! Form::model($gerencia, ['route' => ['gerencias.update', $gerencia->GerenciaID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-2">
        <div>
            <div class="row text-[#101D49] dark:text-white">
                @include('gerencias.fields')
            </div>
        </div>

        <div>
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('gerencias.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection