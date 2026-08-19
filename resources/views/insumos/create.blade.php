@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo insumo" icon="fa-box" subtitle="Completa los datos" :back-url="route('insumos.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'insumos.store']) !!}
    <div class="row crud-form">
        @include('insumos.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('insumos.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
