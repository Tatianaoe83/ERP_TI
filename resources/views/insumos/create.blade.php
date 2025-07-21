@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Crear Insumos</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::open(['route' => 'insumos.store']) !!}

    <div class="flex flex-col gap-2">
        <div class="row">
            @include('insumos.fields')
        </div>

        <div>
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('insumos.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection