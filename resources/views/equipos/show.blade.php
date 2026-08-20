@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de equipo" icon="fa-laptop" subtitle="Solo lectura" :back-url="route('equipos.index')">
    <div class="row crud-show">
        @include('equipos.show_fields')
    </div>
</x-crud-page>
@endsection
