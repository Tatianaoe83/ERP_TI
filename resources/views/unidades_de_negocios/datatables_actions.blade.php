<x-index-actions
    :show-url="route('unidadesDeNegocios.show', $id)"
    show-permission="ver-unidadesdenegocio"
    :edit-url="route('unidadesDeNegocios.edit', $id)"
    edit-permission="editar-unidadesdenegocio"
    :destroy-route="['unidadesDeNegocios.destroy', $id]"
    destroy-permission="borrar-unidadesdenegocio"
    confirm-title="¿Está seguro de que desea borrar esta unidad?"
    success-title="Unidad de negocio borrada"
/>
