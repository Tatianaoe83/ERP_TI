@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            
        </div>
    </section>

    <div class="content px-3">
        @include('flash::message')
        <div class="clearfix"></div>

        <div class="card">
            <div class="card-body">
                @include('lineas_telefonicas.table')
            </div>
        </div>
    </div>
@endsection


