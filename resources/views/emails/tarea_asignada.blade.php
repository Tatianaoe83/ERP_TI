@php
    // Mismo sistema visual que el aviso de críticas (slate + un acento), aquí en azul:
    // es informativo, no una alerta. El color nunca va solo: siempre lleva texto.
    $font = "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    $acento       = '#3B82F6';
    $acentoOscuro = '#1D4ED8';
    $acentoSuave  = '#EFF6FF';
    $acentoBorde  = '#BFDBFE';

    $nombre = optional($tarea->asignado)->NombreEmpleado;
    $hoy = \Carbon\Carbon::today();
    $fecha = $tarea->fecha_compromiso;

    // "Vence hoy", "Vence mañana", "En 5 días", "Vencida hace 2 días"
    $plazo = null;
    if ($fecha) {
        $dias = $hoy->diffInDays($fecha);
        $plazo = $fecha->lt($hoy)
            ? 'Vencida hace ' . $dias . ' ' . ($dias === 1 ? 'día' : 'días')
            : ($dias === 0 ? 'Vence hoy' : ($dias === 1 ? 'Vence mañana' : 'En ' . $dias . ' días'));
    }
    $plazoColor = $fecha && $fecha->lte($hoy) ? '#B45309' : $acentoOscuro;
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Nueva tarea asignada · Soporte TI</title>
    <style>
        /* Los clientes de correo ignoran buena parte del CSS: todo lo estructural va
           inline y aquí solo quedan los ajustes que sí respetan (media queries). */
        body { margin: 0; padding: 0; width: 100% !important; }
        table { border-collapse: collapse !important; }

        .num-tabular { font-variant-numeric: tabular-nums; }

        @media only screen and (max-width: 600px) {
            .card    { width: 100% !important; border-radius: 16px !important; }
            .section { padding-left: 20px !important; padding-right: 20px !important; }
            .dato    { display: block !important; width: 100% !important; padding: 0 0 12px !important; }
        }

        @media (prefers-color-scheme: dark) {
            .body-bg  { background-color: #020617 !important; }
            .card     { background-color: #0F172A !important; border-color: #1E293B !important; }
            .surface  { background-color: #111C33 !important; border-color: #24324B !important; }
            .divider  { border-color: #24324B !important; }
            .t-strong { color: #F8FAFC !important; }
            .t-body   { color: #CBD5E1 !important; }
            .t-muted  { color: #94A3B8 !important; }
        }
    </style>
</head>

<body class="body-bg" style="margin:0; padding:0; background-color:#EEF1F6;">

    {{-- Preheader: se muestra en la bandeja, no en el cuerpo del correo --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
        {{ $tarea->titulo }}{{ $fecha ? ' · compromiso ' . $fecha->format('d/m/Y') : '' }}
    </div>

    <table class="body-bg" width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#EEF1F6; padding:40px 16px;">
        <tr>
            <td align="center">

                <table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation"
                    style="width:600px; max-width:600px; background-color:#FFFFFF; border-radius:20px;
                           overflow:hidden; border:1px solid #E2E8F0;
                           box-shadow:0 8px 24px rgba(15,23,42,0.06);">

                    {{-- ══ Cabecera de marca ══ --}}
                    <tr>
                        <td class="section" style="background-color:#0F172A; padding:22px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left" style="font-family:{{ $font }}; font-size:13px; font-weight:700;
                                        color:#F8FAFC; letter-spacing:0.4px;">
                                        SOPORTE TI · PROSER
                                    </td>
                                    <td align="right" class="num-tabular" style="font-family:{{ $font }};
                                        font-size:12px; color:#94A3B8;">
                                        {{ now()->format('d/m/Y') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="height:4px; background-color:{{ $acento }}; line-height:4px; font-size:4px;">&nbsp;</td>
                    </tr>

                    {{-- ══ Encabezado ══ --}}
                    <tr>
                        <td class="section" style="padding:32px 32px 8px;">
                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:14px;">
                                <tr>
                                    <td style="background-color:{{ $acentoSuave }}; border:1px solid {{ $acentoBorde }};
                                        border-radius:6px; padding:5px 12px; font-family:{{ $font }};
                                        font-size:11px; font-weight:700; letter-spacing:0.8px;
                                        text-transform:uppercase; color:{{ $acentoOscuro }};">
                                        Nueva tarea
                                    </td>
                                </tr>
                            </table>

                            <h1 class="t-strong" style="margin:0 0 8px; font-family:{{ $font }};
                                font-size:24px; line-height:1.3; font-weight:700; color:#0F172A; letter-spacing:-0.4px;">
                                {{ $nombre ? 'Hola, ' . \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($nombre)) : 'Hola' }}
                            </h1>
                            <p class="t-body" style="margin:0; font-family:{{ $font }};
                                font-size:15px; line-height:1.6; color:#475569;">
                                Se te asignó una tarea{{ $asignadoPor ? ' por parte de ' . $asignadoPor : '' }}.
                                Estos son los detalles:
                            </p>
                        </td>
                    </tr>

                    {{-- ══ Ficha de la tarea ══ --}}
                    <tr>
                        <td class="section" style="padding:24px 32px 12px;">
                            <table class="surface" width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px;">
                                <tr>
                                    <td width="4" style="background-color:{{ $acento }}; font-size:1px; line-height:1px;
                                        border-radius:12px 0 0 12px;">&nbsp;</td>
                                    <td style="padding:20px 22px;">

                                        <p class="t-strong" style="margin:0 0 6px; font-family:{{ $font }};
                                            font-size:17px; font-weight:700; line-height:1.4; color:#0F172A;">
                                            {{ $tarea->titulo }}
                                        </p>

                                        @if($tarea->razon)
                                        <p class="t-body" style="margin:0; font-family:{{ $font }};
                                            font-size:14px; line-height:1.6; color:#475569;">
                                            {!! nl2br(e($tarea->razon)) !!}
                                        </p>
                                        @endif

                                        {{-- Datos clave en 3 columnas; en móvil se apilan --}}
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                            class="divider" style="margin-top:16px; border-top:1px solid #E2E8F0;">
                                            <tr>
                                                <td class="dato" width="33%" valign="top" style="padding:14px 12px 0 0;">
                                                    <p class="t-muted" style="margin:0 0 2px; font-family:{{ $font }}; font-size:11px;
                                                        font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:#64748B;">
                                                        Tipo
                                                    </p>
                                                    <p class="t-strong" style="margin:0; font-family:{{ $font }}; font-size:14px;
                                                        font-weight:600; color:#0F172A;">
                                                        {{ $tarea->tipo === 'metrica' ? 'Programada' : 'Evento' }}
                                                    </p>
                                                </td>
                                                <td class="dato" width="33%" valign="top" style="padding:14px 12px 0 0;">
                                                    <p class="t-muted" style="margin:0 0 2px; font-family:{{ $font }}; font-size:11px;
                                                        font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:#64748B;">
                                                        Compromiso
                                                    </p>
                                                    <p class="t-strong num-tabular" style="margin:0; font-family:{{ $font }}; font-size:14px;
                                                        font-weight:600; color:#0F172A;">
                                                        {{ $fecha ? $fecha->format('d/m/Y') : 'Sin fecha' }}
                                                    </p>
                                                </td>
                                                <td class="dato" width="34%" valign="top" style="padding:14px 0 0;">
                                                    <p class="t-muted" style="margin:0 0 2px; font-family:{{ $font }}; font-size:11px;
                                                        font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:#64748B;">
                                                        Plazo
                                                    </p>
                                                    <p class="num-tabular" style="margin:0; font-family:{{ $font }}; font-size:14px;
                                                        font-weight:700; color:{{ $plazo ? $plazoColor : '#64748B' }};">
                                                        {{ $plazo ?? 'Sin plazo' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            <p class="t-muted" style="margin:14px 0 0; font-family:{{ $font }};
                                font-size:12.5px; line-height:1.6; color:#64748B;">
                                Si no puedes atenderla en la fecha, reagéndala indicando el motivo.
                                Pasados dos días de la fecha de compromiso se vuelve crítica.
                            </p>
                        </td>
                    </tr>

                    {{-- ══ Pie ══ --}}
                    <tr>
                        <td style="height:20px; font-size:1px; line-height:1px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="section" align="center" style="padding:26px 32px; background-color:#0F172A;">
                            <p style="margin:0 0 4px; font-family:{{ $font }};
                                font-size:12px; font-weight:700; color:#E2E8F0; letter-spacing:0.3px;">
                                Soporte TI · Proser
                            </p>
                            <p style="margin:0; font-family:{{ $font }};
                                font-size:11px; line-height:1.6; color:#94A3B8;">
                                Correo automático generado a las {{ now()->format('H:i') }} h.
                                Por favor no respondas a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>

</html>
