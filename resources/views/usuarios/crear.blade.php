@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Alta de Usuarios</h3>
<div class="row">
    <div class="col-lg-12">

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
        <div class="row">
            <div class="row">
                <div class="col-xs-4 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label for="name" class="text-[#101D49] dark:text-white">Apellido Paterno</label>
                        {!! Form::text('ApellPaterno', null, array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-xs-4 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label for="name" class="text-[#101D49] dark:text-white">Apellido Materno</label>
                        {!! Form::text('ApellMaterno', null, array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-xs-4 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label for="name" class="text-[#101D49] dark:text-white">Nombres</label>
                        {!! Form::text('nombres', null, array('class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="username" class="text-[#101D49] dark:text-white">Username</label>
                    {!! Form::text('username', null, array('class' => 'form-control')) !!}
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="password" class="text-[#101D49] dark:text-white">Password</label>
                    {!! Form::password('password', array('class' => 'form-control')) !!}
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="confirm-password" class="text-[#101D49] dark:text-white">Confirmar Password</label>
                    {!! Form::password('confirm-password', array('class' => 'form-control')) !!}
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="" class="text-[#101D49] dark:text-white">Roles</label>
                    {!! Form::select('roles[]', $roles,[], array('class' => 'form-control')) !!}
                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="gerenci_id" class="text-[#101D49] dark:text-white">Gerencia(s) <abbr title="Si se requieren todas las gerencias, dejar vacio">(?)</abbr></label>
                    {!!Form::select('gerenci_id[]',App\Models\Gerencia::all()->
                    pluck('NombreGerencia','GerenciaID'),null,['class'=>'form-control jz', 'multiple'=>'multiple', 'style' => 'width: 100%'])!!}

                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-12">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-danger">Cancelar</a>
            </div>
        </div>
        {!! Form::close() !!}

    </div>
</div>
@endsection