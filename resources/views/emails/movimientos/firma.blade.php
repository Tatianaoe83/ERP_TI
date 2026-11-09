<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Visto bueno de movimiento ERP</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#101D49;color:#ffffff;padding:20px 24px;">
                            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">ERP TI · Control</div>
                            <div style="font-size:20px;font-weight:700;margin-top:4px;">Visto bueno de movimiento</div>
                            <div style="font-size:13px;margin-top:4px;">Solicitud #{{ $solicitud->SolicitudMovimientoID }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;">Hola {{ $firmante->NombreEmpleado }},</p>
                            <p style="margin:0 0 16px;line-height:1.5;">
                                <strong>{{ $solicitud->solicitante->NombreEmpleado ?? 'Un solicitante' }}</strong>
                                pide tu visto bueno para un movimiento en el ERP del proyecto
                                <strong>{{ $solicitud->obra->NombreObra ?? '' }}</strong>.
                            </p>
                            <p style="margin:0 0 16px;line-height:1.5;color:#334155;">{{ $solicitud->Justificacion }}</p>
                            <p style="margin:0 0 20px;">
                                <a href="{{ $url }}" style="display:inline-block;background:#0052cc;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">Revisar y firmar</a>
                            </p>
                            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.5;">
                                La liga es personal y vence el {{ $vence }}. Cuando firmes, deja de servir.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
