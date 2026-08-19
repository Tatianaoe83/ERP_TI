<x-index-actions
    :show-url="route('cortes.show', $id)"
    :edit-url="route('cortes.edit', $id)"
    :destroy-route="['cortes.destroy', $id]"
    confirm-title="¿Está seguro de que desea borrar este corte?"
    success-title="Corte borrado"
/>
