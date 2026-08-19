@extends('layouts.app')

@section('content')
<x-crud-page title="Nueva categoría" icon="fa-sitemap" subtitle="Completa los datos" :back-url="route('categorias.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'categorias.store']) !!}
    <div class="row crud-form">
        @include('categorias.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('categorias.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
