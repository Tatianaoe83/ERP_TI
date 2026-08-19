<x-index-actions
    :show-url="route('categorias.show', $id)"
    show-permission="ver-categorias"
    :edit-url="route('categorias.edit', $id)"
    edit-permission="editar-categorias"
    :destroy-route="['categorias.destroy', $id]"
    destroy-permission="borrar-categorias"
    confirm-title="¿Está seguro de que desea borrar esta categoría?"
    success-title="Categoría borrada"
/>
