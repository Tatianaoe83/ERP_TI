{{--
    Vista única para todos los correos del flujo de solicitudes.
    El servicio arma el contenido (datos, aprobaciones previas, ganadores) según la etapa.
--}}
@extends('emails.solicitudes.layout')

@section('contenido')
    @include('emails.solicitudes.partials.datos', [
        'filas'   => $filas ?? [],
        'bloques' => $bloques ?? [],
    ])

    @include('emails.solicitudes.partials.aprobaciones', [
        'aprobaciones' => $aprobaciones ?? [],
    ])

    @include('emails.solicitudes.partials.ganadores', [
        'ganadores' => $ganadores ?? collect(),
    ])

    @if(!empty($aviso))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $accentSoft ?? '#F0FDFA' }}; border-left:4px solid {{ $accent ?? '#0F766E' }}; border-radius:8px; margin:16px 0 0 0;">
            <tr>
                <td style="padding:14px 16px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; line-height:1.6; color:#334155;">
                    {!! $aviso !!}
                </td>
            </tr>
        </table>
    @endif
@endsection
