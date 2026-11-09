@extends('layouts.app')

@section('content')
<x-index-page title="Movimientos ERP" icon="fa-exchange-alt" :show-count="false" subtitle="Solicitudes de cargo en Neodata. El folio se captura a mano, igual que en el ERP.">
    <x-slot name="filters">
        <form method="GET" action="{{ route('movimientos-erp.index') }}" class="contents">
            <div class="form-group">
                <label for="estatus">Estatus</label>
                <select name="estatus" id="estatus" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach ([\App\Models\SolicitudMovimiento::PENDIENTE, \App\Models\SolicitudMovimiento::APROBADA, \App\Models\SolicitudMovimiento::APLICADA, \App\Models\SolicitudMovimiento::RECHAZADA] as $opcion)
                        <option value="{{ $opcion }}" {{ $estatus === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="q">Buscar</label>
                <input type="search" name="q" id="q" value="{{ $buscar }}" class="form-control" placeholder="Folio, proyecto, cargo o solicitante">
            </div>
            <div class="form-group d-flex align-items-end">
                <a href="{{ route('movimientos-erp.index') }}" class="index-page__btn-primary">
                    <i class="fa fa-times"></i> Limpiar filtros
                </a>
            </div>
        </form>
    </x-slot>

    <div class="index-page__table-wrap">
            <table class="index-table w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Solicitante</th>
                        <th>Proyecto</th>
                        <th>Estatus</th>
                        <th>Pasos</th>
                        <th>Folio</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        @php
                            $chip = [
                                \App\Models\SolicitudMovimiento::PENDIENTE => 'sol-status--pendiente',
                                \App\Models\SolicitudMovimiento::APROBADA => 'sol-status--aprobada',
                                \App\Models\SolicitudMovimiento::APLICADA => 'sol-status--listo',
                                \App\Models\SolicitudMovimiento::RECHAZADA => 'sol-status--rechazada',
                            ][$solicitud->Estatus] ?? 'sol-status--default';
                            $vobo = $solicitud->pasos->firstWhere('stage', 'vobo');
                            $control = $solicitud->pasos->firstWhere('stage', 'control');
                            $cargo = $solicitud->lineas->pluck('cargo.Clave')->filter()->unique()->implode(', ') ?: ($solicitud->cargo->Clave ?? '');
                        @endphp
                        <tr>
                            <td><strong>#{{ $solicitud->SolicitudMovimientoID }}</strong></td>
                            <td>
                                <div>{{ $solicitud->solicitante->NombreEmpleado ?? '—' }}</div>
                                <div>{{ $solicitud->solicitante->Correo ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div>{{ $solicitud->obra->NombreObra ?? '—' }}</div>
                                @if ($cargo !== '')
                                    <div>{{ $cargo }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="erp-chip {{ $chip }}">{{ $solicitud->Estatus }}</span>
                            </td>
                            <td>
                                <div class="sol-approvals">
                                    @if ($vobo && $vobo->status === 'approved')
                                        <i class="fas fa-check-circle text-green-500" title="Visto bueno: aprobado{{ $vobo->approver ? ' · ' . $vobo->approver->NombreEmpleado : '' }}"></i>
                                    @elseif ($vobo && $vobo->status === 'rejected')
                                        <i class="fas fa-times-circle text-red-500" title="Visto bueno: rechazado{{ $vobo->approver ? ' · ' . $vobo->approver->NombreEmpleado : '' }}"></i>
                                    @else
                                        <i class="far fa-circle text-yellow-500" title="Visto bueno: pendiente{{ $vobo && $vobo->approver ? ' · ' . $vobo->approver->NombreEmpleado : '' }}"></i>
                                    @endif
                                    @php $aplico = $solicitud->aplicado_por ?: optional(optional($control)->decidedBy)->NombreEmpleado; @endphp
                                    @if ($control && $control->status === 'approved')
                                        <i class="fas fa-check-circle text-green-500" title="Neodata: aplicada por {{ $aplico ?: 'sin registro' }}"></i>
                                    @elseif ($control && $control->status === 'rejected')
                                        <i class="fas fa-times-circle text-red-500" title="Control: rechazado"></i>
                                    @elseif ($vobo && $vobo->status === 'approved')
                                        <i class="far fa-circle text-orange-500" title="Control: pendiente de aplicar"></i>
                                    @else
                                        <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Control: en espera del visto bueno"></i>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($solicitud->Folio)
                                    <span class="erp-chip erp-chip--ok">{{ $solicitud->Folio }}</span>
                                @else
                                    <span class="erp-chip erp-chip--neutro">N/A</span>
                                @endif
                            </td>
                            <td>{{ optional($solicitud->created_at)->format('d/m/Y') }}</td>
                            <td>
                                <div class="index-actions">
                                    <button type="button" class="index-action index-action--view" title="Ver" onclick="window.__abrirModalMovimiento({{ $solicitud->SolicitudMovimientoID }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No hay solicitudes de movimiento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    <div class="index-page__dt-footer">
        {{ $solicitudes->links() }}
    </div>
</x-index-page>
@endsection
