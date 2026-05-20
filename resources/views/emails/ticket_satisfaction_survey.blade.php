@php
    $expiresAt = $survey->expires_at ?? now()->addDays(1);
    $surveyUrl = route('tickets.satisfaction.survey', ['survey' => $survey->uuid]);
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Tu ticket ha sido cerrado · Soporte TI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            color: #1f2937;
            -webkit-font-smoothing: antialiased;
        }

        @media only screen and (max-width: 520px) {
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

<body style="margin:0; padding:0; background-color:#f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#f3f4f6; padding:40px 16px;">
        <tr>
            <td align="center">
                <table class="card" width="480" cellpadding="0" cellspacing="0" role="presentation"
                    style="width:480px; max-width:480px; background-color:#ffffff; border-radius:20px; overflow:hidden;">

                    {{-- ── Header / Ícono + Título ── --}}
                    <tr>
                        <td class="section" align="center" style="padding:44px 36px 28px;">

                            {{-- Ícono check --}}
                            <table cellpadding="0" cellspacing="0" role="presentation" align="center"
                                style="margin-bottom:24px;">
                                <tr>
                                    <td align="center"
                                        style="width:64px; height:64px; background-color:#dcfce7; border-radius:50%;">
                                        <span
                                            style="font-size:30px; line-height:64px; color:#16a34a; font-weight:700;">&#10003;</span>
                                    </td>
                                </tr>
                            </table>

                            <h1
                                style="margin:0 0 10px; font-size:22px; font-weight:600; color:#111827; line-height:1.3; letter-spacing:-0.3px;">
                                Tu ticket ha sido cerrado
                            </h1>
                            <p style="margin:0; font-size:15px; color:#6b7280; line-height:1.6;">
                                El equipo de Soporte TI ha dado respuesta a tu solicitud.
                            </p>

                        </td>
                    </tr>

                    {{-- ── Incidencia ── --}}
                    <tr>
                        <td class="section" style="padding:0 36px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#f9fafb; border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p
                                            style="margin:0 0 6px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#9ca3af;">
                                            Tu incidencia
                                        </p>
                                        <p
                                            style="margin:0; font-size:14px; color:#374151; line-height:1.6; font-style:italic;">
                                            "{{ $ticket->Descripcion }}"
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── CTA ── --}}
                    <tr>
                        <td class="section" align="center"
                            style="padding:28px 36px 36px;">

                            <p style="margin:0 0 6px; font-size:16px; font-weight:600; color:#111827;">
                                ¿Cómo fue tu experiencia?
                            </p>
                            <p style="margin:0 0 22px; font-size:14px; color:#6b7280; line-height:1.5;">
                                Tu opinión tarda menos de un minuto y nos ayuda a mejorar.
                            </p>

                            <table cellpadding="0" cellspacing="0" role="presentation" align="center">
                                <tr>
                                    <td align="center" style="background-color:#2563eb; border-radius:10px;">
                                        <a href="{{ $surveyUrl }}" target="_blank"
                                            style="display:inline-block; padding:13px 32px; font-size:15px; font-weight:500; color:#ffffff; text-decoration:none; letter-spacing:0.1px;">
                                            Calificar mi experiencia
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 0; font-size:11px; color:#9ca3af; line-height:1.6;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $surveyUrl }}" target="_blank" style="color:#2563eb; word-break:break-all;">{{ $surveyUrl }}</a>
                            </p>

                        </td>
                    </tr>

                    {{-- ── Footer ── --}}
                    <tr>
                        <td class="section" align="center"
                            style="padding:20px 36px; background-color:#e5e7eb;">
                            <p style="margin:0 0 4px; font-size:12px; color:#374151;">
                                Este enlace expira el
                                <strong>{{ $expiresAt->locale('es')->translatedFormat('d M Y') }}</strong>
                            </p>
                            <p style="margin:0; font-size:12px; color:#6b7280;">
                                Soporte Técnico · Por favor no respondas a este correo.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>