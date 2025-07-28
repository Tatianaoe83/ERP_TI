@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar departamento</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')


    {!! Form::model($departamentos, ['route' => ['departamentos.update', $departamentos->DepartamentoID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-2">
        <div class="">
            <div class="row">
                @include('departamentos.fields')
            </div>
        </div>

        <div class="">
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('departamentos.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection