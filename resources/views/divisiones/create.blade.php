@extends('layouts.app')

@section('content')
<x-crud-page title="Nueva división" icon="fa-layer-group" subtitle="Es el primer nivel de la cadena. En TI no puede faltar." :back-url="route('divisiones.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'divisiones.store']) !!}
    <div class="row crud-form">
        @include('divisiones.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
