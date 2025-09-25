@extends('layouts.app')

@section('content')
<div class="col-xs-12 col-sm-12 col-md-12">

    <div class="">
        <div class="card-header">
            <h3 class="text-[#101D49] dark:text-white">Empleados</h3>
        </div>

        <div class="card-body">
            <!-- Filtros responsivos -->
            <div class="row mb-4">
                <!-- Primera fila de filtros -->
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="form-group">
                        <label for="filtro_nombre" class="text-[#101D49] dark:text-white">Nombre:</label>
                        <input type="text" id="filtro_nombre" class="form-control" placeholder="Buscar por nombre...">
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="form-group">
                        <label for="filtro_puesto" class="text-[#101D49] dark:text-white">Puesto:</label>
                        <select id="filtro_puesto" class="jz1 form-control">
                            <option value="">Todos los puestos</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="form-group">
                        <label for="filtro_departamento" class="text-[#101D49] dark:text-white">Departamento:</label>
                        <select id="filtro_departamento" class="jz1 form-control">
                            <option value="">Todos los departamentos</option>
                        </select>
                    </div>
                </div>
                
                <!-- Segunda fila de filtros -->
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="form-group">
                        <label for="filtro_estado" class="text-[#101D49] dark:text-white">Estado:</label>
                        <select id="filtro_estado" class="jz1 form-control">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="form-group">
                        <label for="filtro_obra" class="text-[#101D49] dark:text-white">Obra:</label>
                        <select id="filtro_obra" class="jz1 form-control">
                            <option value="">Todas las obras</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="form-group">
                        <label for="filtro_gerencia" class="text-[#101D49] dark:text-white">Gerencia:</label>
                        <select id="filtro_gerencia" class="jz1 form-control">
                            <option value="">Todas las gerencias</option>
                        </select>
                    </div>
                </div>
                
                <!-- Botón de limpiar filtros -->
                <div class="col-12 text-center">
                    <button id="limpiarFiltros" class="btn btn-success">
                        <i class="fa fa-times"></i> Limpiar Filtros
                    </button>
                </div>
            </div>

            @push('third_party_stylesheets')
            <!-- css -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
            <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
            @endpush


            <div class="table-responsive">
                {!! $dataTable->table(['width' => '100%', 'class' => 'table table-bordered table-striped']) !!}
            </div>

            @push('third_party_scripts')
            <!-- Bootstrap -->

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

            <!-- DataTables Core -->
            <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

            <!-- DataTables Buttons -->
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

            <!-- JSZIP y PDFMake para exportación -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

            <!-- DataTables Scripts -->
            {!! $dataTable->scripts() !!}
            
            <script>
            function cargarOpcionesFiltros() {
                // Cargar opciones desde el servidor usando AJAX
                $.ajax({
                    url: '{{ route("empleados.filtros") }}',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        
                        // Cargar puestos
                        var selectPuesto = $('#filtro_puesto');
                        selectPuesto.empty().append('<option value="">Todos los puestos</option>');
                        if (data.puestos) {
                            data.puestos.forEach(function(puesto) {
                                selectPuesto.append('<option value="' + puesto + '">' + puesto + '</option>');
                            });
                        }
                        
                        // Cargar departamentos
                        var selectDepartamento = $('#filtro_departamento');
                        selectDepartamento.empty().append('<option value="">Todos los departamentos</option>');
                        if (data.departamentos) {
                            data.departamentos.forEach(function(departamento) {
                                selectDepartamento.append('<option value="' + departamento + '">' + departamento + '</option>');
                            });
                        }
                        
                        // Cargar obras
                        var selectObra = $('#filtro_obra');
                        selectObra.empty().append('<option value="">Todas las obras</option>');
                        if (data.obras) {
                            data.obras.forEach(function(obra) {
                                selectObra.append('<option value="' + obra + '">' + obra + '</option>');
                            });
                        }
                        
                        // Cargar gerencias
                        var selectGerencia = $('#filtro_gerencia');
                        selectGerencia.empty().append('<option value="">Todas las gerencias</option>');
                        if (data.gerencias) {
                            data.gerencias.forEach(function(gerencia) {
                                selectGerencia.append('<option value="' + gerencia + '">' + gerencia + '</option>');
                            });
                        }
                        
                        // Reinicializar Select2 después de cargar las opciones
                        $('.jz1').select2('destroy').select2({
                            width: '100%',
                            placeholder: 'Seleccionar...',
                            allowClear: true,
                            dropdownParent: $('body'),
                            escapeMarkup: function(markup) {
                                return markup;
                            },
                            templateResult: function(data) {
                                if (data.loading) {
                                    return data.text;
                                }
                                return data.text;
                            },
                            templateSelection: function(data) {
                                return data.text;
                            }
                        });
                        
                      
                    },
                    error: function(xhr, status, error) {
                    
                        // Fallback: intentar cargar desde la tabla
                        cargarOpcionesFiltrosFallback();
                    }
                });
            }
            
            function cargarOpcionesFiltrosFallback() {
                var table = $('#tabla-empleados').DataTable();
                
                setTimeout(function() {
                    
                    // Cargar opciones de puestos usando el índice de columna
                    var puestos = table.column(2).data().unique().sort();
                var selectPuesto = $('#filtro_puesto');
                    selectPuesto.empty().append('<option value="">Todos los puestos</option>');
                puestos.each(function(d) {
                        if (d && d.trim() !== '') {
                            selectPuesto.append('<option value="' + d + '">' + d + '</option>');
                        }
                });
                
                    // Cargar opciones de departamentos usando el índice de columna
                    var departamentos = table.column(4).data().unique().sort();
                var selectDepartamento = $('#filtro_departamento');
                    selectDepartamento.empty().append('<option value="">Todos los departamentos</option>');
                departamentos.each(function(d) {
                        if (d && d.trim() !== '') {
                            selectDepartamento.append('<option value="' + d + '">' + d + '</option>');
                        }
                });
                
                    // Cargar opciones de obras usando el índice de columna
                    var obras = table.column(3).data().unique().sort();
                var selectObra = $('#filtro_obra');
                    selectObra.empty().append('<option value="">Todas las obras</option>');
                obras.each(function(d) {
                        if (d && d.trim() !== '') {
                            selectObra.append('<option value="' + d + '">' + d + '</option>');
                        }
                });
                
                    // Cargar opciones de gerencias usando el índice de columna
                    var gerencias = table.column(5).data().unique().sort();
                var selectGerencia = $('#filtro_gerencia');
                    selectGerencia.empty().append('<option value="">Todas las gerencias</option>');
                gerencias.each(function(d) {
                        if (d && d.trim() !== '') {
                            selectGerencia.append('<option value="' + d + '">' + d + '</option>');
                        }
                    });
                    
                    // Reinicializar Select2 después de cargar las opciones
                    $('.jz1').select2('destroy').select2({
                        width: '100%',
                        placeholder: 'Seleccionar...',
                        allowClear: true,
                        dropdownParent: $('body'),
                        escapeMarkup: function(markup) {
                            return markup;
                        },
                        templateResult: function(data) {
                            if (data.loading) {
                                return data.text;
                            }
                            return data.text;
                        },
                        templateSelection: function(data) {
                            return data.text;
                        }
                    });
                    
                }, 1000);
            }
            
            function configurarFiltros() {
                var table = $('#tabla-empleados').DataTable();
                
                // Evento para aplicar filtros
                $('#aplicarFiltros').on('click', function() {
                    aplicarFiltros();
                });
                
                // Evento para limpiar filtros
                $('#limpiarFiltros').on('click', function() {
                    // Limpiar todos los campos de filtro
                    $('#filtro_nombre').val('');
                    $('#filtro_puesto').val('').trigger('change');
                    $('#filtro_departamento').val('').trigger('change');
                    $('#filtro_estado').val('').trigger('change');
                    $('#filtro_obra').val('').trigger('change');
                    $('#filtro_gerencia').val('').trigger('change');
                    
                    // Limpiar búsquedas en todas las columnas usando el mismo método
                    table.search('').columns().search('').draw();
                });
                
                // Filtro en tiempo real para el nombre
                $('#filtro_nombre').on('keyup', function() {
                    var valor = $(this).val();
                    table.column(1).search(valor).draw(); // NombreEmpleado
                });
                
                // Filtros en tiempo real para los selects
                $('#filtro_puesto, #filtro_departamento, #filtro_estado, #filtro_obra, #filtro_gerencia').on('change', function() {
                    aplicarFiltros();
                });
                
                // Manejar el dismiss de Select2
                $('.jz1').on('select2:close', function(e) {
                    // Forzar el cierre si está abierto
                    var $element = $(this);
                    setTimeout(function() {
                        if ($element.hasClass('select2-container--open')) {
                            $element.select2('close');
                        }
                    }, 10);
                });
                
                // Manejar clicks fuera del dropdown para cerrarlo
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.select2-container').length && 
                        !$(e.target).closest('.jz1').length) {
                        $('.jz1').select2('close');
                    }
                });
                
                // Manejar tecla Escape para cerrar dropdowns
                $(document).on('keydown', function(e) {
                    if (e.keyCode === 27) { // Escape key
                        $('.jz1').select2('close');
                    }
                });
                
                function aplicarFiltros() {
                    var filtroNombre = $('#filtro_nombre').val();
                    var filtroPuesto = $('#filtro_puesto').val();
                    var filtroDepartamento = $('#filtro_departamento').val();
                    var filtroEstado = $('#filtro_estado').val();
                    var filtroObra = $('#filtro_obra').val();
                    var filtroGerencia = $('#filtro_gerencia').val();
                    
                    // Aplicar filtros usando índices de columna
                    // Para nombre, usar búsqueda normal (LIKE)
                    table.column(1).search(filtroNombre || ''); // NombreEmpleado
                    
                    // Para los selects, usar búsqueda exacta
                    if (filtroPuesto) {
                        table.column(2).search('^' + filtroPuesto + '$', true, false); // Puesto - búsqueda exacta
                    } else {
                        table.column(2).search(''); // Puesto
                    }
                    
                    if (filtroObra) {
                        table.column(3).search('^' + filtroObra + '$', true, false); // Obra - búsqueda exacta
                    } else {
                        table.column(3).search(''); // Obra
                    }
                    
                    if (filtroDepartamento) {
                        table.column(4).search('^' + filtroDepartamento + '$', true, false); // Departamento - búsqueda exacta
                    } else {
                        table.column(4).search(''); // Departamento
                    }
                    
                    if (filtroGerencia) {
                        table.column(5).search('^' + filtroGerencia + '$', true, false); // Gerencia - búsqueda exacta
                    } else {
                        table.column(5).search(''); // Gerencia
                    }
                    
                    // Para estado, usar búsqueda exacta también
                    if (filtroEstado) {
                        table.column(8).search('^' + filtroEstado + '$', true, false); // Estado - búsqueda exacta
                    } else {
                        table.column(8).search(''); // Estado
                    }
                    
                    // Redibujar la tabla
                    table.draw();
                }
            }
            </script>
            
            @endpush
        </div>
    </div>
</div>
@endsection