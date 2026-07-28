<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: #0F766E;">{{ $titulo }}</h2>
        <p>Hola <strong>{{ $nombreAprobador }}</strong>,</p>
        <p>{!! $intro !!}</p>

        <div style="background:#f3f4f6;padding:16px;border-radius:8px;margin:16px 0;">
            <p><strong>Solicitante:</strong> {{ $nombreSolicitante }}</p>
            <p><strong>Motivo:</strong> {{ $motivo }}</p>
            @if($stage === 'supervisor')
                <p><strong>Descripción:</strong><br>{{ $desc }}</p>
                <p><strong>Requerimientos:</strong><br>{{ $req }}</p>
            @endif
        </div>

        @if($stage === 'administracion' && $ganadores && $ganadores->isNotEmpty())
            <div style="background:#ecfdf5;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #10b981;">
                <h3 style="color:#059669;margin-top:0;">Productos ganadores</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;">Descripción</th>
                            <th style="text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;">No. Parte</th>
                            <th style="text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;">Proveedor</th>
                            <th style="text-align:right;padding:8px 12px;border-bottom:1px solid #10b981;">Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ganadores as $c)
                            @php
                                $cant = (int) ($c->Cantidad ?? 1);
                                $cantidad = $cant > 1 ? ' × ' . $cant : '';
                            @endphp
                            <tr>
                                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;">{{ $c->Descripcion ?? 'N/A' }}{{ $cantidad }}</td>
                                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;">{{ $c->NumeroParte ?? 'N/A' }}</td>
                                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;">{{ $c->Proveedor ?? 'N/A' }}</td>
                                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;">$ {{ number_format($c->Precio ?? 0, 2, '.', ',') }} MXN</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 24px 0;">
            <tr>
                <td align="center" bgcolor="#0F766E" style="border-radius: 8px;">
                    <a href="{{ $url }}" target="_blank" style="display: inline-block; padding: 14px 28px; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 8px;">{{ $boton }}</a>
                </td>
            </tr>
        </table>

        <p style="font-size: 12px; color: #6b7280;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br><a href="{{ $url }}" style="color: #0F766E;">{{ $url }}</a></p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af;">Este correo fue enviado automáticamente por el Sistema de Solicitudes. Si ya atendiste la solicitud, ignora este mensaje.</p>
    </div>
</body>
</html>
