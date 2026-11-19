@extends('layouts.app')

@section('content')
<x-crud-page title="Editar división" icon="fa-layer-group" subtitle="Las unidades de esta división conservan el vínculo." :back-url="route('divisiones.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($division, ['route' => ['divisiones.update', $division->DivisionID], 'method' => 'patch']) !!}
    <div class="row crud-form">
        @include('divisiones.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
