{!! Form::open(['route' => ['empleados.destroy', $id], 'method' => 'delete', 'class' => 'form-estado-empleado']) !!}
<div class='btn-group'>
   @can('ver-empleados')
    <a href="{{ route('empleados.show', $id) }}" class='btn btn-outline-primary btn-xs'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan

    @can('editar-empleados')
    <a href="{{ route('empleados.edit', $id) }}" class='btn btn-outline-secondary btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('borrar-empleados')
    @if($activo)
    <button
        type="button"
        class="btn btn-xs btn-outline-warning btn-flat btn-cambiar-estado-empleado"
        data-accion="baja"
        title="Dar de baja"
    >
        <i class="fa fa-user-times"></i>
    </button>
    @else
    <button
        type="button"
        class="btn btn-xs btn-outline-success btn-flat btn-cambiar-estado-empleado"
        data-accion="activar"
        title="Activar empleado"
    >
        <i class="fa fa-user-check"></i>
    </button>
    @endif
    @endcan


</div>
{!! Form::close() !!}
