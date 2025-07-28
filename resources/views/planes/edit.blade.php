@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Planes</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::model($planes, ['route' => ['planes.update', $planes->ID], 'method' => 'patch']) !!}

    <div class="flex flex-col gap-2">
        <div class="row">
            @include('planes.fields')
        </div>

        <div class="card-footer">
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('planes.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}

</div>
@endsection