@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo empleado" icon="fa-user" subtitle="Alta según el tipo de persona" :back-url="route('empleados.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'empleados.store']) !!}
    <div class="row crud-form">
        @include('empleados.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
