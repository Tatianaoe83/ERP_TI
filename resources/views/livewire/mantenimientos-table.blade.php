<div>
    <div class="mb-3 d-flex flex-wrap align-items-end gap-2">
        <div class="flex-grow-1" style="min-width: 260px;">
            <label for="buscar-mantenimientos" class="mb-1 text-sm text-[#101D49] dark:text-white">Buscar</label>
            <div class="input-group">
                <input
                    id="buscar-mantenimientos"
                    type="text"
                    wire:model.debounce.400ms="search"
                    class="form-control"
                    placeholder="Empleado, gerencia, folio, comentario, tipo o estatus..."
                >
                @if($search !== '')
                    <div class="input-group-append">
                        <button type="button" wire:click="limpiarBusqueda" class="btn btn-outline-secondary">
                            Limpiar
                        </button>
                    </div>
                @endif
            </div>
        </div>
        <div>
            <label for="anio-mantenimientos" class="mb-1 text-sm text-[#101D49] dark:text-white">Año</label>
            <select
                id="anio-mantenimientos"
                wire:model="anio"
                class="form-control"
            >
                <option value="todos">Todos</option>
                @foreach($aniosDisponibles as $anioDisponible)
                    <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="estatus-mantenimientos" class="mb-1 text-sm text-[#101D49] dark:text-white">Filtrar por estatus</label>
            <select
                id="estatus-mantenimientos"
                wire:model="estatus"
                class="form-control"
            >
                <option value="pendiente">Pendientes</option>
                <option value="requiere_asignacion">Requiere asignación</option>
                <option value="realizado">Completados</option>
                <option value="todos">Todos</option>
            </select>
        </div>
        <div>
            <label for="por-pagina-mantenimientos" class="mb-1 text-sm text-[#101D49] dark:text-white">Registros por página</label>
            <select
                id="por-pagina-mantenimientos"
                wire:model="perPage"
                class="form-control"
            >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    
    </div>

    <div class="mant-table-summary mb-3">
        @if($mantenimientos->total() > 0)
            Mostrando {{ $mantenimientos->firstItem() }}-{{ $mantenimientos->lastItem() }} de {{ $mantenimientos->total() }} registro(s).
        @else
            Total: 0 registro(s).
        @endif
    </div>

    @if($mantenimientos->isEmpty())
        <div class="mant-empty-state">
            <i class="fas fa-info-circle"></i>
            <span>No hay mantenimientos para este filtro.</span>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Gerencia</th>
                        <th>Estatus</th>
                        <th>Tipo</th>
                        <th>Fecha de mantenimiento</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mantenimientos as $item)
                        @php
                            $estatusVisual = $item->EstatusMantenimiento;
                            $estatusClase = $estatusVisual === 'Realizado' ? 'mant-status-success' : (in_array($estatusVisual, ['Baja', 'Sin persona física']) ? 'mant-status-warning' : 'mant-status-danger');
                            $empleadoActualId = optional($item->inventarioEquipo)->EmpleadoID ?: $item->EmpleadoID;
                        @endphp
                        <tr wire:key="mant-{{ $item->id }}">
                            <td>
                                <div class="mant-employee-name">{{ $item->NombreEmpleado }}</div>
                            </td>
                            <td>{{ $item->NombreGerencia ?? '-' }}</td>
                            <td>
                                <span class="mant-status-badge {{ $estatusClase }}">
                                    {{ $estatusVisual }}
                                </span>
                            </td>
                            <td>{{ $item->TipoMantenimiento }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->FechaReprogramada ?: $item->FechaMantenimiento)->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</td>
                            <td style="min-width: 260px;">
                                <div class="mant-actions">
                                    <button
                                        type="button"
                                        wire:click="abrirDetalle({{ $item->id }})"
                                        class="btn btn-link p-0 text-primary text-decoration-none mant-action-link"
                                    >
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </button>

                                    @can('editar-mantenimientos')
                                        @if($item->RequierePersonaFisica && $item->Estatus !== 'Realizado')
                                            @can('transferir-inventario')
                                                @if($empleadoActualId)
                                                    <a
                                                        href="{{ route('inventarios.transferir', $empleadoActualId) }}"
                                                        class="btn btn-link p-0 text-warning text-decoration-none mant-action-link"
                                                        title="Asignar el equipo a una persona tipo FISICA conservando la fecha programada"
                                                    >
                                                        <i class="fas fa-exchange-alt mr-1"></i> Asignar a persona FISICA
                                                    </a>
                                                @else
                                                    <span class="text-warning font-weight-bold mant-action-link">Asignar a persona FISICA</span>
                                                @endif
                                            @else
                                                <span class="text-warning font-weight-bold mant-action-link">Requiere persona FISICA</span>
                                            @endcan
                                        @elseif($item->Estatus !== 'Realizado')
                                            <button
                                                type="button"
                                                wire:click="abrirReprogramar({{ $item->id }})"
                                                class="btn btn-link p-0 text-decoration-none mant-action-link"
                                                style="color: #6f42c1;"
                                            >
                                                <i class="fas fa-calendar-alt mr-1"></i> Reprogramar
                                            </button>
                                            <form
                                                action="{{ route('mantenimientos.realizado', $item) }}"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirmarMantenimientoRealizado(event, this);"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-link p-0 text-success text-decoration-none mant-action-link" title="Registrar mantenimiento realizado">
                                                    <i class="fas fa-check-circle mr-1"></i> Realizado
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-success mant-action-link">
                                                <i class="fas fa-check-circle mr-1"></i> Completado
                                            </span>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="mant-table-summary">
                Página {{ $mantenimientos->currentPage() }} de {{ $mantenimientos->lastPage() }}
            </div>
            <div>
                {{ $mantenimientos->links() }}
            </div>
        </div>
    @endif

    @if($modalReprogramarAbierto)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, .45);" wire:click.self="cerrarReprogramar">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reprogramar mantenimiento</h5>
                        <button type="button" class="close" wire:click="cerrarReprogramar" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="fecha-reprogramada">Nueva fecha</label>
                            <input
                                id="fecha-reprogramada"
                                type="date"
                                wire:model.defer="fechaReprogramada"
                                class="form-control @error('fechaReprogramada') is-invalid @enderror"
                            >
                            @error('fechaReprogramada')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="comentario-reprogramacion">Comentario</label>
                            <textarea
                                id="comentario-reprogramacion"
                                wire:model.defer="comentario"
                                class="form-control @error('comentario') is-invalid @enderror"
                                rows="4"
                                placeholder="Motivo por el que no se realizó a tiempo o seguimiento..."
                            ></textarea>
                            @error('comentario')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn mant-modal-btn mant-modal-btn-secondary" wire:click="cerrarReprogramar">Cancelar</button>
                        <button type="button" class="btn mant-modal-btn mant-modal-btn-primary" wire:click="guardarReprogramacion" wire:loading.attr="disabled">
                            Guardar reprogramación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($modalDetalleAbierto)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, .45);" wire:click.self="cerrarDetalle">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalle del mantenimiento</h5>
                        <button type="button" class="close" wire:click="cerrarDetalle" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            @foreach($detalle as $label => $valor)
                                <div class="col-md-6 mb-3">
                                    <div class="small text-muted">{{ $label }}</div>
                                    <div class="font-weight-bold">{{ $valor }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn mant-modal-btn mant-modal-btn-secondary" wire:click="cerrarDetalle">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @once
        @push('third_party_scripts')
            <script>
                function confirmarMantenimientoRealizado(event, form) {
                    event.preventDefault();

                    if (typeof Swal === 'undefined') {
                        if (confirm('¿Confirmas que este mantenimiento fue realizado?')) {
                            form.submit();
                        }

                        return false;
                    }

                    Swal.fire({
                        title: '¿Marcar como realizado?',
                        text: 'Se guardará con tu usuario y la fecha actual.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, marcar realizado',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });

                    return false;
                }

                window.addEventListener('mantenimiento-seguimiento-guardado', function () {
                    if (typeof Swal === 'undefined') {
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Seguimiento guardado',
                        text: 'El comentario o la fecha reprogramada se guardó correctamente.',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#28a745'
                    });
                });
            </script>
        @endpush
    @endonce
