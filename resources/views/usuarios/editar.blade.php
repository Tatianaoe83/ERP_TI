@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Usuario</h3>
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

        {!! Form::model($user, ['method' => 'PATCH','route' => ['usuarios.update', $user->id]]) !!}
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="name" class="text-[#101D49] dark:text-white">Nombre</label>
                    {!! Form::text('name', null, array('class' => 'form-control')) !!}
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
                    {!! Form::select('roles[]', $roles,$userRole, array('class' => 'form-control')) !!}
                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="gerenci_id" class="text-[#101D49] dark:text-white">Gerencia(s) <abbr title="Si se requieren todas las gerencias, dejar vacio">(?)</abbr></label>
                    {!! Form::select('gerenci_id[]', $geren,$gerenUsuarios, array('style' => 'width: 100%;height:auto','class'=>'form-control jz', 'multiple'=>'multiple')) !!}
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

@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript">
    // In your Javascript (external .js resource or <script> tag)
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