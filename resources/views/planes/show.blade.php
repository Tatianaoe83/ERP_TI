@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de plan" icon="fa-mobile-alt" subtitle="Solo lectura" :back-url="route('planes.index')">
    <div class="row crud-show">
        @include('planes.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('planes.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
