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

<body class="bg-slate-50 dark:bg-slate-900">
    @php
    $propuestas = $productos ?? [];
    $totalPropuestas = count($propuestas);
    $totalProductos = 0;
    $nombresProductos = [];
    $todosProveedores = collect();
    foreach ($propuestas as $prop) {
        $totalProductos += count($prop['productos'] ?? []);
        foreach ($prop['productos'] ?? [] as $prod) {
            $nombresProductos[] = $prod['descripcion'] ?: 'Producto';
            foreach ($prod['cotizaciones'] ?? [] as $c) {
                $todosProveedores->push($c->Proveedor);
            }
        }
    }
    $numProveedores = $todosProveedores->unique()->count();
    $nombresStr = implode(', ', $nombresProductos);
    @endphp
    <div class="min-h-screen py-4 md:py-6 lg:py-8 px-3 md:px-4 lg:px-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-900 dark:to-black rounded-2xl shadow-xl border border-slate-700 dark:border-slate-800 p-5 md:p-6 lg:p-8 mb-5 md:mb-6 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-48 h-48 bg-slate-700/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-slate-600/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white flex items-center gap-3 mb-3">
                        <i class="fas fa-trophy text-amber-400 text-2xl md:text-3xl"></i>
                        Elegir Ganador
                    </h1>
                    <p class="text-sm md:text-base text-slate-300 mb-4">
                        Solicitud #{{ $solicitud->SolicitudID }} • {{ count($propuestas) }} propuestas • {{ $totalProductos }} productos • {{ $numProveedores }} proveedores
                    </p>
                    @if($totalPropuestas > 0)
                    <div class="bg-slate-700/30 backdrop-blur-sm rounded-xl p-4 border border-slate-600/50">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex-1 h-3 bg-slate-600/50 rounded-full overflow-hidden">
                                <div id="progress-fill" class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <span id="progress-text" class="text-sm font-bold text-white whitespace-nowrap">0/{{ $totalPropuestas }}</span>
                        </div>
                        <p class="text-xs text-slate-300">Selecciona un ganador por cada propuesta</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Información de la Solicitud -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-5 md:p-6 mb-5 md:mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center">
                        <i class="fas fa-file-alt text-white text-base"></i>
                    </div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Información de la Solicitud</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1 block">Solicitante</label>
                        <p class="text-sm text-slate-900 dark:text-slate-100 font-semibold">{{ $solicitud->empleadoid->NombreEmpleado ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1 block">Motivo</label>
                        <p class="text-sm text-slate-900 dark:text-slate-100 font-semibold">{{ $solicitud->Motivo ?? 'N/A' }}</p>
                    </div>
                    <div class="md:col-span-2 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 border border-slate-200 dark:border-slate-700">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2 block">Descripción</label>
                        <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Cotizaciones por producto -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-5 md:p-6 mb-5 md:mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center">
                        <i class="fas fa-clipboard-check text-white text-base"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Comparación de Propuestas</h2>
                    </div>
                </div>

                @if(isset($error) && $error)
                <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200">{{ $error }}</div>
                @else
                <div class="space-y-5 md:space-y-6" id="cotizaciones-container">
                    @php $contadorProducto = 0; @endphp
                    @foreach($propuestas as $propIndex => $propuesta)
                    <!-- PROPUESTA -->
                    <div class="propuesta-group bg-slate-100 dark:bg-slate-900/70 rounded-2xl p-4 md:p-5 border-2 border-slate-300 dark:border-slate-600" data-numero-propuesta="{{ $propuesta['numeroPropuesta'] }}">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-lg">{{ $propuesta['numeroPropuesta'] }}</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg md:text-xl font-bold text-slate-900 dark:text-slate-100">
                                    Propuesta {{ $propuesta['numeroPropuesta'] }}
                                </h3>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                    Elige una cotización de cualquier producto de esta propuesta
                                </p>
                            </div>
                        </div>

                        <!-- PRODUCTOS de la Propuesta -->
                        <div class="space-y-4 md:space-y-5">
                            @foreach($propuesta['productos'] as $prodIndex => $prod)
                            @php
                            $claveProducto = 'np_' . $propuesta['numeroPropuesta'] . '_prod_' . $prod['numeroProducto'];
                            @endphp
                            <div class="producto-group bg-slate-50 dark:bg-slate-800 rounded-xl p-4 md:p-5 border border-slate-200 dark:border-slate-700" data-clave-producto="{{ $claveProducto }}">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-base md:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                                            {{ $prod['descripcion'] }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="grid gap-3 md:gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($prod['cotizaciones'] as $cotizacion)

                            @php
                            $cantidad = $cotizacion->Cantidad ?? 1;
                            $precioUnitario = $cotizacion->Precio ?? 0;
                            $costoEnvio = $cotizacion->CostoEnvio ?? 0;
                            $subtotal = $cantidad * $precioUnitario;
                            $total = $subtotal + $costoEnvio;
                            @endphp

                            <div class="propuesta-card group relative bg-white dark:bg-slate-800 rounded-xl border-2 transition-all duration-200 border-slate-300 dark:border-slate-600 hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-lg cursor-pointer overflow-hidden"
                                data-numero-propuesta="{{ $propuesta['numeroPropuesta'] }}"
                                data-cotizacion-id="{{ $cotizacion->CotizacionID }}">
                                
                                <div class="bg-slate-700 dark:bg-slate-700 px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-building text-slate-300 text-sm"></i>
                                        <span class="text-sm font-bold text-white truncate">{{ $cotizacion->Proveedor }}</span>
                                    </div>
                                </div>

                                <!-- Contenido -->
                                <div class="p-4 space-y-3">
                                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 border border-slate-200 dark:border-slate-700 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-600 dark:text-slate-400">Precio Unitario</span>
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">${{ number_format($precioUnitario, 2, '.', ',') }}</span>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-600 dark:text-slate-400">Cantidad</span>
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">×{{ $cantidad }}</span>
                                        </div>
                                        
                                        <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                                            <span class="text-xs text-slate-600 dark:text-slate-400">Subtotal</span>
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">${{ number_format($subtotal, 2, '.', ',') }}</span>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-1">
                                                <i class="fas fa-shipping-fast text-xs"></i>
                                                Envío
                                            </span>
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">${{ number_format($costoEnvio, 2, '.', ',') }}</span>
                                        </div>
                                        
                                        <div class="flex items-center justify-between pt-2 border-t-2 border-slate-300 dark:border-slate-600">
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">TOTAL</span>
                                            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($total, 2, '.', ',') }}</span>
                                        </div>
                                    </div>

                                    <!-- Detalles Adicionales -->
                                    @if(!empty($cotizacion->NumeroParte) || !empty($cotizacion->Descripcion) || !empty($cotizacion->TiempoEntrega))
                                    <div class="space-y-2">
                                        @if(!empty($cotizacion->NumeroParte))
                                        <div class="flex items-start gap-2">
                                            <i class="fas fa-hashtag text-slate-400 text-xs mt-0.5 flex-shrink-0"></i>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ $cotizacion->NumeroParte }}</p>
                                        </div>
                                        @endif
                                        @if(!empty($cotizacion->TiempoEntrega))
                                        <div class="flex items-start gap-2">
                                            <i class="fas fa-clock text-slate-400 text-xs mt-0.5 flex-shrink-0"></i>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Entrega: {{ $cotizacion->TiempoEntrega }} días</p>
                                        </div>
                                        @endif
                                        @if(!empty($cotizacion->Descripcion))
                                        <div class="flex items-start gap-2">
                                            <i class="fas fa-align-left text-slate-400 text-xs mt-0.5 flex-shrink-0"></i>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2">{{ $cotizacion->Descripcion }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Botón -->
                                    <div class="pt-2">
                                        <div class="propuesta-btn pointer-events-none w-full px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg flex items-center justify-center gap-2 transition-all">
                                            <i class="fas fa-trophy"></i>
                                            <span class="btn-label">Elegir ganador</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            <!-- FIN COTIZACIONES -->
                        </div>
                    </div>
                    @endforeach
                    <!-- FIN PRODUCTOS -->
                </div>
            </div>
            @endforeach
            <!-- FIN PROPUESTAS -->
                </div>
                @endif
            </div>

            @if(!isset($error) || !$error)
            <!-- Botones de acción -->
            <div class="flex flex-wrap items-center justify-center sm:justify-between gap-3 md:gap-4 mb-4">
                <button type="button" onclick="cancelar()" class="px-5 md:px-6 py-2.5 md:py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 text-sm md:text-base font-semibold rounded-lg transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar" disabled
                    class="px-5 md:px-6 py-2.5 md:py-3 rounded-lg text-sm md:text-base font-semibold transition-all bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed">
                    <i class="fas fa-check mr-2"></i> Confirmar Ganadores
                </button>
            </div>
            @if($totalPropuestas > 0)
            <div id="warning-box" class="flex items-start gap-3 p-4 md:p-5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700">
                <i class="fas fa-exclamation-triangle text-amber-500 dark:text-amber-400 text-lg mt-0.5 shrink-0"></i>
                <p class="text-sm text-amber-800 dark:text-amber-200 leading-relaxed">Debes seleccionar una cotización ganadora por cada propuesta antes de confirmar.</p>
            </div>
            @endif
            @endif
        </div>
    </div>

    <script>
        window.ELECTOR_TOKEN = @json($token ?? '');
        window.ELECTOR_SOLICITUD_ID = {{ (int)($solicitud->SolicitudID ?? 0) }};
        window.ELECTOR_TOTAL = {{ $totalPropuestas ?? 0 }};
        window.ELECTOR_CSRF = @json(csrf_token());

        const selecciones = {}; // numeroPropuesta -> cotizacionId

        function cancelar() {
            if (window.history.length > 1) window.history.back();
            else window.close();
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
                const btn = card.querySelector('.propuesta-btn');
                const label = card.querySelector('.btn-label');
                if (esGanador) {
                    card.classList.remove('border-slate-300', 'dark:border-slate-600', 'hover:border-amber-400', 'dark:hover:border-amber-500');
                    card.classList.add('border-emerald-500', 'dark:border-emerald-400', 'bg-emerald-50', 'dark:bg-emerald-900/20', 'shadow-lg');
                    if (btn) {
                        btn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                        btn.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                        if (label) label.textContent = '✓ Ganador';
                    }
                } else {
                    card.classList.remove('border-emerald-500', 'dark:border-emerald-400', 'bg-emerald-50', 'dark:bg-emerald-900/20', 'shadow-lg');
                    card.classList.add('border-slate-300', 'dark:border-slate-600', 'hover:border-amber-400', 'dark:hover:border-amber-500');
                    if (btn) {
                        btn.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                        btn.classList.add('bg-amber-500', 'hover:bg-amber-600');
                        if (label) label.textContent = 'Elegir ganador';
                    }
                }
            });

            const todos = total > 0 && n === total;
            const btnConfirm = document.getElementById('btn-confirmar');
            const warning = document.getElementById('warning-box');
            if (btnConfirm) {
                btnConfirm.disabled = !todos;
                btnConfirm.classList.toggle('bg-slate-200', !todos);
                btnConfirm.classList.toggle('dark:bg-slate-700', !todos);
                btnConfirm.classList.toggle('text-slate-400', !todos);
                btnConfirm.classList.toggle('dark:text-slate-500', !todos);
                btnConfirm.classList.toggle('cursor-not-allowed', !todos);
                btnConfirm.classList.toggle('bg-emerald-600', todos);
                btnConfirm.classList.toggle('dark:bg-emerald-600', todos);
                btnConfirm.classList.toggle('hover:bg-emerald-700', todos);
                btnConfirm.classList.toggle('dark:hover:bg-emerald-500', todos);
                btnConfirm.classList.toggle('text-white', todos);
                btnConfirm.classList.toggle('cursor-pointer', todos);
                if (todos) {
                    btnConfirm.onclick = confirmarGanadores;
                } else {
                    btnConfirm.onclick = null;
                }
            }
            if (warning) warning.style.display = todos ? 'none' : 'flex';
        }

        function elegirGanador(numeroPropuesta, cotizacionId) {
            selecciones[numeroPropuesta] = cotizacionId;
            actualizarUI();
        }

        function confirmarGanadores() {
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
                                title: 'Error',
                                text: (data && data.message) || 'Error al confirmar.',
                                icon: 'error'
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

        document.querySelectorAll('.propuesta-card').forEach(function(card) {
            card.addEventListener('click', function() {
                const numPropuesta = parseInt(this.dataset.numeroPropuesta, 10);
                const cid = parseInt(this.dataset.cotizacionId, 10);
                elegirGanador(numPropuesta, cid);
            });
        });

        actualizarUI();
    </script>
</body>

</html>