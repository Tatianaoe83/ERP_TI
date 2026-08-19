<x-index-actions
    :show-url="route('insumos.show', $id)"
    show-permission="ver-insumos"
    :edit-url="route('insumos.edit', $id)"
    edit-permission="editar-insumos"
    :destroy-route="['insumos.destroy', $id]"
    destroy-permission="borrar-insumos"
    confirm-title="¿Está seguro de que desea borrar este insumo?"
    success-title="Insumo borrado"
/>
