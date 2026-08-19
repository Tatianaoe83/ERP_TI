@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de insumo" icon="fa-box" subtitle="Solo lectura" :back-url="route('insumos.index')">
    <div class="row crud-show">
        @include('insumos.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('insumos.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
