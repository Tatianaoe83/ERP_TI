@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo plan" icon="fa-mobile-alt" subtitle="Completa los datos" :back-url="route('planes.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'planes.store']) !!}
    <div class="row crud-form">
        @include('planes.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
