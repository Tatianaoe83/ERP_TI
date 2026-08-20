@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo equipo" icon="fa-laptop" subtitle="Completa los datos" :back-url="route('equipos.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'equipos.store']) !!}
    <div class="row crud-form">
        @include('equipos.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
