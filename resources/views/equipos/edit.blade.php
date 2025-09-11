@extends('layouts.app')

@section('content')
<h3 class="text-[#101D49] dark:text-white">Editar Equipos</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::model($equipos, ['route' => ['equipos.update', $equipos->ID], 'method' => 'patch', 'id' => 'edit-equipo-form']) !!}

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-desktop me-2"></i>Información del Equipo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @include('equipos.fields')
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
                        <p class="mb-0 mt-2">Los cambios se aplicarán automáticamente a todos los registros del inventario que correspondan a este equipo.</p>
                    </div>

                   

                    <!-- Información de registros afectados -->
                    <div id="info-inventario">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Registros en inventario:</strong>
                            <span id="count-inventario">Cargando...</span> registros se actualizarán automáticamente.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        {!! Form::submit('Guardar Cambios', ['class' => 'btn btn-primary btn-lg', 'id' => 'btn-guardar']) !!}
                        <a href="{{ route('equipos.index') }}" class="btn btn-outline-secondary">
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
function initializeEquipoEdit() {
   
    // Cargar información del inventario al cargar la página
    loadInventarioCount();
    
    // Interceptar el envío del formulario
    $('#edit-equipo-form').on('submit', function(e) {
        e.preventDefault();
        showConfirmAlert();
    });
    
    // Validación en tiempo real
    $('input, select').on('change', function() {
        validateForm();
    });
    
    function showConfirmAlert() {
        // Obtener valores actuales para el preview
        var categoria = $('#CategoriaID option:selected').text();
        var marca = $('#Marca').val();
        var modelo = $('#Modelo').val();
        var precio = $('#Precio').val();
        
        // Cargar información del inventario
        loadInventarioCount(function(count) {
            // Crear el contenido HTML para el SweetAlert
            var htmlContent = `
                <div class="text-left">
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¡Atención!</strong> Los cambios se aplicarán automáticamente al inventario.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-desktop me-2"></i>Cambios en el Equipo:
                            </h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Categoría:</span>
                                    <span class="fw-bold">${categoria || 'Sin categoría'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Marca:</span>
                                    <span class="fw-bold">${marca || 'Sin marca'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Modelo:</span>
                                    <span class="fw-bold">${modelo || 'Sin modelo'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Precio:</span>
                                    <span class="fw-bold">${precio ? '$' + parseFloat(precio).toFixed(2) : '$0.00'}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-sync me-2"></i>Impacto en Inventario:
                            </h6>
                            <div class="alert alert-info">
                                <i class="fas fa-database me-2"></i>
                                <strong>Registros afectados:</strong> ${count}
                            </div>
                            <p class="text-muted small">
                                <i class="fas fa-check-circle me-1"></i>
                                Todos los registros del inventario con este equipo se actualizarán automáticamente.
                            </p>
                        </div>
                    </div>
                    
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>¿Estás seguro de continuar?</strong> Esta acción no se puede deshacer.
                    </div>
                </div>
            `;
            
            // Mostrar SweetAlert
            Swal.fire({
                title: '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirmar Guardado',
                html: htmlContent,
                width: '800px',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-save me-2"></i>Sí, Guardar y Sincronizar',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        // Enviar el formulario via AJAX
                        var formData = $('#edit-equipo-form').serialize();
                        var formAction = $('#edit-equipo-form').attr('action');
                        var formMethod = $('#edit-equipo-form').find('input[name="_method"]').val() || 'POST';
                        
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
                                reject('Error al actualizar el equipo: ' + error);
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar mensaje de éxito con el mensaje del servidor
                    var serverMessage = result.value ? result.value.message : 'El equipo se ha actualizado correctamente';
                    Swal.fire({
                        title: '<i class="fas fa-check-circle text-success me-2"></i>¡Actualización Exitosa!',
                        html: `
                            <div class="text-center">
                                <div class="alert alert-success">
                                    <i class="fas fa-sync me-2"></i>
                                    <strong>${serverMessage}</strong>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: '<i class="fas fa-arrow-left me-2"></i>Volver al Listado',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // Redirigir al listado
                        window.location.href = '{{ route("equipos.index") }}';
                    });
                }
            }).catch((error) => {
                // Mostrar mensaje de error
                Swal.fire({
                    title: '<i class="fas fa-exclamation-triangle text-danger me-2"></i>Error',
                    text: error,
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }
    
    function loadInventarioCount(callback) {
        var equipoId = {{ $equipos->ID }};
       
        
        $.ajax({
            url: '{{ route("equipos.inventario-records") }}',
            method: 'GET',
            data: { equipo_id: equipoId },
            dataType: 'json',
            beforeSend: function() {
               
                $('#count-inventario').text('Cargando...');
            },
            success: function(data) {
              
                
                if (data && data.records) {
                    $('#count-inventario').text(data.records.length);
                    if (callback) {
                        callback(data.records.length);
                    }
                } else {
                  
                    $('#count-inventario').text('0');
                    if (callback) {
                        callback(0);
                    }
                }
            },
            error: function(xhr, status, error) {
               
                $('#count-inventario').text('Error');
                if (callback) {
                    callback(0);
                }
            }
        });
    }
    
    function validateForm() {
        var isValid = true;
        
        // Validar campos requeridos
        if (!$('#CategoriaID').val()) {
            isValid = false;
        }
        
        if (!$('#Marca').val().trim()) {
            isValid = false;
        }
        
        if (!$('#Modelo').val().trim()) {
            isValid = false;
        }
        
        if (!$('#Precio').val() || $('#Precio').val() < 0) {
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
        initializeEquipoEdit();
    });
} else {
    // Si jQuery no está disponible, esperar a que se cargue
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                initializeEquipoEdit();
            });
        } 
    });
}
</script>

@endsection