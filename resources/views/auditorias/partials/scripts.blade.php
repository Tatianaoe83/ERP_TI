<script>
    (function () {
        // Sin "responsive": colapsa columnas sacando <td> del DOM y corre los índices.
        // La celda de licencias es compuesta, así que no se ordena.
        jQuery(function ($) {
            var base = {
                paging: true, pageLength: 10, lengthMenu: [10, 25, 50, 100],
                searching: true, ordering: true, info: true,
                // AppNav vuelve a correr este script en cada navegación y DataTables
                // truena con "Cannot reinitialise" si la tabla ya estaba montada.
                destroy: true,
                // Barra propia: cantidad a la izquierda, buscador a la derecha, y el
                // conteo con la paginación al pie. Evita las filas de Bootstrap.
                dom: "<'aud-dt__barra'lf>t<'aud-dt__pie'ip>",
                language: {
                    lengthMenu: "_MENU_ por página",
                    search: "",
                    searchPlaceholder: "Buscar equipo, serie o licencia…",
                    info: "_START_–_END_ de _TOTAL_",
                    infoEmpty: "Sin registros",
                    infoFiltered: "(filtrado de _MAX_)",
                    zeroRecords: "Ningún equipo coincide con la búsqueda",
                    paginate: { first: '«', previous: '‹', next: '›', last: '»' }
                }
            };

            function montar(selector, opciones) {
                if (!$(selector + ' tbody tr').length) return;
                if ($.fn.DataTable.isDataTable(selector)) {
                    $(selector).DataTable().destroy();
                }
                $(selector).DataTable($.extend({}, base, opciones));
            }

            montar('#tablaGeneral', { columnDefs: [{ orderable: false, targets: [6] }] });
        });
    })();
</script>
