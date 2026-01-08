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
                            <label for="">Correo Electrónico *</label>
                            <input type="email" id="correoEmpleado" placeholder="Correo Electrónico" name="Correo" class="w-full p-2 border rounded mb-2" required />
                            <div id="correo-error" class="text-red-500 text-sm hidden mb-2"></div>
                        </div>
                        <div class="relative w-full">
                            <label for="">Empleado</label>
                            <input type="text" id="autoEmpleadosTicket" placeholder="Nombre Empleado" autocomplete="off" class="autoEmpleados w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" class="EmpleadoID" name="EmpleadoID" id="EmpleadoID">
                            <div id="suggestions" class="suggestions absolute top-full left-0 w-full bg-white border border-gray-300 rounded shadow hidden z-50"></div>
                        </div>
                        <div>
                            <label for="">Número Telefónico *</label>
                            <input type="number" id="numeroTelefono" placeholder="Número Telefónico" name="Numero" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled />
                        </div>
                        <div>
                            <label for="">Código AnyDesk</label>
                            <input type="number" placeholder="Código AnyDesk" name="CodeAnyDesk" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled />
                        </div>
                        <div>
                            <label for="">Descripción *</label>
                            <textarea placeholder="Descripción" name="Descripcion" class="w-full p-2 border rounded bg-gray-100" disabled></textarea>
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
                            <label for="">Correo Electrónico *</label>
                            <input type="email" id="correoEmpleadoSolicitud" placeholder="Correo Electrónico" name="Correo" class="w-full p-2 border rounded mb-2" required />
                            <div id="correo-solicitud-error" class="text-red-500 text-sm hidden mb-2"></div>
                        </div>
                        <div class="relative w-full">
                            <label for="">Empleado</label>
                            <input type="text" id="autoEmpleadosSolicitud" placeholder="Nombre Empleado" autocomplete="off" class="autoEmpleados w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" class="EmpleadoID" name="EmpleadoID" id="EmpleadoIDSolicitud">
                            <div id="suggestionsEmpleados" class="suggestions absolute top-full left-0 w-full bg-white border border-gray-300 rounded shadow hidden z-50"></div>
                        </div>
                        <div>
                            <label for="">Gerencia</label>
                            <input type="text" placeholder="Gerencia" name="NombreGerencia" id="NombreGerencia" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="GerenciaID" id="GerenciaID">
                        </div>
                        <div>
                            <label for="">Obra</label>
                            <input type="text" placeholder="Obra" name="NombreObra" id="NombreObra" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="ObraID" id="ObraID">
                        </div>
                        <select name="Motivo" id="Motivo">
                            <option value="">Selecciona un motivo</option>
                            <option value="Nuevo Ingreso">Nuevo Ingreso</option>
                            <option value="Reemplazo por fallo o descompostura">Reemplazo por fallo o descompostura</option>
                            <option value="Renovación">Renovación</option>
                        </select>
                        <textarea placeholder="Describe Motivo" name="DescripcionMotivo" id="DescripcionMotivo" class="w-full p-2 border rounded mb-2"></textarea>
                        <div>
                            <label for="">Puesto</label>
                            <input type="text" placeholder="Puesto" id="NombrePuesto" name="NombrePuesto" class="w-full p-2 border rounded mb-2 bg-gray-100" disabled>
                            <input type="hidden" name="PuestoID" id="PuestoID">
                        </div>
                        <div class="relative w-full">
                            <input type="text" id="SupervisorNombre" placeholder="Supervisor" autocomplete="off" class="autoSupervisor w-full p-2 border rounded mb-2">
                            <input type="hidden" name="SupervisorID" id="SupervisorID" class="SupervisorID">
                            <div id="suggestionsSupervisor" class="suggestionsSupervisor absolute top-full left-0 w-full bg-white border border-gray-300 rounded shadow hidden z-50"></div>
                        </div>
                        <select name="Proyecto" style="width:100%" id="Proyecto" class="cursor-pointer w-full p-2 border rounded mb-2 text-black js-example-basic-single">
                        </select>
                        <textarea name="Requerimientos" id="Requerimientos" placeholder="Requerimientos" class="w-full p-2 border rounded mb-2"></textarea>
                        <button type="submit" class="w-20 h-10 bg-red-500 text-white rounded-md hover:scale-105 transition-all duration-300">Enviar</button>
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
        $(document).ready(function() {
            $('#Proyecto').select2({
                placeholder: "Selecciona Ubicación",
                allowClear: true
            });

            $.ajax({
                url: "/getTypes",
                method: "GET",
                success: function(data) {
                    $('#Proyecto').empty();

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

                        $('#Proyecto').append($optgroup);
                    });

                    $('#Proyecto').val(null).trigger('change');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Ticket Enviado Exitosamente! 🎉',
            html: `
                <div style="text-align: center; padding: 10px;">
                    <p style="font-size: 16px; margin-bottom: 10px; color: #333;">
                        <strong>¡Gracias por contactarnos!</strong>
                    </p>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        Hemos recibido tu solicitud y nuestro equipo de soporte técnico la revisará pronto.
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

            const resetForm = (form) => {
                const inputs = form.querySelectorAll("input, textarea, select");
                inputs.forEach(input => {
                    input.value = "";
                });
            }

            const title = document.getElementById("title");

            select.addEventListener("change", function() {
                const value = this.value;

                ticket.classList.add("hidden");
                solicitud.classList.add("hidden");

                resetForm(ticket);
                resetForm(solicitud);

                if (value === "Ticket") {
                    ticket.classList.remove("hidden");
                    title.textContent = "Crear Nuevo Ticket";
                } else if (value === "Solicitud") {
                    solicitud.classList.remove("hidden");
                    title.textContent = "Crear Nueva Solicitud";
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
            const $input = $(".autoEmpleados");
            const $suggestions = $(".suggestions");

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
                            const $clickedInput = $(this).closest('.relative').find('.autoEmpleados');
                            $clickedInput.val(nombre);
                            
                            let type = $('#type').val();
                            
                            // Asignar el ID según el formulario activo
                            if (type === "Ticket") {
                                $('#EmpleadoID').val(id);
                            } else if (type === "Solicitud") {
                                $('#EmpleadoIDSolicitud').val(id);
                            } else {
                                $(".EmpleadoID").val(id);
                            }

                            $suggestions.empty().addClass("hidden");

                            $.ajax({
                                url: "/getEmpleadoInfo",
                                method: "GET",
                                data: {
                                    EmpleadoID: id,
                                    type: type
                                },
                                success: function(data) {
                                    if (type === "Ticket") {
                                        $("input[name='Correo']").val(data.correo);
                                        $("input[name='Numero']").val(data.telefono);
                                    } else if (type === "Solicitud") {
                                        $("input[name='GerenciaID']").val(data.GerenciaID);
                                        $("input[name='NombreGerencia']").val(data.NombreGerencia);
                                        $("input[name='PuestoID']").val(data.PuestoID);
                                        $("input[name='NombrePuesto']").val(data.NombrePuesto);
                                        $("input[name='ObraID']").val(data.ObraID);
                                        $("input[name='NombreObra']").val(data.NombreObra);
                                    }
                                }
                            });

                        });
                    }
                });
            });

            $(document).on("click", function(e) {
                if (!$(e.target).closest(".autoEmpleados, .suggestions").length) {
                    $suggestions.empty().addClass("hidden");
                }
            });
        });
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
        // Script para validar correo y llenar datos automáticamente
        $(document).ready(function() {
            let correoTimeout;
            
            // Función para deshabilitar todos los campos excepto el correo
            function deshabilitarCampos() {
                $('#autoEmpleadosTicket').prop('disabled', true).addClass('bg-gray-100');
                $('#numeroTelefono').prop('disabled', true).prop('required', false).addClass('bg-gray-100');
                $('input[name="CodeAnyDesk"]').prop('disabled', true).prop('required', false).addClass('bg-gray-100');
                $('textarea[name="Descripcion"]').prop('disabled', true).prop('required', false).addClass('bg-gray-100');
                $('#fileInput').prop('disabled', true);
                $('#btnEnviar').prop('disabled', true).removeClass('bg-red-500 hover:scale-105').addClass('bg-gray-400 cursor-not-allowed');
                $('#dropzone').addClass('bg-gray-100 opacity-50').removeClass('hover:bg-gray-100');
            }
            
            // Función para habilitar solo campos específicos
            function habilitarCamposEspecificos() {
                // Mantener empleado deshabilitado pero visible
                $('#autoEmpleadosTicket').prop('disabled', true).addClass('bg-gray-100');
                
                // Habilitar solo campos específicos y hacerlos requeridos
                $('#numeroTelefono').prop('disabled', false).prop('required', true).removeClass('bg-gray-100');
                $('input[name="CodeAnyDesk"]').prop('disabled', false).removeClass('bg-gray-100');
                $('textarea[name="Descripcion"]').prop('disabled', false).prop('required', true).removeClass('bg-gray-100');
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
                    buscarEmpleadoPorCorreo(correo);
                }, 500);
            });
            
            function buscarEmpleadoPorCorreo(correo) {
                const $errorDiv = $('#correo-error');
                const $empleadoInput = $('#autoEmpleadosTicket');
                const $numeroInput = $('#numeroTelefono');
                const $empleadoIDInput = $('#EmpleadoID');
                
                // Mostrar indicador de carga
                $empleadoInput.val('Buscando...').addClass('border-blue-500');
                $numeroInput.val('Buscando...').addClass('border-blue-500');
                $errorDiv.addClass('hidden').text('');
                
                $.ajax({
                    url: '/buscarEmpleadoPorCorreo',
                    method: 'GET',
                    data: { correo: correo },
                    success: function(data) {
                        // Empleado encontrado - habilitar campos específicos
                        habilitarCamposEspecificos();
                        $empleadoInput.val(data.NombreEmpleado)
                            .removeClass('border-blue-500 border-red-500')
                            .addClass('border-green-500');
                        $numeroInput.val(data.NumTelefono)
                            .removeClass('border-blue-500 border-red-500')
                            .addClass('border-green-500');
                        $empleadoIDInput.val(data.EmpleadoID);
                        $errorDiv.addClass('hidden').text('');
                    },
                    error: function(xhr) {
                        // Error en la búsqueda - deshabilitar campos
                        deshabilitarCampos();
                        if (xhr.status === 404) {
                            $empleadoInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $numeroInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $empleadoIDInput.val('');
                            $errorDiv.removeClass('hidden').text(xhr.responseJSON.error || 'No se encontró correo, contacta a soporte');
                        } else {
                            $empleadoInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $numeroInput.val('')
                                .removeClass('border-blue-500 border-green-500')
                                .addClass('border-red-500');
                            $empleadoIDInput.val('');
                            $errorDiv.removeClass('hidden').text('Error al buscar empleado. Intenta de nuevo.');
                        }
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
            $('input[name="CodeAnyDesk"]').on('input', function() {
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
            $('textarea[name="Descripcion"]').on('input', function() {
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
                const numero = $('#numeroTelefono').val().replace(/\D/g, '');
                const anyDesk = $('input[name="CodeAnyDesk"]').val().trim();
                const descripcion = $('textarea[name="Descripcion"]').val().trim();
                const correo = $('#correoEmpleado').val().trim();
                
                let errores = [];
                
                // Validar que el correo esté validado
                if (!correo || !$('#EmpleadoID').val()) {
                    errores.push('Debe validar un correo electrónico válido');
                }
                
                // Validar número telefónico
                if (numero.length !== 10) {
                    errores.push('El número telefónico debe tener exactamente 10 dígitos');
                }
                
                // Validar descripción
                if (!descripcion) {
                    errores.push('La descripción es requerida');
                }
                
                if (errores.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        html: 'Por favor corrige los siguientes errores:<br><br>• ' + errores.join('<br>• ')
                    });
                    return false;
                }
            });
        });
    </script>
    <script>
        // Script para validar correo y llenar datos automáticamente en formulario de Solicitud
        $(document).ready(function() {
            let correoSolicitudTimeout;
            
            // Función para deshabilitar campos cuando no hay correo válido
            function deshabilitarCamposSolicitud() {
                $('#autoEmpleadosSolicitud').prop('disabled', true).addClass('bg-gray-100');
                $('#NombreGerencia').prop('disabled', true).addClass('bg-gray-100');
                $('#NombreObra').prop('disabled', true).addClass('bg-gray-100');
                $('#NombrePuesto').prop('disabled', true).addClass('bg-gray-100');
            }
            
            // Función para habilitar campos cuando el correo es válido
            function habilitarCamposSolicitud() {
                // Mantener empleado deshabilitado pero visible
                $('#autoEmpleadosSolicitud').prop('disabled', true).addClass('bg-gray-100');
                
                // Habilitar campos de Gerencia, Obra y Puesto (solo lectura, ya están llenos)
                $('#NombreGerencia').prop('disabled', true).removeClass('bg-gray-100').addClass('bg-green-50');
                $('#NombreObra').prop('disabled', true).removeClass('bg-gray-100').addClass('bg-green-50');
                $('#NombrePuesto').prop('disabled', true).removeClass('bg-gray-100').addClass('bg-green-50');
            }
            
            // Deshabilitar campos inicialmente
            deshabilitarCamposSolicitud();
            
            $('#correoEmpleadoSolicitud').on('input', function() {
                const correo = $(this).val().trim();
                const $errorDiv = $('#correo-solicitud-error');
                const $empleadoInput = $('#autoEmpleadosSolicitud');
                const $gerenciaInput = $('#NombreGerencia');
                const $obraInput = $('#NombreObra');
                const $puestoInput = $('#NombrePuesto');
                const $empleadoIDInput = $('#EmpleadoIDSolicitud');
                const $gerenciaIDInput = $('#GerenciaID');
                const $obraIDInput = $('#ObraID');
                const $puestoIDInput = $('#PuestoID');
                
                // Limpiar timeout anterior
                clearTimeout(correoSolicitudTimeout);
                
                // Deshabilitar campos si el correo está vacío
                if (correo === '') {
                    deshabilitarCamposSolicitud();
                    $empleadoInput.val('').removeClass('border-green-500').addClass('border-gray-300');
                    $gerenciaInput.val('').removeClass('border-green-500 border-blue-500').addClass('border-gray-300');
                    $obraInput.val('').removeClass('border-green-500 border-blue-500').addClass('border-gray-300');
                    $puestoInput.val('').removeClass('border-green-500 border-blue-500').addClass('border-gray-300');
                    $empleadoIDInput.val('');
                    $gerenciaIDInput.val('');
                    $obraIDInput.val('');
                    $puestoIDInput.val('');
                    $errorDiv.addClass('hidden').text('');
                    return;
                }
                
                // Validar formato de correo básico
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(correo)) {
                    deshabilitarCamposSolicitud();
                    $errorDiv.removeClass('hidden').text('Por favor ingresa un correo válido');
                    $empleadoInput.val('').removeClass('border-green-500').addClass('border-red-500');
                    $gerenciaInput.val('').removeClass('border-green-500 border-blue-500').addClass('border-red-500');
                    $obraInput.val('').removeClass('border-green-500 border-blue-500').addClass('border-red-500');
                    $puestoInput.val('').removeClass('border-green-500 border-blue-500').addClass('border-red-500');
                    $empleadoIDInput.val('');
                    $gerenciaIDInput.val('');
                    $obraIDInput.val('');
                    $puestoIDInput.val('');
                    return;
                }
                
                // Esperar 500ms después de que el usuario deje de escribir
                correoSolicitudTimeout = setTimeout(function() {
                    buscarEmpleadoPorCorreoSolicitud(correo);
                }, 500);
            });
            
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
                        // Empleado encontrado - habilitar campos y llenar datos
                        habilitarCamposSolicitud();
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
                    },
                    error: function(xhr) {
                        // Error en la búsqueda - deshabilitar campos
                        deshabilitarCamposSolicitud();
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
                            $empleadoIDInput.val('');
                            $gerenciaIDInput.val('');
                            $obraIDInput.val('');
                            $puestoIDInput.val('');
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
                            $empleadoIDInput.val('');
                            $gerenciaIDInput.val('');
                            $obraIDInput.val('');
                            $puestoIDInput.val('');
                            $errorDiv.removeClass('hidden').text('Error al buscar empleado. Intenta de nuevo.');
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>