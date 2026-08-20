@extends('layouts.app')

@section('content')
<x-crud-page title="Editar puesto" icon="fa-briefcase" subtitle="Actualiza los datos" :back-url="route('puestos.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($puestos, ['route' => ['puestos.update', $puestos->PuestoID], 'method' => 'patch']) !!}
    <div class="row crud-form">
        @include('puestos.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
