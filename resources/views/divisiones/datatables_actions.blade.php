<x-index-actions
    :show-url="route('divisiones.show', $id)"
    show-permission="ver-unidadesdenegocio"
    :edit-url="route('divisiones.edit', $id)"
    edit-permission="editar-unidadesdenegocio"
    :destroy-route="['divisiones.destroy', $id]"
    destroy-permission="borrar-unidadesdenegocio"
    confirm-title="¿Está seguro de que desea borrar esta división?"
    success-title="División borrada"
/>
