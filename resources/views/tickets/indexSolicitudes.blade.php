@verbatim
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('solicitudesData', () => ({
        modalAbierto: false,
        modalCotizacionAbierto: false,
        solicitudSeleccionada: null,
        solicitudCotizacionId: null,
        cargando: false,
        cargandoCotizaciones: false,
        proveedores: ['INTERCOMPRAS', 'PCEL', 'ABASTEO'],
        productos: [],
        tieneCotizacionesGuardadas: false,
        tieneCotizacionesEnviadas: false,
        abrirModal(id) {
            this.cargando = true;
            this.modalAbierto = true;
            fetch(`/solicitudes/${id}/datos`)
                .then(response => response.json())
                .then(data => {
                    this.solicitudSeleccionada = data;
                    this.cargando = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'No se pudo cargar la información de la solicitud', 'error');
                    this.cargando = false;
                    this.modalAbierto = false;
                });
        },
        cerrarModal() {
            this.modalAbierto = false;
            this.solicitudSeleccionada = null;
        },
        abrirModalCotizacion(id) {
            this.solicitudCotizacionId = id;
            this.modalCotizacionAbierto = true;
            this.cargarCotizaciones(id);
        },
        cerrarModalCotizacion() {
            this.modalCotizacionAbierto = false;
            this.solicitudCotizacionId = null;
            this.productos = [];
            this.tieneCotizacionesGuardadas = false;
            this.tieneCotizacionesEnviadas = false;
        },
        agregarProveedor() {
            const nombre = prompt('Nombre del proveedor:');
            if (nombre && nombre.trim() && !this.proveedores.includes(nombre.trim())) {
                const nuevoProveedor = nombre.trim();
                this.proveedores.push(nuevoProveedor);
                this.productos.forEach(prod => {
                    if (!prod.precios) prod.precios = {};
                    prod.precios[nuevoProveedor] = '';
                });
            }
        },
        eliminarProveedor(index) {
            if (this.proveedores.length > 1) {
                const proveedorEliminado = this.proveedores[index];
                this.proveedores.splice(index, 1);
                this.productos.forEach(prod => {
                    if (prod.precios && prod.precios[proveedorEliminado]) {
                        delete prod.precios[proveedorEliminado];
                    }
                });
            } else {
                Swal.fire('Aviso', 'Debe haber al menos un proveedor', 'warning');
            }
        },
        agregarProducto() {
            const nuevoProducto = {
                cantidad: 1,
                numeroParte: '',
                descripcion: '',
                unidad: 'PIEZA',
                precios: {},
                tiempoEntrega: {},
                observaciones: {}
            };
            this.proveedores.forEach(prov => {
                nuevoProducto.precios[prov] = '';
            });
            this.productos.push(nuevoProducto);
        },
        eliminarProducto(index) {
            this.productos.splice(index, 1);
        },
        cargarCotizaciones(id) {
            this.cargandoCotizaciones = true;
            // Limpiar datos previos
            this.productos = [];
            this.proveedores = [];
            
            fetch(`/solicitudes/${id}/cotizaciones`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    
                    // Primero establecer los proveedores
                    if (data.proveedores && Array.isArray(data.proveedores) && data.proveedores.length > 0) {
                        this.proveedores = data.proveedores;
                    } else {
                        // Si no hay proveedores en los datos, inicializar con uno por defecto
                        this.proveedores = ['INTERCOMPRAS'];
                    }
                    
                    // Luego procesar los productos
                    if (data.productos && Array.isArray(data.productos) && data.productos.length > 0) {
                        this.productos = data.productos.map(prod => {
                            // Asegurar que el objeto precios existe
                            if (!prod.precios || typeof prod.precios !== 'object') {
                                prod.precios = {};
                            }
                            
                            // Asegurar que todos los proveedores tengan una entrada en precios
                            this.proveedores.forEach(prov => {
                                if (!prod.precios.hasOwnProperty(prov)) {
                                    prod.precios[prov] = '';
                                } else {
                                    // Convertir a string si es número para que funcione con el input
                                    if (typeof prod.precios[prov] === 'number') {
                                        prod.precios[prov] = prod.precios[prov].toString();
                                    }
                                }
                            });
                            
                            // Asegurar otros campos
                            if (!prod.cantidad) prod.cantidad = 1;
                            if (!prod.numeroParte) prod.numeroParte = '';
                            if (!prod.descripcion) prod.descripcion = '';
                            if (!prod.unidad) prod.unidad = 'PIEZA';
                            
                            return prod;
                        });
                        this.tieneCotizacionesGuardadas = true;
                    } else {
                        // Si no hay productos, agregar uno vacío para que el usuario pueda empezar
                        this.agregarProducto();
                        this.tieneCotizacionesGuardadas = false;
                    }
                    
                    this.tieneCotizacionesEnviadas = data.tieneCotizacionesEnviadas || false;
                    this.cargandoCotizaciones = false;
                })
                .catch(error => {
                    console.error('Error cargando cotizaciones:', error);
                    // En caso de error, inicializar con valores por defecto
                    if (this.proveedores.length === 0) {
                        this.proveedores = ['INTERCOMPRAS'];
                    }
                    if (this.productos.length === 0) {
                        this.agregarProducto();
                    }
                    this.tieneCotizacionesGuardadas = false;
                    this.tieneCotizacionesEnviadas = false;
                    this.cargandoCotizaciones = false;
                    
                    // Mostrar mensaje de error al usuario
                    Swal.fire({
                        icon: 'warning',
                        title: 'Aviso',
                        text: 'No se pudieron cargar las cotizaciones guardadas. Puedes agregar nuevas cotizaciones.',
                        confirmButtonColor: '#0F766E'
                    });
                });
        },
        guardarCotizaciones() {
            if (this.productos.length === 0) {
                Swal.fire('Aviso', 'Debe agregar al menos un producto', 'warning');
                return;
            }
            
            const productosValidos = this.productos.filter(prod => prod.descripcion && prod.descripcion.trim() !== '');
            if (productosValidos.length === 0) {
                Swal.fire('Aviso', 'Debe agregar al menos un producto con descripción', 'warning');
                return;
            }
            
            const tienePrecios = productosValidos.some(prod => {
                return Object.values(prod.precios || {}).some(precio => 
                    precio !== null && precio !== '' && precio !== undefined && parseFloat(precio) > 0
                );
            });
            if (!tienePrecios) {
                Swal.fire('Aviso', 'Debe ingresar al menos un precio para algún producto', 'warning');
                return;
            }
            
            const datos = {
                proveedores: this.proveedores,
                productos: this.productos.map(prod => {
                    const preciosLimpios = {};
                    Object.keys(prod.precios || {}).forEach(prov => {
                        const precio = prod.precios[prov];
                        if (precio !== null && precio !== '' && precio !== undefined) {
                            preciosLimpios[prov] = parseFloat(precio) || 0;
                        }
                    });
                    
                    return {
                        cantidad: parseInt(prod.cantidad) || 1,
                        numero_parte: (prod.numeroParte || '').trim(),
                        descripcion: (prod.descripcion || '').trim(),
                        unidad: (prod.unidad || 'PIEZA').trim(),
                        precios: preciosLimpios,
                        tiempo_entrega: prod.tiempoEntrega || {},
                        observaciones: prod.observaciones || {}
                    };
                }).filter(prod => prod.descripcion !== '')
            };

            Swal.fire({
                title: 'Guardando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/solicitudes/${this.solicitudCotizacionId}/guardar-cotizaciones`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(datos)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                Swal.close();
                if (data.success) {
                    this.tieneCotizacionesGuardadas = true;
                    Swal.fire('Éxito', data.message || 'Cotizaciones guardadas correctamente', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar las cotizaciones', 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                const mensaje = error.message || error.error || 'Error al guardar las cotizaciones';
                Swal.fire('Error', mensaje, 'error');
            });
        },
        obtenerPrecioMinimo(producto) {
            const precios = Object.values(producto.precios || {}).filter(p => p && parseFloat(p) > 0);
            return precios.length > 0 ? Math.min(...precios.map(p => parseFloat(p))) : null;
        },
        enviarCotizacionesAlGerente() {
            if (!this.solicitudCotizacionId) {
                Swal.fire('Error', 'No hay solicitud seleccionada', 'error');
                return;
            }

            Swal.fire({
                title: '¿Enviar cotizaciones al gerente?',
                text: 'Se enviará un correo electrónico al gerente con las cotizaciones para que elija el ganador.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Enviando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`/solicitudes/${this.solicitudCotizacionId}/enviar-cotizaciones-gerente`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => Promise.reject(err));
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire('Éxito', data.message || 'Correo enviado al gerente correctamente', 'success').then(() => {
                                this.cerrarModalCotizacion();
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Error al enviar el correo', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        const mensaje = error.message || error.error || 'Error al enviar el correo';
                        Swal.fire('Error', mensaje, 'error');
                    });
                }
            });
        }
    }));
});
</script>
@endverbatim

