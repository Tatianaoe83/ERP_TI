@extends('layouts.app')

@section('content')
<x-crud-page title="Nueva dirección" icon="fa-sitemap" subtitle="Unidad y nombre. La cadena de TI no admite este nivel vacío." :back-url="route('direcciones.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'direcciones.store']) !!}
    <div class="row crud-form">
        @include('direcciones.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
