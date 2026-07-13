<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Tu solicitud de mantenimiento fue atendida · Soporte</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #eef1f6;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            color: #1f2937;
            -webkit-font-smoothing: antialiased;
        }

        .muted-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }

        @media only screen and (max-width: 540px) {
            .card {
                width: 100% !important;
                border-radius: 20px !important;
            }

            .section {
                padding-left: 26px !important;
                padding-right: 26px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#eef1f6;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#eef1f6; padding:48px 16px;">
        <tr>
            <td align="center">
                <table class="card" width="540" cellpadding="0" cellspacing="0" role="presentation"
                    style="width:540px; max-width:540px; background-color:#ffffff; border-radius:24px; overflow:hidden;
                    border:1px solid #e6e9ef;">

                    {{-- ── Barra de acento superior ── --}}
                    <tr>
                        <td style="height:4px; background-color:#10b981; line-height:4px; font-size:4px;">&nbsp;</td>
                    </tr>

                    {{-- ── Header ── --}}
                    <tr>
                        <td class="section" align="center" style="padding:52px 40px 28px;">
                            <table cellpadding="0" cellspacing="0" role="presentation" align="center" style="margin-bottom:24px;">
                                <tr>
                                    <td align="center" valign="middle" style="width:96px; height:96px;">
                                        <img src="data:image/png;base64,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" width="96" height="96" alt="Solicitud atendida" style="display:block; border:0; outline:none; text-decoration:none;">
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:0 0 10px; font-size:24px; font-weight:800; color:#0f172a;
                                line-height:1.3; letter-spacing:-0.5px;
                                margin-top:10px;">
                                Tu solicitud fue atendida
                            </h1>
                            <p style="margin:0 0 20px; font-size:15px; color:#64748b; line-height:1.6; max-width:360px;">
                                El equipo de Soporte concluyó tu solicitud de mantenimiento.
                            </p>

                            <table cellpadding="0" cellspacing="0" role="presentation" align="center">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#ecfdf5 0%, #d1fae5 100%);
                                        border:1px solid #a7f3d0; border-radius:999px;
                                        padding:7px 18px; font-size:13px; font-weight:700; color:#047857;">
                                        Mantenimiento #{{ $ticket->MantenimientoID }}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- ── Solicitud ── --}}
                    <tr>
                        <td class="section" style="padding:8px 40px 12px;">
                            <p class="muted-label" style="margin:0 0 10px; color:#94a3b8;">Tu solicitud</p>
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#f8fafc; border-radius:14px; border:1px solid #eef2f6;">
                                <tr>
                                    <td style="padding:18px 20px; border-left:3px solid #10b981; border-radius:14px 0 0 14px;">
                                        <p style="margin:0; font-size:14px; color:#334155; line-height:1.7; white-space:pre-line;">{!! nl2br(e(trim($ticket->Descripcion))) !!}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Aviso informativo ── --}}
                    <tr>
                        <td class="section" style="padding:12px 40px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#f0f9ff; border-radius:14px; border:1px solid #e0f2fe;">
                                <tr>
                                    <td valign="top" style="padding:16px 0 16px 18px; width:38px;">
                                        <table cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td align="center" valign="middle"
                                                    style="width:24px; height:24px; border-radius:50%; background-color:#0ea5e9; font-size:13px; color:#ffffff; font-weight:700;">
                                                    i
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" style="padding:16px 18px 16px 4px;">
                                        <p style="margin:0 0 3px; font-size:14px; font-weight:600; color:#0c4a6e;">
                                            Aviso informativo
                                        </p>
                                        <p style="margin:0; font-size:13px; color:#0369a1; line-height:1.6;">
                                            No necesitas hacer nada. Si el problema continúa, levanta una nueva solicitud.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Footer ── --}}
                    <tr>
                        <td class="section" align="center"
                            style="padding:26px 40px; background-color:#0f172a;">
                            <p style="margin:0 0 3px; font-size:12px; font-weight:700; color:#e2e8f0; letter-spacing:0.2px;">
                                Mantenimiento de Compras · Proser
                            </p>
                            <p style="margin:0; font-size:11px; color:#64748b;">
                                Correo automático · Por favor no respondas a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>