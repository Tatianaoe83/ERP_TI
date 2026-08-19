@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de obra" icon="fa-hard-hat" subtitle="Solo lectura" :back-url="route('obras.index')">
    <div class="row crud-show">
        @include('obras.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('obras.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
