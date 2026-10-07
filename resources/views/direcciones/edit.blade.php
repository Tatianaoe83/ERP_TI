@extends('layouts.app')

@section('content')
<x-crud-page title="Editar dirección" icon="fa-sitemap" subtitle="La unidad se copia a las gerencias de esta dirección." :back-url="route('direcciones.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($direccion, ['route' => ['direcciones.update', $direccion->DireccionID], 'method' => 'patch']) !!}
    <div class="row crud-form">
        @include('direcciones.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
