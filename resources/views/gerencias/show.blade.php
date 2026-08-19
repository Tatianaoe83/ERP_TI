@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de gerencia" icon="fa-user-tie" subtitle="Solo lectura" :back-url="route('gerencias.index')">
    <div class="row crud-show">
        @include('gerencias.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('gerencias.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
