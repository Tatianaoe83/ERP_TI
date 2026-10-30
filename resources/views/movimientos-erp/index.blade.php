@extends('layouts.app')

@section('content')
<x-index-page title="Movimientos ERP" icon="fa-exchange-alt" :show-count="false" subtitle="Solicitudes de cargo en Neodata. El folio se captura a mano, igual que en el ERP.">
    <x-slot name="filters">
        <form method="GET" action="{{ route('movimientos-erp.index') }}" class="contents">
            <div class="form-group">
                <label for="estatus">Estatus</label>
                <select name="estatus" id="estatus" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach (['Por autorizar', 'Autorizada', 'Aplicada', 'Rechazada'] as $opcion)
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
                        <th>Folio</th>
                        <th>Proyecto</th>
                        <th>Cargo</th>
                        <th>Solicitante</th>
                        <th>Autoriza</th>
                        <th>Residente</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        @php
                            $chip = [
                                'Por autorizar' => 'erp-chip--aviso',
                                'Autorizada' => 'erp-chip--info',
                                'Aplicada' => 'erp-chip--ok',
                                'Rechazada' => 'erp-chip--alerta',
                            ][$solicitud->Estatus] ?? 'erp-chip--neutro';
                        @endphp
                        <tr>
                            <td>{{ $solicitud->SolicitudMovimientoID }}</td>
                            <td>{{ $solicitud->Folio ?: '—' }}</td>
                            <td>{{ $solicitud->obra->NombreObra ?? '—' }}</td>
                            <td>{{ $solicitud->lineas->pluck('cargo.Clave')->filter()->unique()->implode(', ') ?: ($solicitud->cargo->Clave ?? '—') }}</td>
                            <td>{{ $solicitud->solicitante->NombreEmpleado ?? '—' }}</td>
                            <td>{{ $solicitud->autorizador->NombreEmpleado ?? $solicitud->AutorizadorRol }}</td>
                            <td>{{ $solicitud->residente->NombreEmpleado ?? '—' }}</td>
                            <td><span class="erp-chip {{ $chip }}">{{ $solicitud->Estatus }}</span></td>
                            <td>
                                <a href="{{ route('movimientos-erp.show', $solicitud->SolicitudMovimientoID) }}" class="index-action" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No hay solicitudes de movimiento.</td>
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
