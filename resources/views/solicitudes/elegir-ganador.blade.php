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
    @php
        $productos = $productos ?? [];
        $totalProductos = count($productos);
        $nombresProductos = [];
        $todosProveedores = collect();
        foreach ($productos as $p) {
            $nombresProductos[] = $p['descripcion'] ?: 'Producto';
            foreach ($p['cotizaciones'] ?? [] as $c) { $todosProveedores->push($c->Proveedor); }
        }
        $numProveedores = $todosProveedores->unique()->count();
        $numPropuestas = $totalProductos > 0 && !empty($productos[0]['cotizaciones'])
            ? count($productos[0]['cotizaciones']) : 0;
        $nombresStr = implode(', ', $nombresProductos);
    @endphp
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i>
                    Elegir Ganador - Solicitud #{{ $solicitud->SolicitudID }}
                </h1>
                <p class="text-sm text-gray-600 mt-2">
                    La cantidad de productos son: ({{ $totalProductos }}: {{ $nombresStr }}). Cada uno tiene {{ $numPropuestas }} propuestas de {{ $numProveedores }} proveedores. Elige un ganador por cada producto.
                </p>
                @if($totalProductos > 0)
                <div class="mt-4 flex items-center gap-3">
                    <div class="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                        <div id="progress-fill" class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <span id="progress-text" class="text-sm font-medium text-gray-700">0/{{ $totalProductos }}</span>
                </div>
                @endif
            </div>

            <!-- Información de la Solicitud -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-file-alt text-blue-500"></i>
                    Información de la Solicitud
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Solicitante</label>
                        <p class="text-sm text-gray-900 font-semibold">{{ $solicitud->empleadoid->NombreEmpleado ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500">Motivo</label>
                        <p class="text-sm text-gray-900 font-semibold">{{ $solicitud->Motivo ?? 'N/A' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-500">Descripción</label>
                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Cotizaciones por producto -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <span class="w-2 h-2 bg-violet-500 rounded-sm shrink-0"></span>
                    Propuestas de Cotización
                </h2>
                <p class="text-sm text-gray-600 mb-4">Elige un ganador por cada producto.</p>

                @if(isset($error) && $error)
                <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">{{ $error }}</div>
                @else
                <div class="space-y-6" id="cotizaciones-container">
                    @foreach($productos as $idx => $prod)
                    <div class="producto-group" data-numero-propuesta="{{ $prod['numeroPropuesta'] ?? ($idx + 1) }}">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">
                            {{ $idx + 1 }}. {{ $prod['descripcion'] }}
                            @if(($prod['cantidad'] ?? 1) > 1)
                            <span class="text-gray-500 font-normal"> × {{ $prod['cantidad'] }}</span>
                            @endif
                        </h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach($prod['cotizaciones'] as $cotizacion)
                            <div class="propuesta-card p-4 rounded-xl border-2 transition bg-gray-50/50 border-gray-200 hover:border-amber-300 cursor-pointer"
                                 data-numero-propuesta="{{ $prod['numeroPropuesta'] ?? ($idx + 1) }}"
                                 data-cotizacion-id="{{ $cotizacion->CotizacionID }}">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0">
                                            <span class="text-sm font-semibold text-gray-900">Proveedor: {{ $cotizacion->Proveedor }}</span>
                                            <span class="text-sm font-semibold text-gray-900">Precio: ${{ number_format($cotizacion->Precio, 2, '.', ',') }}</span>
                                        </div>
                                        @if(!empty($cotizacion->NumeroParte))
                                        <p class="text-xs text-gray-500">No. parte: {{ $cotizacion->NumeroParte }}</p>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="propuesta-btn px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg inline-flex items-center gap-1.5">
                                            <i class="fas fa-trophy"></i> <span class="btn-label">Elegir ganador</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @if(!isset($error) || !$error)
            <!-- Botones de acción -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <button type="button" onclick="cancelar()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-lg transition">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar" disabled
                    class="px-5 py-2.5 rounded-lg text-sm font-medium transition bg-gray-200 text-gray-400 cursor-not-allowed">
                    <i class="fas fa-check mr-1.5"></i> Confirmar Ganadores
                </button>
            </div>
            @if($totalProductos > 0)
            <div id="warning-box" class="flex items-start gap-3 p-4 rounded-lg bg-amber-50 border border-amber-300">
                <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 shrink-0"></i>
                <p class="text-sm text-amber-800">Debes seleccionar un ganador para cada producto antes de confirmar.</p>
            </div>
            @endif
            @endif
        </div>
    </div>

    <script>
        window.ELECTOR_TOKEN = @json($token ?? '');
        window.ELECTOR_SOLICITUD_ID = {{ (int) ($solicitud->SolicitudID ?? 0) }};
        window.ELECTOR_TOTAL = {{ $totalProductos ?? 0 }};
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

            document.querySelectorAll('.propuesta-card').forEach(function (card) {
                const np = parseInt(card.dataset.numeroPropuesta, 10);
                const cid = parseInt(card.dataset.cotizacionId, 10);
                const esGanador = selecciones[np] === cid;
                const btn = card.querySelector('.propuesta-btn');
                const label = card.querySelector('.btn-label');
                if (esGanador) {
                    card.classList.remove('bg-gray-50/50', 'border-gray-200', 'hover:border-amber-300');
                    card.classList.add('bg-emerald-50', 'border-emerald-500');
                    if (btn) {
                        btn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                        btn.classList.add('bg-emerald-500');
                        if (label) label.textContent = 'Ganador';
                    }
                } else {
                    card.classList.remove('bg-emerald-50', 'border-emerald-500');
                    card.classList.add('bg-gray-50/50', 'border-gray-200', 'hover:border-amber-300');
                    if (btn) {
                        btn.classList.remove('bg-emerald-500');
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
                btnConfirm.classList.toggle('bg-gray-200', !todos);
                btnConfirm.classList.toggle('text-gray-400', !todos);
                btnConfirm.classList.toggle('cursor-not-allowed', !todos);
                btnConfirm.classList.toggle('bg-primary', todos);
                btnConfirm.classList.toggle('hover:bg-primary-hover', todos);
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
            }).then(function (r) {
                if (!r.isConfirmed) return;
                Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                const body = { ganadores };
                const token = (window.ELECTOR_TOKEN || '').trim();
                if (token) body.token = token;
                fetch('/solicitudes/' + window.ELECTOR_SOLICITUD_ID + '/confirmar-ganadores', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ELECTOR_CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                })
                    .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data }; }); })
                    .then(function ({ ok, data }) {
                        Swal.close();
                        if (!ok) {
                            Swal.fire({ title: 'Error', text: (data && data.message) || 'Error al confirmar.', icon: 'error' });
                            return;
                        }
                        Swal.fire({
                            title: '¡Listo!',
                            text: (data && data.message) || 'Ganadores confirmados.',
                            icon: 'success',
                            confirmButtonColor: '#0F766E'
                        }).then(function () {
                            const url = (data && data.redirect) || ('/elegir-ganador/' + (token || ''));
                            window.location.href = url;
                        });
                    })
                    .catch(function (e) {
                        Swal.close();
                        console.error(e);
                        Swal.fire({ title: 'Error', text: 'Error al confirmar ganadores.', icon: 'error' });
                    });
            });
        }

        document.querySelectorAll('.propuesta-card').forEach(function (card) {
            card.addEventListener('click', function () {
                const np = parseInt(this.dataset.numeroPropuesta, 10);
                const cid = parseInt(this.dataset.cotizacionId, 10);
                elegirGanador(np, cid);
            });
        });

        actualizarUI();
    </script>
</body>

</html>
