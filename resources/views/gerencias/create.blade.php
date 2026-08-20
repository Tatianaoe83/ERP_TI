@extends('layouts.app')

@section('content')
<x-crud-page title="Nueva gerencia" icon="fa-user-tie" subtitle="Completa los datos" :back-url="route('gerencias.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::open(['route' => 'gerencias.store']) !!}
    <div class="row crud-form">
        @include('gerencias.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
