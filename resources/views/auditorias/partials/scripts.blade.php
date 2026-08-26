<script>
    (function () {
        jQuery(function ($) {
            // La corrida tiene dos detalles con endpoints distintos; cada control dice
            // a cuál pertenece para no cablear la URL en el script.
            function urlDe($el) {
                var recurso = $el.data('recurso') || 'licencias';
                return '{{ url('auditorias') }}/' + recurso + '/' + $el.data('fila');
            }

            // ── Color del select tri-estado ─────────────────────────────────────
            // Sin revisar es neutro, sí es correcto, no es alerta.
            function pintar($select) {
                var v = $select.val();
                var clase = v === '' ? 'pendiente' : (v === '1' ? 'si' : 'alerta');
                $select.removeClass('aud-editable--si aud-editable--no aud-editable--alerta aud-editable--pendiente')
                       .addClass('aud-editable--' + clase);
            }

            // ── Guardado del select tri-estado ──────────────────────────────────
            $(document).off('change.audEdit').on('change.audEdit', '.aud-editable', function () {
                var $select = $(this);
                var valor   = $select.val();
                var previo  = $select.data('previo');

                $select.prop('disabled', true);
                pintar($select);

                $.ajax({
                    url: urlDe($select),
                    method: 'POST',
                    data: {
                        _method: 'PATCH',
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        campo: $select.data('campo'),
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
                var valor = $.trim($ta.val());
                var previo = $ta.data('previo');

                if (valor === (previo || '')) return;

                $ta.addClass('aud-obs--guardando');

                $.ajax({
                    url: urlDe($ta),
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
