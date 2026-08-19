{!! Form::open(['route' => ['empleados.destroy', $id], 'method' => 'delete', 'class' => 'form-estado-empleado index-action-form']) !!}
<x-index-actions
    :show-url="route('empleados.show', $id)"
    show-permission="ver-empleados"
    :edit-url="route('empleados.edit', $id)"
    edit-permission="editar-empleados"
>
    @can('borrar-empleados')
        @if($activo)
            <button
                type="button"
                class="index-action index-action--warning btn-cambiar-estado-empleado"
                data-accion="baja"
                data-tipo-persona="{{ $tipo_persona }}"
                title="Dar de baja"
            >
                <i class="fa fa-user-times"></i>
            </button>
        @else
            <button
                type="button"
                class="index-action index-action--success btn-cambiar-estado-empleado"
                data-accion="activar"
                data-tipo-persona="{{ $tipo_persona }}"
                title="Activar empleado"
            >
                <i class="fa fa-user-check"></i>
            </button>
        @endif
    @endcan
</x-index-actions>
{!! Form::close() !!}
