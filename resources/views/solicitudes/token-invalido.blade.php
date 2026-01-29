<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Firma Autorizada o Vencida - Revisión de Solicitud</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>

<body class="bg-[#F9FAFB] dark:bg-neutral-950 text-[#333] dark:text-neutral-200 min-h-screen flex items-center justify-center p-4 md:p-8">
    <div class="w-full max-w-4xl flex flex-col lg:flex-row gap-8 lg:gap-12 items-center lg:items-stretch">
        
        <!-- Columna izquierda: imagen -->
        <div class="flex-1 flex items-center justify-center w-full">
            <img src="{{ asset('img/invalid.png') }}" alt="Enlace no disponible" class="w-full max-w-[280px] sm:max-w-[320px] lg:max-w-[360px] h-auto object-contain" />
        </div>

        <!-- Columna derecha: panel de contenido -->
        <div class="flex-1 w-full max-w-xl">
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-[#333] dark:text-white mb-1">
                    Firma Autorizada
                </h1>
                <p class="text-[#555] dark:text-neutral-400 text-sm md:text-base">
                    Este enlace de aprobación ya no es válido.
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 shadow-sm overflow-hidden">
                
                <!-- Sección: Enlace de Aprobación Inválido -->
                <div class="p-6 md:p-8">
                    <h2 class="text-base font-bold text-[#333] dark:text-white mb-2">
                        Enlace de Aprobación Inválido
                    </h2>
                    <p class="text-sm text-[#555] dark:text-neutral-400 mb-4">
                        Este enlace de revisión de solicitud ya no es válido. Esto puede deberse a que:
                    </p>
                    <ul class="space-y-3 text-sm text-[#555] dark:text-neutral-400">
                        <li class="flex items-start gap-2">
                            <span class="text-neutral-400 dark:text-neutral-500 mt-0.5">×</span>
                            <span>
                                <strong class="text-[#333] dark:text-neutral-200">La solicitud ya fue firmada.</strong><br />
                                <span class="text-[#555] dark:text-neutral-500">El enlace fue utilizado para aprobar o rechazar la solicitud.</span>
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-neutral-400 dark:text-neutral-500 mt-0.5">×</span>
                            <span>
                                <strong class="text-[#333] dark:text-neutral-200">El enlace fue revocado.</strong><br />
                                <span class="text-[#555] dark:text-neutral-500">La aprobación fue transferida a otra persona.</span>
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-neutral-400 dark:text-neutral-500 mt-0.5">×</span>
                            <span>
                                <strong class="text-[#333] dark:text-neutral-200">El enlace expiró.</strong><br />
                                <span class="text-[#555] dark:text-neutral-500">Ha pasado el tiempo límite para revisar esta solicitud.</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <hr class="border-neutral-100 dark:border-neutral-700" />

                <!-- Sección: Información del Token -->
                @if(isset($tokenInfo))
                <div class="px-6 md:px-8 py-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full border-2 border-neutral-300 dark:border-neutral-600 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold text-neutral-500 dark:text-neutral-400">i</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-[#333] dark:text-neutral-200 mb-2">Información del Token</p>
                            @if(isset($tokenInfo['razon']))
                            <p class="text-sm text-[#555] dark:text-neutral-400">Razón: {{ $tokenInfo['razon'] }}</p>
                            @endif
                            @if(isset($tokenInfo['fecha_usado']))
                            <p class="text-sm text-[#555] dark:text-neutral-400 mt-1">Fecha de uso: {{ $tokenInfo['fecha_usado'] }}</p>
                            @endif
                            @if(isset($tokenInfo['fecha_expiracion']))
                            <p class="text-sm text-[#555] dark:text-neutral-400 mt-0.5">Fecha de expiración: {{ $tokenInfo['fecha_expiracion'] }}</p>
                            @endif
                            @if(isset($tokenInfo['proveedor_ganador']))
                            <div class="mt-3 pt-3 border-t border-neutral-100 dark:border-neutral-700">
                                <p class="text-sm text-[#555] dark:text-neutral-400"><strong>{{ isset($tokenInfo['multiple_ganadores']) && $tokenInfo['multiple_ganadores'] ? 'Ganadores por producto:' : 'Proveedor:' }}</strong> {{ $tokenInfo['proveedor_ganador'] }}</p>
                                @if(isset($tokenInfo['precio_ganador']) && $tokenInfo['precio_ganador'] !== '')
                                <p class="text-sm text-[#555] dark:text-neutral-400"><strong>Precio:</strong> ${{ $tokenInfo['precio_ganador'] }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <hr class="border-neutral-100 dark:border-neutral-700" />
                @endif

                <!-- Sección: ¿Qué puedes hacer? -->
                <div class="px-6 md:px-8 py-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#E8EEF5] dark:bg-neutral-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-[#6B8FC4]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-[#333] dark:text-white mb-1">¿Qué puedes hacer?</h3>
                            <p class="text-sm text-[#4682B4] dark:text-blue-400">
                                Si necesitas revisar o aprobar esta solicitud, contacta con el administrador del sistema o solicita un nuevo enlace de aprobación.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botones al pie, alineados a la derecha -->
                <div class="px-6 md:px-8 pb-6 md:pb-8 flex flex-col sm:flex-row gap-3 justify-end">
                    <button type="button" onclick="window.close()" class="px-5 py-2.5 bg-white dark:bg-transparent border border-neutral-300 dark:border-neutral-600 text-[#333] dark:text-neutral-300 rounded-lg text-sm font-medium hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                        Cerrar
                    </button>
                    @auth
                    <a href="{{ route('home') }}" class="px-5 py-2.5 bg-[#2C3E50] hover:bg-[#34495e] text-white rounded-lg text-sm font-medium transition-colors inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11v2a1 1 0 01-1 1h-2m-6-1a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1m-6-1h6" /></svg>
                        Ir al Dashboard
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</body>

</html>
