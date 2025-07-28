@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Unidades De Negocio Detalles</h3>
<div class="content px-3">
    <div class="row">
        @include('unidades_de_negocios.show_fields')
    </div>
    <div>

        <a href="{{ route('unidadesDeNegocios.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>

@endsection