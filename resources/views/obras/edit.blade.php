@extends('layouts.app')

@section('content')
<x-crud-page title="Editar obra" icon="fa-hard-hat" subtitle="Actualiza los datos" :back-url="route('obras.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($obras, ['route' => ['obras.update', $obras->ObraID], 'method' => 'patch']) !!}
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
