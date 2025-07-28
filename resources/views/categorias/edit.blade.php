@extends('layouts.app')

@section('content')

<h3 class="text-[#101D49] dark:text-white">Editar Categorias</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')


    {!! Form::model($categorias, ['route' => ['categorias.update', $categorias->ID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-2">
        <div class="row">
            @include('categorias.fields')
        </div>

        <div>
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('categorias.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection