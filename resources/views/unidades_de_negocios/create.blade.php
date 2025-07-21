@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Crear Unidades De Negocio</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::open(['route' => 'unidadesDeNegocios.store']) !!}

    <div class="flex flex-col gap-2">
        <div class="row">
            @include('unidades_de_negocios.fields')
        </div>

        <div>
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('unidadesDeNegocios.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection