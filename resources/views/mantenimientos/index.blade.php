@extends('layouts.app')

@section('content')
<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="mb-0 text-[#101D49] dark:text-white">Mantenimientos</h3>

            @can('editar-mantenimientos')
            <button
                id="btn-abrir-programacion-mantenimientos"
                type="button"
                class="btn text-white font-weight-bold px-4 py-2"
                style="background: #7c3aed; border-radius: 10px; box-shadow: 0 8px 18px rgba(124, 58, 237, .25);"
            >
                <i class="fas fa-file-invoice-dollar mr-2"></i> Generar programación
            </button>
            @endcan
        </div>

        <div class="card-body">
            @livewire('mantenimientos-table')
        </div>
    </div>

    @can('editar-mantenimientos')
    @php
        $gerenciasMantenimiento = collect($gerencias ?? []);
    @endphp
    <div id="modal-programacion-mantenimientos" class="mant-modal-backdrop" style="display: none;">
        <div class="mant-modal-card">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div>
                    <h4 class="mb-1 text-[#101D49] dark:text-white">Generar programación</h4>
                    <p class="mb-0 text-muted">Selecciona la fecha inicial y arrastra las gerencias en el orden en que quieres programarlas.</p>
                </div>
                <button
                    id="btn-cerrar-programacion-mantenimientos"
                    type="button"
                    class="btn btn-link text-muted p-0"
                    style="font-size: 24px; line-height: 1;"
                    aria-label="Cerrar"
                >
                    &times;
                </button>
            </div>

            <form
                id="form-programacion-mantenimientos"
                action="{{ route('mantenimientos.generar') }}"
                method="POST"
                onsubmit="return confirmarGeneracionMantenimientos(event, this);"
            >
                @csrf

                <div class="form-group">
                    <label for="fecha_inicio" class="font-weight-bold text-[#101D49] dark:text-white">Fecha inicial</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="{{ now()->toDateString() }}">
                </div>

                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                    <label class="font-weight-bold mb-0 text-[#101D49] dark:text-white">Orden de gerencias</label>
                    <span class="text-muted small">{{ $gerenciasMantenimiento->count() }} gerencias</span>
                </div>

                <ul id="lista-gerencias-mantenimiento" class="mant-sortable-list">
                    @forelse($gerenciasMantenimiento as $gerencia)
                        <li class="mant-sortable-item" draggable="true" data-gerencia-id="{{ $gerencia->GerenciaID }}">
                            <input type="hidden" name="gerencias_orden[]" value="{{ $gerencia->GerenciaID }}">
                            <span class="mant-drag-handle"><i class="fas fa-grip-vertical"></i></span>
                            <div>
                                <strong>{{ $gerencia->NombreGerencia }}</strong>
                                <div class="text-muted small">
                                    {{ $gerencia->TotalPersonal }} colaborador(es), {{ $gerencia->TotalEquipos }} equipo(s)
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted py-3">No se encontraron gerencias con personal y equipo elegible.</li>
                    @endforelse
                </ul>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button id="btn-cancelar-programacion-mantenimientos" type="button" class="btn btn-light mr-2">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="btn text-white font-weight-bold px-4"
                        style="background: #7c3aed; border-radius: 10px;"
                        @if($gerenciasMantenimiento->isEmpty()) disabled @endif
                    >
                        Generar programación
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('third_party_stylesheets')
    <style>
        .mant-modal-backdrop {
            align-items: center;
            background: rgba(15, 23, 42, .55);
            bottom: 0;
            display: flex;
            justify-content: center;
            left: 0;
            padding: 24px;
            position: fixed;
            right: 0;
            top: 0;
            z-index: 1050;
        }

        .mant-modal-card {
            background: #fff;
            color: #101D49;
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .25);
            max-height: 90vh;
            max-width: 720px;
            overflow-y: auto;
            padding: 28px;
            width: 100%;
        }

        .mant-modal-card .text-muted {
            color: #64748b !important;
        }

        .mant-modal-card .form-control {
            background: #fff;
            border-color: #dbe3ef;
            color: #101D49;
        }

        .mant-sortable-list {
            list-style: none;
            margin: 0;
            max-height: 430px;
            overflow-y: auto;
            padding: 0;
        }

        .mant-sortable-item {
            align-items: center;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            cursor: grab;
            display: flex;
            gap: 14px;
            margin-bottom: 10px;
            padding: 12px 14px;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .mant-sortable-item strong {
            color: #101D49;
        }

        .mant-sortable-item:hover,
        .mant-sortable-item.mant-drag-over {
            border-color: #7c3aed;
            box-shadow: 0 10px 22px rgba(124, 58, 237, .12);
        }

        .mant-sortable-item.mant-dragging {
            opacity: .55;
            transform: scale(.99);
        }

        .mant-drag-handle {
            color: #7c3aed;
            font-size: 18px;
        }

        .dark .mant-modal-backdrop {
            background: rgba(2, 6, 23, .72);
        }

        .dark .mant-modal-card {
            background: #111827;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 24px 70px rgba(0, 0, 0, .55);
            color: #e5e7eb;
        }

        .dark .mant-modal-card .text-muted {
            color: #94a3b8 !important;
        }

        .dark .mant-modal-card .btn-link.text-muted {
            color: #cbd5e1 !important;
        }

        .dark .mant-modal-card .form-control {
            background: #1f2937;
            border-color: #374151;
            color: #f8fafc;
        }

        .dark .mant-modal-card .form-control:focus {
            background: #1f2937;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 .2rem rgba(139, 92, 246, .18);
            color: #f8fafc;
        }

        .dark .mant-sortable-item {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .mant-sortable-item strong {
            color: #f8fafc;
        }

        .dark .mant-sortable-item:hover,
        .dark .mant-sortable-item.mant-drag-over {
            border-color: #8b5cf6;
            box-shadow: 0 10px 22px rgba(139, 92, 246, .18);
        }

        .dark #btn-cancelar-programacion-mantenimientos {
            background: #374151;
            border-color: #4b5563;
            color: #f8fafc;
        }
    </style>
@endpush

@push('third_party_scripts')
    <script>
        function abrirModalProgramacionMantenimientos() {
            const modal = document.getElementById('modal-programacion-mantenimientos');
            if (!modal) {
                return;
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalProgramacionMantenimientos() {
            const modal = document.getElementById('modal-programacion-mantenimientos');
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function inicializarOrdenGerenciasMantenimiento() {
            const lista = document.getElementById('lista-gerencias-mantenimiento');
            if (!lista) {
                return;
            }

            let itemArrastrado = null;

            lista.querySelectorAll('.mant-sortable-item').forEach((item) => {
                item.addEventListener('dragstart', () => {
                    itemArrastrado = item;
                    item.classList.add('mant-dragging');
                });

                item.addEventListener('dragend', () => {
                    item.classList.remove('mant-dragging');
                    itemArrastrado = null;
                    lista.querySelectorAll('.mant-drag-over').forEach((elemento) => {
                        elemento.classList.remove('mant-drag-over');
                    });
                });

                item.addEventListener('dragover', (event) => {
                    event.preventDefault();

                    if (!itemArrastrado || itemArrastrado === item) {
                        return;
                    }

                    item.classList.add('mant-drag-over');
                    const rect = item.getBoundingClientRect();
                    const insertarDespues = event.clientY > rect.top + rect.height / 2;

                    lista.insertBefore(itemArrastrado, insertarDespues ? item.nextSibling : item);
                });

                item.addEventListener('dragleave', () => {
                    item.classList.remove('mant-drag-over');
                });
            });
        }

        function confirmarGeneracionMantenimientos(event, form) {
            event.preventDefault();

            const fechaInicio = form.querySelector('[name="fecha_inicio"]').value;
            const anio = fechaInicio ? new Date(fechaInicio + 'T00:00:00').getFullYear() : new Date().getFullYear();
            const totalGerencias = form.querySelectorAll('[name="gerencias_orden[]"]').length;
            const mensaje = `Se creará la lista del ${anio}, usando el orden seleccionado. Si ya existe una lista para ese corte, no se generará otra.`;

            if (typeof Swal === 'undefined') {
                if (confirm(mensaje)) {
                    form.submit();
                }

                return false;
            }

            Swal.fire({
                title: '¿Generar programación?',
                text: mensaje,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, generar lista',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#101D49',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });

            return false;
        }

        document.addEventListener('DOMContentLoaded', () => {
            inicializarOrdenGerenciasMantenimiento();

            const botonAbrir = document.getElementById('btn-abrir-programacion-mantenimientos');
            if (botonAbrir) {
                botonAbrir.addEventListener('click', abrirModalProgramacionMantenimientos);
            }

            const botonCerrar = document.getElementById('btn-cerrar-programacion-mantenimientos');
            if (botonCerrar) {
                botonCerrar.addEventListener('click', cerrarModalProgramacionMantenimientos);
            }

            const botonCancelar = document.getElementById('btn-cancelar-programacion-mantenimientos');
            if (botonCancelar) {
                botonCancelar.addEventListener('click', cerrarModalProgramacionMantenimientos);
            }

            const modal = document.getElementById('modal-programacion-mantenimientos');
            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        cerrarModalProgramacionMantenimientos();
                    }
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    cerrarModalProgramacionMantenimientos();
                }
            });
        });

        @if(session('sweetalert_success'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: @json(session('sweetalert_success')),
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#28a745'
                });
            }
        @endif

        @if(session('sweetalert_warning'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: @json(session('sweetalert_warning')),
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#ffc107'
                });
            }
        @endif
    </script>
@endpush
