@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de línea telefónica" icon="fa-phone-alt" subtitle="Solo lectura" :back-url="route('lineasTelefonicas.index')">
    <div class="row crud-show">
        @include('lineas_telefonicas.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('lineasTelefonicas.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
