@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de empleado" icon="fa-user" subtitle="Solo lectura" :back-url="route('empleados.index')">
    <div class="row crud-show">
        @include('empleados.show_fields')
    </div>
</x-crud-page>
@endsection
