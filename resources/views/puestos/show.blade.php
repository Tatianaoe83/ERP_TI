@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Puesto Detalles</h3>
<div class="content px-3">
    <div class="card-body">
        <div class="row">
            @include('puestos.show_fields')
        </div>
    </div>
    <div class="">
        <a href="{{ route('puestos.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>
@endsection