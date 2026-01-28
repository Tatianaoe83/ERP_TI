<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Ticket - Sistema de Soporte</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/tsparticles-slim@2.0.6/tsparticles.slim.bundle.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Electrolize&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: "Electrolize", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .fade-change {
            animation: fadeChange 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeChange {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Estilos personalizados para Select2 más amigable */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
            transition: all 0.2s ease !important;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #9ca3af !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #000 !important;
            outline: 2px solid rgba(0, 0, 0, 0.1) !important;
            outline-offset: 2px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px !important;
            padding-left: 0 !important;
            color: #1f2937 !important;
            font-size: 1rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #6b7280 transparent transparent transparent !important;
            border-width: 6px 5px 0 5px !important;
            margin-top: -3px !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #6b7280 transparent !important;
            border-width: 0 5px 6px 5px !important;
            margin-top: -3px !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            margin-top: 4px !important;
        }

        .select2-results__option {
            padding: 12px 16px !important;
            font-size: 0.95rem !important;
            transition: all 0.15s ease !important;
        }

        .select2-results__option[aria-selected=true] {
            background-color: #f3f4f6 !important;
            color: #1f2937 !important;
        }

        .select2-results__option--highlighted {
            background-color: #3b82f6 !important;
            color: white !important;
        }

        .select2-results__group {
            padding: 10px 16px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            color: #374151 !important;
            background-color: #f9fafb !important;
            border-bottom: 1px solid #e5e7eb !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 8px 12px !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #3b82f6 !important;
            outline: 2px solid rgba(59, 130, 246, 0.1) !important;
            outline-offset: 2px !important;
        }

        .select2-results__option .option-icon {
            display: inline-block;
            width: 20px;
            margin-right: 8px;
            text-align: center;
        }
    </style>
</head>

<body class="min-h-screen py-8 px-4">
    <div id="tsparticles" class="absolute top-0 left-0 w-full h-full -z-10"></div>
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8 fade-in">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-4 p-2">
                <img src="{{ asset('img/LogoAzul.png') }}" alt="Logo Proser" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-bold text-white mb-2" id="title">Selecciona una opción</h1>
            <p class="text-indigo-100">Selecciona una opción para continuar</p>
        </div>

        <form action="{{ route('soporte.ticket') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="glass-effect rounded-2xl shadow-2xl p-5 w-full fade-in">
                <div class=" flex flex-col p-3 gap-5 items-start justify-center">
                    <h2 class="text-black text-2xl font-semibold">¿Qué deseas enviar?</h2>
                    <select name="type" id="type" class="cursor-pointer border border-gray-300 rounded-md text-lg text-black w-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-black focus:border-black transition duration-200">
                        <option value="" selected disabled>Selecciona una opción</option>
                        <option value="Ticket">Ticket</option>
                        <option value="Solicitud">Solicitud</option>
                    </select>
                </div>
                <div id="ticket-form" class="hidden flex flex-col gap-3 p-4">
                    <div class="flex flex-row gap-3 items-center">
                        <div class="bg-green-500 rounded-full w-10 h-10 flex items-center justify-center text-white">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="text-xl font-bold text-black text-lg mb-2">Formulario de Ticket</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="correoEmpleado">Correo Electrónico *</label>
                            <input type="email" id="correoEmpleado" placeholder="Correo Electrónico" name="Correo" class="w-full p-2 border rounded mb-2" required />
                            <div id="correo-error" class="text-red-500 text-sm hidden mb-2"></div>
                        </div>
                        <div class="relative w-full">
                            <label for="autoEmpleadosTicket">Empleado</label>
                            <input type="text" id="autoEmpleadosTicket" placeholder="Nombre Empleado" autocomplete="off" class="autoEmpleados w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" class="EmpleadoID" name="EmpleadoID" id="EmpleadoID">
                            <div id="suggestions" class="suggestions absolute top-full left-0 w-full bg-white border border-gray-300 rounded shadow hidden z-50"></div>
                        </div>
                        <div>
                            <label for="numeroTelefono">Número Telefónico *</label>
                            <input type="number" id="numeroTelefono" placeholder="Número Telefónico" name="Numero" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled />
                        </div>
                        <div>
                            <label for="codeAnyDesk">Código AnyDesk</label>
                            <input type="number" id="codeAnyDesk" placeholder="Código AnyDesk" name="CodeAnyDesk" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled />
                        </div>
                        <div>
                            <label for="descripcionTicket">Descripción *</label>
                            <textarea id="descripcionTicket" placeholder="Descripción" name="Descripcion" class="w-full p-2 border rounded bg-gray-100" disabled></textarea>
                        </div>
                        <div
                            id="dropzone"
                            class="w-full border-2 border-dashed border-gray-400 rounded-md p-6 text-center transition bg-gray-100 opacity-50">
                            <input type="file" id="fileInput" name="imagen[]" class="hidden" multiple disabled />
                            <p class="text-gray-600">
                                Arrastra tus archivos aquí o
                                <span class="text-blue-600 underline">haz clic para subir</span>
                            </p>
                            <p id="counter" class="text-sm text-black mt-1">0/4 Imágenes</p>
                            <div id="previewGrid" class="grid grid-cols-2 gap-3 mt-3"></div>
                        </div>
                        <button type="submit" id="btnEnviar" class="w-20 h-10 bg-gray-400 text-white rounded-md transition-all duration-300 cursor-not-allowed" disabled>Enviar</button>
                    </div>
                </div>

                <div id="solicitud-form" class="hidden w-full p-4 flex flex-col gap-3">
                    <div class="flex flex-row gap-3 items-center">
                        <div class="bg-red-500 rounded-full w-10 h-10 flex items-center justify-center text-white">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="text-xl font-bold text-black text-lg mb-2">Formulario de Solicitud</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-black">
                        <div>
                            <label for="correoEmpleadoSolicitud">Correo Electrónico *</label>
                            <input type="email" id="correoEmpleadoSolicitud" placeholder="Correo Electrónico" name="Correo" class="w-full p-2 border rounded mb-2" required />
                            <div id="correo-solicitud-error" class="text-red-500 text-sm hidden mb-2"></div>
                        </div>
                        <div class="relative w-full">
                            <label for="autoEmpleadosSolicitud">Empleado</label>
                            <input type="text" id="autoEmpleadosSolicitud" placeholder="Nombre Empleado" autocomplete="off" class="autoEmpleados w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" class="EmpleadoID" name="EmpleadoID" id="EmpleadoIDSolicitud">
                            <div id="suggestionsEmpleados" class="suggestions absolute top-full left-0 w-full bg-white border border-gray-300 rounded shadow hidden z-50"></div>
                        </div>
                        <div>
                            <label for="NombreGerencia">Gerencia</label>
                            <input type="text" placeholder="Gerencia" name="NombreGerencia" id="NombreGerencia" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="GerenciaID" id="GerenciaID">
                        </div>
                        <div>
                            <label for="NombreObra">Obra</label>
                            <input type="text" placeholder="Obra" name="NombreObra" id="NombreObra" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="ObraID" id="ObraID">
                        </div>
                        <div>
                            <label for="NombrePuesto">Puesto</label>
                            <input type="text" placeholder="Puesto" id="NombrePuesto" name="NombrePuesto" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="PuestoID" id="PuestoID">
                        </div>
                        <div class="relative w-full">
                            <label for="SupervisorNombre">Supervisor/Jefe Inmediato</label>
                            <input type="text" id="SupervisorNombre" placeholder="Supervisor" autocomplete="off" class="autoSupervisor w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="SupervisorID" id="SupervisorID" class="SupervisorID">
                            <div id="suggestionsSupervisor" class="suggestionsSupervisor absolute top-full left-0 w-full bg-white border border-gray-300 rounded shadow hidden z-50"></div>
                        </div>
                            <div>
                                <label for="Motivo">Selecciona el motivo de la solicitud</label>
                                <select name="Motivo" id="Motivo" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                                    <option value="">Selecciona el motivo de la solicitud</option>
                                    <option value="Nuevo Ingreso">Nuevo Ingreso</option>
                                    <option value="Equipo Nuevo">Equipo Nuevo</option> <option value="Reemplazo por fallo o descompostura">Reemplazo por fallo o descompostura</option>
                                    <option value="Renovación">Renovación</option>
                                </select>
                            </div>
                        <div>
                            <label for="DescripcionMotivo">Describe el motivo de la solicitud</label>
                            <textarea id="DescripcionMotivo" placeholder="Describe Motivo" name="DescripcionMotivo" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled></textarea>
                        </div>
                        <div>
                            <label for="Proyecto">
                                Selecciona Ubicación
                                <span
                                    class="d-inline-block ms-1"
                                    tabindex="0"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Selecciona la ubicación donde se ubicará el equipo">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </span>
                            </label>
                            <select name="Proyecto" style="width:100%" id="Proyecto" class="cursor-pointer w-full text-black js-example-basic-single">
                            </select>
                        </div>
                        <div>
                            <label for="Requerimientos">Requerimientos</label>
                            <textarea name="Requerimientos" id="Requerimientos" placeholder="Requerimientos" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled></textarea>
                        </div>
                        <button type="submit" id="btnEnviarSolicitud" class="w-20 h-10 bg-gray-400 text-white rounded-md transition-all duration-300 cursor-not-allowed" disabled>Enviar</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Sección de Extensiones de Soporte -->
        <div class="glass-effect rounded-2xl shadow-2xl p-5 w-full fade-in mt-8">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-500 rounded-full w-10 h-10 flex items-center justify-center text-white">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black">¿Necesitas ayuda? Contacta a Soporte</h3>
                </div>
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center gap-3 mb-3">
                        <i class="fas fa-headset text-blue-600 text-xl"></i>
                        <h4 class="font-semibold text-black">Soporte Técnico</h4>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fas fa-phone text-blue-600"></i>
                        <span class="font-medium text-lg">Ext. 211</span>
                    </div>
                </div>
                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-gray-700 flex items-center gap-2">
                        <i class="fas fa-info-circle text-yellow-600"></i>
                        <span>Horario de atención: Lunes a Viernes de 9:00 AM a 6:00 PM | Sábados de 9:00 AM a 2:00 PM</span>
                    </p>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// =========================================================
// 1. VARIABLES GLOBALES (Usamos window para evitar conflictos)
// =========================================================
window.datosUbicacion = []; 
window.correoSolicitudValido = false; // Para el formulario Solicitud
window.correoTicketValido = false;    // Para el formulario Ticket

$(document).ready(function() {
    
    // Carga inicial de datos de ubicación
    $.ajax({
        url: "/getTypes",
        method: "GET",
        success: function(data) {
            window.datosUbicacion = data;
        }
    });

    // Inicializar Select2 básico (si hay alguno en el Ticket)
    $('.js-example-basic-single').select2();

    // =========================================================
    // 2. LÓGICA DE PESTAÑAS (TICKET vs SOLICITUD)
    // =========================================================
    $('#type').on('change', function() {
        var seleccion = $(this).val();

        $('#ticket-form').addClass('hidden');
        $('#solicitud-form').addClass('hidden');

        if (seleccion === 'Ticket') {
            $('#ticket-form').removeClass('hidden');
        } 
        else if (seleccion === 'Solicitud') {
            $('#solicitud-form').removeClass('hidden');
            // ARREGLO VISUAL: Reiniciar Select2 al mostrar el formulario
            setTimeout(function() { revivirSelect2(); }, 50);
        }
    });

    // =========================================================
    // 3. LÓGICA DEL FORMULARIO "SOLICITUD"
    // =========================================================
    var $inputCorreoSol = $('#correoEmpleadoSolicitud');

    // Deshabilitar campos de solicitud al inicio
    deshabilitarCamposSolicitud();

    // Detección de correo (Solicitud)
    $inputCorreoSol.on('change blur', function() {
        var correo = $(this).val().trim();
        if (correo && esCorreoValido(correo)) {
            buscarEmpleadoSolicitud(correo);
        }
    });
    
    // Enter en correo (Solicitud)
    $inputCorreoSol.on('keypress', function(e) {
        if(e.which === 13) { e.preventDefault(); $(this).blur(); }
    });

    // =========================================================
    // 4. LÓGICA DEL FORMULARIO "TICKET" (Tu código original integrado)
    // =========================================================
    
    // Validar Correo Ticket
    $('#correoEmpleado').on('change blur', function() {
        var correo = $(this).val().trim();
        var $error = $('#correo-error');
        
        if (!correo) {
             deshabilitarCamposTicket();
             return;
        }
        
        if (!esCorreoValido(correo)) {
            $error.removeClass('hidden').text('Correo inválido');
            deshabilitarCamposTicket();
        } else {
            $error.addClass('hidden');
            // Aquí llamarías a tu búsqueda de Ticket si existe
            // Por ahora simulamos que busca:
            buscarEmpleadoTicket(correo); 
        }
    });

    // Validar Teléfono
    $('#numeroTelefono').on('input', function() {
        var val = $(this).val().replace(/\D/g, '');
        $(this).val(val);
        var $err = $('#telefono-error');
        if(!$err.length) $(this).after('<div id="telefono-error" class="text-red-500 text-sm hidden mb-2"></div>');
        
        if (val.length === 10) {
            $('#telefono-error').addClass('hidden');
            $(this).removeClass('border-red-500').addClass('border-green-500');
        } else {
            $('#telefono-error').removeClass('hidden').text('Debe tener 10 dígitos');
            $(this).addClass('border-red-500');
        }
    });

    // Validar AnyDesk y Descripción (Visual)
    $('#codeAnyDesk, #descripcionTicket').on('input', function() {
        if($(this).val().trim().length > 0) $(this).removeClass('border-red-500').addClass('border-green-500');
        else $(this).removeClass('border-green-500').addClass('border-red-500');
    });

    // =========================================================
    // 5. EVENTO ENVIAR (VALIDACIÓN FINAL PARA AMBOS)
    // =========================================================
    $('form').on('submit', function(e) {
        var errores = [];
        var esSolicitud = $('#solicitud-form').is(':visible');
        var esTicket = $('#ticket-form').is(':visible');

        // --- VALIDACIÓN SOLICITUD ---
        if (esSolicitud) {
            var correo = $('#correoEmpleadoSolicitud').val().trim();
            var empleadoID = $('#EmpleadoIDSolicitud').val();
            var proyecto = $('#Proyecto').val();

            if (!correo) errores.push('El correo es requerido.');
            else if (!window.correoSolicitudValido || !empleadoID) errores.push('Debes validar el correo del empleado primero.');
            
            if (!proyecto) errores.push('Debes seleccionar una ubicación (Proyecto).');
        }

        // --- VALIDACIÓN TICKET ---
        else if (esTicket) {
            var correoT = $('#correoEmpleado').val().trim();
            var tel = $('#numeroTelefono').val().replace(/\D/g, '');
            var desc = $('#descripcionTicket').val().trim();

            if (!correoT) errores.push('El correo es requerido.');
            // Si tienes validación de empleado para ticket, agrégala aquí:
            // else if (!window.correoTicketValido) errores.push('Empleado no encontrado.');

            if (tel.length !== 10) errores.push('El teléfono debe tener 10 dígitos.');
            if (!desc) errores.push('La descripción es requerida.');
        }

        // --- MANEJO DE ERRORES ---
        if (errores.length > 0) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Faltan datos', html: errores.join('<br>'), confirmButtonColor: '#ef4444' });
            } else {
                alert(errores.join('\n'));
            }
            return false;
        }

        // Habilitar campos deshabilitados para que se envíen en el POST
        $('input, select, textarea').prop('disabled', false);
    });
});

