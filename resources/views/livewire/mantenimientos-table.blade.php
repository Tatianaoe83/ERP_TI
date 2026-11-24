<div>
    @once
    @push('third_party_stylesheets')
    <style>
        .mant-modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1rem;
            width: 100%;
        }
        .mant-modal-header .modal-title {
            margin: 0;
            flex: 1 1 auto;
            min-width: 0;
            padding-right: 1.5rem;
        }
        .mant-modal-close {
            flex: 0 0 auto;
            margin-left: auto !important;
        }
        .mant-modal-btn {
            border: 0;
            border-radius: 10px;
            font-weight: 700;
            padding: 10px 18px;
            line-height: 1.2;
            cursor: pointer;
        }
        .mant-modal-btn-secondary {
            background: #e2e8f0 !important;
            color: #1e293b !important;
        }
        .dark .mant-modal-btn-secondary {
            background: #64748b !important;
            color: #fff !important;
        }
        .dark .mant-modal-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .modal-content .form-group > label,
        .modal-content .form-group .control-label {
            color: #334155;
        }
        .dark .modal-content .form-group > label,
        .dark .modal-content .form-group .control-label {
            color: #e5e7eb !important;
        }
        .mant-modal-btn-primary {
            background: #2563eb !important;
            color: #fff !important;
        }
        .mant-filters {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(140px, 1fr));
            gap: 0.75rem 1rem;
            align-items: start;
            padding: 0.9rem 1rem 0.35rem;
            margin-bottom: 0;
        }
        .mant-filters .form-group {
            display: flex !important;
            flex-direction: column;
            float: none !important;
            width: 100%;
            min-width: 0;
            margin-bottom: 0.65rem;
        }
        .mant-filters label {
            display: block !important;
            float: none !important;
            position: static !important;
            width: auto !important;
            margin: 0 0 0.3rem !important;
            color: #64748b !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        html.dark .mant-filters label,
        .dark .mant-filters label {
            color: #f3f4f6 !important;
        }
        html.dark .mant-filters .form-control,
        .dark .mant-filters .form-control {
            background-color: #111827;
            border-color: #374151;
            color: #f9fafb;
        }
        html.dark #mantenimientos-page .index-table thead th,
        .dark #mantenimientos-page .index-table thead th {
            color: #e5e7eb !important;
        }
        .mant-col-menu {
            min-width: 14rem;
            padding: 0.35rem;
            border-radius: 0.65rem;
        }
        .mant-col-menu .dropdown-item {
            border-radius: 0.4rem;
            font-size: 0.85rem;
        }
        .index-page__dt-footer .erp-page {
            margin: 0 !important;
        }
        @media (max-width: 768px) {
            .mant-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @endpush
    @endonce
    <div class="index-page__filters">
        <div class="form-group">
            <label for="anio-mantenimientos">Año</label>
            <select id="anio-mantenimientos" wire:model="anio" class="form-control">
                <option value="todos">Todos</option>
                @foreach($aniosDisponibles as $anioDisponible)
                    <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="estatus-mantenimientos">Estatus</label>
            <select id="estatus-mantenimientos" wire:model="estatus" class="form-control">
                <option value="pendiente">Pendientes</option>
                <option value="realizado">Completados</option>
                <option value="todos">Todos</option>
            </select>
        </div>
        <div class="form-group">
            <label for="por-pagina-mantenimientos">Por página</label>
            <select id="por-pagina-mantenimientos" wire:model="perPage" class="form-control">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
        <div class="form-group d-flex align-items-end">
            <button type="button" wire:click="limpiarFiltros" class="index-page__btn-primary">
                <i class="fa fa-times"></i> Limpiar filtros
            </button>
        </div>
    </div>

    <div class="index-page__card">

    @php
        $totalMant = $mantenimientos->total();
        $desdeMant = $mantenimientos->firstItem() ?: 0;
        $hastaMant = $mantenimientos->lastItem() ?: 0;
        $infoMant = $totalMant > 0
            ? 'Mostrando ' . $desdeMant . ' a ' . $hastaMant . ' de ' . $totalMant
            : 'Mostrando 0 a 0 de 0';
        $countMant = $totalMant === 1 ? '1 registro' : $totalMant . ' registros';
    @endphp
    <span id="mant-count-sync" class="d-none" data-total="{{ $totalMant }}" data-label="{{ $countMant }}"></span>

    @php
        $columnasMant = [
            'empleado' => 'Empleado',
            'gerencia' => 'Gerencia',
            'folio' => 'Folio',
            'estatus' => 'Estatus',
            'tipo' => 'Tipo',
            'fecha' => 'Fecha de mantenimiento',
        ];
    @endphp
    <div class="index-page__dt-toolbar">
        <div class="dropdown">
            <button type="button" class="index-page__colvis" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-columns"></i> Columnas
            </button>
            <div class="dropdown-menu mant-col-menu">
                @foreach($columnasMant as $clave => $titulo)
                    <button type="button" class="dropdown-item" wire:click="toggleColumna('{{ $clave }}')">
                        <i class="fas {{ in_array($clave, $columnasOcultas, true) ? 'fa-square' : 'fa-check-square' }} mr-2"></i>{{ $titulo }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="dataTables_filter">
            <label>
                Buscar
                <input
                    type="search"
                    wire:model.debounce.400ms="search"
                    placeholder="Buscar..."
                    aria-label="Buscar en la tabla"
                >
            </label>
        </div>
    </div>

    @if($mantenimientos->isEmpty())
        <div class="mant-empty-state">
            <i class="fas fa-info-circle"></i>
            <span>No hay mantenimientos para este filtro.</span>
        </div>
    @else
        <div class="index-page__table-wrap table-responsive">
            <table class="table index-table w-full align-middle">
                <thead>
                    <tr>
                        @foreach($columnasMant as $clave => $titulo)
                            @unless(in_array($clave, $columnasOcultas, true))
                                <th>{{ $titulo }}</th>
                            @endunless
                        @endforeach
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mantenimientos as $item)
                        @php
                            $estatusVisual = $item->EstatusMantenimiento;
                            $estatusClase = match ($estatusVisual) {
                                'Realizado' => 'erp-chip--ok',
                                'Baja' => 'erp-chip--alerta',
                                'Sin persona física' => 'erp-chip--acento',
                                default => 'erp-chip--aviso',
                            };
                        @endphp
                        <tr wire:key="mant-{{ $item->id }}">
                            @unless(in_array('empleado', $columnasOcultas, true))
                            <td>
                                <div class="mant-employee-name">{{ $item->NombreEmpleado }}</div>
                            </td>
                            @endunless
                            @unless(in_array('gerencia', $columnasOcultas, true))
                            <td>{{ $item->NombreGerencia ?? '-' }}</td>
                            @endunless
                            @unless(in_array('folio', $columnasOcultas, true))
                            <td>{{ $item->Folio ?: (optional($item->inventarioEquipo)->Folio ?: '-') }}</td>
                            @endunless
                            @unless(in_array('estatus', $columnasOcultas, true))
                            <td>
                                <span class="erp-chip {{ $estatusClase }}">
                                    {{ $estatusVisual }}
                                </span>
                            </td>
                            @endunless
                            @unless(in_array('tipo', $columnasOcultas, true))
                            <td>{{ $item->TipoMantenimiento }}</td>
                            @endunless
                            @unless(in_array('fecha', $columnasOcultas, true))
                            <td>{{ \Carbon\Carbon::parse($item->FechaReprogramada ?: $item->FechaMantenimiento)->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</td>
                            @endunless
                            <td>
                                <div class="index-actions">
                                    <button
                                        type="button"
                                        onclick="mantAbrirDetalle(this)"
                                        data-detalle="{{ json_encode($item->detalleModal) }}"
                                        class="index-action index-action--view"
                                        title="Ver"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    @can('editar-mantenimientos')
                                        @if($item->Estatus !== 'Realizado')
                                            <button
                                                type="button"
                                                onclick="mantAbrirReprogramar(this)"
                                                data-id="{{ $item->id }}"
                                                data-fecha="{{ optional($item->FechaReprogramada)->format('Y-m-d') }}"
                                                data-comentario="{{ $item->Comentario }}"
                                                class="index-action index-action--edit"
                                                title="Reprogramar"
                                            >
                                                <i class="fas fa-calendar-alt"></i>
                                            </button>
                                            <form
                                                action="{{ route('mantenimientos.realizado', $item) }}"
                                                method="POST"
                                                class="index-action-form"
                                                onsubmit="return confirmarMantenimientoRealizado(event, this);"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="index-action index-action--success" title="Registrar realizado">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="index-action index-action--success" title="Completado" style="cursor:default;">
                                                <i class="fas fa-check-circle"></i>
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
    @endif

    <div class="index-page__dt-footer">
        <div class="dataTables_info" role="status">{{ $infoMant }}</div>
        {{ $mantenimientos->links('livewire.paginacion-compacta') }}
    </div>
    </div>

    {{-- Se abre y cierra en el navegador (mantAbrirReprogramar); wire:ignore.self conserva el display. --}}
        <div id="mant-modal-reprogramar" wire:ignore.self class="modal fade show mant-lw-modal" tabindex="-1" style="display: none; background: rgba(0, 0, 0, .45);" onclick="if (event.target === this) mantCerrarModal(this)">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header mant-modal-header">
                        <h5 class="modal-title">Reprogramar mantenimiento</h5>
                        <button type="button" class="btn-close mant-modal-close" onclick="mantCerrarModal(this)" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="fecha-reprogramada">Nueva fecha</label>
                            <input
                                id="fecha-reprogramada"
                                min="{{ now()->toDateString() }}"
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
                                data-sin-mayusculas
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
                        <button type="button" class="mant-modal-btn mant-modal-btn-secondary" onclick="mantCerrarModal(this)">Cancelar</button>
                        <button type="button" class="mant-modal-btn mant-modal-btn-primary" onclick="mantGuardarReprogramacion(this)" wire:loading.attr="disabled" wire:target="guardarReprogramacion">
                            Guardar reprogramación
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <div id="mant-modal-detalle" wire:ignore class="modal fade show mant-lw-modal" tabindex="-1" style="display: none; background: rgba(0, 0, 0, .45);" onclick="if (event.target === this) mantCerrarModal(this)">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header mant-modal-header">
                        <h5 class="modal-title">Detalle del mantenimiento</h5>
                        <button type="button" class="btn-close mant-modal-close" onclick="mantCerrarModal(this)" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row" data-detalle-cuerpo></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="mant-modal-btn mant-modal-btn-secondary" onclick="mantCerrarModal(this)">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    @once
        @push('third_party_scripts')
            <script>
                // Los modales de detalle y reprogramar abren en el navegador, sin esperar a Livewire.
                function mantAbrirModal(modal) {
                    modal.classList.remove('is-closing');
                    modal.style.display = 'block';
                }

                function mantCerrarModal(el) {
                    var modal = el.closest('.mant-lw-modal');
                    if (!modal || modal.classList.contains('is-closing')) return;
                    modal.classList.add('is-closing');
                    setTimeout(function () {
                        modal.classList.remove('is-closing');
                        modal.style.display = 'none';
                    }, 140);
                }

                function mantAbrirDetalle(boton) {
                    var modal = document.getElementById('mant-modal-detalle');
                    var cuerpo = modal.querySelector('[data-detalle-cuerpo]');
                    var chips = { 'Realizado': 'erp-chip--ok', 'Baja': 'erp-chip--alerta', 'Sin persona física': 'erp-chip--acento' };
                    var detalle = JSON.parse(boton.dataset.detalle);
                    cuerpo.innerHTML = '';
                    Object.keys(detalle).forEach(function (etiqueta) {
                        var valor = detalle[etiqueta] == null ? '' : String(detalle[etiqueta]);
                        var col = document.createElement('div');
                        col.className = 'col-md-6 mb-3';
                        var label = document.createElement('div');
                        label.className = 'small text-muted';
                        label.textContent = etiqueta;
                        var dato = document.createElement(etiqueta === 'Estatus' ? 'span' : 'div');
                        dato.className = etiqueta === 'Estatus' ? 'erp-chip ' + (chips[valor] || 'erp-chip--aviso') : 'font-weight-bold';
                        dato.textContent = valor;
                        col.appendChild(label);
                        col.appendChild(dato);
                        cuerpo.appendChild(col);
                    });
                    mantAbrirModal(modal);
                }

                function mantAbrirReprogramar(boton) {
                    var modal = document.getElementById('mant-modal-reprogramar');
                    modal.dataset.id = boton.dataset.id;
                    // Quita errores de un intento anterior.
                    modal.querySelectorAll('.invalid-feedback').forEach(function (e) { e.remove(); });
                    modal.querySelectorAll('.is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); });
                    // El evento input deja el valor en wire:model.defer; se envía al guardar.
                    [['fecha-reprogramada', boton.dataset.fecha], ['comentario-reprogramacion', boton.dataset.comentario]].forEach(function (par) {
                        var campo = document.getElementById(par[0]);
                        campo.value = par[1] || '';
                        campo.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    mantAbrirModal(modal);
                }

                function mantGuardarReprogramacion(boton) {
                    var modal = boton.closest('.mant-lw-modal');
                    // Sin selector: "[wire:id]" necesita escapar los dos puntos y es fácil romperlo.
                    var raiz = boton;
                    while (raiz && !raiz.hasAttribute('wire:id')) raiz = raiz.parentElement;
                    window.Livewire.find(raiz.getAttribute('wire:id')).call('guardarReprogramacion', parseInt(modal.dataset.id, 10));
                }

                function syncMantenimientosCount() {
                    var sync = document.getElementById('mant-count-sync');
                    var el = document.querySelector('#mantenimientos-page .index-page__count');
                    if (!sync || !el) return;
                    el.textContent = sync.getAttribute('data-label') || '0 registros';
                }

                document.addEventListener('livewire:load', function () {
                    syncMantenimientosCount();
                    if (window.Livewire && typeof Livewire.hook === 'function') {
                        Livewire.hook('message.processed', function () {
                            syncMantenimientosCount();
                        });
                    }
                });
                document.addEventListener('DOMContentLoaded', syncMantenimientosCount);
                setTimeout(syncMantenimientosCount, 50);

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
                    var modal = document.getElementById('mant-modal-reprogramar');
                    if (modal) mantCerrarModal(modal);
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

            /* Misma entrada que los modales de tareas. Morphdom no recrea el nodo al
               re-renderizar, así que solo se anima al abrir. */
            @keyframes mantBackdropIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes mantModalIn {
                from { opacity: 0; transform: translateY(10px) scale(.97); }
                to   { opacity: 1; transform: translateY(0) scale(1); }
            }
            /* Salida más corta que la entrada; la dispara mantCerrarModal(). */
            @keyframes mantBackdropOut { from { opacity: 1; } to { opacity: 0; } }
            @keyframes mantModalOut {
                from { opacity: 1; transform: translateY(0) scale(1); }
                to   { opacity: 0; transform: translateY(6px) scale(.98); }
            }
            .mant-lw-modal { animation: mantBackdropIn .18s ease-out; }
            .mant-lw-modal .modal-dialog { animation: mantModalIn .2s cubic-bezier(.2, .8, .3, 1); }
            .mant-lw-modal.is-closing { animation: mantBackdropOut .14s ease-in forwards; }
            .mant-lw-modal.is-closing .modal-dialog { animation: mantModalOut .14s ease-in forwards; }
            /* Con "reducir movimiento" (Windows sin efectos de animación) queda solo el fade. */
            @media (prefers-reduced-motion: reduce) {
                .mant-lw-modal .modal-dialog { animation-name: mantBackdropIn; }
                .mant-lw-modal.is-closing .modal-dialog { animation-name: mantBackdropOut; }
            }

            .mant-modal-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 1rem;
                width: 100%;
            }

            .mant-modal-header .modal-title {
                margin: 0;
                flex: 1 1 auto;
                min-width: 0;
                padding-right: 1.5rem;
            }

            .mant-modal-close {
                flex: 0 0 auto;
                margin-left: auto !important;
                opacity: 0.75;
            }

            .mant-modal-close:hover,
            .mant-modal-close:focus {
                opacity: 1;
            }

            .mant-modal-btn {
                border: 0;
                border-radius: 10px;
                font-weight: 700;
                padding: 10px 18px;
                line-height: 1.2;
                cursor: pointer;
                transition: background-color .15s ease, box-shadow .15s ease, transform .15s ease;
            }

            .mant-modal-btn:focus {
                box-shadow: 0 0 0 .2rem rgba(124, 58, 237, .2);
            }

            .mant-modal-btn:hover {
                transform: translateY(-1px);
            }

            .mant-modal-btn-secondary {
                background: #e2e8f0 !important;
                color: #1e293b !important;
            }

            .mant-modal-btn-secondary:hover,
            .mant-modal-btn-secondary:focus {
                background: #cbd5e1 !important;
                color: #0f172a !important;
            }

            .mant-modal-btn-primary {
                background: #2563eb !important;
                color: #fff !important;
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

            .dark .mant-modal-close {
                filter: invert(1) grayscale(100%) brightness(200%);
            }

            .dark .mant-modal-btn-secondary {
                background: #64748b !important;
                color: #fff !important;
            }

            .dark .mant-modal-btn-secondary:hover,
            .dark .mant-modal-btn-secondary:focus {
                background: #94a3b8 !important;
                color: #0f172a !important;
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
