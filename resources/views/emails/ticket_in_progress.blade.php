<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Tu ticket esta en progreso · Soporte TI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

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
            letter-spacing: 0.6px;
            color: #9aa3b2;
        }

        @media only screen and (max-width: 540px) {
            .card {
                width: 100% !important;
                border-radius: 16px !important;
            }

            .section {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#eef1f6;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#eef1f6; padding:40px 16px;">
        <tr>
            <td align="center">
                <table class="card" width="520" cellpadding="0" cellspacing="0" role="presentation"
                    style="width:520px; max-width:520px; background-color:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e6e9ef; box-shadow:0 12px 32px rgba(17,24,39,0.08);">

                    {{-- ── Barra de acento superior ── --}}
                    <tr>
                        <td style="height:5px; background-color:#16a34a; background:linear-gradient(90deg,#22c55e,#16a34a);
                            line-height:5px; font-size:5px;">&nbsp;</td>
                    </tr>

                    {{-- ── Header / Ícono + Título ── --}}
                    <tr>
                        <td class="section" align="center" style="padding:40px 40px 24px;">

                            {{-- Ícono reloj de arena --}}
                            <table cellpadding="0" cellspacing="0" role="presentation" align="center"
                                style="margin-bottom:22px;">
                                <tr>
                                    <td align="center" valign="middle"
                                        style="width:72px; height:72px; background-color:#dcfce7; border-radius:50%;
                                        border:2px solid #16a34a; box-shadow:0 0 0 6px rgba(34,197,94,0.15);">
                                        <span style="font-size:32px; line-height:64px;">&#9203;</span>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:0 0 8px; font-size:23px; font-weight:700; color:#0f172a;
                                line-height:1.3; letter-spacing:-0.4px;">
                                Tu ticket está en progreso
                            </h1>
                            <p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
                                El equipo de Soporte TI ya está trabajando en tu solicitud.
                            </p>

                            {{-- Badge del número de ticket --}}
                            <table cellpadding="0" cellspacing="0" role="presentation" align="center">
                                <tr>
                                    <td style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:999px;
                                        padding:6px 16px; font-size:13px; font-weight:600; color:#15803d;">
                                        Ticket #{{ $ticket->TicketID }}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- ── Separador ── --}}
                    <tr>
                        <td class="section" style="padding:4px 40px;">
                            <div style="height:1px; background-color:#eef1f6; line-height:1px; font-size:1px;">&nbsp;</div>
                        </td>
                    </tr>

                    {{-- ── Incidencia ── --}}
                    <tr>
                        <td class="section" style="padding:24px 40px 12px;">
                            <p class="muted-label" style="margin:0 0 10px;">Tu incidencia</p>
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#f8fafc; border:1px solid #eef1f6; border-left:3px solid #22c55e;
                                border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0; font-size:14px; color:#334155; line-height:1.7; white-space:pre-line;">{!! nl2br(e(trim($ticket->Descripcion))) !!}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Aviso de seguimiento ── --}}
                    <tr>
                        <td class="section" style="padding:12px 40px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#eff6ff; border:1px solid #dbeafe; border-radius:10px;">
                                <tr>
                                    <td valign="top" style="padding:16px 18px; width:40px;">
                                        <span style="font-size:20px; line-height:20px;">&#128276;</span>
                                    </td>
                                    <td valign="middle" style="padding:16px 18px 16px 0;">
                                        <p style="margin:0 0 3px; font-size:14px; font-weight:600; color:#1e3a8a;">
                                            Soporte te contactará
                                        </p>
                                        <p style="margin:0; font-size:13px; color:#475569; line-height:1.6;">
                                            Mantente atento, te avisaremos de cualquier avance.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Footer ── --}}
                    <tr>
                        <td class="section" align="center"
                            style="padding:22px 40px; background-color:#f8fafc; border-top:1px solid #eef1f6;">
                            <p style="margin:0 0 2px; font-size:12px; font-weight:600; color:#475569;">
                                Soporte Técnico · Proser
                            </p>
                            <p style="margin:0; font-size:11px; color:#94a3b8;">
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
