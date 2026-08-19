@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de empleado" icon="fa-user" subtitle="Solo lectura" :back-url="route('empleados.index')">
    <div class="row crud-show">
        @include('empleados.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('empleados.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
