@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo usuario" icon="fa-users" subtitle="Completa los datos" :back-url="route('usuarios.index')">
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

    {!! Form::open(array('route' => 'usuarios.store','method'=>'POST')) !!}
    <div class="row crud-form">
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label for="name">Apellido Paterno</label>
                {!! Form::text('ApellPaterno', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label for="name">Apellido Materno</label>
                {!! Form::text('ApellMaterno', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label for="name">Nombres</label>
                {!! Form::text('nombres', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="username">Username</label>
                {!! Form::text('username', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="password">Password</label>
                {!! Form::password('password', array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="confirm-password">Confirmar Password</label>
                {!! Form::password('confirm-password', array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="">Roles</label>
                {!! Form::select('roles[]', $roles,[], array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="gerenci_id">Gerencia(s) <abbr title="Si se requieren todas las gerencias, dejar vacio">(?)</abbr></label>
                {!!Form::select('gerenci_id[]',App\Models\Gerencia::all()->
                pluck('NombreGerencia','GerenciaID'),null,['class'=>'form-control jz', 'multiple'=>'multiple', 'style' => 'width: 100%'])!!}
            </div>
        </div>
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
