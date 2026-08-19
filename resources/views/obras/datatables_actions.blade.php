<x-index-actions
    :show-url="route('obras.show', $id)"
    show-permission="ver-obras"
    :edit-url="route('obras.edit', $id)"
    edit-permission="editar-obras"
    :destroy-route="['obras.destroy', $id]"
    destroy-permission="editar-obras"
    confirm-title="¿Está seguro de que desea borrar esta obra?"
    success-title="Obra borrada"
/>
