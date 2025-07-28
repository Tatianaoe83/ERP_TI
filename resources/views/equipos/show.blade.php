@extends('layouts.app')

@section('content')

<h3 class="text-[#101D49] dark:text-white">Equipos Detalles</h3>

<div class="content px-3">
    <div class="row">
        @include('equipos.show_fields')
    </div>
    <div class="">
        <a href="{{ route('equipos.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>
</section>
@endsection