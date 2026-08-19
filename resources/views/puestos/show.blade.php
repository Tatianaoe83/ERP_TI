@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de puesto" icon="fa-briefcase" subtitle="Solo lectura" :back-url="route('puestos.index')">
    <div class="row crud-show">
        @include('puestos.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('puestos.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
