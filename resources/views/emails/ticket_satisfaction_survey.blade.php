@php
    use Illuminate\Support\Facades\URL;

    $expiresAt = $survey->expires_at ?? now()->addDays(7);

    $fields = [
        'fastness' => [
            'desc' => '¿Qué tan rápido se atendió tu solicitud?',
            'accent' => '#6366F1',
            'badge_bg' => '#EEF2FF',
            'badge_txt' => '#4338CA',
        ],
        'attention' => [
            'desc' => '¿Cómo fue el trato recibido por parte del equipo?',
            'accent' => '#8B5CF6',
            'badge_bg' => '#F5F3FF',
            'badge_txt' => '#6D28D9',
        ],
        'resolution' => [
            'desc' => '¿Se resolvió correctamente tu problema?',
            'accent' => '#0EA5E9',
            'badge_bg' => '#F0F9FF',
            'badge_txt' => '#0369A1',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Califica tu experiencia · Soporte TI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .stars-rtl {
            direction: rtl;
            display: inline-block;
            font-size: 0;
            line-height: 1;
        }

        .s {
            display: inline-block;
            font-size: 48px;
            line-height: 1;
            padding: 0 4px;
            color: #E2E8F0;
            text-decoration: none;
            transition: color 0.12s ease, transform 0.12s ease;
        }

        .s:hover,
        .s:hover~.s {
            color: #FBBF24 !important;
            transform: scale(1.1) translateY(-2px);
            text-shadow: 0 4px 10px rgba(251, 191, 36, 0.4);
        }

        /* ── Responsive ── */
        @media only screen and (max-width: 620px) {
            .wrap {
                padding: 16px 8px !important;
            }

            .card {
                width: 100% !important;
                border-radius: 20px !important;
            }

            .hpad,
            .bpad,
            .fpad {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .s {
                font-size: 42px !important;
                padding: 0 3px !important;
            }

            .htitle {
                font-size: 26px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#F1F5F9;">

    <table class="wrap" width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#F1F5F9; padding:32px 12px;">
        <tr>
            <td align="center">
                <table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px; max-width:600px; background:#FFFFFF;
                          border-radius:24px;
                          box-shadow:0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);">
                    <tr>
                        <td class="hpad" align="center" style="padding:40px 32px 32px;
                               background:linear-gradient(135deg, #4338CA 0%, #6366F1 100%);
                               border-radius:24px 24px 0 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="margin-bottom:24px;">
                                <tr>
                                    <td align="left">
                                        <table cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td align="center" style="background:rgba(255,255,255,0.2);
                                                       border-radius:100px; padding:4px 14px;">
                                                    <span style="font-size:10px; font-weight:800; letter-spacing:1px;
                                                             text-transform:uppercase; color:#E0E7FF;">
                                                        Soporte TI
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right">
                                        <table cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td align="center" style="background:rgba(255,255,255,0.2);
                                                       border-radius:100px; padding:4px 14px;">
                                                    <span style="font-size:10px; font-weight:800; letter-spacing:1px;
                                                             text-transform:uppercase; color:#E0E7FF;">
                                                        Ticket #{{ $ticket->TicketID }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <h1 class="htitle" style="margin:0 0 12px; font-size:28px; font-weight:800;
                                   line-height:1.2; letter-spacing:-0.5px; color:#FFFFFF;">
                                Califica tu experiencia
                            </h1>
                            <p style="margin:0; font-size:14.5px; font-weight:400; line-height:1.5;
                                  color:#E0E7FF; max-width:380px; display:inline-block;">
                                Gracias por usar nuestro servicio. Tu opinión nos ayuda a mejorar.
                            </p>
                        </td>
                    </tr>

                    @php
                        $nombre = ($ticket->responsableTI->NombreEmpleado);
                        $nombreSeparado = explode(" ", $nombre);
                        $apellidoPaterno = $nombreSeparado[0] ?? '';
                        $nombre = $nombreSeparado[2] ?? $nombreSeparado[1] ?? '';
                    @endphp

                    <tr>
                        <td class="bpad" style="padding:24px 32px 12px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F8FAFC;
                                      border:1px solid #E2E8F0; border-left:4px solid #94A3B8;
                                      border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 6px; font-size:11px; font-weight:800;
                                              letter-spacing:0.5px; text-transform:uppercase; color:#64748B;">
                                            Su incidencia
                                        </p>
                                        <p style="margin:0; font-size:14px; font-weight:500;
                                              line-height:1.6; color:#1E293B; font-style:italic;">
                                            "{{ $ticket->Descripcion }}"
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="bpad" style="padding:0 32px 8px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#EEF2FF;
                                      border:1px solid #C7D2FE; border-left:4px solid #6366F1;
                                      border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 6px; font-size:11px; font-weight:800;
                                              letter-spacing:0.5px; text-transform:uppercase; color:#4338CA;">
                                            Resolución de {{ $nombre }} {{ $apellidoPaterno }}
                                        </p>
                                        <p style="margin:0; font-size:14px; font-weight:500;
                                              line-height:1.6; color:#1E293B; font-style:italic;">
                                            "{{ $resolution }}"
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="bpad" style="padding:16px 32px 0;">
                            <p style="margin:0; font-size:11px; font-weight:700; letter-spacing:1px;
                                  text-transform:uppercase; color:#94A3B8;">
                                Califica cada criterio
                            </p>
                        </td>
                    </tr>

                    @foreach ($fields as $fieldKey => $fieldInfo)
                        <tr>
                            <td class="bpad" style="padding:12px 32px;">

                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                    style="background:#FFFFFF;
                                                                                                                                          border:1px solid #CBD5E1;
                                                                                                                                          border-radius:16px;
                                                                                                                                          box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                    <tr>
                                        <td style="padding:20px 24px;" align="center">
                                            <p
                                                style="margin:0 0 16px; font-size:15px; font-weight:600; color:#0F172A; line-height:1.4;">
                                                {{ $fieldInfo['desc'] }}
                                            </p>

                                            <div class="stars-rtl" dir="rtl" style="margin-bottom:12px;">
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <a class="s"
                                                        href="{{ URL::temporarySignedRoute('tickets.satisfaction.answer', $expiresAt, ['survey' => $survey->uuid, 'field' => $fieldKey, 'rating' => $i]) }}"
                                                        title="{{ $i }} estrella{{ $i > 1 ? 's' : '' }}">&#9733;</a>
                                                @endfor
                                            </div>

                                            <table style="width:100%; max-width:280px;" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tr>
                                                    <td
                                                        style="width:33%; text-align:left; font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.5px;">
                                                        Malo
                                                    </td>
                                                    <td
                                                        style="width:33%; text-align:right; font-size:11px; font-weight:700; color:{{ $fieldInfo['accent'] }}; text-transform:uppercase; letter-spacing:0.5px;">
                                                        Excelente
                                                    </td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>

                                    <tr>
                                        <td
                                            style="height:4px; background:linear-gradient(90deg, {{ $fieldInfo['accent'] }}, transparent); border-radius:0 0 14px 14px; font-size:0; line-height:0;">
                                            &nbsp;
                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>
                    @endforeach

                    <tr>
                        <td class="fpad"
                            style="padding:28px 32px 32px; text-align:center; background:#F8FAFC; border-top:1px solid #E2E8F0; border-radius:0 0 24px 24px;">
                            <table align="center" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td
                                        style="padding:6px 16px; background:#EEF2FF; border-radius:100px; border:1px solid #C7D2FE;">
                                        <p
                                            style="margin:0; font-size:11.5px; font-weight:600; color:#4F46E5; line-height:1.5; letter-spacing:0.3px;">
                                            Este enlace expira el <strong
                                                style="font-weight:800;">{{ $expiresAt->locale('es')->translatedFormat('d M Y') }}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p
                                style="margin:16px 0 0; font-size:11px; font-weight:500; color:#94A3B8; line-height:1.5;">
                                Soporte Técnico Automático · Por favor no respondas a este correo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>