@php
    // Paleta slate (Soft UI Evolution): fondo oscuro de marca, tarjeta clara y un
    // único acento semántico según el tipo de aviso.
    $acento       = $esCritico ? '#EF4444' : '#22C55E';
    $acentoOscuro = $esCritico ? '#B91C1C' : '#15803D';
    $acentoSuave  = $esCritico ? '#FEF2F2' : '#F0FDF4';
    $acentoBorde  = $esCritico ? '#FECACA' : '#BBF7D0';

    $titulo = $esCritico
        ? 'Tareas programadas en estado crítico'
        : 'Tareas programadas generadas hoy';

    $bajada = $esCritico
        ? 'Llevan más de dos días desde su fecha de compromiso y siguen pendientes.'
        : 'El sistema generó automáticamente las tareas del mes.';

    // Texto de preview que muestran Gmail/Outlook junto al asunto.
    $preheader = $tareas->count() . ' ' . ($tareas->count() === 1 ? 'tarea' : 'tareas')
        . ' · ' . $bajada;

    $sinResponsable = $tareas->filter(fn ($t) => ! $t->asignado_id)->count();
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $titulo }} · Soporte TI</title>
    <style>
        /* Los clientes de correo ignoran buena parte del CSS: todo lo estructural va
           inline y aquí solo quedan los ajustes que sí respetan (media queries). */
        body { margin: 0; padding: 0; width: 100% !important; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        table { border-collapse: collapse !important; }

        .num-tabular { font-variant-numeric: tabular-nums; }

        @media only screen and (max-width: 600px) {
            .card       { width: 100% !important; border-radius: 18px !important; }
            .section    { padding-left: 22px !important; padding-right: 22px !important; }
            .stack      { display: block !important; width: 100% !important; }
            .stack-gap  { padding-top: 10px !important; }
            .cta        { display: block !important; width: 100% !important; }
            .meta-label { display: block !important; padding-bottom: 2px !important; }
        }

        @media (prefers-color-scheme: dark) {
            .body-bg  { background-color: #020617 !important; }
            .card     { background-color: #0F172A !important; border-color: #1E293B !important; }
            .surface  { background-color: #111C33 !important; border-color: #24324B !important; }
            .t-strong { color: #F8FAFC !important; }
            .t-body   { color: #CBD5E1 !important; }
            .t-muted  { color: #94A3B8 !important; }
        }
    </style>
</head>

<body class="body-bg" style="margin:0; padding:0; background-color:#EEF1F6;">

    {{-- Preheader: se muestra en la bandeja, no en el cuerpo del correo --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
        {{ $preheader }}
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
                                    <td align="left" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                        font-size:13px; font-weight:700; color:#F8FAFC; letter-spacing:0.4px;">
                                        SOPORTE TI · PROSER
                                    </td>
                                    <td align="right" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                        font-size:12px; color:#94A3B8;" class="num-tabular">
                                        {{ now()->format('d/m/Y') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Franja de acento: refuerza el tipo de aviso sin depender solo del color --}}
                    <tr>
                        <td style="height:4px; background-color:{{ $acento }}; line-height:4px; font-size:4px;">&nbsp;</td>
                    </tr>

                    {{-- ══ Encabezado del aviso ══ --}}
                    <tr>
                        <td class="section" style="padding:32px 32px 8px;">

                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:14px;">
                                <tr>
                                    <td style="background-color:{{ $acentoSuave }}; border:1px solid {{ $acentoBorde }};
                                        border-radius:6px; padding:5px 12px;
                                        font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                        font-size:11px; font-weight:700; letter-spacing:0.8px;
                                        text-transform:uppercase; color:{{ $acentoOscuro }};">
                                        {{ $esCritico ? 'Requiere atención' : 'Aviso automático' }}
                                    </td>
                                </tr>
                            </table>

                            <h1 class="t-strong" style="margin:0 0 8px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                font-size:24px; line-height:1.3; font-weight:700; color:#0F172A; letter-spacing:-0.4px;">
                                {{ $titulo }}
                            </h1>
                            <p class="t-body" style="margin:0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                font-size:15px; line-height:1.6; color:#475569;">
                                {{ $bajada }}
                            </p>
                        </td>
                    </tr>

                    {{-- ══ Resumen en cifras ══ --}}
                    <tr>
                        <td class="section" style="padding:20px 32px 4px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="stack surface" width="50%" valign="top"
                                        style="background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px;
                                               padding:14px 16px;">
                                        <p class="t-muted" style="margin:0 0 2px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                            font-size:11px; font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:#64748B;">
                                            {{ $esCritico ? 'En estado crítico' : 'Tareas generadas' }}
                                        </p>
                                        <p class="t-strong num-tabular" style="margin:0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                            font-size:26px; font-weight:700; line-height:1.2; color:{{ $esCritico ? $acentoOscuro : '#0F172A' }};">
                                            {{ $tareas->count() }}
                                        </p>
                                    </td>
                                    <td width="12" style="font-size:1px; line-height:1px;">&nbsp;</td>
                                    <td class="stack surface stack-gap" width="50%" valign="top"
                                        style="background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px;
                                               padding:14px 16px;">
                                        <p class="t-muted" style="margin:0 0 2px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                            font-size:11px; font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:#64748B;">
                                            Sin responsable
                                        </p>
                                        <p class="t-strong num-tabular" style="margin:0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                            font-size:26px; font-weight:700; line-height:1.2; color:{{ $sinResponsable > 0 ? '#B45309' : '#0F172A' }};">
                                            {{ $sinResponsable }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ══ Detalle de tareas ══ --}}
                    <tr>
                        <td class="section" style="padding:22px 32px 6px;">
                            <p class="t-muted" style="margin:0 0 12px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                font-size:11px; font-weight:700; letter-spacing:0.9px; text-transform:uppercase; color:#94A3B8;">
                                Detalle
                            </p>

                            @foreach($tareas as $tarea)
                            @php
                                $retraso = $tarea->fecha_compromiso
                                    ? $tarea->fecha_compromiso->diffInDays(now())
                                    : null;
                                $responsable = optional($tarea->asignado)->NombreEmpleado;
                            @endphp
                            <table class="surface" width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px;
                                       margin-bottom:10px;">
                                <tr>
                                    {{-- Barra lateral de color: refuerzo visual, nunca el único indicador --}}
                                    <td width="4" style="background-color:{{ $acento }}; font-size:1px; line-height:1px;
                                        border-radius:12px 0 0 12px;">&nbsp;</td>
                                    <td style="padding:16px 18px;">

                                        <p class="t-strong" style="margin:0 0 4px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                            font-size:15px; font-weight:700; line-height:1.4; color:#0F172A;">
                                            {{ $tarea->titulo }}
                                        </p>

                                        @if($tarea->razon)
                                        <p class="t-body" style="margin:0 0 10px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                            font-size:13px; line-height:1.6; color:#475569;">
                                            {{ \Illuminate\Support\Str::limit($tarea->razon, 180) }}
                                        </p>
                                        @endif

                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                            style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:12.5px;">
                                            <tr>
                                                <td class="t-muted meta-label" width="112" valign="top"
                                                    style="padding:3px 0; color:#94A3B8;">Responsable</td>
                                                <td class="t-body" valign="top" style="padding:3px 0; color:#334155; font-weight:600;">
                                                    @if($responsable)
                                                        {{ $responsable }}
                                                    @else
                                                        <span style="color:#B45309;">Por asignar</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="t-muted meta-label" width="112" valign="top"
                                                    style="padding:3px 0; color:#94A3B8;">Compromiso</td>
                                                <td class="t-body num-tabular" valign="top" style="padding:3px 0; color:#334155; font-weight:600;">
                                                    {{ optional($tarea->fecha_compromiso)->format('d/m/Y') ?: 'Sin fecha' }}
                                                </td>
                                            </tr>
                                            @if($esCritico && $retraso !== null)
                                            <tr>
                                                <td class="t-muted meta-label" width="112" valign="top"
                                                    style="padding:3px 0; color:#94A3B8;">Retraso</td>
                                                <td valign="top" style="padding:3px 0;">
                                                    <span class="num-tabular" style="color:{{ $acentoOscuro }}; font-weight:700;">
                                                        {{ $retraso }} {{ $retraso === 1 ? 'día' : 'días' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>

                                    </td>
                                </tr>
                            </table>
                            @endforeach
                        </td>
                    </tr>

                    {{-- ══ Acción principal ══ --}}
                    <tr>
                        <td class="section" align="center" style="padding:18px 32px 6px;">
                            <table cellpadding="0" cellspacing="0" role="presentation" class="cta">
                                <tr>
                                    <td align="center" style="background-color:#0F172A; border-radius:10px;">
                                        <a href="{{ $urlTareas }}"
                                           style="display:inline-block; padding:13px 28px;
                                                  font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                                  font-size:14px; font-weight:600; color:#FFFFFF;
                                                  text-decoration:none; border-radius:10px;">
                                            {{ $esCritico ? 'Atender tareas ahora' : 'Ver tareas en el ERP' }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p class="t-muted" style="margin:12px 0 0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                font-size:12px; line-height:1.6; color:#94A3B8;">
                                @if($esCritico)
                                    Complétalas o reagéndalas indicando el motivo; el cambio queda en el historial.
                                @else
                                    Asigna responsable a las que aún no lo tienen.
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- ══ Pie ══ --}}
                    <tr>
                        <td class="section" align="center" style="padding:26px 32px; background-color:#0F172A;">
                            <p style="margin:0 0 4px; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                font-size:12px; font-weight:700; color:#E2E8F0; letter-spacing:0.3px;">
                                Soporte TI · Proser
                            </p>
                            <p style="margin:0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                                font-size:11px; line-height:1.6; color:#64748B;">
                                Correo automático generado a las {{ now()->format('H:i') }} h.
                                Por favor no respondas a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="margin:16px 0 0; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
                    font-size:11px; color:#94A3B8;">
                    Recibes este aviso porque formas parte del equipo de soporte TI.
                </p>

            </td>
        </tr>
    </table>
</body>

</html>
