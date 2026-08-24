<script>
    (function () {
        jQuery(function ($) {
            var base = {
                paging: true, pageLength: 10, lengthMenu: [10, 25, 50, 100],
                searching: true, ordering: true, info: true,
                destroy: true,
                dom: "<'aud-dt__barra'lf>t<'aud-dt__pie'ip>",
                language: {
                    lengthMenu: "_MENU_ por página",
                    search: "",
                    searchPlaceholder: "Buscar licencia…",
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

            // 5 columnas: Licencia(0), TieneLic(1), Original(2), Obs(3), Anterior(4)
            montar('#tablaGeneral', {
                columnDefs: [{ orderable: false, targets: [1, 2, 3, 4] }]
            });

            // ── Color del select de original ────────────────────────────────────
            var COLORES = {
                original: function (v) { return v === '' ? 'pendiente' : (v === '1' ? 'si' : 'alerta'); }
            };

            function pintar($select) {
                var clase = COLORES.original($select.val());
                $select.removeClass('aud-editable--si aud-editable--no aud-editable--alerta aud-editable--pendiente')
                       .addClass('aud-editable--' + clase);
            }

            // ── Guardado de original (select) ───────────────────────────────────
            $(document).off('change.audEdit').on('change.audEdit', '.aud-editable', function () {
                var $select = $(this);
                var fila    = $select.data('fila');
                var valor   = $select.val();
                var previo  = $select.data('previo');

                $select.prop('disabled', true);
                pintar($select);

                $.ajax({
                    url: '{{ url('auditorias/licencias') }}/' + fila,
                    method: 'POST',
                    data: {
                        _method: 'PATCH',
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        campo: 'original',
                        valor: valor === '' ? null : valor
                    }
                }).done(function () {
                    $select.data('previo', valor);
                }).fail(function () {
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

            // ── Guardado de observaciones (textarea, auto-save en blur) ─────────
            $(document).off('blur.audObs').on('blur.audObs', '.aud-obs', function () {
                var $ta   = $(this);
                var fila  = $ta.data('fila');
                var valor = $.trim($ta.val());
                var previo = $ta.data('previo');

                if (valor === (previo || '')) return;

                $ta.addClass('aud-obs--guardando');

                $.ajax({
                    url: '{{ url('auditorias/licencias') }}/' + fila,
                    method: 'POST',
                    data: {
                        _method: 'PATCH',
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        campo: 'observaciones',
                        valor: valor || null
                    }
                }).done(function () {
                    $ta.data('previo', valor);
                    $ta.removeClass('aud-obs--guardando').addClass('aud-obs--ok');
                    setTimeout(function () { $ta.removeClass('aud-obs--ok'); }, 1800);
                }).fail(function () {
                    $ta.val(previo || '');
                    $ta.removeClass('aud-obs--guardando').addClass('aud-obs--error');
                    setTimeout(function () { $ta.removeClass('aud-obs--error'); }, 2500);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se guardó la observación',
                            text: 'Revisa tu conexión e inténtalo de nuevo.'
                        });
                    }
                });
            });

            // ── Filtros por tipo de cambio ──────────────────────────────────────
            $(document).off('click.audMarca').on('click.audMarca', '[data-filtro-marca]', function () {
                var marca = $(this).data('filtro-marca');

                $('[data-filtro-marca]').removeClass('is-activo');
                $(this).addClass('is-activo');

                var $filas = $('#tablaGeneral tbody tr');
                if (marca === 'todas') {
                    $filas.show();
                } else {
                    $filas.each(function () {
                        var $tr = $(this);
                        $tr.toggle($tr.data('marca') === marca);
                    });
                }

                var dt = $('#tablaGeneral').DataTable();
                if (dt) { dt.draw(false); }
            });

            // ── Estado inicial ──────────────────────────────────────────────────
            $('.aud-editable').each(function () {
                $(this).data('previo', $(this).val());
            });
            $('.aud-obs').each(function () {
                $(this).data('previo', $.trim($(this).val()));
            });
        });
    })();
</script>
