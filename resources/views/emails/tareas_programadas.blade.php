@php
    // Paleta slate (Data-Dense Dashboard): cabecera oscura de marca, tarjeta clara y
    // rojo como único acento semántico. El color nunca va solo: siempre lleva texto.
    // Individual ($esRecordatorio = false): la tarea del día que se volvió crítica.
    // Recordatorio: concentrado de las críticas que siguen sin cerrar.
    $font = "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    $acento       = '#EF4444';
    $acentoOscuro = '#B91C1C';
    $acentoSuave  = '#FEF2F2';
    $acentoBorde  = '#FECACA';

    $titulo = $esRecordatorio ? 'Críticas sin cerrar' : 'Tarea en estado crítico';

    $bajada = $esRecordatorio
        ? 'Siguen pendientes con más de dos días de retraso. Este recordatorio se repite cada día hasta que se completen o se reagenden.'
        : 'Pasaron más de dos días desde su fecha de compromiso y sigue pendiente.';

    // Texto de preview que muestran Gmail/Outlook junto al asunto.
    $preheader = $esRecordatorio
        ? $tareas->count() . ' ' . ($tareas->count() === 1 ? 'tarea crítica sigue' : 'tareas críticas siguen') . ' sin cerrar'
        : $tareas->first()->titulo;

    $hoy = \Carbon\Carbon::today();
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
            .card       { width: 100% !important; border-radius: 16px !important; }
            .section    { padding-left: 20px !important; padding-right: 20px !important; }
            .fila-fecha { width: 84px !important; }
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
        {{ $preheader }}
    </div>

    <table class="body-bg" width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#EEF1F6; padding:40px 16px;">
        <tr>
            <td align="center">

                <table class="card" width="640" cellpadding="0" cellspacing="0" role="presentation"
                    style="width:640px; max-width:640px; background-color:#FFFFFF; border-radius:20px;
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
                                        border-radius:6px; padding:5px 12px; font-family:{{ $font }};
                                        font-size:11px; font-weight:700; letter-spacing:0.8px;
                                        text-transform:uppercase; color:{{ $acentoOscuro }};">
                                        {{ $esRecordatorio ? 'Recordatorio diario' : 'Requiere atención' }}
                                    </td>
                                </tr>
                            </table>

                            <h1 class="t-strong" style="margin:0 0 8px; font-family:{{ $font }};
                                font-size:24px; line-height:1.3; font-weight:700; color:#0F172A; letter-spacing:-0.4px;">
                                {{ $titulo }}
                                @if($esRecordatorio)
                                <span style="color:{{ $acentoOscuro }};">({{ $tareas->count() }})</span>
                                @endif
                            </h1>
                            <p class="t-body" style="margin:0; font-family:{{ $font }};
                                font-size:15px; line-height:1.6; color:#475569;">
                                {{ $bajada }}
                            </p>
                        </td>
                    </tr>

                    {{-- ══ Individual: ficha completa de la tarea que se volvió crítica ══ --}}
                    @unless($esRecordatorio)
                    <tr>
                        <td class="section" style="padding:26px 32px 20px;">
                            @foreach($tareas as $tarea)
                            @php
                                $retraso = $tarea->fecha_compromiso
                                    ? $tarea->fecha_compromiso->diffInDays($hoy)
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

                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td valign="top">
                                                    <p class="t-strong" style="margin:0 0 4px; font-family:{{ $font }};
                                                        font-size:15px; font-weight:700; line-height:1.4; color:#0F172A;">
                                                        {{ $tarea->titulo }}
                                                    </p>
                                                </td>
                                                @if($retraso !== null)
                                                <td valign="top" align="right" style="padding-left:12px; white-space:nowrap;">
                                                    <span class="num-tabular" style="display:inline-block; font-family:{{ $font }};
                                                        font-size:11px; font-weight:700; color:#B91C1C; background-color:#FEF2F2;
                                                        border:1px solid #FECACA; border-radius:999px; padding:3px 10px;">
                                                        {{ $retraso }} {{ $retraso === 1 ? 'día' : 'días' }} de retraso
                                                    </span>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>

                                        @if($tarea->razon)
                                        <p class="t-body" style="margin:0 0 10px; font-family:{{ $font }};
                                            font-size:13px; line-height:1.6; color:#475569;">
                                            {{ \Illuminate\Support\Str::limit($tarea->razon, 180) }}
                                        </p>
                                        @endif

                                        {{-- Metadatos en línea: se leen de un vistazo y envuelven solos en móvil --}}
                                        <p class="t-body" style="margin:0; font-family:{{ $font }}; font-size:12.5px;
                                            line-height:1.7; color:#334155;">
                                            <span class="t-muted" style="color:#64748B;">Tipo</span>
                                            <strong>{{ $tarea->tipo === 'metrica' ? 'Programada' : 'Evento' }}</strong>
                                            &nbsp;·&nbsp;
                                            <span class="t-muted" style="color:#64748B;">Responsable</span>
                                            @if($responsable)
                                                <strong>{{ $responsable }}</strong>
                                            @else
                                                <strong style="color:#B45309;">Por asignar</strong>
                                            @endif
                                            &nbsp;·&nbsp;
                                            <span class="t-muted" style="color:#64748B;">Compromiso</span>
                                            <strong class="num-tabular">{{ optional($tarea->fecha_compromiso)->format('d/m/Y') ?: 'Sin fecha' }}</strong>
                                        </p>

                                    </td>
                                </tr>
                            </table>
                            @endforeach
                        </td>
                    </tr>
                    @endunless

                    {{-- ══ Recordatorio: concentrado de las críticas que siguen sin cerrar ══ --}}
                    @if($esRecordatorio)
                    <tr>
                        <td class="section" style="padding:26px 32px 20px;">
                            {{-- Filas: título y responsable a la izquierda, fecha y retraso alineados a la derecha --}}
                            <table class="surface" width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                style="background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px;
                                       overflow:hidden;">
                                @foreach($tareas as $fila)
                                @php
                                    $resp = optional($fila->asignado)->NombreEmpleado;
                                    $dias = $fila->fecha_compromiso ? $fila->fecha_compromiso->diffInDays($hoy) : null;
                                    $borde = $loop->first ? '' : 'border-top:1px solid #E2E8F0;';
                                @endphp
                                <tr>
                                    <td width="4" style="background-color:#EF4444; font-size:1px; line-height:1px;">&nbsp;</td>
                                    <td class="{{ $loop->first ? '' : 'divider' }}" valign="top"
                                        style="padding:12px 14px; {{ $borde }}">
                                        <p class="t-strong" style="margin:0; font-family:{{ $font }}; font-size:13.5px;
                                            font-weight:600; line-height:1.4; color:#0F172A;">
                                            {{ $fila->titulo }}
                                        </p>
                                        <p class="t-muted" style="margin:2px 0 0; font-family:{{ $font }}; font-size:12px;
                                            line-height:1.5; color:#64748B;">
                                            {{ $fila->tipo === 'metrica' ? 'Programada' : 'Evento' }}
                                            &nbsp;·&nbsp;
                                            @if($resp)
                                                {{ $resp }}
                                            @else
                                                <span style="color:#B45309; font-weight:600;">Por asignar</span>
                                            @endif
                                        </p>
                                    </td>
                                    <td class="fila-fecha {{ $loop->first ? '' : 'divider' }}" width="104" align="right" valign="top"
                                        style="padding:12px 14px 12px 0; white-space:nowrap; {{ $borde }}">
                                        <p class="t-strong num-tabular" style="margin:0; font-family:{{ $font }}; font-size:13px;
                                            font-weight:600; line-height:1.4; color:#0F172A;">
                                            {{ optional($fila->fecha_compromiso)->format('d/m/Y') ?: '—' }}
                                        </p>
                                        @if($dias !== null)
                                        <p class="num-tabular" style="margin:2px 0 0; font-family:{{ $font }}; font-size:11.5px;
                                            font-weight:700; line-height:1.5; color:#B91C1C;">
                                            {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }} de retraso
                                        </p>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ══ Pie ══ --}}
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

                <p style="margin:16px 0 0; font-family:{{ $font }}; font-size:11px; color:#64748B;">
                    Aviso enviado al buzón del área de Soporte TI.
                </p>

            </td>
        </tr>
    </table>
</body>

</html>
