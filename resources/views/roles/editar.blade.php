@extends('layouts.app')

@section('content')
<x-crud-page title="Editar rol" icon="fa-shield-alt" subtitle="Actualiza los datos" :back-url="route('roles.index')">
    @if ($errors->any())
    <div class="alert alert-dark alert-dismissible fade show" role="alert">
        <strong>¡Revise los campos!</strong>
        @foreach ($errors->all() as $error)
        <span class="badge badge-danger">{{ $error }}</span>
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    {!! Form::model($role, ['method' => 'PATCH','route' => ['roles.update', $role->id]]) !!}
    <div class="row crud-form">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <label for="">Nombre del Rol</label>
            {!! Form::text('name', null, array('class' => 'form-control')) !!}
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            @include('roles.permisos', ['rolePermissions' => $rolePermissions])
        </div>
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
