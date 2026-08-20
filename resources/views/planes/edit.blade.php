@extends('layouts.app')

@section('content')
<x-crud-page title="Editar plan" icon="fa-mobile-alt" subtitle="Actualiza los datos" :back-url="route('planes.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($planes, ['route' => ['planes.update', $planes->ID], 'method' => 'patch']) !!}
    <div class="row crud-form">
        @include('planes.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