// =========================================================
// FUNCIONES AUXILIARES
// =========================================================

function esCorreoValido(correo) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
}

// --- SOLICITUD ---
function deshabilitarCamposSolicitud() {
    window.correoSolicitudValido = false;
    $('#autoEmpleadosSolicitud, #NombreGerencia, #NombreObra, #NombrePuesto, #Motivo, #DescripcionMotivo, #SupervisorNombre, #Requerimientos')
        .prop('disabled', true).addClass('bg-gray-100');
    $('#btnEnviarSolicitud').prop('disabled', true).addClass('bg-gray-400 cursor-not-allowed').removeClass('bg-red-500');
    console.log('deshabilitarCamposSolicitud');

    // Deshabilitar Select2 de forma segura
    var $p = $('#Proyecto');
    $p.prop('disabled', true);
    if ($p.hasClass('select2-hidden-accessible')) { try{ $p.select2('enable', false); }catch(e){} }
    var $cont = $p.next('.select2-container');
    if($cont.length) $cont.addClass('select2-container--disabled');
}
function buscarEmpleadoSolicitud(correo) {
    $('#autoEmpleadosSolicitud').val('Buscando...');
    
    $.ajax({
        url: '/buscarEmpleadoPorCorreo',
        method: 'GET',
        data: { correo: correo, type: 'Solicitud' },
        success: function(data) {
            window.correoSolicitudValido = true;
            
            // 1. Llenar campos visuales
            $('#autoEmpleadosSolicitud').val(data.NombreEmpleado).removeClass('border-blue-500').addClass('border-green-500');
            $('#NombreGerencia').val(data.NombreGerencia);
            $('#NombreObra').val(data.NombreObra);
            $('#NombrePuesto').val(data.NombrePuesto);
            
            // 2. Llenar IDs ocultos
            $('#EmpleadoIDSolicitud').val(data.EmpleadoID);
            $('#GerenciaID').val(data.GerenciaID);
            $('#ObraID').val(data.ObraID);
            $('#PuestoID').val(data.PuestoID);

            // =========================================================
            // 3. LÓGICA PARA OCULTAR SUPERVISOR SI ES GERENTE
            // =========================================================
            let puesto = (data.NombrePuesto || '').toUpperCase();
            let $supervisorInput = $('#SupervisorNombre');
            let $supervisorContainer = $supervisorInput.closest('div'); // Selecciona el contenedor (label + input)

            // Lista de palabras clave para identificar jefes que no requieren supervisor
            if (puesto.includes('GERENTE') || puesto.includes('DIRECTOR')) {
                // CASO GERENTE: Ocultamos el campo
                $supervisorContainer.addClass('hidden'); 
                
                // Le ponemos un valor automático y quitamos el required para que el formulario pase
                $supervisorInput
                    .val('N/A - Jerarquía Gerencial')
                    .prop('required', false)
                    .prop('disabled', false); // Debe estar habilitado para que se envíe el valor "N/A"
            } else {
                // CASO NORMAL: Mostramos el campo
                $supervisorContainer.removeClass('hidden');
                
                // Limpiamos, habilitamos y hacemos obligatorio
                $supervisorInput
                    .val('')
                    .prop('required', true)
                    .prop('disabled', false)
                    .removeClass('bg-gray-100');
            }

            // 4. Habilitar el resto de los campos (Nota: Quité #SupervisorNombre de aquí porque ya se manejó arriba)
            $('#Motivo, #DescripcionMotivo, #Requerimientos').prop('disabled', false).removeClass('bg-gray-100');
            
            // 5. Activar botón de envío
            $('#btnEnviarSolicitud').prop('disabled', false).removeClass('bg-gray-400 cursor-not-allowed').addClass('bg-red-500');
            
            console.log('habilitarCamposSolicitud con lógica de jerarquía');
            
            // 6. Desbloquear ubicación (Select2)
            revivirSelect2(); 
        },
        error: function() {
            deshabilitarCamposSolicitud();
            $('#autoEmpleadosSolicitud').val('').addClass('border-red-500');
            $('#correo-solicitud-error').removeClass('hidden').text('No encontrado');
        }
    });
}

