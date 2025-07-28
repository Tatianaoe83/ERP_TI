@extends('layouts.app')

@section('content')

<h3 class="text-[#101D49] dark:text-white">Planes Detalles</h3>

<div class="content px-3">
    <div class="row">
        @include('planes.show_fields')
    </div>
    <div>
        <a href="{{ route('planes.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>
@endsection