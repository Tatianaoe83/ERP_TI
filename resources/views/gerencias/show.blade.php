@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Gerencia Detalles</h3>

<div class="content px-3">
    <div>
        <div class="row">
            @include('gerencias.show_fields')
        </div>
    </div>
    <div>
        <a href="{{ route('gerencias.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>

@endsection