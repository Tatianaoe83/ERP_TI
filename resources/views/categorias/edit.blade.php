@extends('layouts.app')

@section('content')
<x-crud-page title="Editar categoría" icon="fa-sitemap" subtitle="Actualiza los datos" :back-url="route('categorias.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($categorias, ['route' => ['categorias.update', $categorias->ID], 'method' => 'patch']) !!}
    <div class="row crud-form">
        @include('categorias.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
