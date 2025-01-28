@extends('layouts.app')

@section('content')

    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Equipos</h3>
        </div>

    
    <div class="section-body">


    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($equipos, ['route' => ['equipos.update', $equipos->ID], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('equipos.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('equipos.index') }}" class="btn btn-danger">Cancelar</a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
    </section>
@endsection
