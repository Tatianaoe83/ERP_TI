<x-index-actions
    :show-url="route('lineasTelefonicas.show', $id)"
    show-permission="ver-Lineastelefonicas"
    :edit-url="route('lineasTelefonicas.edit', $id)"
    edit-permission="editar-Lineastelefonicas"
    :destroy-route="['lineasTelefonicas.destroy', $id]"
    destroy-permission="borrar-Lineastelefonicas"
    confirm-title="¿Está seguro de que desea borrar esta línea telefónica?"
    success-title="Línea telefónica borrada"
/>
