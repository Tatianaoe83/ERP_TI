@once
    @push('third_party_stylesheets')
        @include('layouts.datatables_css')
    @endpush

    @push('third_party_scripts')
        @include('layouts.datatables_js')
        @include('layouts.partials.index-page-js')
        <script>
            $(function () {
                $('table.js-index-table').each(function () {
                    if ($.fn.dataTable.isDataTable(this)) {
                        return;
                    }

                    $(this).DataTable({
                        retrieve: true,
                        responsive: true,
                        paging: true,
                        pageLength: 10,
                        searching: true,
                        ordering: true,
                        info: true,
                        order: [],
                        columnDefs: [
                            { orderable: false, targets: -1 }
                        ],
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
                        drawCallback: function () {
                            if (window.IndexPage) {
                                window.IndexPage.updateCount(this.api());
                            }
                        },
                        initComplete: function () {
                            if (window.IndexPage) {
                                window.IndexPage.init(this.api());
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
