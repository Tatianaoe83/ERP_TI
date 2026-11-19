@extends('layouts.app')

@section('content')
@php $tabMov = request('tab') === 'productividad' ? 'productividad' : 'movimientos'; @endphp
<div data-app-tabset>
<x-index-page title="Movimientos ERP" icon="fa-exchange-alt" :show-count="false" subtitle="Solicitudes de cargo en Neodata. El folio se captura a mano, igual que en el ERP.">
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist">
            <a href="{{ route('movimientos-erp.index') }}" data-app-tab="movimientos" class="app-tabs__btn {{ $tabMov === 'movimientos' ? 'is-active' : '' }}">
                <i class="fas fa-exchange-alt"></i>
                <span>Movimientos</span>
            </a>
            <a href="{{ route('movimientos-erp.index', ['tab' => 'productividad']) }}" data-app-tab="productividad" class="app-tabs__btn {{ $tabMov === 'productividad' ? 'is-active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Productividad</span>
            </a>
        </div>
    </x-slot>
    @if($tabMov === 'productividad')
    <x-slot name="filters">
        @php
            $f = $productividad['filtros'] ?? [];
            $cat = $productividad['catalogo'] ?? ['anios' => [], 'obras' => [], 'meses' => []];
        @endphp
        <form method="GET" action="{{ route('movimientos-erp.index') }}" class="contents">
            <input type="hidden" name="tab" value="productividad">
            <div class="form-group">
                <label for="prod-anio">Año</label>
                <select name="anio" id="prod-anio" class="jz form-control">
                    <option value="0">Todos</option>
                    @foreach ($cat['anios'] as $anio)
                        <option value="{{ $anio }}" {{ (int) ($f['anio'] ?? 0) === (int) $anio ? 'selected' : '' }}>{{ $anio }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="prod-mes">Mes</label>
                <select name="mes" id="prod-mes" class="jz form-control">
                    <option value="0">Todos</option>
                    @foreach ($cat['meses'] as $numero => $nombre)
                        <option value="{{ $numero }}" {{ (int) ($f['mes'] ?? 0) === (int) $numero ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="prod-obra">Proyecto</label>
                <select name="obra_id" id="prod-obra" class="jz form-control">
                    <option value="0">Todos</option>
                    @foreach ($cat['obras'] as $obra)
                        <option value="{{ $obra->ObraID }}" {{ (int) ($f['obra_id'] ?? 0) === (int) $obra->ObraID ? 'selected' : '' }}>{{ $obra->NombreObra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="prod-estatus">Estatus</label>
                <select name="estatus_prod" id="prod-estatus" class="jz form-control">
                    <option value="">Todos</option>
                    @foreach ([\App\Models\SolicitudMovimiento::PENDIENTE, \App\Models\SolicitudMovimiento::APROBADA, \App\Models\SolicitudMovimiento::APLICADA, \App\Models\SolicitudMovimiento::RECHAZADA] as $opcion)
                        <option value="{{ $opcion }}" {{ ($f['estatus'] ?? '') === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group d-flex align-items-end" style="gap:0.5rem;">
                <button type="submit" class="index-page__btn-primary">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
                <a href="{{ route('movimientos-erp.index', ['tab' => 'productividad']) }}" class="index-page__btn-primary">
                    <i class="fa fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </x-slot>
    @elseif($tabMov === 'movimientos')
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
    @endif

    @if($tabMov === 'productividad')
        @include('partials.productividad-movimientos')
    @else
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
    @endif
</x-index-page>
</div>
@endsection
