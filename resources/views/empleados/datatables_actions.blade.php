{!! Form::open(['route' => ['empleados.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
   @can('ver-empleados')
    <a href="{{ route('empleados.show', $id) }}" class='btn btn-outline-primary btn-xs'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan

    @can('editar-empleados')
    <a href="{{ route('empleados.edit', $id) }}" class='btn btn-outline-secondary btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('borrar-empleados')
    <button type="submit" class="btn btn-xs btn-outline-warning btn-flat show_confirm" title="Dar de baja"><i class="fa fa-user-times"></i></button>
    @endcan
</div>
{!! Form::close() !!}




<script type="text/javascript">
 
  $('.show_confirm').click(function(event) {
       var form =  $(this).closest("form");
       event.preventDefault();
       swal.fire({
           title: `¿Está seguro de que desea dar de baja este empleado? `,
           icon: "warning",
           //buttons: true,
           showDenyButton: true,
           confirmButtonText: 'Confirmar',
           denyButtonText: `Cerrar`,
           dangerMode: true,
       }).then(function(willDelete) {
         if (willDelete.isConfirmed) {
          swal.fire({
              title: 'Empleado dado de baja',
              icon: 'success'
            }).then(function(){
              form.submit();
            });
          }else if (willDelete.isDenied){
            swal.fire("Cambios no generados");
          }
       });
   });
</script>
