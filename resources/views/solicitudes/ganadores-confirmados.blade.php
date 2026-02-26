<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Ganadores confirmados - Solicitud</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>

<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen flex items-center justify-center p-4 md:p-8">
    <div class="w-full max-w-xl">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 md:p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                    <i class="fas fa-check-circle text-3xl text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white mb-2">
                    Ganadores confirmados
                </h1>
                <p class="text-slate-600 dark:text-slate-300 mb-4">
                    Has elegido los ganadores correctamente. La aprobación de gerencia ha sido registrada.
                </p>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700/50 text-left">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Se ha enviado la solicitud a <strong>Administración</strong> para su aprobación final. El administrador recibirá un correo con el enlace para revisar y aprobar.
                    </p>
                </div>
            </div>
            <div class="px-6 md:px-8 pb-6 flex justify-center">
                @auth
                <a href="{{ route('home') }}" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2">
                    <i class="fas fa-home"></i>
                    Ir al Dashboard
                </a>
                @else
                <button type="button" onclick="window.close()" class="px-5 py-2.5 bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg text-sm font-medium hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                    Cerrar
                </button>
                @endauth
            </div>
        </div>
    </div>
</body>

</html>
