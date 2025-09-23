@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Líneas Telefónicas</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::model($lineasTelefonicas, ['route' => ['lineasTelefonicas.update', $lineasTelefonicas->LineaID], 'method' => 'patch', 'id' => 'edit-linea-form']) !!}

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-phone me-2"></i>Información de la Línea Telefónica
                    </h5>
                </div>
                <div class="card-body">
                <div class="row">
                    @include('lineas_telefonicas.fields')
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Sincronización -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sync me-2"></i>Sincronización con Inventario
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Sincronización Automática</strong>
                        <p class="mb-0 mt-2">Los cambios se aplicarán automáticamente a todos los registros del inventario que correspondan a esta línea telefónica.</p>
                    </div>

                    <!-- Información de registros afectados -->
                    <div id="info-inventario">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Registros en inventario:</strong>
                            <span id="count-inventario">Cargando...</span> registros se actualizarán automáticamente.
                        </div>
                        <div id="error-inventario" class="alert alert-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nota:</strong> No se pudo cargar la información del inventario. Los cambios se aplicarán cuando se guarden.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        {!! Form::submit('Guardar Cambios', ['class' => 'btn btn-primary btn-lg', 'id' => 'btn-guardar']) !!}
                        <a href="{{ route('lineasTelefonicas.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
            </div>
        </div>

        {!! Form::close() !!}
</div>

