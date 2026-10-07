<x-index-actions
    :show-url="route('direcciones.show', $id)"
    show-permission="ver-gerencias"
    :edit-url="route('direcciones.edit', $id)"
    edit-permission="editar-gerencias"
    :destroy-route="['direcciones.destroy', $id]"
    destroy-permission="borrar-gerencias"
    confirm-title="¿Está seguro de que desea borrar esta dirección?"
    success-title="Dirección borrada"
/>
