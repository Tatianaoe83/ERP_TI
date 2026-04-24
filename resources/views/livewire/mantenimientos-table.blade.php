<div>
    <div class="mb-3 d-flex flex-wrap align-items-end gap-2">
        <div>
            <label for="estatus-mantenimientos" class="mb-1 text-sm text-[#101D49] dark:text-white">Filtrar por estatus</label>
            <select
                id="estatus-mantenimientos"
                wire:model="estatus"
                class="form-control"
            >
                <option value="pendiente">Pendientes</option>
                <option value="realizado">Completados</option>
                <option value="todos">Todos</option>
            </select>
        </div>
    
    </div>

    @if($mantenimientos->isEmpty())
        <div class="alert alert-info mb-0">
            No hay mantenimientos para este filtro.
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
                        <th>Realizado por</th>
                        <th>Fecha realizado</th>
                        @can('editar-mantenimientos')
                            <th>Acción</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach($mantenimientos as $item)
                        <tr wire:key="mant-{{ $item->id }}">
                            <td>{{ $item->NombreEmpleado }}</td>
                            <td>{{ $item->NombreGerencia ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $item->Estatus === 'Realizado' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $item->Estatus }}
                                </span>
                            </td>
                            <td>{{ $item->TipoMantenimiento }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->FechaMantenimiento)->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</td>
                            <td>{{ $item->RealizadoPor ? ($usuariosRealizacion[$item->RealizadoPor] ?? '-') : '-' }}</td>
                            <td>{{ $item->FechaRealizado ? \Carbon\Carbon::parse($item->FechaRealizado)->format('d/m/Y H:i') : '-' }}</td>
                            @can('editar-mantenimientos')
                                <td>
                                    @if($item->Estatus !== 'Realizado')
                                        <form
                                            action="{{ route('mantenimientos.realizado', $item) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirmarMantenimientoRealizado(event, this);"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success" title="Registrar mantenimiento realizado">
                                                <i class="fas fa-check-square mr-1"></i> Realizado
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-success font-weight-bold">Completado</span>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $mantenimientos->links() }}
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
            </script>
        @endpush
    @endonce
</div>
