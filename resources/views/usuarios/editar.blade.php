@extends('layouts.app')

@section('content')
<x-crud-page title="Editar usuario" icon="fa-users" subtitle="Actualiza los datos" :back-url="route('usuarios.index')">
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

    {!! Form::model($user, ['method' => 'PATCH','route' => ['usuarios.update', $user->id]]) !!}
    <div class="row crud-form">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="name">Nombre</label>
                {!! Form::text('name', null, array('class' => 'form-control')) !!}
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
                {!! Form::select('roles[]', $roles,$userRole, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="gerenci_id">Gerencia(s) <abbr title="Si se requieren todas las gerencias, dejar vacio">(?)</abbr></label>
                {!! Form::select('gerenci_id[]', $geren,$gerenUsuarios, array('style' => 'width: 100%;height:auto','class'=>'form-control jz', 'multiple'=>'multiple')) !!}
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

@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.jz').select2({
            theme: "classic",
            width: 'resolve'
        });
        $(document).on("select2:open", () => {
            document.querySelector(".select2-container--open .select2-search__field").focus()
        })
    });
</script>
@endsection
