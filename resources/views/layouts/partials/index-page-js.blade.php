@once
<script>
    window.IndexPage = window.IndexPage || {
        init: function (api) {
            this.updateCount(api);
            var $input = $(api.table().container()).find('.dataTables_filter input');
            if ($input.length && !$input.attr('placeholder')) {
                $input.attr('placeholder', 'Buscar...');
            }
        },
        updateCount: function (api) {
            var info = api.page.info();
            var total = info.recordsDisplay;
            var label = total === 1 ? '1 registro' : total + ' registros';
            $(api.table().container()).closest('.index-page').find('.index-page__count').text(label);
        }
    };

    $(document).off('click.indexActionConfirm').on('click.indexActionConfirm', '.index-action-confirm', function (event) {
        event.preventDefault();
        var form = $(this).closest('form');
        var title = $(this).data('confirm-title') || '¿Está seguro de que desea borrar este registro?';
        var successTitle = $(this).data('success-title') || 'Registro borrado';

        Swal.fire({
            title: title,
            icon: 'warning',
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cerrar',
            confirmButtonColor: '#101D49',
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: successTitle,
                    icon: 'success',
                    timer: 900,
                    showConfirmButton: false,
                }).then(function () {
                    form.submit();
                });
            }
        });
    });
</script>
@endonce
