@extends('layouts.app')

@section('content')

<h3 class="text-[#101D49] dark:text-white">Lineas Telefonicas Detalles</h3>
<div class="content px-3 grid grid-cols-2">
    @include('lineas_telefonicas.show_fields')
</div>
<div class="">
    <a href="{{ route('lineasTelefonicas.index') }}" class="btn btn-danger">Cancelar</a>
</div>
@endsection