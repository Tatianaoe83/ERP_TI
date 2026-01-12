<div
    x-data="{
        vista: 'kanban',
        cambiarVista(tipo) {
            this.vista = tipo;
            localStorage.setItem('solicitudesVista', tipo);
        },
        init() {
            const vistaGuardada = localStorage.getItem('solicitudesVista') || 'kanban';
            this.vista = vistaGuardada;
        }
    }"
    class="space-y-4 w-full max-w-full overflow-x-hidden">
    
    <!-- Selector de Vista -->
    <div class="flex justify-end gap-2 mb-4">
        <button
            @click="cambiarVista('kanban')"
            :class="vista === 'kanban' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
            class="px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2">
            <i class="fas fa-th-large"></i>
            <span>Kanban</span>
        </button>
        <button
            @click="cambiarVista('tabla')"
            :class="vista === 'tabla' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
            class="px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2">
            <i class="fas fa-table"></i>
            <span>Tabla</span>
        </button>
    </div>

    <!-- Vista Kanban con Flujo de Aprobación -->
    <div x-show="vista === 'kanban'" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        @php
            $columnas = [
                'pendiente_supervisor' => ['titulo' => 'Pendiente Supervisor', 'icono' => 'user-tie', 'color' => 'yellow'],
                'pendiente_gerencia' => ['titulo' => 'Pendiente Gerencia', 'icono' => 'building', 'color' => 'orange'],
                'pendiente_administracion' => ['titulo' => 'Pendiente Administración', 'icono' => 'briefcase', 'color' => 'purple'],
                'pendiente_cotizacion' => ['titulo' => 'Pendiente Cotización TI', 'icono' => 'calculator', 'color' => 'blue'],
                'rechazadas' => ['titulo' => 'Rechazadas', 'icono' => 'times-circle', 'color' => 'red'],
                'completadas' => ['titulo' => 'Completadas', 'icono' => 'check-circle', 'color' => 'green'],
            ];
        @endphp

        @foreach ($columnas as $key => $columna)
        <div class="bg-gray-50 rounded-lg p-4 min-h-[500px]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-{{ $columna['icono'] }} text-{{ $columna['color'] }}-500"></i>
                    <h3 class="font-semibold text-gray-700 text-sm">{{ $columna['titulo'] }}</h3>
                </div>
                <span class="bg-{{ $columna['color'] }}-200 text-{{ $columna['color'] }}-700 px-2 py-1 rounded-full text-xs font-medium">
                    {{ count($solicitudesStatus[$key] ?? []) }}
                </span>
            </div>
            <div class="space-y-3 max-h-[600px] overflow-y-auto">
                @php
                    // Ordenar las solicitudes de esta columna por fecha (más recientes primero)
                    $solicitudesColumna = collect($solicitudesStatus[$key] ?? [])
                        ->sortByDesc(function($solicitud) {
                            return $solicitud->created_at ? $solicitud->created_at->timestamp : 0;
                        })
                        ->values();
                @endphp
                @forelse ($solicitudesColumna as $solicitud)
                @php
                    $empleado = $solicitud->empleadoid;
                    $gerencia = $solicitud->gerenciaid;
                    $obra = $solicitud->obraid;
                    
                    // Formatear nombre del empleado
                    $partes = preg_split('/\s+/', trim($empleado->NombreEmpleado ?? ''));
                    if (count($partes) >= 3) array_splice($partes, 1, 1);
                    $nombreFormateado = \Illuminate\Support\Str::of(implode(' ', $partes))->title();
                    
                    // Determinar color según estatus
                    $colorBorde = match($key) {
                        'pendiente_supervisor' => 'border-yellow-300',
                        'pendiente_gerencia' => 'border-orange-300',
                        'pendiente_administracion' => 'border-purple-300',
                        'pendiente_cotizacion' => 'border-blue-300',
                        'rechazadas' => 'border-red-300',
                        'completadas' => 'border-green-300',
                        default => 'border-gray-300'
                    };
                    
                    // Determinar estatus real para mostrar
                    $estatusMostrar = $solicitud->Estatus ?? 'Pendiente';
                    if ($estatusMostrar === 'Pendiente' || empty($estatusMostrar)) {
                        if ($solicitud->AprobacionSupervisor === 'Aprobado') {
                            if ($solicitud->AprobacionGerencia === 'Aprobado') {
                                if ($solicitud->AprobacionAdministracion === 'Aprobado') {
                                    $estatusMostrar = 'Pendiente Cotización TI';
                                } else {
                                    $estatusMostrar = 'Pendiente Aprobación Administración';
                                }
                            } else {
                                $estatusMostrar = 'Pendiente Aprobación Gerencia';
                            }
                        } else {
                            $estatusMostrar = 'Pendiente Aprobación Supervisor';
                        }
                    }

                    // Verificar si el usuario puede aprobar en este nivel
                    $puedeAprobar = false;
                    $nivelAprobacion = '';
                    
                    if (auth()->check()) {
                        $usuarioActual = auth()->user();
                        $usuarioEmpleado = \App\Models\Empleados::where('Correo', $usuarioActual->email)->first();
                        $usuarioEmpleadoID = $usuarioEmpleado->EmpleadoID ?? null;
                        
                        if ($key === 'pendiente_supervisor' && $solicitud->SupervisorID == $usuarioEmpleadoID) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'supervisor';
                        } elseif ($key === 'pendiente_gerencia' && $solicitud->GerenciaID && auth()->user()->can('aprobar-solicitudes-gerencia')) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'gerencia';
                        } elseif ($key === 'pendiente_administracion' && auth()->user()->can('aprobar-solicitudes-administracion')) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'administracion';
                        } elseif ($key === 'pendiente_cotizacion' && (auth()->user()->can('crear-cotizaciones-ti') || auth()->user()->can('ver-tickets'))) {
                            $puedeAprobar = true;
                            $nivelAprobacion = 'cotizacion';
                        }
                    }
                @endphp
                <div class="bg-white rounded-lg border-2 {{ $colorBorde }} hover:shadow-md transition p-4 cursor-pointer" 
                     @click="abrirModalSolicitud({{ $solicitud->SolicitudID }})">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 text-sm mb-1">
                                Solicitud #{{ $solicitud->SolicitudID }}
                            </div>
                            @if($key !== 'rechazadas' && $key !== 'completadas')
                            <div class="text-xs text-gray-500 mb-2">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $solicitud->created_at ? $solicitud->created_at->diffForHumans() : '' }}
                            </div>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $solicitud->created_at ? $solicitud->created_at->format('d/m/Y') : 'N/A' }}
                        </div>
                    </div>
                    
                    <!-- Información del empleado -->
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <i class="fas fa-user text-gray-400 text-xs"></i>
                            <span class="font-medium text-xs">{{ $nombreFormateado }}</span>
                        </div>
                        
                        @if($gerencia)
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <i class="fas fa-building text-gray-400"></i>
                            <span class="truncate">{{ $gerencia->NombreGerencia ?? 'N/A' }}</span>
                        </div>
                        @endif
                        
                        @if($solicitud->Motivo)
                        <div class="mt-2">
                            <div class="text-xs font-medium text-gray-700 mb-1">{{ Str::limit($solicitud->Motivo, 30) }}</div>
                        </div>
                        @endif

                        <!-- Estado de aprobaciones -->
                        <div class="mt-3 pt-2 border-t border-gray-200">
                            <div class="flex items-center gap-2 text-xs mb-1">
                                <span class="font-medium text-gray-600">Flujo de aprobación:</span>
                            </div>
                            <div class="space-y-1">
                                @php
                                    $aprobaciones = [
                                        'Supervisor' => [
                                            'estado' => $solicitud->AprobacionSupervisor,
                                            'fecha' => $solicitud->FechaAprobacionSupervisor,
                                            'aprobador' => $solicitud->supervisorAprobador
                                        ],
                                        'Gerencia' => [
                                            'estado' => $solicitud->AprobacionGerencia,
                                            'fecha' => $solicitud->FechaAprobacionGerencia,
                                            'aprobador' => $solicitud->gerenteAprobador
                                        ],
                                        'Administración' => [
                                            'estado' => $solicitud->AprobacionAdministracion,
                                            'fecha' => $solicitud->FechaAprobacionAdministracion,
                                            'aprobador' => $solicitud->administradorAprobador
                                        ],
                                    ];
                                @endphp

                                @foreach ($aprobaciones as $nivel => $info)
                                <div class="flex items-center gap-2 text-xs">
                                    @if($info['estado'] === 'Aprobado')
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-green-600 font-medium">{{ $nivel }}</span>
                                    @elseif($info['estado'] === 'Rechazado')
                                        <i class="fas fa-times-circle text-red-500"></i>
                                        <span class="text-red-600 font-medium">{{ $nivel }}</span>
                                    @else
                                        <i class="far fa-circle text-gray-400"></i>
                                        <span class="text-gray-500">{{ $nivel }}</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Cotizaciones si existe -->
                        @if($key === 'pendiente_cotizacion' || $key === 'completadas')
                        @if($solicitud->cotizaciones && $solicitud->cotizaciones->count() > 0)
                        <div class="mt-2 pt-2 border-t border-gray-200">
                            <div class="text-xs text-gray-600 mb-1">
                                <i class="fas fa-file-invoice-dollar mr-1"></i>
                                Cotizaciones: {{ $solicitud->cotizaciones->count() }}/3
                            </div>
                        </div>
                        @endif
                        @endif

                        <!-- Botones de acción si puede aprobar -->
                        @if($puedeAprobar && $key !== 'rechazadas' && $key !== 'completadas')
                        <div class="mt-3 pt-2 border-t border-gray-200 flex gap-2" @click.stop>
                            <button 
                                @click.stop="aprobarSolicitud({{ $solicitud->SolicitudID }}, '{{ $nivelAprobacion }}')"
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white text-xs py-1.5 px-2 rounded transition">
                                <i class="fas fa-check mr-1"></i> Aprobar
                            </button>
                            <button 
                                @click.stop="rechazarSolicitud({{ $solicitud->SolicitudID }}, '{{ $nivelAprobacion }}')"
                                class="flex-1 bg-red-500 hover:bg-red-600 text-white text-xs py-1.5 px-2 rounded transition">
                                <i class="fas fa-times mr-1"></i> Rechazar
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p class="text-xs">No hay solicitudes</p>
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    <!-- Vista Tabla -->
    <div x-show="vista === 'tabla'" x-transition class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estatus</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aprobaciones</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cotizaciones</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        // Combinar todas las solicitudes, eliminar duplicados y ordenar por fecha de creación (más recientes primero)
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
                        
                        // Determinar el estatus real basándose en el flujo de aprobación
                        $estatusReal = $solicitud->Estatus ?? 'Pendiente';
                        if (in_array($solicitud->Estatus, ['Pendiente', null, '']) || empty($solicitud->Estatus)) {
                            // Si tiene estatus antiguo, determinar el estado real según aprobaciones
                            if ($solicitud->AprobacionSupervisor === 'Aprobado') {
                                if ($solicitud->AprobacionGerencia === 'Aprobado') {
                                    if ($solicitud->AprobacionAdministracion === 'Aprobado') {
                                        $cotizacionesCount = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;
                                        $estatusReal = ($cotizacionesCount >= 3) ? 'Completada' : 'Pendiente Cotización TI';
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
                        
                        $colorEstatus = match($estatusReal) {
                            'Pendiente Aprobación Supervisor' => 'bg-yellow-100 text-yellow-800',
                            'Pendiente Aprobación Gerencia' => 'bg-orange-100 text-orange-800',
                            'Pendiente Aprobación Administración' => 'bg-purple-100 text-purple-800',
                            'Pendiente Cotización TI' => 'bg-blue-100 text-blue-800',
                            'Rechazada' => 'bg-red-100 text-red-800',
                            'Completada' => 'bg-green-100 text-green-800',
                            'Pendiente' => 'bg-yellow-100 text-yellow-800', // Fallback para valores antiguos
                            default => 'bg-gray-100 text-gray-800'
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">#{{ $solicitud->SolicitudID }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">{{ $nombreFormateado }}</div>
                            <div class="text-xs text-gray-500">{{ $empleado->Correo ?? 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 font-medium">{{ Str::limit($solicitud->Motivo ?? 'N/A', 30) }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($solicitud->DescripcionMotivo ?? '', 50) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-block px-2 py-1 rounded text-xs font-medium {{ $colorEstatus }}">
                                {{ $estatusReal }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1 text-xs">
                                <div class="flex items-center gap-1">
                                    @if($solicitud->AprobacionSupervisor === 'Aprobado')
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-green-600">Sup.</span>
                                    @elseif($solicitud->AprobacionSupervisor === 'Rechazado')
                                        <i class="fas fa-times-circle text-red-500"></i>
                                        <span class="text-red-600">Sup.</span>
                                    @else
                                        <i class="far fa-circle text-gray-400"></i>
                                        <span class="text-gray-500">Sup.</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($solicitud->AprobacionGerencia === 'Aprobado')
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-green-600">Ger.</span>
                                    @elseif($solicitud->AprobacionGerencia === 'Rechazado')
                                        <i class="fas fa-times-circle text-red-500"></i>
                                        <span class="text-red-600">Ger.</span>
                                    @else
                                        <i class="far fa-circle text-gray-400"></i>
                                        <span class="text-gray-500">Ger.</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($solicitud->AprobacionAdministracion === 'Aprobado')
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-green-600">Adm.</span>
                                    @elseif($solicitud->AprobacionAdministracion === 'Rechazado')
                                        <i class="fas fa-times-circle text-red-500"></i>
                                        <span class="text-red-600">Adm.</span>
                                    @else
                                        <i class="far fa-circle text-gray-400"></i>
                                        <span class="text-gray-500">Adm.</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($solicitud->cotizaciones && $solicitud->cotizaciones->count() > 0)
                                <span class="text-sm text-gray-900">{{ $solicitud->cotizaciones->count() }}/3</span>
                            @else
                                <span class="text-sm text-gray-400">0/3</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $solicitud->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $solicitud->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <button 
                                @click="abrirModalSolicitud({{ $solicitud->SolicitudID }})"
                                class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            <p>No hay solicitudes registradas</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts para manejar aprobaciones -->
<script>
    function aprobarSolicitud(solicitudId, nivel) {
        // Implementar lógica de aprobación con modal
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

    function abrirModalSolicitud(solicitudId) {
        // Implementar modal para ver detalles completos de la solicitud
        // Por ahora solo recargar para mostrar más información
        window.location.href = `/solicitudes/${solicitudId}/ver`;
    }
</script>
