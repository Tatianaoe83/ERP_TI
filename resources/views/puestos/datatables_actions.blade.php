<x-index-actions
    :show-url="route('puestos.show', $id)"
    show-permission="ver-puestos"
    :edit-url="route('puestos.edit', $id)"
    edit-permission="editar-puestos"
    :destroy-route="['puestos.destroy', $id]"
    destroy-permission="borrar-puestos"
    confirm-title="¿Está seguro de que desea borrar este puesto?"
    success-title="Puesto borrado"
/>
