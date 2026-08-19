@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo puesto" icon="fa-briefcase" subtitle="Completa los datos" :back-url="route('puestos.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'puestos.store']) !!}
    <div class="row crud-form">
        @include('puestos.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('puestos.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
