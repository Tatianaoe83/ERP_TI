@extends('layouts.app')

@section('content')
<section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Unidades De Negocio</h3>
        </div>

    
    <div class="section-body">

   

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($unidadesDeNegocio, ['route' => ['unidadesDeNegocios.update', $unidadesDeNegocio->UnidadNegocioID], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('unidades_de_negocios.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('unidadesDeNegocios.index') }}" class="btn btn-danger">Cancelar</a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
    </section>
@endsection
