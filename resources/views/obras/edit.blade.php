@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Obra</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::model($obras, ['route' => ['obras.update', $obras->ObraID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-3">
        <div class="">
            <div class="row">
                @include('obras.fields')
            </div>
        </div>

        <div class="">
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('obras.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}
</div>
@endsection