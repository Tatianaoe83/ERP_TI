@include('layouts.partials.index-datatable')

@push('third_party_scripts')
<script>
function reiniciarSelect2FiltrosEmpleados() {
    if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.select2 !== 'function') {
        return;
    }
    jQuery('.jz1').each(function () {
        var $el = jQuery(this);
        if ($el.hasClass('select2-hidden-accessible')) {
            try { $el.select2('destroy'); } catch (e) {}
        }
        $el.select2({
            width: '100%',
            placeholder: 'Seleccionar...',
            allowClear: true,
            dropdownParent: jQuery('body')
        });
    });
}

function cargarOpcionesFiltros() {
    $.ajax({
        url: '{{ route("empleados.filtros") }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var selectPuesto = $('#filtro_puesto');
            selectPuesto.empty().append('<option value="">Todos los puestos</option>');
            if (data.puestos) {
                data.puestos.forEach(function(puesto) {
                    selectPuesto.append('<option value="' + puesto + '">' + puesto + '</option>');
                });
            }

            var selectDepartamento = $('#filtro_departamento');
            selectDepartamento.empty().append('<option value="">Todos los departamentos</option>');
            if (data.departamentos) {
                data.departamentos.forEach(function(departamento) {
                    selectDepartamento.append('<option value="' + departamento + '">' + departamento + '</option>');
                });
            }

            var selectObra = $('#filtro_obra');
            selectObra.empty().append('<option value="">Todas las obras</option>');
            if (data.obras) {
                data.obras.forEach(function(obra) {
                    selectObra.append('<option value="' + obra + '">' + obra + '</option>');
                });
            }

            var selectGerencia = $('#filtro_gerencia');
            selectGerencia.empty().append('<option value="">Todas las gerencias</option>');
            if (data.gerencias) {
                data.gerencias.forEach(function(gerencia) {
                    selectGerencia.append('<option value="' + gerencia + '">' + gerencia + '</option>');
                });
            }

            reiniciarSelect2FiltrosEmpleados();
        },
        error: function() {
            cargarOpcionesFiltrosFallback();
        }
    });
}

function cargarOpcionesFiltrosFallback() {
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#tabla-empleados')) {
        return;
    }
    var table = $('#tabla-empleados').DataTable();

    setTimeout(function() {
        var puestos = table.column(2).data().unique().sort();
        var selectPuesto = $('#filtro_puesto');
        selectPuesto.empty().append('<option value="">Todos los puestos</option>');
        puestos.each(function(d) {
            if (d && d.trim() !== '') {
                selectPuesto.append('<option value="' + d + '">' + d + '</option>');
            }
        });

        var departamentos = table.column(4).data().unique().sort();
        var selectDepartamento = $('#filtro_departamento');
        selectDepartamento.empty().append('<option value="">Todos los departamentos</option>');
        departamentos.each(function(d) {
            if (d && d.trim() !== '') {
                selectDepartamento.append('<option value="' + d + '">' + d + '</option>');
            }
        });

        var obras = table.column(3).data().unique().sort();
        var selectObra = $('#filtro_obra');
        selectObra.empty().append('<option value="">Todas las obras</option>');
        obras.each(function(d) {
            if (d && d.trim() !== '') {
                selectObra.append('<option value="' + d + '">' + d + '</option>');
            }
        });

        var gerencias = table.column(5).data().unique().sort();
        var selectGerencia = $('#filtro_gerencia');
        selectGerencia.empty().append('<option value="">Todas las gerencias</option>');
        gerencias.each(function(d) {
            if (d && d.trim() !== '') {
                selectGerencia.append('<option value="' + d + '">' + d + '</option>');
            }
        });

        reiniciarSelect2FiltrosEmpleados();
    }, 1000);
}

function configurarFiltros() {
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#tabla-empleados')) {
        return;
    }
    var table = $('#tabla-empleados').DataTable();

    $('#limpiarFiltros').on('click', function() {
        $('#filtro_nombre').val('');
        $('#filtro_puesto').val('').trigger('change');
        $('#filtro_departamento').val('').trigger('change');
        $('#filtro_estado').val('').trigger('change');
        $('#filtro_obra').val('').trigger('change');
        $('#filtro_gerencia').val('').trigger('change');
        $('#filtro_tipo_persona').val('').trigger('change');
        table.search('').columns().search('').draw();
    });

    $('#filtro_nombre').on('keyup', function() {
        table.column(1).search($(this).val()).draw();
    });

    $('#filtro_puesto, #filtro_departamento, #filtro_estado, #filtro_obra, #filtro_gerencia, #filtro_tipo_persona').on('change', function() {
        aplicarFiltros();
    });

    function aplicarFiltros() {
        var filtroNombre = $('#filtro_nombre').val();
        var filtroPuesto = $('#filtro_puesto').val();
        var filtroDepartamento = $('#filtro_departamento').val();
        var filtroEstado = $('#filtro_estado').val();
        var filtroObra = $('#filtro_obra').val();
        var filtroGerencia = $('#filtro_gerencia').val();
        var filtroTipoPersona = $('#filtro_tipo_persona').val();

        table.column(1).search(filtroNombre || '');

        if (filtroPuesto) {
            table.column(2).search('^' + filtroPuesto + '$', true, false);
        } else {
            table.column(2).search('');
        }

        if (filtroObra) {
            table.column(3).search('^' + filtroObra + '$', true, false);
        } else {
            table.column(3).search('');
        }

        if (filtroDepartamento) {
            table.column(4).search('^' + filtroDepartamento + '$', true, false);
        } else {
            table.column(4).search('');
        }

        if (filtroGerencia) {
            table.column(5).search('^' + filtroGerencia + '$', true, false);
        } else {
            table.column(5).search('');
        }

        if (filtroTipoPersona) {
            table.column(8).search('^' + filtroTipoPersona + '$', true, false);
        } else {
            table.column(8).search('');
        }

        if (filtroEstado) {
            table.column(9).search('^' + filtroEstado + '$', true, false);
        } else {
            table.column(9).search('');
        }

        table.draw();
    }
}

$(document).ready(function() {
    @if(session('sweetalert_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('sweetalert_success') }}',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#101D49'
        });
    @endif

    @if(session('sweetalert_error'))
        Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: '{{ session('sweetalert_error') }}',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('sweetalert_warning'))
        Swal.fire({
            icon: 'warning',
            title: '¡Advertencia!',
            text: '{{ session('sweetalert_warning') }}',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('sweetalert_info'))
        Swal.fire({
            icon: 'info',
            title: 'Información',
            text: '{{ session('sweetalert_info') }}',
            confirmButtonText: 'Aceptar'
        });
    @endif
});

$(document).on('click', '.btn-cambiar-estado-empleado', function (event) {
    event.preventDefault();

    var form = $(this).closest('form');
    var accion = $(this).data('accion');
    var esActivacion = accion === 'activar';
    var tipoPersona = $(this).data('tipo-persona');
    var textoActivacion = tipoPersona === 'FISICA'
        ? 'Se validará que su correo no esté en uso por otro empleado activo.'
        : '';

    Swal.fire({
        title: esActivacion
            ? '¿Desea activar este empleado?'
            : '¿Está seguro de que desea dar de baja este empleado?',
        text: esActivacion
            ? textoActivacion
            : 'Se verificará que no tenga inventario asociado.',
        icon: 'warning',
        showDenyButton: true,
        confirmButtonText: esActivacion ? 'Activar' : 'Confirmar',
        denyButtonText: 'Cerrar',
        confirmButtonColor: esActivacion ? '#10b981' : '#f59e0b',
    }).then(function (result) {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>
@endpush
