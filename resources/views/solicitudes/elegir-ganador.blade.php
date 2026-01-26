<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Elegir Ganador - Solicitud #{{ $solicitud->SolicitudID }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#0F766E",
                        "primary-hover": "#115E59",
                        "secondary": "#3B82F6",
                        "danger": "#EF4444",
                        "background-light": "#F3F4F6",
                        "background-dark": "#111827",
                        "surface-light": "#FFFFFF",
                        "surface-dark": "#1F2937",
                        "border-light": "#E5E7EB",
                        "border-dark": "#374151",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                        body: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
</head>

<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-trophy text-amber-500"></i>
                            Elegir Ganador - Solicitud #{{ $solicitud->SolicitudID }}
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">Los 3 responsables ya firmaron. Revisa las propuestas y selecciona la cotización ganadora.</p>
                    </div>
                </div>
            </div>

            <!-- Información de la Solicitud -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    Información de la Solicitud
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Solicitante</label>
                        <p class="text-sm text-gray-900 font-medium">{{ $solicitud->empleadoid->NombreEmpleado ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500">Motivo</label>
                        <p class="text-sm text-gray-900">{{ $solicitud->Motivo ?? 'N/A' }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs font-medium text-gray-500">Descripción</label>
                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Cotizaciones -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar text-violet-500"></i>
                    Propuestas de Cotización
                </h2>
                <p class="text-sm text-gray-600 mb-4">Selecciona la propuesta ganadora haciendo clic en el botón "Elegir ganador".</p>
                
                <div class="space-y-4" id="cotizaciones-container">
                    @foreach($cotizaciones as $cotizacion)
                    <div class="p-4 rounded-xl border-2 transition {{ $cotizacion->Estatus === 'Seleccionada' ? 'bg-emerald-50 border-emerald-300' : 'bg-white border-gray-200 hover:border-sky-200' }}" 
                         data-cotizacion-id="{{ $cotizacion->CotizacionID }}">
                        <div class="grid grid-cols-4 gap-4 mb-3">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Proveedor</label>
                                <p class="text-sm font-semibold text-gray-900">{{ $cotizacion->Proveedor }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">NO. PARTE</label>
                                <p class="text-sm font-semibold text-gray-900">{{ $cotizacion->NumeroParte ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Precio</label>
                                <p class="text-sm font-semibold text-gray-900">${{ number_format($cotizacion->Precio, 2, '.', ',') }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Numero de Parte</label>
                                <p class="text-sm font-semibold text-gray-900">{{ $cotizacion->NumeroParte ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <label class="text-xs font-medium text-gray-500">Descripción</label>
                                <p class="text-sm text-gray-700">{{ $cotizacion->Descripcion }}</p>
                            </div>
                            @if($cotizacion->Estatus === 'Pendiente')
                            <button 
                                onclick="seleccionarCotizacion({{ $cotizacion->CotizacionID }}, '{{ $token }}')"
                                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                                <i class="fas fa-trophy mr-1"></i> Elegir ganador
                            </button>
                            @elseif($cotizacion->Estatus === 'Seleccionada')
                            <span class="px-4 py-2 bg-emerald-500 text-white text-sm font-semibold rounded-lg">
                                <i class="fas fa-check-circle mr-1"></i> Ganador seleccionado
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        async function seleccionarCotizacion(cotizacionId, token) {
            const ok = await Swal.fire({
                title: '¿Elegir esta propuesta como ganador?',
                text: 'La solicitud pasará a Aprobada y se procederá a la compra.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, elegir ganador',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0F766E'
            }).then(r => r.isConfirmed);
            
            if (!ok) return;
            
            Swal.fire({ 
                title: 'Guardando...', 
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });
            
            try {
                const requestBody = {
                    cotizacion_id: parseInt(cotizacionId)
                };
                
                // Solo agregar token si existe y no está vacío
                if (token && token.trim() !== '') {
                    requestBody.token = token.trim();
                }
                
                const res = await fetch('/solicitudes/{{ $solicitud->SolicitudID }}/seleccionar-cotizacion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });
                
                const responseData = await res.json().catch(() => ({}));
                Swal.close();
                
                if (!res.ok) {
                    // Manejar errores de validación (422) u otros errores
                    let errorMessage = 'Error al elegir ganador';
                    if (responseData.message) {
                        errorMessage = responseData.message;
                    } else if (responseData.errors) {
                        // Si hay errores de validación, mostrar el primero
                        const firstError = Object.values(responseData.errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                    
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error'
                    });
                    return;
                }
                
                if (responseData.success) {
                    await Swal.fire({
                        title: '¡Éxito!',
                        text: responseData.message || 'Ganador seleccionado. La solicitud está Aprobada y se procederá a la compra.',
                        icon: 'success',
                        confirmButtonColor: '#0F766E'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: responseData.message || 'Error al elegir ganador',
                        icon: 'error'
                    });
                }
            } catch (e) {
                Swal.close();
                console.error(e);
                Swal.fire({
                    title: 'Error',
                    text: 'Error al seleccionar la cotización',
                    icon: 'error'
                });
            }
        }
    </script>
</body>

</html>
