<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Token Inválido - Revisión de Solicitud</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#0F766E",
                        "primary-hover": "#115E59",
                        "danger": "#EF4444",
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            
            <!-- Header con código de error -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-8 py-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-4xl"></i>
                    </div>
                </div>
                <h1 class="text-6xl font-bold text-white mb-2">401</h1>
                <h2 class="text-xl text-white/90 font-semibold">Firma Autorizada o Vencida</h2>
            </div>

            <!-- Contenido principal -->
            <div class="p-8">
                <div class="mb-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Enlace de Aprobación Inválido
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                Este enlace de revisión de solicitud ya no es válido. Esto puede deberse a que:
                            </p>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300 text-sm mb-4">
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-red-500 mt-1"></i>
                                    <span><strong>La solicitud ya fue firmada</strong> - El enlace fue utilizado para aprobar o rechazar la solicitud</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-red-500 mt-1"></i>
                                    <span><strong>El enlace fue revocado</strong> - La aprobación fue transferida a otra persona</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-red-500 mt-1"></i>
                                    <span><strong>El enlace expiró</strong> - Ha pasado el tiempo límite para revisar esta solicitud</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Información adicional -->
                @if(isset($tokenInfo))
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Información del Token</span>
                    </div>
                    @if(isset($tokenInfo['razon']))
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Razón:</strong> {{ $tokenInfo['razon'] }}
                    </p>
                    @endif
                    @if(isset($tokenInfo['fecha_usado']))
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <strong>Fecha de uso:</strong> {{ $tokenInfo['fecha_usado'] }}
                    </p>
                    @endif
                    @if(isset($tokenInfo['fecha_expiracion']))
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <strong>Fecha de expiración:</strong> {{ $tokenInfo['fecha_expiracion'] }}
                    </p>
                    @endif
                </div>
                @endif

                <!-- Mensaje de ayuda -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-lightbulb text-blue-500 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-1">¿Qué puedes hacer?</h4>
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                Si necesitas revisar o aprobar esta solicitud, contacta con el administrador del sistema o solicita un nuevo enlace de aprobación.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button onclick="window.close()" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i>
                        Cerrar
                    </button>
                    @auth
                    <a href="{{ route('home') }}" class="px-6 py-3 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-home"></i>
                        Ir al Dashboard
                    </a>
                    @endauth
                </div>
            </div>

        </div>

       
     
    </div>
</body>

</html>