<script>
// Función para inicializar cuando jQuery esté disponible
function initializeLineaEdit() {
   
    // Cargar información del inventario al cargar la página
    loadInventarioCount();
    
    // Interceptar el envío del formulario
    $('#edit-linea-form').on('submit', function(e) {
        e.preventDefault();
        showConfirmAlert();
    });
    
    // Validación en tiempo real
    $('input, select').on('change', function() {
        validateForm();
    });
    
    
    function showConfirmAlert() {
        // Obtener valores actuales para el preview
        var numTelefonico = $('#NumTelefonico').val();
        var planId = $('#PlanID option:selected').text();
        var cuentaPadre = $('#CuentaPadre').val();
        var cuentaHija = $('#CuentaHija').val();
        var tipoLinea = $('#TipoLinea').val();
        var obraId = $('#ObraID option:selected').text();
        var fechaFianza = $('#FechaFianza').val();
        var costoFianza = $('#CostoFianza').val();
        var montoRenovacion = $('#MontoRenovacionFianza').val();
        
        // Cargar información del inventario
        loadInventarioCount(function(count) {
            // Crear el contenido HTML para el SweetAlert
            var htmlContent = `
             <div class="text-left">
                    <div class="mb-4">
                        <h6 class="mb-3">Cambios en la Línea:</h6>
                        <div class="mb-2">
                            <strong>Número:</strong> ${numTelefonico || 'Sin número'}
                        </div>
                        <div class="mb-2">
                            <strong>Plan:</strong> ${planId || 'Sin plan'}
                        </div>
                        <div class="mb-2">
                            <strong>Cuenta Padre:</strong> ${cuentaPadre || 'Sin cuenta padre'}
                        </div>
                        <div class="mb-2">
                            <strong>Cuenta Hija:</strong> ${cuentaHija || 'Sin cuenta hija'}
                        </div>
                        <div class="mb-2">
                            <strong>Tipo de Línea:</strong> ${tipoLinea || 'Sin tipo de línea'}
                        </div>
                        <div class="mb-3">
                            <strong>Fecha Fianza:</strong> ${fechaFianza || 'Sin fecha fianza'}
                        </div>
                        <div class="mb-3">
                            <strong>Costo Fianza:</strong> ${costoFianza || 'Sin costo fianza'}
                        </div>
                        <div class="mb-3">
                            <strong>Monto de Renovación de Fianza:</strong> ${montoRenovacion || 'Sin monto de renovación de fianza'}
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="mb-2">
                            <strong>Registros en inventario afectados:</strong> ${count}
                        </div>
                        <p class="text-muted small mb-0">
                            Los cambios se aplicarán automáticamente al inventario.
                        </p>
                    </div>
                    
                    <div class="border-top pt-3">
                        <p class="mb-0"><strong>¿Continuar con el guardado?</strong></p>
                    </div>
                </div>
            `;
            
            // Mostrar SweetAlert
            Swal.fire({
                title: 'Confirmar Guardado',
                html: htmlContent,
                width: '800px',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        // Enviar el formulario via AJAX
                        var formData = $('#edit-linea-form').serialize();
                        var formAction = $('#edit-linea-form').attr('action');
                        var formMethod = $('#edit-linea-form').find('input[name="_method"]').val() || 'POST';
                        
                        $.ajax({
                            url: formAction,
                            type: 'POST',
                            data: formData,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Error desconocido');
                                }
                            },
                            error: function(xhr, status, error) {
                                reject('Error al actualizar la línea: ' + error);
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar mensaje de éxito con el mensaje del servidor
                    var serverMessage = result.value ? result.value.message : 'La línea se ha actualizado correctamente';
                    Swal.fire({
                        title: 'Actualización Exitosa',
                        text: serverMessage,
                        icon: 'success',
                        confirmButtonText: 'Continuar',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // Redirigir al listado
                        window.location.href = '{{ route("lineasTelefonicas.index") }}';
                    });
                }
            }).catch((error) => {
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: error,
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }
    
    function loadInventarioCount(callback) {
        var lineaId = {{ $lineasTelefonicas->LineaID }};
        console.log('Cargando inventario para línea ID:', lineaId);
        console.log('URL de la petición:', '{{ route("lineas-telefonicas.inventario-records") }}');
        
        $.ajax({
            url: '{{ route("lineas-telefonicas.inventario-records") }}',
            method: 'GET',
            data: { linea_id: lineaId },
            dataType: 'json',
            beforeSend: function() {
                console.log('Enviando petición AJAX...');
                $('#count-inventario').text('Cargando...');
            },
            success: function(data) {
                console.log('Respuesta del servidor recibida:', data);
                console.log('Tipo de datos:', typeof data);
                console.log('Tiene records:', data.hasOwnProperty('records'));
                console.log('Records es array:', Array.isArray(data.records));
                console.log('Cantidad de records:', data.records ? data.records.length : 'undefined');
                
                if (data && data.records && Array.isArray(data.records)) {
                    var count = data.records.length;
                    $('#count-inventario').text(count);
                    $('#error-inventario').hide();
                    if (callback) {
                        callback(count);
                    }
                } else {
                    console.warn('Estructura de datos inesperada o sin registros:', data);
                    $('#count-inventario').text('0');
                    $('#error-inventario').hide();
                    if (callback) {
                        callback(0);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar inventario:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Response JSON:', xhr.responseJSON);
                
                // Mostrar mensaje más amigable en lugar de "Error"
                $('#count-inventario').text('No disponible');
                $('#error-inventario').show();
                
                // Mostrar mensaje de error más específico
                var errorMessage = 'No se pudo cargar la información del inventario';
                if (xhr.status === 404) {
                    errorMessage = 'Ruta no encontrada';
                } else if (xhr.status === 500) {
                    errorMessage = 'Error interno del servidor';
                } else if (xhr.status === 0) {
                    errorMessage = 'Sin conexión al servidor';
                }
                
                // Mostrar alerta temporal con el error
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error de Conexión',
                        text: errorMessage,
                        icon: 'warning',
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
                
                if (callback) {
                    callback(0);
                }
            }
        });
    }
    
    function validateForm() {
        var isValid = true;
        
        // Validar campos requeridos
        if (!$('#NumTelefonico').val().trim()) {
            isValid = false;
        }
        
        if (!$('#PlanID').val()) {
            isValid = false;
        }
        
        if (!$('#CuentaPadre').val().trim()) {
            isValid = false;
        }
        
        if (!$('#CuentaHija').val().trim()) {
            isValid = false;
        }
        
        if (!$('#TipoLinea').val()) {
            isValid = false;
        }
        
        if (!$('#ObraID').val()) {
            isValid = false;
        }
        
        if (!$('#FechaFianza').val()) {
            isValid = false;
        }
        
        if (!$('#CostoFianza').val() || $('#CostoFianza').val() < 0) {
            isValid = false;
        }
        
        // Validar MontoRenovacionFianza si tiene valor
        if ($('#MontoRenovacionFianza').val() && $('#MontoRenovacionFianza').val() < 0) {
            isValid = false;
        }
        
        // Habilitar/deshabilitar botón
        if (!isValid) { 
            $('#btn-guardar').prop('disabled', true).addClass('disabled');
        } else {
            $('#btn-guardar').prop('disabled', false).removeClass('disabled');
        }
    }
    
    // Inicializar validación
    validateForm();
}

// Verificar si jQuery está disponible y ejecutar inicialización
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        initializeLineaEdit();
    });
} else {
    // Si jQuery no está disponible, esperar a que se cargue
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                initializeLineaEdit();
            });
        } 
    });
}
</script>

@endsection