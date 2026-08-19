@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de equipo" icon="fa-laptop" subtitle="Solo lectura" :back-url="route('equipos.index')">
    <div class="row crud-show">
        @include('equipos.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('equipos.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
