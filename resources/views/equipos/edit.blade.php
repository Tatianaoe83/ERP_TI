@extends('layouts.app')

@section('content')

<h3 class="text-[#101D49] dark:text-white">Editar Equipos</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')


    {!! Form::model($equipos, ['route' => ['equipos.update', $equipos->ID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-2">
        <div class="row">
            @include('equipos.fields')
        </div>

        <div>
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('equipos.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection