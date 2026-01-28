    <script>
        // Variables globales para rastrear validación de correos
        let correoValido = false;
        let correoSolicitudValido = false;

        // Script para validar correo y llenar datos automáticamente
        $(document).ready(function() {
            let correoTimeout;

            // Función para deshabilitar todos los campos excepto el correo
            function deshabilitarCampos() {
                correoValido = false; // Marcar correo como inválido
                $('#autoEmpleadosTicket').prop('disabled', true).addClass('bg-gray-100');
                $('#numeroTelefono').prop('disabled', true).prop('required', false).addClass('bg-gray-100');
                $('#codeAnyDesk').prop('disabled', true).prop('required', false).addClass('bg-gray-100');
                $('#descripcionTicket').prop('disabled', true).prop('required', false).addClass('bg-gray-100');
                $('#fileInput').prop('disabled', true);
                $('#btnEnviar').prop('disabled', true).removeClass('bg-red-500 hover:scale-105').addClass('bg-gray-400 cursor-not-allowed');
                $('#dropzone').addClass('bg-gray-100 opacity-50').removeClass('hover:bg-gray-100');
            }

            // Función para habilitar solo campos específicos
            function habilitarCamposEspecificos() {
                correoValido = true; // Marcar correo como válido
                // Mantener empleado deshabilitado pero visible
                $('#autoEmpleadosTicket').prop('disabled', true).addClass('bg-gray-100');

                // Habilitar solo campos específicos y hacerlos requeridos
                $('#numeroTelefono').prop('disabled', false).prop('required', true).removeClass('bg-gray-100');
                $('#codeAnyDesk').prop('disabled', false).removeClass('bg-gray-100');
                $('#descripcionTicket').prop('disabled', false).prop('required', true).removeClass('bg-gray-100');
                $('#fileInput').prop('disabled', false);
                $('#btnEnviar').prop('disabled', false).removeClass('bg-gray-400 cursor-not-allowed').addClass('bg-red-500 hover:scale-105');
                $('#dropzone').removeClass('bg-gray-100 opacity-50').addClass('hover:bg-gray-100');
            }

            // Deshabilitar campos inicialmente
            deshabilitarCampos();

            $('#correoEmpleado').on('input', function() {
                const correo = $(this).val().trim();
                const $errorDiv = $('#correo-error');
                const $empleadoInput = $('#autoEmpleadosTicket');
                const $numeroInput = $('#numeroTelefono');
                const $empleadoIDInput = $('#EmpleadoID');

                // Limpiar timeout anterior
                clearTimeout(correoTimeout);

                // Deshabilitar campos si el correo está vacío
                if (correo === '') {
                    deshabilitarCampos();
                    $empleadoInput.val('').removeClass('border-green-500').addClass('border-gray-300');
                    $numeroInput.val('').removeClass('border-green-500').addClass('border-gray-300');
                    $empleadoIDInput.val('');
                    $errorDiv.addClass('hidden').text('');
                    return;
                }

                // Validar formato de correo básico
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(correo)) {
                    deshabilitarCampos();
                    $errorDiv.removeClass('hidden').text('Por favor ingresa un correo válido');
                    $empleadoInput.val('').removeClass('border-green-500').addClass('border-red-500');
                    $numeroInput.val('').removeClass('border-green-500').addClass('border-red-500');
                    $empleadoIDInput.val('');
                    return;
                }

                // Esperar 500ms después de que el usuario deje de escribir
                correoTimeout = setTimeout(function() {
                    // Buscar empleado por correo para tickets
                    $.ajax({
                        url: '/buscarEmpleadoPorCorreo',
                        method: 'GET',
                        data: { correo: correo, type: 'Ticket' },
                        success: function(data) {
                            window.correoTicketValido = true;
                            correoValido = true;
                            $('#autoEmpleadosTicket').val(data.NombreEmpleado).addClass('border-green-500');
                            $('#EmpleadoID').val(data.EmpleadoID);
                            $errorDiv.addClass('hidden');
                            
                            // Habilitar campos
                            habilitarCamposEspecificos();
                        },
                        error: function() {
                            deshabilitarCampos();
                            $errorDiv.removeClass('hidden').text('No se encontró el empleado');
                            $empleadoInput.val('').addClass('border-red-500');
                        }
                    });
                }, 500);
            });

            // Función corregida para buscar empleado (SOLICITUD)
            // Función corregida para buscar empleado (SOLICITUD)
            function buscarEmpleadoPorCorreoSolicitud(correo) {
                const $errorDiv = $('#correo-solicitud-error');
                // Referencias a campos
                const $empleadoInput = $('#autoEmpleadosSolicitud');
                const $gerenciaInput = $('#NombreGerencia');
                const $obraInput = $('#NombreObra');
                const $puestoInput = $('#NombrePuesto');
                const $empleadoIDInput = $('#EmpleadoIDSolicitud');
                const $gerenciaIDInput = $('#GerenciaID');
                const $obraIDInput = $('#ObraID');
                const $puestoIDInput = $('#PuestoID');

                // Referencias al Supervisor
                const $supervisorInput = $('#SupervisorNombre');
                // Buscamos el contenedor padre (el div que envuelve el input y el label) para ocultar todo
                const $supervisorContainer = $supervisorInput.closest('div'); 

                // Indicadores visuales de carga
                $empleadoInput.val('Buscando...').addClass('border-blue-500');
                $gerenciaInput.val('Buscando...').addClass('border-blue-500');
                $obraInput.val('Buscando...').addClass('border-blue-500');
                $puestoInput.val('Buscando...').addClass('border-blue-500');
                $errorDiv.addClass('hidden').text('');

                $.ajax({
                    url: '/buscarEmpleadoPorCorreo',
                    method: 'GET',
                    data: {
                        correo: correo,
                        type: 'Solicitud'
                    },
                    success: function(data) {
                        // MARCAR COMO VÁLIDO INMEDIATAMENTE
                        correoSolicitudValido = true;
                        window.correoSolicitudValido = true;

                        // Llenar datos visuales
                        $empleadoInput.val(data.NombreEmpleado).removeClass('border-blue-500 border-red-500').addClass('border-green-500');
                        $gerenciaInput.val(data.NombreGerencia || '').removeClass('border-blue-500 border-red-500').addClass('border-green-500');
                        $obraInput.val(data.NombreObra || '').removeClass('border-blue-500 border-red-500').addClass('border-green-500');
                        $puestoInput.val(data.NombrePuesto || '').removeClass('border-blue-500 border-red-500').addClass('border-green-500');

                        // Llenar IDs
                        $empleadoIDInput.val(data.EmpleadoID);
                        $gerenciaIDInput.val(data.GerenciaID || '');
                        $obraIDInput.val(data.ObraID || '');
                        $puestoIDInput.val(data.PuestoID || '');

                        // =======================================================
                        // LÓGICA DE JERARQUÍA (GERENTE vs SUPERVISOR)
                        // =======================================================
                        let nombrePuesto = (data.NombrePuesto || '').toUpperCase();
                        
                        // Si el puesto contiene GERENTE o DIRECTOR, ocultamos supervisor
                        if (nombrePuesto.includes('GERENTE') || nombrePuesto.includes('DIRECTOR')) {
                            // Ocultar contenedor visualmente
                            $supervisorContainer.addClass('hidden');
                            
                            // Deshabilitar validación y poner valor por defecto para que el backend no falle
                            $supervisorInput.prop('required', false).prop('disabled', false).val('N/A - Jerarquía Gerencial');
                        } else {
                            // Si NO es gerente, mostramos el campo
                            $supervisorContainer.removeClass('hidden');
                            
                            // Habilitar campo, limpiar valor anterior y hacerlo requerido
                            $supervisorInput.prop('disabled', false).prop('required', true).val('').removeClass('bg-gray-100');
                        }

                        // Habilitar campos de texto generales
                        $('#Motivo').prop('disabled', false).removeClass('bg-gray-100');
                        $('#DescripcionMotivo').prop('disabled', false).removeClass('bg-gray-100');
                        $('#Requerimientos').prop('disabled', false).removeClass('bg-gray-100');
                        
                        $('#btnEnviarSolicitud').prop('disabled', false).removeClass('bg-gray-400 cursor-not-allowed').addClass('bg-red-500 hover:scale-105');

                        // =======================================================
                        // ZONA CRÍTICA: DESBLOQUEO DE UBICACIÓN (PROYECTO)
                        // =======================================================
                        var $proyecto = $('#Proyecto');

                        // 1. Aseguramos que el select nativo esté libre
                        $proyecto.prop('disabled', false);
                        $proyecto.removeAttr('disabled');

                        // 2. Si Select2 está activo, lo forzamos a habilitarse
                        if ($proyecto.hasClass("select2-hidden-accessible")) {
                            $proyecto.select2('enable', true);
                        }

                        // 3. TRUCO FINAL: Eliminamos manualmente la clase de bloqueo del contenedor visual
                        var $s2Container = $proyecto.next('.select2-container');
                        if ($s2Container.length) {
                            $s2Container.removeClass('select2-container--disabled');
                            $s2Container.find('*').css({
                                'pointer-events': 'auto',
                                'opacity': '1',
                                'cursor': 'pointer'
                            });
                            $s2Container.find('input').prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        // En error sí bloqueamos
                        correoSolicitudValido = false;

                        // Bloquear ubicación y Supervisor
                        $('#Proyecto').prop('disabled', true);
                        $('#SupervisorNombre').prop('disabled', true).addClass('bg-gray-100');
                        
                        try {
                            $('#Proyecto').select2('enable', false);
                        } catch (e) {}

                        // Limpieza de error visual...
                        $empleadoInput.val('').addClass('border-red-500');
                        $errorDiv.removeClass('hidden').text('No se encontró el empleado.');
                    }
                });
            }

            // Validación del número telefónico (10 dígitos)
            $('#numeroTelefono').on('input', function() {
                const numero = $(this).val().replace(/\D/g, ''); // Solo números
                const $errorDiv = $('#telefono-error');

                // Crear div de error si no existe
                if ($errorDiv.length === 0) {
                    $(this).after('<div id="telefono-error" class="text-red-500 text-sm hidden mb-2"></div>');
                }

                if (numero.length === 0) {
                    $('#telefono-error').addClass('hidden').text('');
                    $(this).removeClass('border-red-500 border-green-500').addClass('border-gray-300');
                } else if (numero.length === 10) {
                    $('#telefono-error').addClass('hidden').text('');
                    $(this).removeClass('border-red-500 border-gray-300').addClass('border-green-500');
                } else {
                    $('#telefono-error').removeClass('hidden').text('El número telefónico debe tener exactamente 10 dígitos');
                    $(this).removeClass('border-green-500 border-gray-300').addClass('border-red-500');
                }

                // Actualizar el valor solo con números
                $(this).val(numero);
            });

            // Validación del código AnyDesk
            $('#codeAnyDesk').on('input', function() {
                const anyDesk = $(this).val().trim();
                const $errorDiv = $('#anydesk-error');

                // Crear div de error si no existe
                if ($errorDiv.length === 0) {
                    $(this).after('<div id="anydesk-error" class="text-red-500 text-sm hidden mb-2"></div>');
                }

                if (anyDesk.length === 0) {
                    $('#anydesk-error').addClass('hidden').text('');
                    $(this).removeClass('border-red-500 border-green-500').addClass('border-gray-300');
                } else {
                    $('#anydesk-error').addClass('hidden').text('');
                    $(this).removeClass('border-red-500 border-gray-300').addClass('border-green-500');
                }
            });

            // Validación de la descripción
            $('#descripcionTicket').on('input', function() {
                const descripcion = $(this).val().trim();
                const $errorDiv = $('#descripcion-error');

                // Crear div de error si no existe
                if ($errorDiv.length === 0) {
                    $(this).after('<div id="descripcion-error" class="text-red-500 text-sm hidden mb-2"></div>');
                }

                if (descripcion.length === 0) {
                    $('#descripcion-error').addClass('hidden').text('');
                    $(this).removeClass('border-red-500 border-green-500').addClass('border-gray-300');
                } else {
                    $('#descripcion-error').addClass('hidden').text('');
                    $(this).removeClass('border-red-500 border-gray-300').addClass('border-green-500');
                }
            });

            // Validar formulario antes de enviar
            $('form').on('submit', function(e) {
                let errores = [];

                // Validar si es el formulario de Ticket
                if ($('#ticket-form').is(':visible')) {
                    const numero = $('#numeroTelefono').val().replace(/\D/g, '');
                    const anyDesk = $('#codeAnyDesk').val().trim();
                    const descripcion = $('#descripcionTicket').val().trim();
                    const correo = $('#correoEmpleado').val().trim();
                    const empleadoID = $('#EmpleadoID').val();

                    // Asegurar que el campo de correo se envíe correctamente
                    // Si el campo está deshabilitado, habilitarlo temporalmente para el envío
                    const $correoInput = $('#correoEmpleado');
                    const correoWasDisabled = $correoInput.prop('disabled');
                    if (correoWasDisabled) {
                        $correoInput.prop('disabled', false);
                    }

                    // Crear un campo hidden con el correo para asegurar que se envíe
                    if (correo && !$('#correoHidden').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'correoHidden',
                            name: 'Correo',
                            value: correo
                        }).appendTo('form');
                    } else if (correo && $('#correoHidden').length) {
                        $('#correoHidden').val(correo);
                    }

                    // Validar formato de correo
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!correo) {
                        errores.push('El correo electrónico es requerido');
                    } else if (!emailRegex.test(correo)) {
                        errores.push('El formato del correo electrónico no es válido');
                    } else if (!correoValido || !empleadoID) {
                        errores.push('Debe validar un correo electrónico válido. Por favor, espera a que se valide el correo antes de enviar.');
                        // Resaltar el campo de correo
                        $('#correoEmpleado').addClass('border-red-500').focus();
                        $('#correo-error').removeClass('hidden').text('Debe validar el correo electrónico antes de enviar');
                    }

                    // Validar número telefónico
                    if (numero.length !== 10) {
                        errores.push('El número telefónico debe tener exactamente 10 dígitos');
                    }

                    // Validar descripción
                    if (!descripcion) {
                        errores.push('La descripción es requerida');
                    }
                }
                // Validar si es el formulario de Solicitud
                else if ($('#solicitud-form').is(':visible')) {
                    const correo = $('#correoEmpleadoSolicitud').val().trim();
                    const empleadoID = $('#EmpleadoIDSolicitud').val();

                    if (!correo) {
                        errores.push('El correo electrónico es requerido');
                    } else if (!emailRegex.test(correo)) {
                        errores.push('El formato del correo electrónico no es válido');
                    } else if (!correoSolicitudValido || !empleadoID) {
                        errores.push('Debe validar un correo electrónico válido. Por favor, espera a que se valide el correo antes de enviar.');
                        // Resaltar el campo de correo
                        $('#correoEmpleadoSolicitud').addClass('border-red-500').focus();
                        $('#correo-solicitud-error').removeClass('hidden').text('Debe validar el correo electrónico antes de enviar');
                    }
                }

                if (errores.length > 0) {
                    e.preventDefault();
                    // Restaurar estado del campo de correo si se modificó
                    if (typeof correoWasDisabled !== 'undefined' && correoWasDisabled) {
                        $('#correoEmpleado').prop('disabled', true);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        html: 'Por favor corrige los siguientes errores:<br><br>• ' + errores.join('<br>• '),
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#ef4444'
                    });
                    return false;
                }

                // Asegurar que el campo de correo esté habilitado antes de enviar
                if ($('#ticket-form').is(':visible')) {
                    $('#correoEmpleado').prop('disabled', false);
                }
            });
        });
        // Evento para arreglar el Select2 al cambiar entre Ticket y Solicitud
        $('#type').on('change', function() {
            var seleccion = $(this).val();

            // Ocultar todo primero
            $('#ticket-form').addClass('hidden');
            $('#solicitud-form').addClass('hidden');

            if (seleccion === 'Ticket') {
                $('#ticket-form').removeClass('hidden');
            } else if (seleccion === 'Solicitud') {
                $('#solicitud-form').removeClass('hidden');

                // REINICIAR SELECT2 AL MOSTRAR EL FORMULARIO
                setTimeout(function() {
                    var $proyecto = $('#Proyecto');

                    // Si existe una instancia previa rota, la destruimos
                    if ($proyecto.hasClass("select2-hidden-accessible")) {
                        $proyecto.select2('destroy');
                    }

                    // Aseguramos que el HTML esté desbloqueado
                    $proyecto.prop('disabled', false).removeAttr('disabled');

                    // Creamos la instancia nueva y limpia
                    $proyecto.select2({
                        placeholder: "Busca y selecciona una ubicación...",
                        allowClear: true,
                        width: '100%',
                        // Tus templates visuales (si los usas) van aquí
                        templateResult: function(data) {
                            return data.id ? $('<span>' + data.text + '</span>') : data.text;
                        },
                        templateSelection: function(data) {
                            return data.text;
                        }
                    });
                }, 100); // Pequeño retraso para asegurar que el div ya es visible
            }
        });