</div>

@once
    @push('third_party_stylesheets')
        <style>
            .mant-employee-name {
                color: #101D49;
                font-weight: 600;
                line-height: 1.35;
            }

            .mant-assignment-alert {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                border-radius: 10px;
                color: #9a3412;
                padding: 8px 10px;
            }

            .mant-alert-title {
                font-size: 12px;
                font-weight: 700;
                line-height: 1.25;
            }

            .mant-alert-text {
                color: #c2410c;
                font-size: 12px;
                line-height: 1.25;
                margin-top: 2px;
            }

            .mant-status-badge {
                border-radius: 999px;
                display: inline-flex;
                font-size: 12px;
                font-weight: 700;
                justify-content: center;
                line-height: 1.2;
                min-width: 112px;
                padding: 8px 12px;
                text-align: center;
                white-space: normal;
            }

            .mant-status-success {
                background: #dcfce7;
                color: #166534;
            }

            .mant-status-danger {
                background: #fee2e2;
                color: #b91c1c;
            }

            .mant-status-warning {
                background: #ffedd5;
                color: #9a3412;
            }

            .mant-actions {
                align-items: flex-start;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .mant-action-link {
                font-size: 13px;
                font-weight: 700;
                line-height: 1.25;
            }

            .mant-table-summary {
                color: #64748b;
                font-size: 13px;
                font-weight: 600;
            }

            .mant-empty-state {
                align-items: center;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                color: #64748b;
                display: flex;
                font-size: 14px;
                gap: 10px;
                margin: 0;
                padding: 14px 16px;
            }

            .mant-empty-state i {
                color: #94a3b8;
                font-size: 15px;
            }

            .mant-modal-btn {
                border: 0;
                border-radius: 10px;
                font-weight: 700;
                padding: 10px 18px;
                transition: background-color .15s ease, box-shadow .15s ease, transform .15s ease;
            }

            .mant-modal-btn:focus {
                box-shadow: 0 0 0 .2rem rgba(124, 58, 237, .2);
            }

            .mant-modal-btn:hover {
                transform: translateY(-1px);
            }

            .mant-modal-btn-secondary {
                background: #e2e8f0;
                color: #334155;
            }

            .mant-modal-btn-secondary:hover,
            .mant-modal-btn-secondary:focus {
                background: #cbd5e1;
                color: #1e293b;
            }

            .mant-modal-btn-primary {
                background: #2563eb;
                color: #fff;
                box-shadow: 0 8px 18px rgba(37, 99, 235, .24);
            }

            .mant-modal-btn-primary:hover,
            .mant-modal-btn-primary:focus {
                background: #1d4ed8;
                color: #fff;
            }

            .mant-modal-btn-primary:disabled {
                background: #93c5fd;
                box-shadow: none;
                cursor: not-allowed;
            }

            .dark .mant-employee-name {
                color: #fff;
            }

            .dark .mant-assignment-alert {
                background: rgba(154, 52, 18, .18);
                border-color: rgba(251, 146, 60, .35);
            }

            .dark .mant-empty-state {
                background: rgba(15, 23, 42, .35);
                border-color: rgba(148, 163, 184, .25);
                color: #cbd5e1;
            }

            .dark .mant-table-summary {
                color: #cbd5e1;
            }

            .dark .mant-modal-btn-secondary {
                background: #334155;
                color: #e2e8f0;
            }

            .dark .mant-modal-btn-secondary:hover,
            .dark .mant-modal-btn-secondary:focus {
                background: #475569;
                color: #fff;
            }

            .dark .mant-modal-btn-primary {
                background: #3b82f6;
                color: #fff;
                box-shadow: 0 8px 18px rgba(59, 130, 246, .26);
            }

            .dark .mant-modal-btn-primary:hover,
            .dark .mant-modal-btn-primary:focus {
                background: #2563eb;
                color: #fff;
            }
        </style>
    @endpush
@endonce
