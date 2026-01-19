<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Revisión de Solicitud</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
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
                        "subtle-light": "#F9FAFB",
                        "subtle-dark": "#111827",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                        body: ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                        'xl': '1rem',
                        '2xl': '1.5rem',
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        'card': '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025)',
                    }
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

<body class="bg-background-light dark:bg-background-dark text-gray-800 dark:text-gray-100 h-screen overflow-hidden transition-colors duration-300">

    @php
    $stageLabels = [
    'supervisor' => 'Supervisor',
    'gerencia' => 'Gerencia',
    'administracion' => 'Administración',
    ];

    $stageLabel = $stageLabels[$step->stage ?? ''] ?? 'Aprobación';

    $status = $step->status ?? 'pending';

    $statusLabel = $status === 'approved' ? 'Aprobada'
    : ($status === 'rejected' ? 'Rechazada' : 'Pendiente');

    $statusClasses = $status === 'approved'
    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
    : ($status === 'rejected'
    ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-200 dark:border-red-800'
    : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-800');

    $pulseDot = $status === 'pending';

    $tokenUsed = !empty($tokenRow->used_at);
    $tokenRevoked = !empty($tokenRow->revoked_at);
    $tokenExpired = !empty($tokenRow->expires_at) && now()->greaterThan($tokenRow->expires_at);
    $tokenActive = !$tokenUsed && !$tokenRevoked && !$tokenExpired;

    $canDecide = ($canDecide ?? false) && $tokenActive;

    $blockedReason = (!$tokenActive)
    ? 'El enlace ya no es válido (usado, revocado o expirado).'
    : (!empty($waitingFor) ? $waitingFor : '');
    @endphp

    <div class="flex flex-col lg:flex-row h-full max-w-7xl mx-auto p-4 lg:p-8 gap-6 lg:gap-8">
        <main class="flex-1 min-h-0">
            <div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-card border border-border-light dark:border-border-dark overflow-hidden transition-colors duration-300 h-full flex flex-col">

                <div class="p-6 lg:p-8 border-b border-border-light dark:border-border-dark flex flex-col md:flex-row justify-between items-start md:items-center gap-4 flex-shrink-0">
                    <div>
                        <span class="text-xs font-semibold tracking-wider text-blue-600 dark:text-blue-400 uppercase mb-2 block">
                            {{ $solicitud->gerenciaid->NombreGerencia ?? 'Sin Gerencia' }}
                        </span>
                        <div class="flex items-baseline gap-3">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                Solicitud #{{ $solicitud->SolicitudID }}
                            </h1>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ optional($solicitud->created_at)->translatedFormat('d M, Y') ?? 'Sin Fecha' }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Etapa actual: <span class="font-semibold">{{ $stageLabel }}</span>
                        </div>
                    </div>

                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium border {{ $statusClasses }}">
                        <span class="w-2 h-2 mr-2 rounded-full {{ $status === 'approved' ? 'bg-emerald-500' : ($status === 'rejected' ? 'bg-red-500' : 'bg-amber-500') }} {{ $pulseDot ? 'animate-pulse' : '' }}"></span>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="overflow-y-auto min-h-0 pr-2">
                    <div class="p-6 lg:p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-subtle-light dark:bg-gray-800/50 rounded-xl p-5 border border-border-light dark:border-border-dark">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-icons-outlined text-gray-400 text-lg">person</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Solicitante</span>
                            </div>
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1">
                                {{ $solicitud->empleadoid->NombreEmpleado ?? 'Sin Solicitante' }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $solicitud->puestoid->NombrePuesto ?? 'Sin Puesto' }}
                            </p>
                        </div>

                        <div class="bg-subtle-light dark:bg-gray-800/50 rounded-xl p-5 border border-border-light dark:border-border-dark">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-icons-outlined text-gray-400 text-lg">business_center</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Proyecto / Obra</span>
                            </div>
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1">
                                {{ $solicitud->obraid->NombreObra ?? 'Sin Obra' }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $solicitud->Proyecto ?? 'Sin Proyecto' }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 lg:px-8 pb-8">
                        <div class="bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl p-6 lg:p-8 border border-blue-100 dark:border-blue-900/30">
                            <div class="mb-8">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Motivo</span>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $solicitud->Motivo ?? 'Sin Motivo' }}
                                </h2>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-gray-900 dark:bg-gray-300 rounded-full"></span>
                                            <h4 class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Descripción</h4>
                                        </div>
                                        <span class="text-xs text-gray-400 font-medium">DETALLE</span>
                                    </div>

                                    <div class="overflow-y-auto overflow-x-hidden break-words bg-white dark:bg-gray-800 rounded-lg p-4 text-gray-600 dark:text-gray-300 text-sm leading-relaxed border border-gray-100 dark:border-gray-700 shadow-sm space-y-4 min-h-[170px]">
                                        <p>{{ $solicitud->DescripcionMotivo }}</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            <h4 class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Requerimientos</h4>
                                        </div>
                                        <span class="text-xs text-gray-400 font-medium">ESPECIFICACIÓN</span>
                                    </div>

                                    <div class="overflow-y-auto overflow-x-hidden break-words bg-white dark:bg-gray-800 rounded-lg p-4 text-gray-600 dark:text-gray-300 text-sm leading-relaxed border border-gray-100 dark:border-gray-700 shadow-sm min-h-[170px]">
                                        <p>{{ $solicitud->Requerimientos }}</p>
                                    </div>
                                </div>
                            </div>

                            @if(!empty($step->comment))
                            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                                    Comentario registrado ({{ $stageLabel }})
                                </div>
                                <div class="text-sm text-gray-700 dark:text-gray-200 break-words">
                                    {{ $step->comment }}
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                </div>
            </div>
        </main>

        <aside class="w-full lg:w-96 flex-shrink-0 h-full overflow-hidden">
            <div class="sticky top-8 space-y-6 max-h-[calc(100vh-4rem)] overflow-hidden">

                <div class="bg-emerald-50/50 dark:bg-surface-dark border border-emerald-100 dark:border-border-dark rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3 block">
                        Aprobador asignado ({{ $stageLabel }})
                    </span>
                    <div class="flex items-center gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
                                {{ $step->approverEmpleado->NombreEmpleado ?? 'Sin aprobador' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $stageLabel }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-card border border-border-light dark:border-border-dark p-6">
                    <div class="mb-6">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2" for="comment">
                            Comentarios ({{ $stageLabel }})
                        </label>
                        <textarea
                            class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm p-3 resize-none transition-colors"
                            id="comment"
                            name="comment"
                            placeholder="Observaciones de {{ strtolower($stageLabel) }}..."
                            rows="4"
                            {{ (!$canDecide) ? 'disabled' : '' }}></textarea>
                    </div>

                    @if(!$canDecide && $status === 'pending')
                    <div class="mb-4 text-sm text-amber-700 dark:text-amber-400">
                        {{ $blockedReason }}
                    </div>
                    @endif

                    @if($status === 'pending')
                    <div class="space-y-3">

                        <form method="POST" action="{{ route('solicitudes.public.decide', ['token' => $tokenRow->token]) }}" onsubmit="document.getElementById('comment_approved').value = document.getElementById('comment').value;">
                            @csrf
                            <input type="hidden" name="decision" value="approved">
                            <input type="hidden" name="comment" id="comment_approved">
                            <button type="submit" class="w-full group bg-primary hover:bg-primary-hover text-white font-semibold py-3.5 px-6 rounded-xl shadow-lg shadow-teal-700/20 transition-all duration-200 transform active:scale-[0.98] flex justify-center items-center gap-2" {{ (!$canDecide) ? 'disabled' : '' }}>
                                <span>Aprobar</span>
                                <span class="material-icons-outlined text-lg group-hover:translate-x-1 transition-transform">check_circle</span>
                            </button>
                        </form>

                        <div class="grid grid-cols-2 gap-3">

                            <form method="POST" action="{{ route('solicitudes.public.decide', ['token' => $tokenRow->token]) }}" onsubmit="document.getElementById('comment_rejected').value = document.getElementById('comment').value;">
                                @csrf
                                <input type="hidden" name="decision" value="rejected">
                                <input type="hidden" name="comment" id="comment_rejected">
                                <button type="submit" class="w-full bg-white dark:bg-transparent border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 font-medium py-3 px-4 rounded-xl transition-colors duration-200 flex justify-center items-center gap-2 text-sm" {{ (!$canDecide) ? 'disabled' : '' }}>
                                    <span class="material-icons-outlined text-lg">cancel</span>
                                    Rechazar
                                </button>
                            </form>

                            <p class="w-full cursor-pointer bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium py-3 px-4 rounded-xl transition-colors duration-200 flex justify-center items-center gap-2 text-sm">
                                Transferir
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Esta etapa ya fue resuelta: <span class="font-semibold">{{ $statusLabel }}</span>
                        @if($step->decided_at)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Fecha: {{ $step->decided_at->translatedFormat('d M, Y H:i') }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

            </div>
        </aside>
    </div>

    <div class="fixed top-0 left-0 w-full h-full -z-10 overflow-hidden pointer-events-none opacity-40 dark:opacity-10">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl -translate-x-1/2 translate-y-1/2"></div>
    </div>
</body>

</html>