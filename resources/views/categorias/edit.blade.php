@extends('layouts.app')

@section('content')

    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Categorias</h3>
        </div>

    
    <div class="section-body">

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($categorias, ['route' => ['categorias.update', $categorias->ID], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('categorias.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Guargar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('categorias.index') }}" class="btn btn-danger">Cancelar</a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
    </section>
@endsection
