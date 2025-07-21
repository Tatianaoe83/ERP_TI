@extends('layouts.app')

@section('content')
<section>
  <h3 class="text-[#101D49] dark:text-white">Usuarios</h3>
  <div class="row">
    <div class="col-lg-12">
      @can('crear-usuarios')
      <a class="btn btn-primary" href="{{ route('usuarios.create') }}"><i class="fa fa-plus"></i> Crear</a>
      @endcan
      <table class="table table-bordered table-striped" id="tableUsu">
        <thead>

          <th>Nombre</th>
          <th>Username</th>
          <th>Rol</th>
          <th>Acciones</th>
        </thead>
        <tbody>
          @foreach ($usuarios as $usuario)
          <tr>

            <td>{{ $usuario->name }}</td>
            <td>{{ $usuario->username }}</td>
            <td>
              @if(!empty($usuario->getRoleNames()))
              @foreach($usuario->getRoleNames() as $rolNombre)
              <h5><span class="badge badge-dark">{{ $rolNombre }}</span></h5>
              @endforeach
              @endif
            </td>

            <td>

              @can('editar-usuarios')
              <a href="{{ route('usuarios.edit', $usuario->id) }}" class='btn btn-outline-secondary btn-xs'>
                <i class="fa fa-edit"></i>
              </a>
              @endcan

              {!! Form::open(['method' => 'DELETE','route' => ['usuarios.destroy', $usuario->id],'style'=>'display:inline']) !!}
              @can('borrar-usuarios')
              <button type="submit" class="btn btn-xs btn-outline-danger btn-flat show_confirm"><i class="fa fa-trash"></i></button>
              @endcan
              {!! Form::close() !!}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection

@push('third_party_scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
  $(document).ready(function() {
    let table1_1 = $('#tableUsu').DataTable({
      "paging": true,
      "lengthMenu": [5, 10, 25, 50],
      "pageLength": 5,
      "searching": true,
      "ordering": true,
      "info": true,
      "createdRow": function(row, data, dataIndex) {
        $(row).addClass('dark:bg-[#2d2d2d] dark:text-white');
      },
      "headerCallback": function(thead, data, start, end, display) {
        $(thead).addClass('text-white');
      },
      "drawCallback": function(settings) {
        $('#tableUsu tbody tr').each(function() {
          $(this).find('td').addClass('dark:bg-[#101010] dark:text-white px-4 py-2 text-sm');
        });
      }
    });

    $('.show_confirm').click(function(event) {
      var form = $(this).closest("form");
      event.preventDefault();
      swal.fire({
        title: `¿Está seguro de que desea borrar este usuario? `,
        icon: "warning",
        //buttons: true,
        showDenyButton: true,
        confirmButtonText: 'Confirmar',
        denyButtonText: `Cerrar`,
        dangerMode: true,
      }).then(function(willDelete) {
        if (willDelete.isConfirmed) {
          swal.fire({
            title: 'Unidad borrada',
            icon: 'success'
          }).then(function() {
            form.submit();
          });
        } else if (willDelete.isDenied) {
          swal.fire("Cambios no generados");
        }
      });
    });

  });
</script>
@endpush