@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Empleado Detalles</h3>
<div class="content px-3">
    <div class="">
        <div class="">
            <div class="row">
                @include('empleados.show_fields')
            </div>
        </div>
        <div class="">
            <a href="{{ route('empleados.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>
</div>
@endsection