function revivirSelect2() {
    var $p = $('#Proyecto');
    if ($p.hasClass("select2-hidden-accessible")) $p.select2('destroy');
    
    $p.empty().append('<option></option>');
    if (window.datosUbicacion.length) {
        $.each(window.datosUbicacion, function(i, g) {
            var $opt = $('<optgroup>', { label: g.text });
            var pre = g.text.includes("Proyecto") ? "PR" : (g.text.includes("Obra") ? "OB" : "GE");
            if(g.children) $.each(g.children, function(j,item){ $opt.append($('<option>',{value:pre+item.id, text:item.text})); });
            $p.append($opt);
        });
    }
    
    $p.prop('disabled', false).removeAttr('disabled');
    $p.select2({
        placeholder: "Selecciona ubicación...", allowClear: true, width: '100%',
        templateResult: function(d){ return d.id?$('<span>'+d.text+'</span>'):d.text; },
        templateSelection: function(d){ return d.text; }
    });
}

// --- TICKET (Funciones básicas) ---
function deshabilitarCamposTicket() {
    window.correoTicketValido = false;
    $('#numeroTelefono, #codeAnyDesk, #descripcionTicket, #fileInput').prop('disabled', true).addClass('bg-gray-100');
    $('#btnEnviar').prop('disabled', true);
}

