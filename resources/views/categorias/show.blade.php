@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de categoría" icon="fa-sitemap" subtitle="Solo lectura" :back-url="route('categorias.index')">
    <div class="row crud-show">
        @include('categorias.show_fields')
    </div>
</x-crud-page>
@endsection