<div 
    x-data="solicitudesData()"
    class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800">Solicitudes de Equipos TI</h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estatus</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aprobaciones</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cotiz.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $todasSolicitudes = collect($solicitudesStatus)
                        ->flatten()
                        ->unique('SolicitudID')
                        ->sortByDesc(function($solicitud) {
                            return $solicitud->created_at ? $solicitud->created_at->timestamp : 0;
                        })
                        ->values();
                @endphp
                @forelse ($todasSolicitudes as $solicitud)
                @php
                    $empleado = $solicitud->empleadoid;
                    $partes = preg_split('/\s+/', trim($empleado->NombreEmpleado ?? ''));
                    if (count($partes) >= 3) array_splice($partes, 1, 1);
                    $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();
                    
                    $pasoSupervisor = $solicitud->pasoSupervisor;
                    $pasoGerencia = $solicitud->pasoGerencia;
                    $pasoAdministracion = $solicitud->pasoAdministracion;
                    
                    $estatusReal = $solicitud->Estatus ?? 'Pendiente';
                    $estaRechazada = false;
                    
                    if (($pasoSupervisor && $pasoSupervisor->status === 'rejected') ||
                        ($pasoGerencia && $pasoGerencia->status === 'rejected') ||
                        ($pasoAdministracion && $pasoAdministracion->status === 'rejected')) {
                        $estatusReal = 'Rechazada';
                        $estaRechazada = true;
                    } elseif ($solicitud->Estatus === 'Aprobado') {
                        $estatusReal = 'Aprobado';
                    } elseif (in_array($solicitud->Estatus, ['Pendiente', null, ''], true) || empty($solicitud->Estatus)) {
                        if ($pasoSupervisor && $pasoSupervisor->status === 'approved') {
                            if ($pasoGerencia && $pasoGerencia->status === 'approved') {
                                if ($pasoAdministracion && $pasoAdministracion->status === 'approved') {
                                    $tieneSeleccionada = $solicitud->cotizaciones && $solicitud->cotizaciones->where('Estatus', 'Seleccionada')->isNotEmpty();
                                    $cotizacionesCount = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;
                                    $estatusReal = $tieneSeleccionada ? 'Aprobado' : ($cotizacionesCount >= 1 ? 'Completada' : 'Pendiente Cotización TI');
                                } else {
                                    $estatusReal = 'Pendiente Aprobación Administración';
                                }
                            } else {
                                $estatusReal = 'Pendiente Aprobación Gerencia';
                            }
                        } else {
                            $estatusReal = 'Pendiente Aprobación Supervisor';
                        }
                    }
                    
                    if ($estatusReal === 'Rechazada') {
                        $estatusDisplay = 'Rechazada';
                    } elseif ($estatusReal === 'Aprobado' || ($solicitud->cotizaciones && $solicitud->cotizaciones->where('Estatus', 'Seleccionada')->isNotEmpty())) {
                        $estatusDisplay = 'Aprobada';
                    } elseif ($estatusReal === 'Cotizaciones Enviadas') {
                        $estatusDisplay = 'Cotizaciones Enviadas';
                    } elseif ($estatusReal === 'Completada') {
                        $estatusDisplay = 'En revisión';
                    } elseif ($estatusReal === 'Pendiente Cotización TI') {
                        $estatusDisplay = 'Pendiente';
                    } elseif (in_array($estatusReal, ['Pendiente Aprobación Supervisor', 'Pendiente Aprobación Gerencia', 'Pendiente Aprobación Administración'], true)) {
                        $estatusDisplay = 'En revisión';
                    } else {
                        $estatusDisplay = 'Pendiente';
                    }
                    
                    $todasFirmaron = ($pasoSupervisor && $pasoSupervisor->status === 'approved')
                        && ($pasoGerencia && $pasoGerencia->status === 'approved')
                        && ($pasoAdministracion && $pasoAdministracion->status === 'approved');
                    $puedeCotizar = $todasFirmaron && auth()->check() && $estatusDisplay !== 'Aprobada';
                    $puedeSubirFactura = $estatusDisplay === 'Aprobada' && auth()->check();
                    
                    $colorEstatus = match($estatusDisplay) {
                        'Pendiente' => 'bg-amber-50 text-amber-800 border border-amber-200',
                        'Rechazada' => 'bg-red-50 text-red-800 border border-red-200',
                        'En revisión' => 'bg-sky-50 text-sky-800 border border-sky-200',
                        'Aprobada' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                        'Cotizaciones Enviadas' => 'bg-blue-50 text-blue-800 border border-blue-200',
                        default => 'bg-gray-50 text-gray-700 border border-gray-200'
                    };
                    
                    $puedeAprobar = false;
                    $nivelAprobacion = '';
                    
                    if (auth()->check() && !$estaRechazada) {
                        $usuarioActual = auth()->user();
                        $usuarioEmpleado = \App\Models\Empleados::where('Correo', $usuarioActual->email)->first();
                        $usuarioEmpleadoID = $usuarioEmpleado->EmpleadoID ?? null;
                        
                        if ($estatusReal === 'Pendiente Aprobación Supervisor' && $pasoSupervisor && $pasoSupervisor->approver_empleado_id == $usuarioEmpleadoID) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'supervisor';
                        } elseif ($estatusReal === 'Pendiente Aprobación Gerencia' && $solicitud->GerenciaID && auth()->user()->can('aprobar-solicitudes-gerencia')) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'gerencia';
                        } elseif ($estatusReal === 'Pendiente Aprobación Administración' && auth()->user()->can('aprobar-solicitudes-administracion')) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'administracion';
                        }
                    }
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">#{{ $solicitud->SolicitudID }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $nombreFormateado }}</div>
                        <div class="text-xs text-gray-500">{{ Str::limit($empleado->Correo ?? 'N/A', 25) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900">{{ Str::limit($solicitud->Motivo ?? 'N/A', 30) }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $colorEstatus }}">
                            {{ $estatusDisplay }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if($pasoSupervisor)
                                @if($pasoSupervisor->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Supervisor: Aprobado"></i>
                                @elseif($pasoSupervisor->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Supervisor: Rechazado"></i>
                                @else
                                    <i class="far fa-circle text-yellow-500" title="Supervisor: Pendiente"></i>
                                @endif
                            @else
                                <i class="far fa-circle text-gray-400" title="Supervisor: Pendiente"></i>
                            @endif
                            
                            @if($pasoGerencia)
                                @if($pasoGerencia->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Gerencia: Aprobado"></i>
                                @elseif($pasoGerencia->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Gerencia: Rechazado"></i>
                                @else
                                    <i class="far fa-circle text-orange-500" title="Gerencia: Pendiente"></i>
                                @endif
                            @else
                                <i class="far fa-circle text-gray-300" title="Gerencia: Esperando"></i>
                            @endif
                            
                            @if($pasoAdministracion)
                                @if($pasoAdministracion->status === 'approved')
                                    <i class="fas fa-check-circle text-green-500" title="Administración: Aprobado"></i>
                                @elseif($pasoAdministracion->status === 'rejected')
                                    <i class="fas fa-times-circle text-red-500" title="Administración: Rechazado"></i>
                                @else
                                    <i class="far fa-circle text-purple-500" title="Administración: Pendiente"></i>
                                @endif
                            @else
                                <i class="far fa-circle text-gray-300" title="Administración: Esperando"></i>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($solicitud->cotizaciones && $solicitud->cotizaciones->count() > 0)
                            <span class="text-sm font-medium text-gray-900">{{ $solicitud->cotizaciones->count() }}/3</span>
                        @else
                            <span class="text-sm text-gray-400">0/3</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $solicitud->created_at->format('d/m/Y') }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            <button 
                                @click="abrirModal({{ $solicitud->SolicitudID }})"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium transition">
                                <i class="fas fa-eye mr-1"></i> Ver
                            </button>
                            @if($puedeCotizar)
                            <button 
                                @click="abrirModalCotizacion({{ $solicitud->SolicitudID }})"
                                class="text-violet-600 hover:text-violet-800 text-sm font-medium transition">
                                <i class="fas fa-file-invoice-dollar mr-1"></i> Cotizar
                            </button>
                            @endif
                            @if($puedeSubirFactura)
                            <span 
                                class="text-emerald-600 text-sm font-medium cursor-default">
                                <i class="fas fa-file-invoice mr-1"></i> Factura
                            </span>
                            @endif
                            @if($puedeAprobar)
                            <div class="flex gap-1" @click.stop>
                                <button 
                                    @click.stop="aprobarSolicitud({{ $solicitud->SolicitudID }}, '{{ $nivelAprobacion }}')"
                                    class="text-green-600 hover:text-green-800 text-xs px-2 py-1 rounded border border-green-300 hover:bg-green-50 transition"
                                    title="Aprobar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button 
                                    @click.stop="rechazarSolicitud({{ $solicitud->SolicitudID }}, '{{ $nivelAprobacion }}')"
                                    class="text-red-600 hover:text-red-800 text-xs px-2 py-1 rounded border border-red-300 hover:bg-red-50 transition"
                                    title="Rechazar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 block text-gray-400"></i>
                        <p class="text-lg font-medium">No hay solicitudes registradas</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal de Detalles -->
    <div 
        x-show="modalAbierto"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="cerrarModal()"
        style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-xl font-semibold text-gray-900">
                    Detalles de Solicitud
                    <span x-show="solicitudSeleccionada" x-text="'#' + solicitudSeleccionada?.SolicitudID" class="text-gray-500"></span>
                </h3>
                <button @click="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="mt-4">
                <div x-show="cargando" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                    <p class="mt-2 text-gray-600">Cargando información...</p>
                </div>
                
                <div x-show="!cargando && solicitudSeleccionada" style="display: none;">
                    <!-- Información del Empleado -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-user text-blue-500"></i>
                            Información del Solicitante
                        </h4>
                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Nombre</label>
                                <p class="text-sm text-gray-900 font-medium" x-text="solicitudSeleccionada?.empleado?.NombreEmpleado"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Correo</label>
                                <p class="text-sm text-gray-900" x-text="solicitudSeleccionada?.empleado?.Correo"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Gerencia</label>
                                <p class="text-sm text-gray-900" x-text="solicitudSeleccionada?.gerencia?.NombreGerencia || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Obra</label>
                                <p class="text-sm text-gray-900" x-text="solicitudSeleccionada?.obra?.NombreObra || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Puesto</label>
                                <p class="text-sm text-gray-900" x-text="solicitudSeleccionada?.puesto?.NombrePuesto || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Ubicación</label>
                                <p class="text-sm text-gray-900" x-text="solicitudSeleccionada?.ProyectoNombre || solicitudSeleccionada?.Proyecto || 'N/A'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles de la Solicitud -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-file-alt text-green-500"></i>
                            Detalles de la Solicitud
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Motivo</label>
                                <p class="text-sm text-gray-900 font-medium" x-text="solicitudSeleccionada?.Motivo || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Descripción del Motivo</label>
                                <p class="text-sm text-gray-900 whitespace-pre-wrap" x-text="solicitudSeleccionada?.DescripcionMotivo || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Requerimientos</label>
                                <p class="text-sm text-gray-900 whitespace-pre-wrap" x-text="solicitudSeleccionada?.Requerimientos || 'N/A'"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Estatus</label>
                                    <p class="text-sm font-semibold" 
                                       :class="{
                                           'text-amber-600': (solicitudSeleccionada?.estatusDisplay || '') === 'Pendiente',
                                           'text-red-600': (solicitudSeleccionada?.estatusDisplay || '') === 'Rechazada',
                                           'text-sky-600': (solicitudSeleccionada?.estatusDisplay || '') === 'En revisión',
                                           'text-emerald-600': (solicitudSeleccionada?.estatusDisplay || '') === 'Aprobada',
                                           'text-blue-600': (solicitudSeleccionada?.estatusDisplay || '') === 'Cotizaciones Enviadas',
                                           'text-gray-900': !['Pendiente','Rechazada','En revisión','Aprobada','Cotizaciones Enviadas'].includes(solicitudSeleccionada?.estatusDisplay || '')
                                       }"
                                       x-text="solicitudSeleccionada?.estatusDisplay || 'Pendiente'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Fecha de Creación</label>
                                    <p class="text-sm text-gray-900" x-text="solicitudSeleccionada?.fechaCreacion || 'N/A'"></p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2" x-show="solicitudSeleccionada?.puedeCotizar && solicitudSeleccionada?.estatusDisplay !== 'Aprobada' && solicitudSeleccionada?.estatusDisplay !== 'Cotizaciones Enviadas'">
                                <button @click="abrirModalCotizacion(solicitudSeleccionada?.SolicitudID)"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span x-text="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0 ? 'Editar cotizaciones' : 'Cotizar'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Flujo de Aprobación -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-tasks text-purple-500"></i>
                            Flujo de Aprobación
                        </h4>
                        <div class="space-y-4">
                            <template x-for="(paso, index) in solicitudSeleccionada?.pasosAprobacion || []" :key="index">
                                <div class="bg-gray-50 p-4 rounded-lg border-l-4" 
                                     :class="{
                                         'border-green-500': paso.status === 'approved',
                                         'border-red-500': paso.status === 'rejected',
                                         'border-yellow-500': paso.status === 'pending'
                                     }">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <i class="fas" 
                                               :class="{
                                                   'fa-check-circle text-green-500': paso.status === 'approved',
                                                   'fa-times-circle text-red-500': paso.status === 'rejected',
                                                   'fa-circle text-yellow-500': paso.status === 'pending'
                                               }"></i>
                                            <span class="font-semibold text-gray-900" x-text="paso.stageLabel"></span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded"
                                              :class="{
                                                  'bg-green-100 text-green-800': paso.status === 'approved',
                                                  'bg-red-100 text-red-800': paso.status === 'rejected',
                                                  'bg-yellow-100 text-yellow-800': paso.status === 'pending'
                                              }"
                                              x-text="paso.statusLabel"></span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p><span class="font-medium">Aprobador asignado:</span> <span x-text="paso.approverNombre || 'N/A'"></span></p>
                                        <p x-show="paso.decidedByNombre"><span class="font-medium">Aprobado por:</span> <span x-text="paso.decidedByNombre"></span></p>
                                        <p x-show="paso.decidedAt"><span class="font-medium">Fecha:</span> <span x-text="paso.decidedAt"></span></p>
                                        <p x-show="paso.comment"><span class="font-medium">Comentario:</span> <span x-text="paso.comment"></span></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Cotizaciones -->
                    <div class="mb-6" x-show="(solicitudSeleccionada?.cotizaciones?.length || 0) > 0">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-violet-500"></i>
                            Cotizaciones (<span x-text="solicitudSeleccionada?.cotizaciones?.length || 0"></span>)
                        </h4>
                        <div class="space-y-3">
                            <template x-for="(cotizacion, index) in solicitudSeleccionada?.cotizaciones || []" :key="index">
                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-500">Proveedor</label>
                                            <p class="text-sm text-gray-900 font-medium" x-text="cotizacion.Proveedor"></p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-500">NO. PARTE</label>
                                            <p class="text-sm text-gray-900 font-medium" x-text="cotizacion.NumeroParte || 'N/A'"></p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-500">Precio</label>
                                            <p class="text-sm text-gray-900 font-medium" x-text="'$' + parseFloat(cotizacion.Precio).toLocaleString('es-MX', {minimumFractionDigits: 2})"></p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-500">Estatus</label>
                                            <p class="text-sm font-medium" 
                                               :class="{
                                                   'text-emerald-600': cotizacion.Estatus === 'Seleccionada',
                                                   'text-red-600': cotizacion.Estatus === 'Rechazada',
                                                   'text-gray-600': cotizacion.Estatus === 'Pendiente'
                                               }"
                                               x-text="cotizacion.Estatus === 'Seleccionada' ? 'Ganador' : cotizacion.Estatus"></p>
                                        </div>
                                        <div class="col-span-4">
                                            <label class="text-xs font-medium text-gray-500">Descripción</label>
                                            <p class="text-sm text-gray-900" x-text="cotizacion.Descripcion"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                  
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cotización -->
    <div 
        x-show="modalCotizacionAbierto"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="cerrarModalCotizacion()"
        style="display: none;">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-7xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center pb-3 border-b sticky top-0 bg-white z-10">
                <h3 class="text-xl font-semibold text-gray-900">
                    Cotización - Solicitud #<span x-text="solicitudCotizacionId"></span>
                    <span class="text-xs font-normal text-gray-500 ml-2">PRECIO IVA INCLUIDO</span>
                </h3>
                <button @click="cerrarModalCotizacion()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="mt-4">
                <div x-show="cargandoCotizaciones" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                    <p class="mt-2 text-gray-600">Cargando cotizaciones...</p>
                </div>
                
                <div x-show="!cargandoCotizaciones" style="display: none;">
                    <!-- Alerta de cotizaciones enviadas -->
                    <div x-show="tieneCotizacionesEnviadas || solicitudSeleccionada?.estatusDisplay === 'Cotizaciones Enviadas'" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-500"></i>
                            <p class="text-sm text-blue-800">
                                <strong>Cotizaciones enviadas:</strong> Ya se han enviado cotizaciones al gerente para su revisión. 
                                Puedes agregar o editar nuevas cotizaciones si es necesario.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Controles de Proveedores -->
                    <div class="mb-4 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-gray-700">Proveedores:</h4>
                            <template x-for="(prov, index) in proveedores" :key="index">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium" 
                                      x-text="prov"></span>
                            </template>
                        </div>
                        <button 
                            @click="agregarProveedor()"
                            class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-sm rounded transition">
                            <i class="fas fa-plus mr-1"></i> Agregar Proveedor
                        </button>
                    </div>

                    <!-- Tabla de Cotización -->
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">CANT.</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">NO. PARTE</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">DESCRIPCIÓN</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Unidad</th>
                                        <template x-for="(proveedor, provIndex) in proveedores" :key="provIndex">
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase relative">
                                                <div class="flex flex-col items-center">
                                                    <span x-text="proveedor"></span>
                                                    <button 
                                                        @click.stop="eliminarProveedor(provIndex)"
                                                        class="text-red-500 hover:text-red-700 text-xs mt-1"
                                                        x-show="proveedores.length > 1"
                                                        title="Eliminar proveedor">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </th>
                                        </template>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(producto, prodIndex) in productos" :key="prodIndex">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2">
                                            <input 
                                                type="number" 
                                                x-model="producto.cantidad"
                                                min="1"
                                                class="w-16 px-2 py-1 border rounded text-center text-sm"
                                                placeholder="1">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input 
                                                type="text" 
                                                x-model="producto.numeroParte"
                                                class="w-full px-2 py-1 border rounded text-sm"
                                                placeholder="Número de parte">
                                        </td>
                                        <td class="px-3 py-2">
                                            <textarea 
                                                x-model="producto.descripcion"
                                                rows="2"
                                                class="w-full px-2 py-1 border rounded text-sm"
                                                placeholder="Descripción del producto"></textarea>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input 
                                                type="text" 
                                                x-model="producto.unidad"
                                                class="w-20 px-2 py-1 border rounded text-center text-sm"
                                                placeholder="PIEZA">
                                        </td>
                                        <template x-for="(proveedor, provIndex) in proveedores" :key="provIndex">
                                            <td class="px-3 py-2">
                                                <div class="space-y-1">
                                                    <div class="relative">
                                                        <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">$</span>
                                                        <input 
                                                            type="number" 
                                                            step="0.01"
                                                            min="0"
                                                            :value="producto.precios[proveedor] || ''"
                                                            @input="producto.precios[proveedor] = $event.target.value"
                                                            @blur="if(producto.precios[proveedor]) { const val = parseFloat(producto.precios[proveedor]); producto.precios[proveedor] = isNaN(val) ? '' : val.toFixed(2); }"
                                                            class="w-full pl-6 pr-2 py-1 border rounded text-sm text-right"
                                                            :class="{
                                                                'border-red-300 bg-red-50 text-red-700 font-semibold': obtenerPrecioMinimo(producto) && parseFloat(producto.precios[proveedor] || 0) === obtenerPrecioMinimo(producto) && obtenerPrecioMinimo(producto) > 0
                                                            }"
                                                            placeholder="0.00">
                                                    </div>
                                                </div>
                                            </td>
                                        </template>
                                        <td class="px-3 py-2 text-center">
                                            <button 
                                                @click="eliminarProducto(prodIndex)"
                                                class="text-red-600 hover:text-red-800 transition">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="productos.length === 0">
                                    <td :colspan="proveedores.length + 5" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                        <p>No hay productos agregados. Haz clic en "Agregar Producto" para comenzar.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="mt-4 flex justify-between items-center">
                        <button 
                            @click="agregarProducto()"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition">
                            <i class="fas fa-plus mr-2"></i> Agregar Producto
                        </button>
                        <div class="flex gap-2">
                            <button 
                                @click="cerrarModalCotizacion()"
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded transition">
                                Cancelar
                            </button>
                            <button 
                                @click="guardarCotizaciones()"
                                class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded transition">
                                <i class="fas fa-save mr-2"></i> Guardar Cotizaciones
                            </button>
                            <button 
                                x-show="tieneCotizacionesGuardadas"
                                @click="enviarCotizacionesAlGerente()"
                                class="px-4 py-2 bg-violet-500 hover:bg-violet-600 text-white rounded transition">
                                <i class="fas fa-envelope mr-2"></i> Enviar al Gerente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para manejar aprobaciones -->
<script>
    function aprobarSolicitud(solicitudId, nivel) {
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Ingrese un comentario (opcional)',
            input: 'textarea',
            inputPlaceholder: 'Comentario...',
            showCancelButton: true,
            confirmButtonText: 'Aprobar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            preConfirm: (comentario) => {
                return fetch(`/solicitudes/${solicitudId}/aprobar-${nivel}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ comentario: comentario || '' })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Error al aprobar');
                    }
                    return data;
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('¡Aprobado!', result.value.message, 'success').then(() => {
                    location.reload();
                });
            }
        }).catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
    }

    function rechazarSolicitud(solicitudId, nivel) {
        Swal.fire({
            title: '¿Rechazar solicitud?',
            text: 'Ingrese el motivo del rechazo',
            input: 'textarea',
            inputPlaceholder: 'Motivo del rechazo...',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe ingresar un motivo';
                }
            },
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            preConfirm: (comentario) => {
                return fetch(`/solicitudes/${solicitudId}/rechazar-${nivel}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ comentario: comentario })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Error al rechazar');
                    }
                    return data;
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Rechazada', result.value.message, 'info').then(() => {
                    location.reload();
                });
            }
        }).catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
    }
</script>
