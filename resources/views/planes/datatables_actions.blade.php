<x-index-actions
    :show-url="route('planes.show', $id)"
    show-permission="ver-planes"
    :edit-url="route('planes.edit', $id)"
    edit-permission="editar-planes"
    :destroy-route="['planes.destroy', $id]"
    destroy-permission="borrar-planes"
    confirm-title="¿Está seguro de que desea borrar este plan?"
    success-title="Plan borrado"
/>
