@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Roles"
    icon="fa-shield-alt"
    :create-url="route('roles.create')"
    create-permission="crear-rol"
>
    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full" id="tableRoles">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td><span class="index-badge index-badge--dark">{{ $role->name }}</span></td>
                        <td>
                            <x-index-actions
                                :edit-url="route('roles.edit', $role->id)"
                                edit-permission="editar-rol"
                                :destroy-route="['roles.destroy', $role->id]"
                                destroy-permission="borrar-rol"
                                confirm-title="¿Está seguro de que desea borrar este rol?"
                                success-title="Rol borrado"
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
            $('#tableRoles').DataTable({
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
