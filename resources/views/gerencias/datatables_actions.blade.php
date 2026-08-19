<x-index-actions
    :show-url="route('gerencias.show', $id)"
    show-permission="ver-gerencias"
    :edit-url="route('gerencias.edit', $id)"
    edit-permission="editar-gerencias"
    :destroy-route="['gerencias.destroy', $id]"
    destroy-permission="borrar-gerencias"
    confirm-title="¿Está seguro de que desea borrar esta gerencia?"
    success-title="Gerencia borrada"
/>
