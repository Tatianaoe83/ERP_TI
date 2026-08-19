@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de unidad de negocio" icon="fa-city" subtitle="Solo lectura" :back-url="route('unidadesDeNegocios.index')">
    <div class="row crud-show">
        @include('unidades_de_negocios.show_fields')
    </div>
    <div class="crud-page__actions">
        <a href="{{ route('unidadesDeNegocios.index') }}" class="crud-page__btn-ghost">Volver</a>
    </div>
</x-crud-page>
@endsection
