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
                    zeroRecords: "Ninguna licencia coincide con la búsqueda",
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

            // Las dos últimas columnas son <select>: ordenarlas no dice nada.
            montar('#tablaGeneral', { columnDefs: [{ orderable: false, targets: [1, 2] }] });

            // ── Captura en la tabla ──
            // Se guarda al cambiar el select, sin botón: es un solo campo por celda.
            var COLORES = {
                tiene_licencia: function (v) { return v === '1' ? 'si' : 'no'; },
                original: function (v) { return v === '' ? 'pendiente' : (v === '1' ? 'si' : 'alerta'); }
            };

            function pintar($select) {
                var campo = $select.data('campo');
                var clase = COLORES[campo]($select.val());
                $select.removeClass('aud-editable--si aud-editable--no aud-editable--alerta aud-editable--pendiente')
                       .addClass('aud-editable--' + clase);
            }

            $(document).off('change.audEdit').on('change.audEdit', '.aud-editable', function () {
                var $select = $(this);
                var fila = $select.data('fila');
                var campo = $select.data('campo');
                var valor = $select.val();
                var previo = $select.data('previo');

                // Bloqueado mientras viaja: evita mandar dos cambios encimados.
                $select.prop('disabled', true);
                pintar($select);

                $.ajax({
                    url: '{{ url('auditorias/licencias') }}/' + fila,
                    method: 'POST',
                    data: {
                        _method: 'PATCH',
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        campo: campo,
                        valor: valor === '' ? null : valor
                    }
                }).done(function (r) {
                    $select.data('previo', valor);

                    // Sin licencia no hay origen que revisar: el servidor lo limpia y
                    // la fila tiene que reflejarlo, no quedarse con el valor viejo.
                    if (campo === 'tiene_licencia') {
                        var $origen = $('#original-' + fila);
                        if ($origen.length) {
                            $origen.prop('disabled', !r.tiene_licencia);
                            if (!r.tiene_licencia) {
                                $origen.val('');
                                pintar($origen);
                            }
                        }
                    }
                }).fail(function () {
                    // Se revierte a lo último confirmado: la tabla nunca muestra algo
                    // que la base no guardó.
                    if (previo !== undefined) {
                        $select.val(previo);
                        pintar($select);
                    }

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se guardó el cambio',
                            text: 'Revisa tu conexión e inténtalo de nuevo.'
                        });
                    }
                }).always(function () {
                    $select.prop('disabled', false);
                });
            });

            // Estado inicial para poder revertir si falla el guardado.
            $('.aud-editable').each(function () {
                $(this).data('previo', $(this).val());
            });
        });
    })();
</script>
