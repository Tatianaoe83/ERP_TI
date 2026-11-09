<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visto bueno · movimiento #{{ $solicitud->SolicitudMovimientoID }}</title>
    <style>
        body { margin: 0; background: #f4f7fb; color: #0f172a; font-family: Arial, sans-serif; }
        .caja { max-width: 760px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 40px rgba(15, 23, 42, .08); }
        .cabeza { background: #101D49; color: #fff; padding: 22px 28px; }
        .cuerpo { padding: 24px 28px 32px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: #64748b; }
        label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        textarea { width: 100%; min-height: 90px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font: inherit; box-sizing: border-box; }
        .botones { display: flex; gap: 10px; margin-top: 12px; }
        button { border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .si { background: #0052cc; color: #fff; }
        .no { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
        .aviso { background: #fff7ed; color: #9a3412; border-radius: 8px; padding: 12px 14px; }
        .ok { background: #ecfdf5; color: #065f46; border-radius: 8px; padding: 12px 14px; }
    </style>
</head>
<body>
    <div class="caja">
        <div class="cabeza">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.75;">ERP TI · Control</div>
            <h1 style="margin:6px 0 0;font-size:22px;">Visto bueno del movimiento #{{ $solicitud->SolicitudMovimientoID }}</h1>
        </div>
        <div class="cuerpo">
            @if (session('error'))
                <p class="aviso">{{ session('error') }}</p>
            @endif

            <p><strong>Solicitó:</strong> {{ $solicitud->solicitante->NombreEmpleado ?? '—' }}</p>
            <p><strong>Proyecto:</strong> {{ $solicitud->obra->NombreObra ?? '—' }}</p>
            <p><strong>Mensaje:</strong> {{ $solicitud->Justificacion }}</p>

            <table>
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($solicitud->lineas as $linea)
                        <tr>
                            <td>{{ $linea->cargo->Clave ?? '—' }}</td>
                            <td>{{ $linea->CodigoInsumo }}</td>
                            <td>{{ $linea->Descripcion }}</td>
                            <td>{{ number_format((float) $linea->Cantidad, 2) }}</td>
                            <td>${{ number_format((float) $linea->Importe, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($paso->status === 'approved')
                <p class="ok">Este visto bueno ya quedó firmado{{ $paso->decided_at ? ' el ' . $paso->decided_at->format('d/m/Y H:i') : '' }}.</p>
            @elseif ($paso->status === 'rejected')
                <p class="aviso">Este visto bueno fue rechazado{{ $paso->comment ? ': ' . $paso->comment : '' }}.</p>
            @elseif (!$liga->vigente())
                <p class="aviso">Esta liga ya no sirve. Pide que te envíen otra desde el tablero de movimientos.</p>
            @elseif ($puedeFirmar)
                <form method="POST" action="{{ route('movimientos-erp.public.decide', $liga->token) }}">
                    @csrf
                    <label for="Texto">Comentario</label>
                    <textarea name="Texto" id="Texto" required maxlength="2000" placeholder="Adelante con el movimiento"></textarea>
                    <div class="botones">
                        <button class="si" type="submit" name="decision" value="approved">Firmar visto bueno</button>
                        <button class="no" type="submit" name="decision" value="rejected">Rechazar</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
