@extends('layouts.app')

@section('content')
<section class="section">
        <div class="section-header">
            <h3 class="page__heading">Inventario de:</h3> <h5 style="margin-bottom: 6px;padding-left: 5px;">{{$inventario->NombreEmpleado}}</h5>
        </div>

    
    <div class="section-body">

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($inventario, ['route' => ['inventarios.update', $inventario->EmpleadoID], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('inventarios.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('inventarios.index') }}" class="btn btn-default">Cancel</a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
    </section>
@endsection


