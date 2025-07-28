@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Crear Rol</h3>

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
<div class="row gap-0.5">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <label for="" class="text-[#101D49] dark:text-white">Nombre del Rol:</label>
        {!! Form::text('name', null, array('class' => 'form-control')) !!}
    </div>
    <label for="" class="text-[#101D49] dark:text-white">Permisos para este Rol:</label>
    <br />
    <div>
        <div class="bg-[#101D49] text-white dark:bg-white dark:!text-[#101D49] rounded-md flex items-start w-[170px] justify-center cursor-pointer transition" id="selectAll">Seleccionar todos</div>
    </div>
    <br />
    <div class="grid grid-cols-1 sm:grid-cols-4 lg:grid-cols-10 gap-0.5">
        @foreach($permission as $value)
        <div class="form-group flex items-center space-x-2">
            <label class="text-md text-[#101D49] dark:text-white">
                {{ Form::checkbox('permission[]', $value->id, false, array('class' => 'name cursor-pointer')) }}
                <span>{{ $value->name }}</span>
            </label>
        </div>
        @endforeach
    </div>
</div>
<button type="submit" class="btn btn-primary">Guardar</button>
<a href="{{ route('roles.index') }}" class="btn btn-danger">Cancelar</a>
{!! Form::close() !!}
</div>
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