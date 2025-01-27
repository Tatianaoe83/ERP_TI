{!! Form::open(['route' => ['equipos.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('equipos.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('equipos.edit', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    <button type="submit" class="btn btn-xs btn-danger btn-flat show_confirm"><i class="fa fa-trash"></i></button>
</div>
{!! Form::close() !!}




<script type="text/javascript">
 
  $('.show_confirm').click(function(event) {
       var form =  $(this).closest("form");
       event.preventDefault();
       swal.fire({
           title: `¿Está seguro de que desea borrar este equipo? `,
           icon: "warning",
           //buttons: true,
           showDenyButton: true,
           confirmButtonText: 'Confirmar',
           denyButtonText: `Cerrar`,
           dangerMode: true,
       }).then(function(willDelete) {
         if (willDelete.isConfirmed) {
          swal.fire({
              title: 'Equipo borrado',
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
