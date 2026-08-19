@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Usuarios"
    icon="fa-users"
    :create-url="route('usuarios.create')"
    create-permission="crear-usuarios"
>
    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full" id="tableUsu">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Username</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->name }}</td>
                        <td>{{ $usuario->username }}</td>
                        <td>
                            @if(!empty($usuario->getRoleNames()))
                                @foreach($usuario->getRoleNames() as $rolNombre)
                                    <span class="index-badge index-badge--dark">{{ $rolNombre }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <x-index-actions
                                :edit-url="route('usuarios.edit', $usuario->id)"
                                edit-permission="editar-usuarios"
                                :destroy-route="['usuarios.destroy', $usuario->id]"
                                destroy-permission="borrar-usuarios"
                                confirm-title="¿Está seguro de que desea borrar este usuario?"
                                success-title="Usuario borrado"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-index-page>
@endsection

@push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('layouts.partials.index-page-js')
    <script>
        $(document).ready(function() {
            $('#tableUsu').DataTable({
                responsive: true,
                paging: true,
                pageLength: 10,
                searching: true,
                ordering: true,
                info: true,
                dom: "<'index-page__dt-toolbar'Bf>t<'index-page__dt-footer'ip>",
                buttons: [{
                    extend: 'colvis',
                    className: 'index-page__colvis',
                    text: '<i class="fas fa-columns"></i> Columnas'
                }],
                language: {
                    sProcessing: 'Procesando...',
                    sZeroRecords: 'No se encontraron resultados',
                    sEmptyTable: 'Ningún dato disponible en esta tabla',
                    sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
                    sInfoEmpty: 'Mostrando 0 a 0 de 0',
                    sInfoFiltered: '(filtrado de _MAX_ registros)',
                    sSearch: '',
                    searchPlaceholder: 'Buscar...',
                    oPaginate: {
                        sFirst: 'Primero',
                        sLast: 'Último',
                        sNext: 'Siguiente',
                        sPrevious: 'Anterior'
                    }
                },
                drawCallback: function() {
                    if (window.IndexPage) {
                        window.IndexPage.updateCount(this.api());
                    }
                },
                initComplete: function() {
                    if (window.IndexPage) {
                        window.IndexPage.init(this.api());
                    }
                }
            });
        });
    </script>
@endpush
