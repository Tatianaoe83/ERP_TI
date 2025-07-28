@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Obras Detalles</h3>

<div class="content px-3">
    <div class="row">
        @include('obras.show_fields')
    </div>

    <a href="{{ route('obras.index') }}" class="btn btn-danger">Cancelar</a>
</div>

@endsection