function buscarEmpleadoTicket(correo) {
    // Aquí puedes poner tu AJAX para ticket si es distinto al de solicitud
    // Por ahora solo habilitamos para que funcione el ejemplo
    $.ajax({
        url: '/buscarEmpleadoPorCorreo',
        method: 'GET',
        data: { correo: correo, type: 'Ticket' },
        success: function(data) {
             window.correoTicketValido = true;
             $('#autoEmpleadosTicket').val(data.NombreEmpleado).addClass('border-green-500');
             $('#EmpleadoID').val(data.EmpleadoID);
             // Habilitar campos
             $('#numeroTelefono, #codeAnyDesk, #descripcionTicket, #fileInput').prop('disabled', false).removeClass('bg-gray-100');
             $('#btnEnviar').prop('disabled', false).removeClass('bg-gray-400').addClass('bg-red-500');
        }
    });
}
</script>    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
    @php
    $tipo = session('tipo', 'Ticket'); // Por defecto es Ticket si no se especifica
    $titulo = $tipo === 'Solicitud' ? '¡Solicitud Enviada Exitosamente! 🎉' : '¡Ticket Enviado Exitosamente! 🎉';
    $mensaje = $tipo === 'Solicitud'
    ? 'Hemos recibido tu solicitud y nuestro equipo la revisará pronto para procesarla.'
    : 'Hemos recibido tu ticket y nuestro equipo de soporte técnico la revisará pronto.';
    @endphp
    <script>
        Swal.fire({
            icon: 'success',
            title: '{{ $titulo }}',
            html: `
                <div style="text-align: center; padding: 10px;">
                    <p style="font-size: 16px; margin-bottom: 10px; color: #333;">
                        <strong>¡Gracias por contactarnos!</strong>
                    </p>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        {{ $mensaje }}
                    </p>
                    <p style="font-size: 13px; color: #888; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <i class="fas fa-clock"></i> Te contactaremos a la brevedad posible
                    </p>
                </div>
            `,
            confirmButtonText: '¡Entendido!',
            confirmButtonColor: '#10b981',
            timer: 10000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        });
    </script>
    @elseif (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops, algo salió mal 😔',
            html: `
                <div style="text-align: center; padding: 10px;">
                    <p style="font-size: 16px; margin-bottom: 10px; color: #333;">
                        <strong>No pudimos procesar tu solicitud</strong>
                    </p>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        {{ session('error') }}
                    </p>
                    <p style="font-size: 13px; color: #888; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <i class="fas fa-info-circle"></i> Por favor, intenta nuevamente o contacta a soporte
                    </p>
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px; margin-top: 15px;">
                        <p style="font-size: 12px; color: #0369a1; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-phone-alt"></i> Extensión de Soporte:
                        </p>
                        <div style="font-size: 11px; color: #0c4a6e;">
                            <span><strong>Soporte Técnico:</strong> Ext. 211</span>
                        </div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 8px; padding-top: 8px; border-top: 1px solid #cbd5e1;">
                            <i class="fas fa-clock"></i> Horario: Lunes a Viernes 9:00 AM - 6:00 PM | Sábados 9:00 AM - 2:00 PM
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#ef4444',
            timer: 5000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        });
    </script>
    @endif
    <script type="text/javascript">
        tsParticles.load(
            "tsparticles", {
                background: {
                    color: "#000"
                },
                particles: {
                    links: {
                        enable: true
                    },
                    move: {
                        enable: true
                    },
                    opacity: {
                        value: {
                            min: 0.5,
                            max: 1
                        }
                    },
                    size: {
                        value: {
                            min: 1,
                            max: 3
                        }
                    }
                },
                interactivity: {
                    events: {
                        onHover: {
                            enable: false,
                            mode: "repulse"
                        },
                        onclick: {
                            enable: false
                        }
                    }
                }
            }
        )
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const select = document.getElementById("type");
            const ticket = document.getElementById("ticket-form");
            const solicitud = document.getElementById("solicitud-form");

            // Función para manejar atributos required según visibilidad
            const manejarRequired = (form, visible) => {
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (visible) {
                        field.setAttribute('required', 'required');
                    } else {
                        field.removeAttribute('required');
                    }
                });
            };

            // Remover required inicialmente ya que ambos formularios están ocultos
            manejarRequired(ticket, false);
            manejarRequired(solicitud, false);

            const resetForm = (form) => {
                const inputs = form.querySelectorAll("input, textarea, select");
                inputs.forEach(input => {
                    input.value = "";
                });
            }

            const title = document.getElementById("title");

            select.addEventListener("change", function() {
                const value = this.value;

                // Remover required de ambos formularios antes de ocultarlos
                manejarRequired(ticket, false);
                manejarRequired(solicitud, false);

                ticket.classList.add("hidden");
                solicitud.classList.add("hidden");

                resetForm(ticket);
                resetForm(solicitud);

                if (value === "Ticket") {
                    ticket.classList.remove("hidden");
                    manejarRequired(ticket, true); // Agregar required cuando se muestra
                    title.textContent = "Crear Nuevo Ticket";
                    // Deshabilitar campos del formulario de Ticket
                    if (typeof deshabilitarCampos === 'function') {
                        deshabilitarCampos();
                    }
                } else if (value === "Solicitud") {
                    solicitud.classList.remove("hidden");
                    manejarRequired(solicitud, true); // Agregar required cuando se muestra
                    title.textContent = "Crear Nueva Solicitud";
                    // Deshabilitar campos del formulario de Solicitud
                    if (typeof deshabilitarCamposSolicitud === 'function') {
                        deshabilitarCamposSolicitud();
                    }
                }

                title.classList.remove("fade-change");
                void title.offsetWidth;
                title.classList.add("fade-change");
            });
        });
    </script>



    <script>
        (() => {
            const dropzone = document.getElementById("dropzone");
            const fileInput = document.getElementById("fileInput");
            const previewGrid = document.getElementById("previewGrid");
            const counter = document.getElementById("counter");

            const MAX_FILES = 4;
            const FILE_MAX_SIZE = 2 * 1024 * 1024;
            const MAX_SIZE = 8 * 1024 * 1024;
            const dt = new DataTransfer();

            const updateCounter = () => {
                counter.textContent = `${dt.files.length} / ${MAX_FILES} Archivos`;
            };

            const isImage = (file) => file && file.type.startsWith("image/");

            const formatBytes = (bytes) => {
                if (!bytes && bytes !== 0) return "";
                const sizes = ["B", "KB", "MB", "GB"];
                const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), sizes.length - 1);
                const val = bytes / Math.pow(1024, i);
                return `${val.toFixed(val >= 10 || i === 0 ? 0 : 1)} ${sizes[i]}`;
            };

            const getExt = (name) => {
                const p = name.lastIndexOf(".");
                return p >= 0 ? name.slice(p + 1).toLowerCase() : "";
            };

            const getFileIconInfo = (file) => {
                const ext = getExt(file.name);
                // Intentar usar iconos específicos primero, con fallback a iconos básicos
                if (file.type === "application/pdf" || ext === "pdf") {
                    return {
                        icon: "fa-file-pdf",
                        fallbackIcon: "fa-file",
                        style: "fas",
                        color: "text-red-600",
                        bgColor: "bg-red-50",
                        emoji: "📄"
                    };
                }
                if (/msword|vnd.openxmlformats-officedocument.wordprocessingml/.test(file.type) || ["doc", "docx"].includes(ext)) {
                    return {
                        icon: "fa-file-word",
                        fallbackIcon: "fa-file-alt",
                        style: "fas",
                        color: "text-blue-600",
                        bgColor: "bg-blue-50",
                        emoji: "📝"
                    };
                }
                if (/vnd.ms-excel|spreadsheetml|csv/.test(file.type) || ["xls", "xlsx", "csv"].includes(ext)) {
                    return {
                        icon: "fa-file-excel",
                        fallbackIcon: "fa-file-alt",
                        style: "fas",
                        color: "text-green-600",
                        bgColor: "bg-green-50",
                        emoji: "📊"
                    };
                }
                if (/vnd.ms-powerpoint|presentationml/.test(file.type) || ["ppt", "pptx"].includes(ext)) {
                    return {
                        icon: "fa-file-powerpoint",
                        fallbackIcon: "fa-file-alt",
                        style: "fas",
                        color: "text-orange-600",
                        bgColor: "bg-orange-50",
                        emoji: "📽️"
                    };
                }
                if (/zip|x-7z-compressed|x-rar-compressed|x-zip-compressed/.test(file.type) || ["zip", "rar", "7z"].includes(ext)) {
                    return {
                        icon: "fa-file-archive",
                        fallbackIcon: "fa-file",
                        style: "fas",
                        color: "text-yellow-600",
                        bgColor: "bg-yellow-50",
                        emoji: "📦"
                    };
                }
                if (/text\/plain|md|json|xml/.test(file.type) || ["txt", "md", "json", "xml"].includes(ext)) {
                    return {
                        icon: "fa-file-alt",
                        fallbackIcon: "fa-file-alt",
                        style: "fas",
                        color: "text-gray-600",
                        bgColor: "bg-gray-50",
                        emoji: "📄"
                    };
                }
                return {
                    icon: "fa-file",
                    fallbackIcon: "fa-file",
                    style: "fas",
                    color: "text-gray-600",
                    bgColor: "bg-gray-50",
                    emoji: "📄"
                };
            };

            const renderPreviews = () => {
                previewGrid.innerHTML = "";
                Array.from(dt.files).forEach((file, idx) => {
                    const card = document.createElement("div");
                    card.className = "relative rounded-md overflow-hidden border border-gray-200 shadow-sm flex flex-col";

                    const visual = document.createElement("div");
                    visual.className = "w-full h-32 flex items-center justify-center bg-gray-50";

                    if (isImage(file)) {
                        const url = URL.createObjectURL(file);
                        const img = document.createElement("img");
                        img.src = url;
                        img.alt = file.name;
                        img.className = "w-full h-32 object-cover";
                        img.onload = () => URL.revokeObjectURL(url);
                        visual.appendChild(img);
                    } else {
                        const fileInfo = getFileIconInfo(file);
                        visual.className = `w-full h-32 flex flex-col items-center justify-center ${fileInfo.bgColor}`;

                        // Usar emoji como icono principal (más confiable y universal)
                        const emoji = document.createElement("div");
                        emoji.className = "text-6xl mb-2";
                        emoji.textContent = fileInfo.emoji;
                        emoji.style.fontSize = "3.5rem";
                        visual.appendChild(emoji);

                        // Agregar extensión como texto
                        const ext = getExt(file.name);
                        if (ext) {
                            const extText = document.createElement("span");
                            extText.className = "text-xs font-bold uppercase " + fileInfo.color.replace('text-', 'text-').replace('-600', '-700');
                            extText.textContent = "." + ext;
                            visual.appendChild(extText);
                        }
                    }

                    const meta = document.createElement("div");
                    meta.className = "px-2 py-1 bg-white text-xs text-gray-700";
                    meta.innerHTML = `
        <div class="truncate" title="${file.name}">${file.name}</div>
        <div class="text-gray-500">${formatBytes(file.size)}</div>
      `;

                    const removeBtn = document.createElement("button");
                    removeBtn.type = "button";
                    removeBtn.className = "absolute top-1 right-1 bg-black/70 text-white rounded-full w-6 h-6 leading-6 text-center";
                    removeBtn.textContent = "×";
                    removeBtn.title = "Quitar";
                    removeBtn.addEventListener("click", (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const next = new DataTransfer();
                        Array.from(dt.files).forEach((f, i) => {
                            if (i !== idx) next.items.add(f);
                        });
                        while (dt.items.length) dt.items.remove(0);
                        Array.from(next.files).forEach(f => dt.items.add(f));
                        fileInput.files = dt.files;
                        renderPreviews();
                        updateCounter();
                    });

                    card.append(visual, removeBtn, meta);
                    previewGrid.appendChild(card);
                });
                updateCounter();
            };

            const addFiles = (fileList) => {
                const incoming = Array.from(fileList);
                let currenTotal = Array.from(dt.files).reduce((acc, f) => acc + f.size, 0);
                for (const file of incoming) {
                    if (dt.files.length >= MAX_FILES) {
                        Swal.fire("Límite alcanzado", "Solo puedes subir hasta 4 archivos", "warning");
                        break;
                    };

                    if (file.size > FILE_MAX_SIZE) {
                        Swal.fire("Archivo demasiado pesado", `${file.name} supera los 2MB`, "error");
                        break;
                    };

                    if (currenTotal + file.size > MAX_SIZE) {
                        Swal.fire("Límite total excedido", "El total no debera pasar de 8MB", "error");
                        break;
                    }

                    const duplicate = Array.from(dt.files).some(
                        (f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
                    );
                    if (duplicate) continue;

                    dt.items.add(file);
                }
                fileInput.files = dt.files;
                renderPreviews();
            };

            dropzone.addEventListener("click", (e) => {
                if (e.target.closest("button")) return;
                fileInput.click();
            });

            dropzone.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropzone.classList.add("bg-blue-50", "border-blue-500");
            });
            dropzone.addEventListener("dragleave", () => {
                dropzone.classList.remove("bg-blue-50", "border-blue-500");
            });
            dropzone.addEventListener("drop", (e) => {
                e.preventDefault();
                dropzone.classList.remove("bg-blue-50", "border-blue-500");
                addFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener("change", () => {
                addFiles(fileInput.files);
                //fileInput.value = "";
            });

            updateCounter();
        })();
    </script>
  
    <script>
        $(document).ready(function() {
            const $input = $(".autoSupervisor");
            const $suggestions = $(".suggestionsSupervisor");

            $input.on("input", function() {
                const query = $(this).val().trim();

                if (query.length < 2) {
                    $suggestions.empty().addClass("hidden");
                    return;
                }

                $.ajax({
                    url: "/autocompleteEmpleado",
                    method: "GET",
                    data: {
                        query
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            $suggestions.html("<div class='p-2 text-gray-500'>Sin resultados</div>").removeClass("hidden");
                            return;
                        }
                        let html = "";
                        data.forEach(item => {
                            html += `<div class="p-2 hover:bg-blue-100 cursor-pointer" data-id="${item.EmpleadoID}" data-name="${item.NombreEmpleado}">${item.NombreEmpleado}</div>`;
                        });

                        $suggestions.html(html).removeClass("hidden");
                        $suggestions.children().on("click", function() {
                            const nombre = $(this).data("name");
                            const id = $(this).data("id");
                            $input.val(nombre);
                            $("#SupervisorID").val(id);

                            $suggestions.empty().addClass("hidden");
                        });
                    }
                });
            });

            $(document).on("click", function(e) {
                if (!$(e.target).closest(".autoSupervisor, .suggestionsSupervisor").length) {
                    $suggestions.empty().addClass("hidden");
                }
            });
        });
    </script>




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
    </script>


    <script>
        // Script para validar correo y llenar datos automáticamente en formulario de Solicitud
        $(document).ready(function() {
            let correoSolicitudTimeout;
            let intervaloHabilitacion = null;

            // Función para deshabilitar campos cuando no hay correo válido
            function deshabilitarCamposSolicitud() {

                correoSolicitudValido = false; // Marcar correo como inválido
                $('#autoEmpleadosSolicitud').prop('disabled', true).addClass('bg-gray-100');
                $('#NombreGerencia').prop('disabled', true).addClass('bg-gray-100');
                $('#NombreObra').prop('disabled', true).addClass('bg-gray-100');
                $('#NombrePuesto').prop('disabled', true).addClass('bg-gray-100');
                $('#Motivo').prop('disabled', true).addClass('bg-gray-100');
                $('#DescripcionMotivo').prop('disabled', true).addClass('bg-gray-100');
                $('#SupervisorNombre').prop('disabled', true).addClass('bg-gray-100');
                // Deshabilitar campo Proyecto (Select2)
                $('#Proyecto').prop('disabled', true);
                $('#Proyecto').select2('enable', false);
                // Agregar clase de deshabilitado al contenedor de Select2 si existe
                var $select2Container = $('#Proyecto').next('.select2-container');
                if ($select2Container.length) {
                    $select2Container.addClass('select2-container--disabled');
                }
                $('#Requerimientos').prop('disabled', true).addClass('bg-gray-100');
                console.log('deshabilitarCamposSolicitud');

                $('#btnEnviarSolicitud').prop('disabled', true).removeClass('bg-red-500 hover:scale-105').addClass('bg-gray-400 cursor-not-allowed');
                console.log('deshabilitarCamposSolicitud');
            }

            // Función para cargar datos en el campo de ubicación
            function cargarDatosUbicacion(callback) {
                var $proyecto = $('#Proyecto');

                // Verificar si el select ya tiene opciones
                if ($proyecto.find('option').length > 0 && $proyecto.find('optgroup').length > 0) {
                    // Ya tiene datos, ejecutar callback si existe con un pequeño delay
                    if (typeof callback === 'function') {
                        setTimeout(function() {
                            callback();
                        }, 100);
                    }
                    return;
                }

                // Cargar datos desde el servidor
                $.ajax({
                    url: "/getTypes",
                    method: "GET",
                    success: function(data) {
                        $proyecto.empty();

                        $.each(data, function(index, group) {
                            var $optgroup = $('<optgroup>', {
                                label: group.text
                            });

                            var prefix = "";

                            if (group.text.toLowerCase().includes("proyecto")) {
                                prefix = "PR";
                            } else if (group.text.toLowerCase().includes("obra")) {
                                prefix = "OB";
                            } else if (group.text.toLowerCase().includes("gerencia")) {
                                prefix = "GE";
                            }

                            if (group.children) {
                                $.each(group.children, function(i, item) {
                                    $optgroup.append(
                                        $('<option>', {
                                            value: prefix + item.id,
                                            text: item.text
                                        })
                                    );
                                });
                            }

                            $proyecto.append($optgroup);
                        });

                        // Actualizar Select2 después de agregar las opciones
                        if (typeof $.fn.select2 !== 'undefined' && $proyecto.hasClass('select2-hidden-accessible')) {
                            $proyecto.trigger('change.select2');
                        }

                        $proyecto.val(null).trigger('change');

                        // Ejecutar callback si existe (con un pequeño delay para asegurar que Select2 se actualice)
                        if (typeof callback === 'function') {
                            setTimeout(function() {
                                callback();
                            }, 100);
                        }
                    },
                    error: function() {

                        // Ejecutar callback incluso si hay error
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                });
            }

            // Función para habilitar campo de ubicación
            function habilitarCampoUbicacion() {


                if (!correoSolicitudValido) {
                    return;
                }

                var $proyecto = $('#Proyecto');

                if (!$proyecto.length) {

                    return;
                }

                // Limpiar intervalo anterior si existe
                if (intervaloHabilitacion) {
                    clearInterval(intervaloHabilitacion);
                    intervaloHabilitacion = null;
                }

                // Función simple y directa para habilitar
                function habilitarAhora() {
                    if (!correoSolicitudValido) {
                        return;
                    }

                    var $proyecto = $('#Proyecto');
                    var $select2Container = $proyecto.next('.select2-container');

                    if (!$select2Container.length) {
                        $select2Container = $proyecto.parent().find('.select2-container');
                    }

                    // Forzar habilitación del select
                    $proyecto.prop('disabled', false);
                    $proyecto.removeAttr('disabled');

                    // Habilitar Select2
                    if (typeof $.fn.select2 !== 'undefined') {
                        try {
                            $proyecto.select2('enable', true);
                        } catch (e) {

                        }
                    }

                    // Habilitar contenedor
                    if ($select2Container.length) {
                        $select2Container.removeClass('select2-container--disabled');
                        $select2Container.find('.select2-selection').css({
                            'pointer-events': 'auto',
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                        $select2Container.find('input').prop('disabled', false);
                    }


                }

                // Cargar datos primero si es necesario
                cargarDatosUbicacion(function() {


                    // Habilitar inmediatamente
                    habilitarAhora();

                    // Habilitar después de delays para asegurar
                    setTimeout(habilitarAhora, 50);
                    setTimeout(habilitarAhora, 200);
                    setTimeout(habilitarAhora, 500);

                    // Crear intervalo para mantener habilitado
                    intervaloHabilitacion = setInterval(function() {
                        if (correoSolicitudValido) {
                            habilitarAhora();
                        } else {
                            clearInterval(intervaloHabilitacion);
                            intervaloHabilitacion = null;
                        }
                    }, 300);

                    console.log('Intervalo de habilitación creado');
                });
            }

            // Función para habilitar campos cuando el correo es válido
            function habilitarCamposSolicitud() {
                correoSolicitudValido = true; // Marcar correo como válido
                // Mantener empleado deshabilitado pero visible
                $('#autoEmpleadosSolicitud').prop('disabled', true).addClass('bg-gray-100');

                // Habilitar campos de Gerencia, Obra y Puesto (solo lectura, ya están llenos)
                $('#NombreGerencia').prop('disabled', true).removeClass('bg-gray-100').addClass('bg-green-50');
                $('#NombreObra').prop('disabled', true).removeClass('bg-gray-100').addClass('bg-green-50');
                $('#NombrePuesto').prop('disabled', true).removeClass('bg-gray-100').addClass('bg-green-50');

                // Habilitar todos los demás campos
                $('#Motivo').prop('disabled', false).removeClass('bg-gray-100');
                $('#DescripcionMotivo').prop('disabled', false).removeClass('bg-gray-100');
                $('#SupervisorNombre').prop('disabled', false).removeClass('bg-gray-100');

                // Habilitar campo Proyecto (Select2) usando la función específica
                habilitarCampoUbicacion();

                $('#Requerimientos').prop('disabled', false).removeClass('bg-gray-100');
                console.log('habilitarCamposSolicitud');
                $('#btnEnviarSolicitud').prop('disabled', false).removeClass('bg-gray-400 cursor-not-allowed').addClass('bg-red-500 hover:scale-105');
            }

            // Deshabilitar campos inicialmente
            deshabilitarCamposSolicitud();

            // Función para deshabilitar campo de ubicación
            function deshabilitarCamposSolicitud() {
    correoSolicitudValido = false; // Marcar correo como inválido

    // Deshabilitar inputs normales (Esto está bien, no da error)
    $('#autoEmpleadosSolicitud').prop('disabled', true).addClass('bg-gray-100');
    $('#NombreGerencia').prop('disabled', true).addClass('bg-gray-100');
    $('#NombreObra').prop('disabled', true).addClass('bg-gray-100');
    $('#NombrePuesto').prop('disabled', true).addClass('bg-gray-100');
    $('#Motivo').prop('disabled', true).addClass('bg-gray-100');
    $('#DescripcionMotivo').prop('disabled', true).addClass('bg-gray-100');
    $('#SupervisorNombre').prop('disabled', true).addClass('bg-gray-100');
    $('#Requerimientos').prop('disabled', true).addClass('bg-gray-100');
    console.log('deshabilitarCamposSolicitud');
    $('#btnEnviarSolicitud').prop('disabled', true).removeClass('bg-red-500 hover:scale-105').addClass('bg-gray-400 cursor-not-allowed');

    var $proyecto = $('#Proyecto');

    // 1. Siempre deshabilitamos el HTML nativo (esto nunca falla y es seguro)
    $proyecto.prop('disabled', true);

    // 2. Solo llamamos a Select2 SI ya fue inicializado.
    // La clase 'select2-hidden-accessible' es la marca de que Select2 está vivo.
    if ($proyecto.hasClass('select2-hidden-accessible')) {
        try {
            $proyecto.select2('enable', false);
        } catch (e) {
            console.warn("Select2 aún no listo, ignorando comando disable.");
        }
    }

    // 3. Forzamos el estilo visual gris al contenedor (si existe)
    // Esto asegura que se vea bloqueado aunque el JS de Select2 no haya cargado aún
    var $select2Container = $proyecto.next('.select2-container');
    if ($select2Container.length) {
        $select2Container.addClass('select2-container--disabled');
    }
}


            function buscarEmpleadoPorCorreoSolicitud(correo) {
                const $errorDiv = $('#correo-solicitud-error');
                const $empleadoInput = $('#autoEmpleadosSolicitud');
                const $gerenciaInput = $('#NombreGerencia');
                const $obraInput = $('#NombreObra');
                const $puestoInput = $('#NombrePuesto');
                const $empleadoIDInput = $('#EmpleadoIDSolicitud');
                const $gerenciaIDInput = $('#GerenciaID');
                const $obraIDInput = $('#ObraID');
                const $puestoIDInput = $('#PuestoID');

                // Asegurar que el campo de ubicación esté deshabilitado durante la búsqueda
                deshabilitarCampoUbicacion();

                // Mostrar indicador de carga
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


                        // Primero marcar correo como válido
                        correoSolicitudValido = true;

                        // Llenar datos primero
                        $empleadoInput.val(data.NombreEmpleado)
                            .removeClass('border-blue-500 border-red-500')
                            .addClass('border-green-500');
                        $gerenciaInput.val(data.NombreGerencia || '')
                            .removeClass('border-blue-500 border-red-500')
                            .addClass('border-green-500');
                        $obraInput.val(data.NombreObra || '')
                            .removeClass('border-blue-500 border-red-500')
                            .addClass('border-green-500');
                        $puestoInput.val(data.NombrePuesto || '')
                            .removeClass('border-blue-500 border-red-500')
                            .addClass('border-green-500');
                        $empleadoIDInput.val(data.EmpleadoID);
                        $gerenciaIDInput.val(data.GerenciaID || '');
                        $obraIDInput.val(data.ObraID || '');
                        $puestoIDInput.val(data.PuestoID || '');
                        $errorDiv.addClass('hidden').text('');

                        // Luego habilitar todos los campos
                        habilitarCamposSolicitud();

                        // Habilitar campo de ubicación con múltiples intentos
                        setTimeout(function() {
                            habilitarCampoUbicacion();
                        }, 100);

                        setTimeout(function() {
                            habilitarCampoUbicacion();
                        }, 300);

                        setTimeout(function() {
                            habilitarCampoUbicacion();
                        }, 600);
                    },
                    error: function(xhr) {
                        // Error en la búsqueda - deshabilitar campos
                        deshabilitarCamposSolicitud();
                        deshabilitarCampoUbicacion(); // Asegurar que el campo de ubicación esté deshabilitado
                        if (xhr.status === 404) {
                            $empleadoInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $gerenciaInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $obraInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $puestoInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $('#Motivo').val('').removeClass('border-green-500').addClass('border-red-500');
                            $('#DescripcionMotivo').val('').removeClass('border-green-500').addClass('border-red-500');
                            $('#SupervisorNombre').val('').removeClass('border-green-500').addClass('border-red-500');
                            $('#Proyecto').val(null).trigger('change');
                            $('#Requerimientos').val('').removeClass('border-green-500').addClass('border-red-500');
                            $empleadoIDInput.val('');
                            $gerenciaIDInput.val('');
                            $obraIDInput.val('');
                            $puestoIDInput.val('');
                            $('#SupervisorID').val('');
                            $errorDiv.removeClass('hidden').text(xhr.responseJSON?.error || 'No se encontró correo, contacta a soporte');
                        } else {
                            $empleadoInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $gerenciaInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $obraInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $puestoInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $('#Motivo').val('').removeClass('border-green-500').addClass('border-red-500');
                            $('#DescripcionMotivo').val('').removeClass('border-green-500').addClass('border-red-500');
                            $('#SupervisorNombre').val('').removeClass('border-green-500').addClass('border-red-500');
                            $('#Proyecto').val(null).trigger('change');
                            $('#Requerimientos').val('').removeClass('border-green-500').addClass('border-red-500');
                            $empleadoIDInput.val('');
                            $gerenciaIDInput.val('');
                            $obraIDInput.val('');
                            $puestoIDInput.val('');
                            $('#SupervisorID').val('');
                            $errorDiv.removeClass('hidden').text('Error al buscar empleado. Intenta de nuevo.');
                        }
                    }
                });
            }
        });

        // Función auxiliar para que no tengas que repetir el código de los iconos dos veces
        function formatState(data) {
            if (!data.id) {
                return data.text;
            }
            var $result = $('<span></span>');
            var icon = '<i class="fas fa-map-marker-alt text-gray-500 mr-2"></i>';
            var prefix = '';

            // Intentamos obtener el grupo
            var element = data.element;
            var groupLabel = '';
            if (element) {
                var optgroup = $(element).closest('optgroup');
                groupLabel = optgroup.attr('label') || '';
            }

            if (groupLabel.toLowerCase().includes("proyecto")) {
                icon = '<i class="fas fa-folder-open text-blue-500 mr-2"></i>';
                prefix = '<span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded mr-2">PR</span>';
            } else if (groupLabel.toLowerCase().includes("obra")) {
                icon = '<i class="fas fa-building text-orange-500 mr-2"></i>';
                prefix = '<span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded mr-2">OB</span>';
            } else if (groupLabel.toLowerCase().includes("gerencia")) {
                icon = '<i class="fas fa-briefcase text-purple-500 mr-2"></i>';
                prefix = '<span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded mr-2">GE</span>';
            }

            $result.append(icon + prefix + '<span>' + data.text + '</span>');
            return $result;
        };
    </script>
</body>

</html>