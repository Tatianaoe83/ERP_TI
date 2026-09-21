{{--
    Etapas que se quedaron sin firmar cuando la solicitud se canceló o rechazó.
    $pendientes: array de ['etapa','nombre']
--}}
@php $pendientes = $pendientes ?? []; @endphp

@if(count($pendientes))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFFBEB; border:1px solid #FDE68A; border-radius:12px; margin:16px 0 0 0;">
        <tr>
            <td style="padding:18px 20px; font-family:Segoe UI,Helvetica,Arial,sans-serif;">
                <p style="margin:0 0 14px 0; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#B45309;">Faltaron por firmar</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    @foreach($pendientes as $i => $p)
                        <tr>
                            <td width="28" style="vertical-align:top; padding:{{ $i === 0 ? '0' : '12px' }} 12px 0 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td width="22" height="22" align="center" bgcolor="#F59E0B" style="width:22px; height:22px; border-radius:11px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; font-weight:700; color:#FFFFFF; line-height:22px;">&ndash;</td>
                                    </tr>
                                </table>
                            </td>
                            <td style="vertical-align:top; padding-top:{{ $i === 0 ? '0' : '12px' }};">
                                <p style="margin:0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:14px; font-weight:700; color:#0F172A;">
                                    {{ $p['nombre'] }}
                                </p>
                                <p style="margin:2px 0 0 0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; color:#B45309;">
                                    {{ $p['etapa'] }} &middot; Sin firmar
                                </p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>
@endif
