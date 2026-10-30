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
            'Por autorizar' => 'erp-chip--aviso',
            'Autorizada' => 'erp-chip--info',
            'Aplicada' => 'erp-chip--ok',
            'Rechazada' => 'erp-chip--alerta',
        ][$solicitud->Estatus] ?? 'erp-chip--neutro';
    @endphp

    <div class="index-page__card p-4 mb-4">
        <p class="mb-3"><span class="erp-chip {{ $chip }}">{{ $solicitud->Estatus }}</span></p>
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

    <div class="index-page__card p-4 mb-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Documentos</h2>
        @forelse ($solicitud->archivos as $archivo)
            <p class="mb-1">
                <a href="{{ asset('storage/' . $archivo->Ruta) }}" target="_blank" rel="noopener">{{ $archivo->Nombre }}</a>
                <span class="erp-chip erp-chip--neutro">{{ $archivo->Tipo === 'correo' ? 'Copia del correo' : 'Soporte' }}</span>
            </p>
        @empty
            <p class="text-sm">Sin documentos.</p>
        @endforelse
    </div>

    @if ($solicitud->Estatus === 'Por autorizar')
        <div class="index-page__card p-4 mb-4 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('movimientos-erp.autorizar', $solicitud->SolicitudMovimientoID) }}" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div>
                    <label for="respuesta" class="block text-xs font-semibold uppercase mb-1">Respuesta</label>
                    <input type="text" name="Texto" id="respuesta" class="form-control" required maxlength="2000" placeholder="Adelante con el movimiento">
                </div>
                <button type="submit" class="index-page__btn-primary">Autorizar cargo</button>
            </form>
            <form method="POST" action="{{ route('movimientos-erp.rechazar', $solicitud->SolicitudMovimientoID) }}" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div>
                    <label for="motivo" class="block text-xs font-semibold uppercase mb-1">Motivo del rechazo</label>
                    <input type="text" name="Texto" id="motivo" class="form-control" required maxlength="4000">
                </div>
                <button type="submit" class="index-page__btn-secondary">Rechazar</button>
            </form>
        </div>
    @endif

    @if ($solicitud->Estatus === 'Autorizada')
        <div class="index-page__card p-4 mb-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Aplicar movimiento</h2>
            <p class="text-sm mb-3">El folio se escribe a mano, con el mismo número del ERP. La copia del correo se guarda con ese nombre.</p>
            <form method="POST" action="{{ route('movimientos-erp.aplicar', $solicitud->SolicitudMovimientoID) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                <div>
                    <label for="Folio" class="block text-xs font-semibold uppercase mb-1">Folio</label>
                    <input type="text" name="Folio" id="Folio" value="{{ old('Folio', $solicitud->Folio) }}" class="form-control" required maxlength="40">
                </div>
                <div>
                    <label for="correo" class="block text-xs font-semibold uppercase mb-1">Copia del correo</label>
                    <input type="file" name="correo" id="correo" class="form-control">
                </div>
                <div>
                    <button type="submit" class="index-page__btn-primary">Marcar aplicado</button>
                </div>
            </form>
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
