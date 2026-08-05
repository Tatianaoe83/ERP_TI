{{--
    Tabla de productos ganadores.
    $ganadores: colección de Cotizacion (Descripcion, NumeroParte, Proveedor, Precio, Cantidad)
--}}
@php
    $ganadores = $ganadores ?? collect();
    $total = 0;
    foreach ($ganadores as $g) {
        $total += (float) ($g->Precio ?? 0) * max(1, (int) ($g->Cantidad ?? 1));
    }
@endphp

@if($ganadores && count($ganadores))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; margin:16px 0 0 0;">
        <tr>
            <td style="padding:18px 20px 8px 20px; font-family:Segoe UI,Helvetica,Arial,sans-serif;">
                <p style="margin:0; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#0F766E;">
                    {{ count($ganadores) > 1 ? 'Productos ganadores' : 'Producto ganador' }}
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:0 20px 18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                    @foreach($ganadores as $c)
                        @php
                            $cant   = max(1, (int) ($c->Cantidad ?? 1));
                            $precio = (float) ($c->Precio ?? 0);
                        @endphp
                        <tr>
                            <td style="padding:12px 0; border-top:1px solid #E2E8F0; font-family:Segoe UI,Helvetica,Arial,sans-serif;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="vertical-align:top; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:14px; font-weight:700; color:#0F172A;">
                                            {{ $c->Descripcion ?? 'N/A' }}@if($cant > 1) <span style="font-weight:600; color:#64748B;">&times; {{ $cant }}</span>@endif
                                        </td>
                                        <td align="right" style="vertical-align:top; white-space:nowrap; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:14px; font-weight:700; color:#0F172A;">
                                            $ {{ number_format($precio, 2, '.', ',') }} MXN
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="padding-top:4px; font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:12px; color:#64748B;">
                                            {{ $c->Proveedor ?? 'Proveedor N/A' }}
                                            @if(!empty($c->NumeroParte)) &middot; No. parte {{ $c->NumeroParte }} @endif
                                            @if($cant > 1) &middot; Subtotal $ {{ number_format($precio * $cant, 2, '.', ',') }} MXN @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="padding:12px 0 0 0; border-top:2px solid #0F766E;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:13px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.05em;">Total</td>
                                    <td align="right" style="font-family:Segoe UI,Helvetica,Arial,sans-serif; font-size:17px; font-weight:700; color:#0F766E;">$ {{ number_format($total, 2, '.', ',') }} MXN</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endif
