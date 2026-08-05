{{--
    Tarjeta con los datos de la solicitud.
    $filas: array [ 'Etiqueta' => 'valor' ]  (los valores vacíos se omiten)
    $bloques: array [ 'Etiqueta' => 'texto largo' ] (se muestran en párrafo, opcional)
--}}
@php
    $filas   = array_filter($filas ?? [], fn($v) => filled($v));
    $bloques = array_filter($bloques ?? [], fn($v) => filled($v));
@endphp

@if($filas || $bloques)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; margin:8px 0 0 0;">
        <tr>
            <td style="padding:18px 20px; font-family:Segoe UI,Helvetica,Arial,sans-serif;">
                <p style="margin:0 0 12px 0; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#64748B;">Detalle de la solicitud</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    @foreach($filas as $etiqueta => $valor)
                        <tr>
                            <td width="38%" style="padding:6px 0; vertical-align:top; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; color:#64748B;">{{ $etiqueta }}</td>
                            <td style="padding:6px 0; vertical-align:top; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#0F172A;">{{ $valor }}</td>
                        </tr>
                    @endforeach
                </table>

                @foreach($bloques as $etiqueta => $texto)
                    <div style="margin-top:14px; padding-top:14px; border-top:1px solid #E2E8F0;">
                        <p style="margin:0 0 4px 0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:12px; font-weight:700; color:#64748B;">{{ $etiqueta }}</p>
                        <p style="margin:0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:14px; line-height:1.6; color:#334155; white-space:pre-line;">{{ $texto }}</p>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>
@endif
