<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visto bueno · movimiento #{{ $solicitud->SolicitudMovimientoID }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; }
    </style>
</head>
@php
    $firmado = $paso->status === 'approved';
    $rechazado = $paso->status === 'rejected';
    $statusLabel = $firmado ? 'Aprobada' : ($rechazado ? 'Rechazada' : 'Pendiente');
    $statusClasses = $firmado
        ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
        : ($rechazado ? 'bg-red-100 text-red-800 border-red-200' : 'bg-amber-100 text-amber-800 border-amber-200');
    $puesto = $solicitud->solicitante->puestos->NombrePuesto ?? '';
@endphp
<body class="bg-slate-50 text-slate-800 min-h-screen">
    <div class="flex flex-col lg:flex-row w-full max-w-7xl mx-auto p-4 lg:p-8 gap-6 lg:gap-8">
        <main class="flex-1 w-full min-w-0">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-6 lg:p-8 border-b border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <img src="{{ asset('img/LogoAzul.png') }}" alt="Proser" class="h-10 w-auto mb-4">
                        <span class="text-xs font-semibold tracking-wider text-blue-600 uppercase mb-2 block">Movimiento en el ERP</span>
                        <div class="flex flex-wrap items-baseline gap-3">
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-900">Movimiento #{{ $solicitud->SolicitudMovimientoID }}</h1>
                            <span class="text-sm text-slate-500">{{ optional($solicitud->created_at)->translatedFormat('d M, Y') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Etapa actual: <span class="font-semibold text-slate-700">Visto bueno</span></p>
                    </div>
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium border {{ $statusClasses }}">
                        <span class="w-2 h-2 mr-2 rounded-full {{ $firmado ? 'bg-emerald-500' : ($rechazado ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="p-6 lg:p-8">
                    @if (session('error'))
                        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-icons-outlined text-slate-400 text-lg">person</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Solicitante</span>
                            </div>
                            <h3 class="font-semibold text-lg text-slate-900">{{ $solicitud->solicitante->NombreEmpleado ?? 'Sin solicitante' }}</h3>
                            @if ($puesto !== '')
                                <p class="text-sm text-slate-600">{{ $puesto }}</p>
                            @endif
                        </div>
                        <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-icons-outlined text-slate-400 text-lg">business_center</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Proyecto</span>
                            </div>
                            <h3 class="font-semibold text-lg text-slate-900">{{ $solicitud->obra->NombreObra ?? 'Sin proyecto' }}</h3>
                        </div>
                    </div>

                    <div class="bg-blue-50/50 rounded-2xl p-6 lg:p-8 border border-blue-100">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                            <span class="text-xs font-semibold uppercase tracking-wider text-blue-600">Mensaje</span>
                        </div>
                        <p class="text-slate-700 text-sm leading-relaxed">{{ $solicitud->Justificacion }}</p>

                        <div class="mt-8 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                                        <th class="py-2 pr-3">Cargo</th>
                                        <th class="py-2 pr-3">Código</th>
                                        <th class="py-2 pr-3">Descripción</th>
                                        <th class="py-2 pr-3">Cantidad</th>
                                        <th class="py-2">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($solicitud->lineas as $linea)
                                        <tr class="border-b border-slate-100">
                                            <td class="py-2 pr-3">{{ $linea->cargo->Clave ?? '—' }}</td>
                                            <td class="py-2 pr-3">{{ $linea->CodigoInsumo }}</td>
                                            <td class="py-2 pr-3">{{ $linea->Descripcion }}</td>
                                            <td class="py-2 pr-3">{{ number_format((float) $linea->Cantidad, 2) }}</td>
                                            <td class="py-2">${{ number_format((float) $linea->Importe, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <aside class="w-full lg:w-96 flex-shrink-0">
            <div class="lg:sticky lg:top-8 space-y-6">
                <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 mb-3 block">Aprobador asignado (Visto bueno)</span>
                    <h3 class="font-semibold text-slate-900 text-sm">{{ $paso->approver->NombreEmpleado ?? 'Sin aprobador' }}</h3>
                    <p class="text-xs text-slate-500">Visto bueno</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    @if ($firmado)
                        <p class="text-sm text-emerald-800">Este visto bueno ya quedó firmado{{ $paso->decided_at ? ' el ' . $paso->decided_at->format('d/m/Y H:i') : '' }}.</p>
                    @elseif ($rechazado)
                        <p class="text-sm text-red-700">Este visto bueno fue rechazado{{ $paso->comment ? ': ' . $paso->comment : '' }}.</p>
                    @elseif (!$puedeFirmar)
                        <p class="text-sm text-amber-700">Esta liga ya no sirve. Pide que te envíen otra desde el tablero de movimientos.</p>
                    @else
                        <form method="POST" action="{{ route('movimientos-erp.public.decide', $liga->token) }}">
                            @csrf
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2" for="Texto">Comentario</label>
                            <textarea id="Texto" name="Texto" rows="4" required maxlength="2000" placeholder="Adelante con el movimiento" class="w-full rounded-xl border border-slate-300 bg-slate-50 text-sm p-3 mb-4"></textarea>
                            <button type="submit" name="decision" value="approved" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 px-6 rounded-xl flex justify-center items-center gap-2 cursor-pointer">
                                Firmar visto bueno
                                <span class="material-icons-outlined text-lg">check_circle</span>
                            </button>
                            <button type="submit" name="decision" value="rejected" class="w-full mt-3 bg-white border border-red-200 text-red-600 font-medium py-3 px-4 rounded-xl flex justify-center items-center gap-2 cursor-pointer">
                                <span class="material-icons-outlined text-lg">cancel</span>
                                Rechazar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
