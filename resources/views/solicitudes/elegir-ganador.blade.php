<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>{{ ($soloLectura ?? false) ? 'Ganadores confirmados' : 'Elegir Ganador' }} - Solicitud #{{ $solicitud->SolicitudID }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet" />
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
                        ink: "#0a1628",
                    },
                    fontFamily: {
                        display: ["Space Grotesk", "Inter", "sans-serif"],
                        body: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .num { font-family: 'Space Grotesk', 'Inter', sans-serif; font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }

        /* Tarjeta de cotización: el JS solo cambia is-ganador / is-descartada */
        .propuesta-card { transition: border-color .15s, box-shadow .15s, transform .15s, opacity .15s; }
        .propuesta-card.is-elegible:hover { border-color: #0a1628; transform: translateY(-2px); box-shadow: 0 10px 24px -12px rgba(10, 22, 40, .35); }
        .dark .propuesta-card.is-elegible:hover { border-color: #e2e8f0; }
        .propuesta-card.is-ganador { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, .18); }
        .propuesta-card.is-ganador .cot-check { opacity: 1; transform: scale(1); }
        .propuesta-card.is-descartada { opacity: .5; }
        .cot-check { opacity: 0; transform: scale(.6); transition: opacity .15s, transform .2s cubic-bezier(.2, .9, .3, 1.3); }
        .propuesta-btn { transition: background-color .15s, color .15s; }
        .propuesta-card.is-ganador .propuesta-btn { background: #059669; color: #fff; border-color: #059669; }

        .prop-step.is-done .prop-step__dot { background: #10b981; border-color: #10b981; color: #0a1628; }
        .prop-step.is-done .prop-step__dot i { opacity: 1; }
        .prop-step.is-done .prop-step__estado { color: #6ee7b7; }
        .prop-head.is-done .prop-head__estado { background: #d1fae5; color: #065f46; }
        .dark .prop-head.is-done .prop-head__estado { background: rgba(16, 185, 129, .15); color: #6ee7b7; }
        .prop-head.is-done .prop-head__icon { color: #059669; }
        .prop-head.is-done .prop-head__ganador { color: inherit; }
        .prop-head.is-done .prop-next:not(:disabled) { background: #0a1628; border-color: #0a1628; color: #fff; }
        .dark .prop-head.is-done .prop-next:not(:disabled) { background: #f1f5f9; border-color: #f1f5f9; color: #0a1628; }
        .prop-step.is-active { background: rgba(248, 250, 252, .08); }
        .prop-step.is-active .prop-step__dot { border-color: #f8fafc; }

        @media (prefers-reduced-motion: reduce) {
            .propuesta-card, .cot-check, .propuesta-btn { transition: none; }
            .propuesta-card.is-elegible:hover { transform: none; }
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100">
    @php
    $soloLectura = $soloLectura ?? false;
    $seleccionadas = $seleccionadas ?? [];
    $propuestas = $productos ?? [];
    $totalPropuestas = count($propuestas);
    $totalProductos = 0;
    $todosProveedores = collect();
    foreach ($propuestas as $prop) {
        $totalProductos += count($prop['productos'] ?? []);
        foreach ($prop['productos'] ?? [] as $prod) {
            foreach ($prod['cotizaciones'] ?? [] as $c) {
                $todosProveedores->push($c->Proveedor);
            }
        }
    }
    $numProveedores = $todosProveedores->unique()->count();
    $totalCot = fn($c) => (($c->Cantidad ?? 1) * ($c->Precio ?? 0)) + ($c->CostoEnvio ?? 0);
    @endphp

    <div class="lg:flex lg:min-h-screen">

        {{-- ═════ Marcador lateral ═════ --}}
        <aside class="lg:w-[340px] lg:shrink-0 bg-ink text-slate-200">
            <div class="lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto flex flex-col gap-6 p-5 md:p-6">
                <div>
                    <img src="{{ asset('img/LogoBlanco.png') }}" alt="Proser" class="h-8 w-auto mb-5">
                    <p class="text-sm text-slate-400">Solicitud #{{ $solicitud->SolicitudID }}</p>
                    <h1 class="font-display text-2xl md:text-[1.7rem] font-bold text-white leading-tight mt-1">
                        {{ $soloLectura ? 'Ganadores confirmados' : 'Elige los ganadores' }}
                    </h1>
                    <p class="text-sm text-slate-400 mt-2">
                        {{ $totalPropuestas }} {{ $totalPropuestas === 1 ? 'propuesta' : 'propuestas' }},
                        {{ $totalProductos }} {{ $totalProductos === 1 ? 'producto' : 'productos' }} y
                        {{ $numProveedores }} {{ $numProveedores === 1 ? 'proveedor' : 'proveedores' }}
                    </p>
                </div>

                @if($totalPropuestas > 0)
                <div>
                    <div class="flex items-baseline justify-between mb-2">
                        <span class="text-sm font-medium text-slate-300">{{ $soloLectura ? 'Elegidos' : 'Avance' }}</span>
                        <span id="progress-text" class="num text-lg font-bold text-white">0/{{ $totalPropuestas }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-50/10 overflow-hidden">
                        <div id="progress-fill" class="h-full rounded-full bg-emerald-400 transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <ol class="mt-4 space-y-1">
                        @foreach($propuestas as $prop)
                        <li>
                            <button type="button" onclick="mostrarPropuesta({{ $prop['numeroPropuesta'] }})"
                                class="prop-step w-full text-left flex items-center gap-3 rounded-lg px-2 py-2 -mx-2 hover:bg-slate-50/5 transition-colors" data-step-propuesta="{{ $prop['numeroPropuesta'] }}">
                                <span class="prop-step__dot w-6 h-6 shrink-0 rounded-full border-2 border-slate-500 flex items-center justify-center text-[10px] transition-colors">
                                    <i class="fas fa-check opacity-0"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm text-slate-200 truncate">Propuesta {{ $prop['numeroPropuesta'] }}</span>
                                    <span class="prop-step__ganador block text-xs text-slate-500 truncate"></span>
                                </span>
                                <span class="prop-step__estado text-xs text-slate-500">Pendiente</span>
                            </button>
                        </li>
                        @endforeach
                    </ol>
                </div>
                @endif

                <div class="space-y-3 text-sm border-t border-white/10 pt-5">
                    <div>
                        <p class="text-xs text-slate-500">Solicitante</p>
                        <p class="font-semibold text-white">{{ $solicitud->empleadoid->NombreEmpleado ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Motivo</p>
                        <p class="font-semibold text-white">{{ $solicitud->Motivo ?? 'N/A' }}</p>
                    </div>
                    <details class="group">
                        <summary class="flex items-center justify-between cursor-pointer list-none text-xs text-slate-500 hover:text-slate-300">
                            Descripción <i class="fas fa-chevron-down text-[10px] transition-transform group-open:rotate-180"></i>
                        </summary>
                        <p class="mt-1.5 text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
                    </details>
                </div>

                <div class="mt-auto space-y-3 border-t border-white/10 pt-5">
                    @if($soloLectura)
                    <div class="rounded-xl bg-emerald-400/10 border border-emerald-400/30 p-4">
                        <p class="text-sm font-semibold text-emerald-200 flex items-center gap-2"><i class="fas fa-lock text-xs"></i> Elección registrada</p>
                        <p class="text-xs text-emerald-100/80 mt-1 leading-relaxed">Es solo de consulta. La solicitud pasó a <strong>Administración</strong> para su aprobación final.</p>
                    </div>
                    @auth
                    <a href="{{ route('home') }}" class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-xl bg-slate-50/10 hover:bg-slate-50/15 text-white text-sm font-semibold transition-colors">
                        <i class="fas fa-home"></i> Ir al Dashboard
                    </a>
                    @else
                    <button type="button" onclick="window.close()" class="w-full h-11 rounded-xl bg-slate-50/10 hover:bg-slate-50/15 text-white text-sm font-semibold transition-colors">Cerrar</button>
                    @endauth
                    @elseif(!isset($error) || !$error)
                    @if($totalPropuestas > 0)
                    <p id="warning-box" class="flex items-start gap-2 text-xs text-amber-300 leading-relaxed">
                        <i class="fas fa-circle-info mt-0.5"></i>
                        Elige una cotización por cada propuesta para confirmar.
                    </p>
                    @endif
                    <button type="button" id="btn-confirmar" disabled
                        class="w-full h-12 rounded-xl text-sm font-semibold transition-colors inline-flex items-center justify-center gap-2 bg-slate-50/10 text-slate-500 cursor-not-allowed">
                        <i class="fas fa-check"></i> Confirmar ganadores
                    </button>
                    <div class="grid grid-cols-2 gap-2">
                        @if($totalPropuestas > 0)
                        <button type="button" onclick="abrirModalRecotizar()" class="h-10 rounded-xl border border-white/15 hover:bg-slate-50/5 text-slate-200 text-xs font-semibold transition-colors inline-flex items-center justify-center gap-1.5">
                            <i class="fas fa-redo-alt"></i> Re-cotizar
                        </button>
                        @endif
                        <button type="button" onclick="abrirModalCancelar()" class="h-10 rounded-xl border border-rose-400/30 hover:bg-rose-500/10 text-rose-300 text-xs font-semibold transition-colors inline-flex items-center justify-center gap-1.5 {{ $totalPropuestas > 0 ? '' : 'col-span-2' }}">
                            <i class="fas fa-ban"></i> Rechazar
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </aside>

        {{-- ═════ Comparación ═════ --}}
        <main class="flex-1 min-w-0 p-4 md:p-6 lg:p-8">
            @if(isset($error) && $error)
            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200">{{ $error }}</div>
            @else
            {{-- Una propuesta a la vez; cada producto es un carril con sus cotizaciones en fila.
                 Un solo ganador por propuesta: elegir en cualquier carril reemplaza la elección anterior. --}}
            <div class="max-w-[1400px]" id="cotizaciones-container">
                @foreach($propuestas as $propIndex => $propuesta)
                @php $np = $propuesta['numeroPropuesta']; @endphp
                <section id="propuesta-{{ $np }}" class="propuesta-group {{ $loop->first ? '' : 'hidden' }}" data-numero-propuesta="{{ $np }}" aria-labelledby="titulo-propuesta-{{ $np }}">
                    <header class="prop-head flex flex-wrap items-end gap-x-6 gap-y-3 mb-5" data-head-propuesta="{{ $np }}">
                        <div class="flex-1 min-w-[14rem]">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Propuesta {{ $loop->iteration }} de {{ $totalPropuestas }}</p>
                            <h2 id="titulo-propuesta-{{ $np }}" class="font-display text-2xl md:text-3xl font-bold text-slate-900 dark:text-white leading-tight">Propuesta {{ $np }}</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $soloLectura ? 'Cotización elegida para esta propuesta.' : 'Elige 1 cotización entre todos sus productos.' }}
                            </p>
                        </div>

                        <div class="prop-head__estado flex items-center gap-3 rounded-xl px-4 py-2.5 bg-slate-200/70 dark:bg-slate-800 min-w-[14rem]">
                            <i class="fas fa-trophy text-slate-400 prop-head__icon"></i>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-none">Ganador</p>
                                <p class="prop-head__ganador text-sm font-semibold text-slate-700 dark:text-slate-200 truncate mt-1">Sin elegir</p>
                            </div>
                        </div>

                        @if($totalPropuestas > 1)
                        <div class="flex gap-2">
                            <button type="button" onclick="moverPropuesta(-1)" {{ $loop->first ? 'disabled' : '' }}
                                class="h-11 w-11 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors" aria-label="Propuesta anterior">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <button type="button" onclick="moverPropuesta(1)" {{ $loop->last ? 'disabled' : '' }}
                                class="prop-next h-11 px-4 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-200/70 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors inline-flex items-center gap-2">
                                Siguiente <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        @endif
                    </header>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 overflow-hidden divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($propuesta['productos'] as $prodIndex => $prod)
                        @php
                        $claveProducto = 'np_' . $np . '_prod_' . $prod['numeroProducto'];
                        $cots = collect($prod['cotizaciones'] ?? []);
                        $minTotal = $cots->count() > 1 ? $cots->min($totalCot) : null;
                        $desde = $cots->count() ? $cots->min($totalCot) : 0;
                        @endphp
                        <div class="producto-group lg:grid lg:grid-cols-[230px_minmax(0,1fr)]" data-clave-producto="{{ $claveProducto }}">
                            {{-- Carril: producto --}}
                            <div class="px-5 py-4 bg-slate-100/70 dark:bg-slate-950/40 lg:border-r border-slate-200 dark:border-slate-800">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Producto {{ $loop->iteration }}</p>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 break-words">{{ $prod['descripcion'] }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                    {{ $cots->count() }} {{ $cots->count() === 1 ? 'cotización' : 'cotizaciones' }}
                                    @if($cots->count()) · desde <span class="num font-semibold text-slate-700 dark:text-slate-200">${{ number_format($desde, 2, '.', ',') }}</span> @endif
                                </p>
                            </div>

                            {{-- Carril: cotizaciones en fila --}}
                            <div class="flex gap-3 overflow-x-auto snap-x p-4">
                                @foreach($prod['cotizaciones'] as $cotizacion)
                                @php
                                $cantidad = $cotizacion->Cantidad ?? 1;
                                $precioUnitario = $cotizacion->Precio ?? 0;
                                $costoEnvio = $cotizacion->CostoEnvio ?? 0;
                                $total = $totalCot($cotizacion);
                                $esMasBajo = $minTotal !== null && $total == $minTotal;
                                @endphp
                                <div class="propuesta-card relative snap-start shrink-0 w-[250px] flex flex-col rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 p-3.5 {{ $soloLectura ? 'cursor-default select-none' : 'is-elegible cursor-pointer' }}"
                                    data-numero-propuesta="{{ $np }}"
                                    data-cotizacion-id="{{ $cotizacion->CotizacionID }}"
                                    data-resumen="{{ $cotizacion->Proveedor }} · ${{ number_format($total, 2, '.', ',') }}"
                                    @unless($soloLectura) role="button" tabindex="0" aria-label="Elegir {{ $cotizacion->Proveedor }}, {{ $prod['descripcion'] }}, para la propuesta {{ $np }}" @endunless>

                                    <span class="cot-check absolute -top-2 -right-2 w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] shadow-md" aria-hidden="true">
                                        <i class="fas fa-trophy"></i>
                                    </span>

                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate" title="{{ $cotizacion->Proveedor }}">{{ $cotizacion->Proveedor }}</p>
                                        @if($esMasBajo)
                                        <span class="shrink-0 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-400/10 px-1.5 py-0.5 rounded-full">Más bajo</span>
                                        @endif
                                    </div>

                                    <p class="num text-2xl font-bold text-slate-900 dark:text-white mt-1.5 leading-none">${{ number_format($total, 2, '.', ',') }}</p>
                                    <p class="num text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                        ${{ number_format($precioUnitario, 2, '.', ',') }} × {{ $cantidad }}
                                        @if($costoEnvio > 0) + ${{ number_format($costoEnvio, 2, '.', ',') }} envío @else · sin envío @endif
                                    </p>

                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-snug mt-2.5 line-clamp-2 break-words" title="{{ $cotizacion->Descripcion ?: ($prod['descripcion'] ?? '') }}">
                                        {{ $cotizacion->Descripcion ?: ($prod['descripcion'] ?? 'Sin descripción') }}
                                    </p>

                                    <div class="mt-auto pt-3 flex items-center justify-between gap-2">
                                        <span class="min-w-0 flex gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                            @if(!empty($cotizacion->NumeroParte))
                                            <span class="truncate" title="{{ $cotizacion->NumeroParte }}"><i class="fas fa-hashtag mr-0.5"></i>{{ $cotizacion->NumeroParte }}</span>
                                            @endif
                                            @if(!empty($cotizacion->TiempoEntrega))
                                            <span class="shrink-0"><i class="fas fa-clock mr-0.5"></i>{{ $cotizacion->TiempoEntrega }} d</span>
                                            @endif
                                        </span>
                                        <span class="propuesta-btn pointer-events-none shrink-0 h-8 px-3 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold inline-flex items-center">
                                            <span class="btn-label">Elegir</span>
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>
                @endforeach
            </div>
            @endif
        </main>
    </div>

    @if(!$soloLectura && (!isset($error) || !$error))
    <!-- Modal Solicitar re-cotización -->
    <div id="modal-recotizar" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-recotizar-title" role="dialog">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="cerrarModalRecotizar()"></div>
            <div class="relative bg-gray-50 dark:bg-slate-800 rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-600">
                <h3 id="modal-recotizar-title" class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2 flex items-center gap-2">
                    <i class="fas fa-redo-alt text-amber-500"></i>
                    Solicitar re-cotización
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Indica qué propuesta(s) están mal o deben recotizarse y el motivo. TI verá esta información en el listado.</p>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Propuestas a recotizar</label>
                    <div id="recotizar-checkboxes" class="space-y-2">
                        @foreach($propuestas as $prop)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="recotizar_prop" value="{{ $prop['numeroPropuesta'] }}" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-sm text-slate-800 dark:text-slate-200">Propuesta {{ $prop['numeroPropuesta'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Motivo (obligatorio)</label>
                    <textarea id="recotizar-motivo" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-700 text-slate-900 dark:text-slate-100 text-sm" placeholder="Ej.: Precio muy alto en propuesta 2, solicito nueva cotización..."></textarea>
                    <p id="recotizar-motivo-error" class="text-red-500 text-xs mt-1 hidden">Escribe el motivo de la re-cotización.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="cerrarModalRecotizar()" class="px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 text-sm font-semibold hover:bg-slate-300 dark:hover:bg-slate-500">Cerrar</button>
                    <button type="button" onclick="enviarSolicitudRecotizacion()" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Enviar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Rechazar solicitud -->
    <div id="modal-cancelar" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-cancelar-title" role="dialog">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="cerrarModalCancelar()"></div>
            <div class="relative bg-slate-50 dark:bg-slate-800 rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-600">
                <h3 id="modal-cancelar-title" class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2 flex items-center gap-2">
                    <i class="fas fa-ban text-rose-600"></i>
                    Rechazar solicitud #{{ $solicitud->SolicitudID }}
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">La solicitud se detiene por completo y ya no se podrá elegir ganador. Se avisará por correo a quienes ya la aprobaron.</p>
                <div class="mb-5">
                    <label for="cancelar-motivo" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Motivo de rechazo (obligatorio)</label>
                    <textarea id="cancelar-motivo" rows="4" maxlength="1000" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-slate-100 text-sm" placeholder="Describe por qué se rechaza esta solicitud..."></textarea>
                    <p id="cancelar-motivo-error" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="cerrarModalCancelar()" class="px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 text-sm font-semibold hover:bg-slate-300 dark:hover:bg-slate-500">Cerrar</button>
                    <button type="button" id="btn-confirmar-cancelacion" onclick="enviarCancelacion()" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold">
                        <i class="fas fa-ban mr-1"></i> Confirmar rechazo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        window.ELECTOR_TOKEN = @json($token ?? '');
        window.ELECTOR_SOLICITUD_ID = {{ (int)($solicitud->SolicitudID ?? 0) }};
        window.ELECTOR_TOTAL = {{ $totalPropuestas ?? 0 }};
        window.ELECTOR_CSRF = @json(csrf_token());
        window.ELECTOR_READONLY = @json($soloLectura);

        // numeroPropuesta -> cotizacionId (precargado cuando la elección ya fue hecha)
        const selecciones = Object.assign({}, @json((object)$seleccionadas));

        function abrirModalCancelar() {
            document.getElementById('modal-cancelar').classList.remove('hidden');
            document.getElementById('cancelar-motivo').value = '';
            document.getElementById('cancelar-motivo-error').classList.add('hidden');
        }

        function cerrarModalCancelar() {
            document.getElementById('modal-cancelar').classList.add('hidden');
        }

        function enviarCancelacion() {
            var motivo = (document.getElementById('cancelar-motivo').value || '').trim();
            var errEl = document.getElementById('cancelar-motivo-error');
            if (motivo.length < 10) {
                errEl.textContent = 'El motivo debe tener al menos 10 caracteres.';
                errEl.classList.remove('hidden');
                return;
            }
            errEl.classList.add('hidden');

            var btn = document.getElementById('btn-confirmar-cancelacion');
            btn.disabled = true;

            fetch('/solicitudes/' + window.ELECTOR_SOLICITUD_ID + '/rechazar-gerente', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.ELECTOR_CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ motivo: motivo, token: window.ELECTOR_TOKEN })
            })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(result) {
                btn.disabled = false;
                if (!result.ok) {
                    var msg = (result.data && result.data.errors && result.data.errors.motivo && result.data.errors.motivo[0])
                        || (result.data && result.data.message)
                        || 'No se pudo rechazar la solicitud.';
                    errEl.textContent = msg;
                    errEl.classList.remove('hidden');
                    return;
                }
                cerrarModalCancelar();
                Swal.fire({
                    title: 'Solicitud rechazada',
                    text: result.data.message,
                    icon: 'success',
                    confirmButtonColor: '#0F766E'
                }).then(function() {
                    if (result.data.redirect) window.location.href = result.data.redirect;
                    else window.location.reload();
                });
            })
            .catch(function(e) {
                console.error(e);
                btn.disabled = false;
                errEl.textContent = 'Error al rechazar la solicitud.';
                errEl.classList.remove('hidden');
            });
        }

        function abrirModalRecotizar() {
            document.getElementById('modal-recotizar').classList.remove('hidden');
            document.querySelectorAll('#recotizar-checkboxes input').forEach(function(cb) { cb.checked = false; });
            document.getElementById('recotizar-motivo').value = '';
            document.getElementById('recotizar-motivo-error').classList.add('hidden');
        }

        function cerrarModalRecotizar() {
            document.getElementById('modal-recotizar').classList.add('hidden');
        }

        function enviarSolicitudRecotizacion() {
            var propuestas = [];
            document.querySelectorAll('#recotizar-checkboxes input:checked').forEach(function(cb) {
                propuestas.push(parseInt(cb.value, 10));
            });
            var motivo = (document.getElementById('recotizar-motivo').value || '').trim();
            var errEl = document.getElementById('recotizar-motivo-error');
            if (propuestas.length === 0) {
                errEl.textContent = 'Selecciona al menos una propuesta.';
                errEl.classList.remove('hidden');
                return;
            }
            if (!motivo) {
                errEl.textContent = 'Escribe el motivo de la re-cotización.';
                errEl.classList.remove('hidden');
                return;
            }
            errEl.classList.add('hidden');

            var body = { propuestas: propuestas, motivo: motivo };
            if (window.ELECTOR_TOKEN) body.token = window.ELECTOR_TOKEN;

            fetch('/solicitudes/' + window.ELECTOR_SOLICITUD_ID + '/solicitar-recotizacion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.ELECTOR_CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(result) {
                cerrarModalRecotizar();
                if (!result.ok) {
                    Swal.fire({ title: 'Error', text: (result.data && result.data.message) || 'No se pudo enviar la solicitud.', icon: 'error' });
                    return;
                }
                Swal.fire({
                    title: 'Enviado',
                    text: (result.data && result.data.message) || 'TI verá qué propuestas debe recotizar.',
                    icon: 'success',
                    confirmButtonColor: '#0F766E'
                }).then(function() {
                    if (result.data && result.data.redirect) window.location.href = result.data.redirect;
                    else window.location.reload();
                });
            })
            .catch(function(e) {
                console.error(e);
                cerrarModalRecotizar();
                Swal.fire({ title: 'Error', text: 'Error al enviar la solicitud de re-cotización.', icon: 'error' });
            });
        }

        function actualizarUI() {
            const total = window.ELECTOR_TOTAL || 0;
            const n = Object.keys(selecciones).length;
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            if (progressFill) progressFill.style.width = total ? (100 * n / total) + '%' : '0%';
            if (progressText) progressText.textContent = n + '/' + total;

            document.querySelectorAll('.propuesta-card').forEach(function(card) {
                const numPropuesta = parseInt(card.dataset.numeroPropuesta, 10);
                const cid = parseInt(card.dataset.cotizacionId, 10);
                const esGanador = selecciones[numPropuesta] === cid;
                const label = card.querySelector('.btn-label');
                card.classList.toggle('is-ganador', esGanador);
                card.classList.toggle('is-descartada', !esGanador && window.ELECTOR_READONLY);
                card.setAttribute('aria-pressed', esGanador ? 'true' : 'false');
                if (label) label.textContent = esGanador ? 'Ganador' : (window.ELECTOR_READONLY ? 'No seleccionada' : 'Elegir');
            });

            // Estado por propuesta: lista del marcador y encabezado de cada propuesta
            // Resumen del ganador por propuesta: marcador lateral + encabezado de la propuesta
            function resumenGanador(num) {
                const card = document.querySelector('.propuesta-card[data-numero-propuesta="' + num + '"][data-cotizacion-id="' + selecciones[num] + '"]');
                return card ? card.dataset.resumen : '';
            }
            document.querySelectorAll('[data-step-propuesta]').forEach(function(el) {
                const num = parseInt(el.dataset.stepPropuesta, 10);
                const hecho = selecciones[num] !== undefined;
                el.classList.toggle('is-done', hecho);
                const estado = el.querySelector('.prop-step__estado');
                if (estado) estado.textContent = hecho ? 'Elegida' : 'Pendiente';
                const ganador = el.querySelector('.prop-step__ganador');
                if (ganador) ganador.textContent = hecho ? resumenGanador(num) : '';
            });
            document.querySelectorAll('[data-head-propuesta]').forEach(function(el) {
                const num = parseInt(el.dataset.headPropuesta, 10);
                const hecho = selecciones[num] !== undefined;
                el.classList.toggle('is-done', hecho);
                const ganador = el.querySelector('.prop-head__ganador');
                if (ganador) ganador.textContent = hecho ? resumenGanador(num) : 'Sin elegir';
            });

            const todos = total > 0 && n === total;
            const btnConfirm = document.getElementById('btn-confirmar');
            const warning = document.getElementById('warning-box');
            if (btnConfirm) {
                btnConfirm.disabled = !todos;
                btnConfirm.classList.toggle('bg-slate-50/10', !todos);
                btnConfirm.classList.toggle('text-slate-500', !todos);
                btnConfirm.classList.toggle('cursor-not-allowed', !todos);
                btnConfirm.classList.toggle('bg-emerald-500', todos);
                btnConfirm.classList.toggle('hover:bg-emerald-400', todos);
                btnConfirm.classList.toggle('text-ink', todos);
                btnConfirm.classList.toggle('cursor-pointer', todos);
                btnConfirm.onclick = todos ? confirmarGanadores : null;
            }
            if (warning) warning.style.display = todos ? 'none' : 'flex';
        }

        // Navegación: una propuesta visible a la vez
        const ordenPropuestas = Array.from(document.querySelectorAll('.propuesta-group')).map(function(s) { return parseInt(s.dataset.numeroPropuesta, 10); });
        let propuestaActual = ordenPropuestas[0];

        function mostrarPropuesta(num, sinScroll) {
            propuestaActual = num;
            document.querySelectorAll('.propuesta-group').forEach(function(s) {
                s.classList.toggle('hidden', parseInt(s.dataset.numeroPropuesta, 10) !== num);
            });
            document.querySelectorAll('[data-step-propuesta]').forEach(function(el) {
                const activa = parseInt(el.dataset.stepPropuesta, 10) === num;
                el.classList.toggle('is-active', activa);
                el.setAttribute('aria-current', activa ? 'step' : 'false');
            });
            const main = document.querySelector('main');
            if (main && !sinScroll) main.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }

        function moverPropuesta(delta) {
            const i = ordenPropuestas.indexOf(propuestaActual) + delta;
            if (i >= 0 && i < ordenPropuestas.length) mostrarPropuesta(ordenPropuestas[i]);
        }

        function elegirGanador(numeroPropuesta, cotizacionId) {
            if (window.ELECTOR_READONLY) return;
            selecciones[numeroPropuesta] = cotizacionId;
            actualizarUI();
        }

        function confirmarGanadores() {
            if (window.ELECTOR_READONLY) return;
            const total = window.ELECTOR_TOTAL || 0;
            if (Object.keys(selecciones).length !== total) return;
            const ganadores = Object.values(selecciones).map(Number);

            Swal.fire({
                title: '¿Confirmar ganadores?',
                text: 'Se guardarán los ganadores seleccionados para todos los productos.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0F766E'
            }).then(function(r) {
                if (!r.isConfirmed) return;
                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                const body = {
                    ganadores
                };
                const token = (window.ELECTOR_TOKEN || '').trim();
                if (token) body.token = token;
                fetch('/solicitudes/' + window.ELECTOR_SOLICITUD_ID + '/confirmar-ganadores', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.ELECTOR_CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    })
                    .then(function(res) {
                        return res.json().then(function(data) {
                            return {
                                ok: res.ok,
                                data
                            };
                        });
                    })
                    .then(function({
                        ok,
                        data
                    }) {
                        Swal.close();
                        if (!ok) {
                            Swal.fire({
                                title: 'Aviso',
                                text: (data && data.message) || 'Error al confirmar.',
                                icon: 'warning',
                                confirmButtonColor: '#0F766E'
                            }).then(function() {
                                if (data && data.redirect) window.location.href = data.redirect;
                            });
                            return;
                        }
                        Swal.fire({
                            title: '¡Listo!',
                            text: (data && data.message) || 'Ganadores confirmados.',
                            icon: 'success',
                            confirmButtonColor: '#0F766E'
                        }).then(function() {
                            const url = (data && data.redirect) || ('/elegir-ganador/' + (token || ''));
                            window.location.href = url;
                        });
                    })
                    .catch(function(e) {
                        Swal.close();
                        console.error(e);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al confirmar ganadores.',
                            icon: 'error'
                        });
                    });
            });
        }

        if (!window.ELECTOR_READONLY) {
            document.querySelectorAll('.propuesta-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const numPropuesta = parseInt(this.dataset.numeroPropuesta, 10);
                    const cid = parseInt(this.dataset.cotizacionId, 10);
                    elegirGanador(numPropuesta, cid);
                });
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
                });
            });
        }

        actualizarUI();
        if (ordenPropuestas.length) mostrarPropuesta(ordenPropuestas[0], true);
    </script>
</body>

