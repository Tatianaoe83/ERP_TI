<div class='btn-group'>
    @can('ver-reportes')
    <a href="{{ route('reportes.show', $id) }}" class='btn btn-outline-success btn-xs'>
        <i class="fas fa-eye"></i>
    </a>
    @endcan
    @can('editar-reportes')
    <a href="{{ route('reportes.edit', $id) }}" class='btn btn-outline-warning btn-xs'>
        <i class="fas fa-edit"></i>
    </a>
    @endcan
    @can('borrar-reportes')
    <form action="{{ route('reportes.destroy', $id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-xs btn-outline-danger btn-flat show_confirm">
            <i class="fa fa-trash"></i>
        </button>
    </form>
    @endcan

</div>

<script type="text/javascript">
    $('.show_confirm').click(function(event) {
        var form = $(this).closest("form");
        event.preventDefault();
        swal.fire({
            title: `¿Está seguro de que desea borrar este reporte? `,
            icon: "warning",
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: `Cerrar`,
        }).then(function(willDelete) {
            if (willDelete.isConfirmed) {
                swal.fire({
                    title: 'Reporte borrado',
                    icon: 'success'
                }).then(function() {
                    form.submit();
                });
            } else if (willDelete.isDenied) {
                swal.fire("Cambios no generados");
            }
        });
    });
</script>