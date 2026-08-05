{{--
    Historial de quién ya aprobó antes de la etapa actual.
    $aprobaciones: array de ['etapa','nombre','fecha','auto','nota']
--}}
@php $aprobaciones = $aprobaciones ?? []; @endphp

@if(count($aprobaciones))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px; margin:16px 0 0 0;">
        <tr>
            <td style="padding:18px 20px; font-family:Segoe UI,Helvetica,Arial,sans-serif;">
                <p style="margin:0 0 14px 0; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#15803D;">Aprobaciones previas</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    @foreach($aprobaciones as $i => $a)
                        <tr>
                            <td width="28" style="vertical-align:top; padding:{{ $i === 0 ? '0' : '12px' }} 12px 0 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td width="22" height="22" align="center" bgcolor="#16A34A" style="width:22px; height:22px; border-radius:11px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; font-weight:700; color:#FFFFFF; line-height:22px;">&#10003;</td>
                                    </tr>
                                </table>
                            </td>
                            <td style="vertical-align:top; padding-top:{{ $i === 0 ? '0' : '12px' }};">
                                <p style="margin:0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:14px; font-weight:700; color:#0F172A;">
                                    {{ $a['nombre'] }}
                                </p>
                                <p style="margin:2px 0 0 0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; color:#15803D;">
                                    {{ $a['etapa'] }} &middot; Aprobado{{ !empty($a['fecha']) ? ' el ' . $a['fecha'] : '' }}
                                </p>
                                @if(!empty($a['nota']))
                                    <p style="margin:4px 0 0 0; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:12px; line-height:1.5; color:#64748B;">{{ $a['nota'] }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>
@endif
