@extends('layouts.app')

@section('content')
<x-crud-page title="Nueva obra" icon="fa-hard-hat" subtitle="Completa los datos" :back-url="route('obras.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'obras.store']) !!}
    <div class="row crud-form">
        @include('obras.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('obras.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
