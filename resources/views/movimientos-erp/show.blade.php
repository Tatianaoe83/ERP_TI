@extends('layouts.app')

@section('content')
<x-index-page
    title="Movimiento #{{ $solicitud->SolicitudMovimientoID }}"
    icon="fa-exchange-alt"
    :show-count="false"
    subtitle="{{ $solicitud->obra->NombreObra ?? 'Sin proyecto' }} · {{ $solicitud->cargo->Clave ?? '' }}"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('movimientos-erp.formato', $solicitud->SolicitudMovimientoID) }}" class="index-page__btn-primary">
            <i class="fas fa-file-excel"></i> Descargar formato
        </a>
        <a href="{{ route('movimientos-erp.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-list"></i> Volver al listado
        </a>
    </x-slot>

    @php
        $chip = [
            \App\Models\SolicitudMovimiento::PENDIENTE => 'erp-chip--aviso',
            \App\Models\SolicitudMovimiento::APROBADA => 'erp-chip--info',
            'Aplicada' => 'erp-chip--ok',
            'Rechazada' => 'erp-chip--alerta',
        ][$solicitud->Estatus] ?? 'erp-chip--neutro';
    @endphp

    <div class="index-page__card p-4 mb-4">
        @php
            $vobo = $solicitud->pasos->firstWhere('stage', 'vobo');
            $control = $solicitud->pasos->firstWhere('stage', 'control');
            $textoPaso = ['approved' => 'Aprobado', 'rejected' => 'Rechazado', 'pending' => 'Pendiente'];
        @endphp
        <div class="flex flex-wrap items-center gap-4 mb-4">
            <span class="erp-chip {{ $chip }}">{{ $solicitud->Estatus }}</span>
            @if ($solicitud->pasos->isNotEmpty())
                <div class="sol-approvals">
                    @if ($vobo && $vobo->status === 'approved')
                        <i class="fas fa-check-circle text-green-500" title="Visto bueno"></i>
                    @elseif ($vobo && $vobo->status === 'rejected')
                        <i class="fas fa-times-circle text-red-500" title="Visto bueno"></i>
                    @else
                        <i class="far fa-circle text-yellow-500" title="Visto bueno"></i>
                    @endif
                    <span class="text-sm">Visto bueno{{ $vobo ? ' · ' . ($textoPaso[$vobo->status] ?? $vobo->status) : '' }}{{ $vobo && $vobo->approver ? ' · ' . $vobo->approver->NombreEmpleado : '' }}</span>
                    @if ($control && $control->status === 'approved')
                        <i class="fas fa-check-circle text-green-500" title="Control"></i>
                    @elseif ($control && $control->status === 'rejected')
                        <i class="fas fa-times-circle text-red-500" title="Control"></i>
                    @elseif ($vobo && $vobo->status === 'approved')
                        <i class="far fa-circle text-orange-500" title="Control"></i>
                    @else
                        <i class="far fa-circle text-slate-300 dark:text-slate-600" title="Control"></i>
                    @endif
                    <span class="text-sm">Neodata{{ $control ? ' · ' . ($textoPaso[$control->status] ?? $control->status) : '' }}{{ $solicitud->aplicado_por ? ' · ' . $solicitud->aplicado_por : '' }}</span>
                </div>
            @endif
        </div>
        @if ($solicitud->pasos->isNotEmpty())
            @php $pasoVobo = $solicitud->pasos->firstWhere('stage', 'vobo'); @endphp
            @if ($solicitud->Estatus === \App\Models\SolicitudMovimiento::PENDIENTE && $pasoVobo && $pasoVobo->status === 'pending')
                <form method="POST" action="{{ route('movimientos-erp.reenviar-firma', $solicitud->SolicitudMovimientoID) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="index-page__btn-secondary">Reenviar liga de firma</button>
                </form>
            @endif
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <p><strong>Solicitó:</strong> {{ $solicitud->solicitante->NombreEmpleado ?? '—' }}{{ $solicitud->solicitante && $solicitud->solicitante->puestos ? ' · ' . $solicitud->solicitante->puestos->NombrePuesto : '' }}</p>
            <p><strong>Obra:</strong> {{ $solicitud->obra->NombreObra ?? '—' }}</p>
            <p><strong>Fecha:</strong> {{ $solicitud->created_at?->format('d/m/Y') }}</p>
            <p><strong>Folio ERP:</strong> {{ $solicitud->Folio ?: 'Pendiente' }}</p>
            <p><strong>Quién autoriza:</strong> {{ $solicitud->AutorizadorRol }}{{ $solicitud->autorizador ? ' · ' . $solicitud->autorizador->NombreEmpleado : '' }}{{ $solicitud->autorizador && $solicitud->autorizador->puestos ? ' · ' . $solicitud->autorizador->puestos->NombrePuesto : '' }}</p>
            <p><strong>Residente de control:</strong> {{ $solicitud->residente->NombreEmpleado ?? 'Se avisa a los residentes de control' }}</p>
        </div>
        <p class="mt-3 text-sm"><strong>Mensaje</strong><br>{{ $solicitud->Justificacion }}</p>
        @if ($solicitud->Respuesta)
            <p class="mt-3 text-sm"><strong>Respuesta</strong><br>{{ $solicitud->Respuesta }}</p>
        @endif
    </div>

    <div class="index-page__card p-4 mb-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Partidas del movimiento</h2>
        <div class="index-page__table-wrap">
            <table class="index-table w-full text-sm">
                <thead>
                    <tr>
                        <th>Ítem</th>
                        <th>Con cargo a</th>
                        <th>Código insumo</th>
                        <th>Breve descripción</th>
                        <th>Partida</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Importe</th>
                        <th>Justificación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitud->lineas as $linea)
                        <tr>
                            <td>{{ $linea->Orden }}</td>
                            <td>{{ $linea->cargo->Clave ?? '—' }}</td>
                            <td>{{ $linea->CodigoInsumo }}</td>
                            <td>{{ $linea->Descripcion }}</td>
                            <td>{{ $linea->Partida }}</td>
                            <td>{{ number_format((float) $linea->Cantidad, 2) }}</td>
                            <td>${{ number_format((float) $linea->Precio, 2) }}</td>
                            <td>${{ number_format((float) $linea->Importe, 2) }}</td>
                            <td>{{ $linea->Justificacion }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">Esta solicitud no trae partidas capturadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($solicitud->Estatus === \App\Models\SolicitudMovimiento::PENDIENTE)
        <div class="index-page__card p-4 mb-4">
            <p class="text-sm">El visto bueno solo lo firma quien recibió la liga. Desde aquí no se aprueba ni se rechaza.</p>
        </div>
    @endif

    @if ($solicitud->Estatus === \App\Models\SolicitudMovimiento::APROBADA)
        <div class="index-page__card p-4 mb-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Aplicar en Neodata</h2>
            <p class="text-sm mb-3">Confirma que el movimiento ya se hizo en Neodata y escribe el folio del ERP. Queda registrado con tu usuario.</p>
            <form method="POST" action="{{ route('movimientos-erp.aplicar', $solicitud->SolicitudMovimientoID) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                <div>
                    <label for="Folio" class="block text-xs font-semibold uppercase mb-1">Folio</label>
                    <input type="text" name="Folio" id="Folio" value="{{ old('Folio', $solicitud->Folio) }}" class="form-control" required maxlength="40">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="index-page__btn-primary">Marcar aplicada</button>
                </div>
            </form>
        </div>
    @endif

    @if ($solicitud->Estatus === \App\Models\SolicitudMovimiento::APLICADA)
        <div class="index-page__card p-4 mb-4">
            <p class="text-sm"><strong>Aplicada en Neodata por:</strong> {{ $solicitud->aplicado_por ?: 'Sin registro' }}{{ $solicitud->aplicado_en ? ' · ' . $solicitud->aplicado_en->format('d/m/Y H:i') : '' }}</p>
        </div>
    @endif

    <div class="index-page__card p-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Comentarios entre áreas</h2>
        @forelse ($solicitud->comentarios as $comentario)
            <div class="mb-3 pb-3 border-b border-slate-200">
                <p class="text-xs uppercase tracking-wide">{{ $comentario->Autor }} · {{ $comentario->created_at?->format('d/m/Y H:i') }}</p>
                <p class="text-sm mt-1">{{ $comentario->Texto }}</p>
            </div>
        @empty
            <p class="text-sm mb-3">Todavía no hay comentarios.</p>
        @endforelse
        <form method="POST" action="{{ route('movimientos-erp.comentar', $solicitud->SolicitudMovimientoID) }}">
            @csrf
            <label for="Texto" class="block text-xs font-semibold uppercase mb-1">Comentario</label>
            <textarea name="Texto" id="Texto" rows="3" class="form-control mb-2" required maxlength="4000" placeholder="Aclaración del cargo, partida o insumo"></textarea>
            <button type="submit" class="index-page__btn-primary">Publicar comentario</button>
        </form>
    </div>
</x-index-page>
@endsection
