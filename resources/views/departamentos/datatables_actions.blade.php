<x-index-actions
    :show-url="route('departamentos.show', $id)"
    show-permission="ver-departamentos"
    :edit-url="route('departamentos.edit', $id)"
    edit-permission="editar-departamentos"
    :destroy-route="['departamentos.destroy', $id]"
    destroy-permission="borrar-departamentos"
    confirm-title="¿Está seguro de que desea borrar este departamento?"
    success-title="Departamento borrado"
/>
