@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Categorias Detalles</h3>

<div class="content px-3">
    <div class="row">
        @include('categorias.show_fields')
    </div>
    <div>
        <a href="{{ route('categorias.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>
@endsection