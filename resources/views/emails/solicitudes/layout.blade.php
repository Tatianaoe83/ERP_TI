{{--
    Layout base para los correos del flujo de solicitudes.
    HTML seguro para clientes de correo: tablas, estilos en línea, ancho máximo 600px.

    Variables esperadas:
      $accent      color de acento de la etapa (hex)
      $accentSoft  fondo suave del acento (hex)
      $eyebrow     etiqueta corta arriba del título (etapa)
      $titulo      título principal
      $preheader   texto de vista previa en la bandeja
      $folio       "#123"
      $saludo      nombre de la persona (opcional)
      $intro       párrafo introductorio (HTML permitido)
      $url         destino del botón (opcional)
      $boton       texto del botón (opcional)
      $nota        línea final de contexto (opcional)
--}}
@php
    $accent     = $accent     ?? '#0F766E';
    $accentSoft = $accentSoft ?? '#F0FDFA';
    $ink        = '#0F172A';
    $muted      = '#64748B';
    $border     = '#E2E8F0';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $titulo }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F1F5F9; -webkit-font-smoothing:antialiased;">

    {{-- Texto de vista previa (oculto en el cuerpo, visible en la bandeja) --}}
    <div style="display:none; font-size:1px; color:#F1F5F9; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ $preheader ?? $titulo }}
        &#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F1F5F9;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background-color:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.08);">

                    {{-- Barra de marca --}}
                    <tr>
                        <td style="background-color:{{ $accent }}; padding:20px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#FFFFFF;">
                                        Sistema de Solicitudes
                                    </td>
                                    @if(!empty($folio))
                                        <td align="right" style="font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; font-weight:600; color:#FFFFFF;">
                                            <span style="display:inline-block; padding:5px 12px; border-radius:999px; background-color:rgba(255,255,255,0.18); color:#FFFFFF;">Folio {{ $folio }}</span>
                                        </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Encabezado --}}
                    <tr>
                        <td style="padding:32px 32px 8px 32px; font-family:Segoe UI,Helvetica,Arial,sans-serif;">
                            @if(!empty($eyebrow))
                                <p style="margin:0 0 12px 0;">
                                    <span style="display:inline-block; padding:5px 12px; border-radius:999px; background-color:{{ $accentSoft }}; color:{{ $accent }}; font-size:12px; font-weight:700; letter-spacing:0.05em; text-transform:uppercase;">{{ $eyebrow }}</span>
                                </p>
                            @endif
                            <h1 style="margin:0; font-size:24px; line-height:1.3; font-weight:700; color:{{ $ink }};">{{ $titulo }}</h1>
                        </td>
                    </tr>

                    {{-- Cuerpo --}}
                    <tr>
                        <td style="padding:16px 32px 8px 32px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:15px; line-height:1.6; color:#334155;">
                            @if(!empty($saludo))
                                <p style="margin:0 0 12px 0;">Hola <strong style="color:{{ $ink }};">{{ $saludo }}</strong>,</p>
                            @endif
                            @if(!empty($intro))
                                <p style="margin:0 0 8px 0;">{!! $intro !!}</p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 32px 0 32px;">
                            @yield('contenido')
                        </td>
                    </tr>

                    {{-- Llamada a la acción --}}
                    @if(!empty($url))
                        <tr>
                            <td align="center" style="padding:28px 32px 8px 32px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td align="center" bgcolor="{{ $accent }}" style="border-radius:10px;">
                                            <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:15px 34px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:15px; font-weight:700; color:#FFFFFF; text-decoration:none; border-radius:10px;">{{ $boton ?? 'Abrir solicitud' }}</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" style="padding:4px 32px 0 32px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:12px; line-height:1.6; color:{{ $muted }};">
                                ¿No abre el botón? Copia este enlace:<br>
                                <a href="{{ $url }}" style="color:{{ $accent }}; word-break:break-all;">{{ $url }}</a>
                            </td>
                        </tr>
                    @endif

                    {{-- Pie --}}
                    <tr>
                        <td style="padding:28px 32px 32px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-top:1px solid {{ $border }}; padding-top:16px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:12px; line-height:1.6; color:#94A3B8;">
                                        @if(!empty($nota))
                                            <p style="margin:0 0 6px 0; color:{{ $muted }};">{!! $nota !!}</p>
                                        @endif
                                        <p style="margin:0;">Correo automático del Sistema de Solicitudes. No es necesario responder este mensaje.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <p style="margin:16px 0 0 0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:11px; color:#94A3B8;">Folio {{ $folio ?? '' }} &middot; Proser</p>

            </td>
        </tr>
    </table>

</body>
</html>
