@extends('layouts.app')

@section('content')
<section class="section">
        <div class="section-header">
            <h3 class="page__heading">Empleado Detalles</h3>
        </div>
        <div class="section-body">

    

        <div class="content px-3">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @include('empleados.show_fields')
                    </div>
                </div>
                <div class="card-footer">
                
                <a href="{{ route('empleados.index') }}" class="btn btn-danger">Cancelar</a>
            </div>
            </div>
        </div>
        </section>

@endsection


