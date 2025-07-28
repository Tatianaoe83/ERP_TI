@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Departamentos Detalles</h3>
<div class="content px-3">
    <div class="">
        <div class="">
            <div class="row">
                @include('departamentos.show_fields')
            </div>
        </div>
        <div class="">
            <a href="{{ route('departamentos.index') }}" class="btn btn-danger">Cancelar</a>
        </div>
    </div>
</div>
@endsection