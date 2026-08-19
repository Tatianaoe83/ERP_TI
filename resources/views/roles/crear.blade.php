@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo rol" icon="fa-shield-alt" subtitle="Completa los datos" :back-url="route('roles.index')">
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

    {!! Form::open(array('route' => 'roles.store','method'=>'POST')) !!}
    <div class="row crud-form">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <label for="">Nombre del Rol</label>
            {!! Form::text('name', null, array('class' => 'form-control')) !!}
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <label for="">Permisos para este Rol</label>
            <div class="crud-select-all" id="selectAll">Seleccionar todos</div>
            <div class="crud-perms">
                @foreach($permission as $value)
                <div class="form-group flex items-center space-x-2">
                    <label>
                        {{ Form::checkbox('permission[]', $value->id, false, array('class' => 'name cursor-pointer')) }}
                        <span>{{ $value->name }}</span>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('roles.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection

@push('third_party_scripts')
<script>
    document.getElementById('selectAll').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="permission[]"]');
        const isChecked = checkboxes[0].checked;

        checkboxes.forEach(checkbox => {
            checkbox.checked = !isChecked;
        });

        this.textContent = isChecked ? 'Seleccionar todos' : 'Deseleccionar todos';
    });
</script>
@endpush
