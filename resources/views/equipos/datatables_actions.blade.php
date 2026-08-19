<x-index-actions
    :show-url="route('equipos.show', $id)"
    show-permission="ver-equipos"
    :edit-url="route('equipos.edit', $id)"
    edit-permission="editar-equipos"
    :destroy-route="['equipos.destroy', $id]"
    destroy-permission="borrar-equipos"
    confirm-title="¿Está seguro de que desea borrar este equipo?"
    success-title="Equipo borrado"
/>
