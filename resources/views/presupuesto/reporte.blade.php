@php
    $seccion = $etiquetaSeccion ?? 'Presupuesto';
    $kpis = collect($datosheader ?? []);
    $kpiTotal = $kpis->last();
    $kpiItems = $kpis->slice(0, max(0, $kpis->count() - 1))->values();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>REPORTE {{ $title }}</title>
    <style>
        @page { margin: 10px 12px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        table { border-collapse: collapse; }
        .brand {
            width: 100%;
            background: #101d49;
            color: #fff;
            margin-bottom: 8px;
        }
        .brand td { padding: 10px 12px; vertical-align: middle; }
        .brand-kicker {
            font-size: 7px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #bfdbfe;
            margin: 0 0 2px;
        }
        .brand-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .brand img { height: 38px; width: auto; }
        .meta {
            width: 100%;
            margin-bottom: 6px;
        }
        .meta td {
            width: 33.33%;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            vertical-align: top;
        }
        .meta .lbl {
            display: block;
            font-size: 6.5px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }
        .meta .val {
            font-size: 8.5px;
            font-weight: bold;
            color: #101d49;
        }
        .note {
            background: #eef2ff;
            border-left: 4px solid #6366f1;
            color: #3730a3;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-size: 7.5px;
        }
        .kpis { width: 100%; margin-bottom: 10px; }
        .kpis td {
            width: 25%;
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            background: #fff;
            vertical-align: top;
        }
        .kpis .lbl {
            display: block;
            font-size: 6.5px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .kpis .val {
            font-size: 10px;
            font-weight: bold;
            color: #101d49;
        }
        .kpis .is-total {
            background: #101d49;
        }
        .kpis .is-total .lbl { color: #bfdbfe; }
        .kpis .is-total .val { color: #fff; }
        .section {
            background: #101d49;
            color: #fff;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 8px;
            margin: 10px 0 0;
        }
        table.data {
            width: 100%;
            margin: 0 0 4px;
            font-size: 7px;
        }
        table.data th {
            background: #101d49;
            color: #fff;
            border: 1px solid #0c1638;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
        }
        table.data td.left { text-align: left; }
        table.data tr.alt td { background: #f8fafc; }
        table.data tr.total td {
            background: #dbeafe;
            color: #101d49;
            font-weight: bold;
        }
    </style>
</head>
<body>

<table class="brand">
    <tr>
        <td>
            <div class="brand-kicker">{{ $tipoDocumento ?? 'PRESUPUESTO' }} · {{ $title }} · {{ $anioDocumento ?? (now()->year + 1) }}</div>
            <div class="brand-title">{{ $tipoDocumento ?? 'PRESUPUESTO' }} DE TECNOLOGÍAS</div>
        </td>
        <td style="width: 160px; text-align: right;">
            <img src="{{ public_path('img/logo.png') }}" alt="Logo">
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td>
            <span class="lbl">Gerencia</span>
            <span class="val">{{ $GerenciaTb->NombreGerencia ?? '' }}</span>
        </td>
        <td>
            <span class="lbl">Gerente</span>
            <span class="val">{{ $GerenciaTb->NombreGerente ?? '' }}</span>
        </td>
        <td>
            <span class="lbl">Empleados</span>
            <span class="val">{{ $GerenciaTb->CantidadEmpleados ?? '' }}</span>
        </td>
    </tr>
</table>

@if(!empty($leyendaInclusion))
    <div class="note">{{ $leyendaInclusion }}</div>
@endif

@if($kpis->isNotEmpty())
<table class="kpis">
    @foreach($kpiItems->chunk(4) as $fila)
        <tr>
            @foreach($fila as $item)
                <td>
                    <span class="lbl">{{ $item->Categoria }}</span>
                    <span class="val">$ {{ number_format((float) $item->TotalCosto, 0) }}</span>
                </td>
            @endforeach
            @for($i = $fila->count(); $i < 4; $i++)
                <td></td>
            @endfor
        </tr>
    @endforeach
    @if($kpiTotal)
        <tr>
            <td class="is-total" colspan="4">
                <span class="lbl">{{ $kpiTotal->Categoria }}</span>
                <span class="val">$ {{ number_format((float) $kpiTotal->TotalCosto, 0) }}</span>
            </td>
        </tr>
    @endif
</table>
@endif

<div class="section">{{ $seccion }} de Licenciamiento</div>
<table class="data">
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Puesto</th>
            @foreach($columnaspresup_lics as $columna => $_)
                <th>{{ $columna }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tablapresup_lics as $empleado)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="left">{{ $empleado['NombreEmpleado'] }}</td>
                <td class="left">{{ $empleado['NombrePuesto'] }}</td>
                @foreach($columnaspresup_lics as $columna => $_)
                    <td>$ {{ number_format((float) ($empleado[$columna] ?? 0), 0) }}</td>
                @endforeach
                <td>$ {{ number_format((float) $empleado['TotalPorEmpleado'], 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="left">TOTAL</td>
            <td></td>
            @foreach($columnaspresup_lics as $columna => $_)
                <td>$ {{ number_format((float) ($totalespresup_lics[$columna] ?? 0), 0) }}</td>
            @endforeach
            <td>$ {{ number_format((float) $granTotalpresup_lics, 0) }}</td>
        </tr>
    </tbody>
</table>

<div class="section">{{ $seccion }} de Inversiones</div>
<table class="data">
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Puesto</th>
            @foreach($columnashardware as $columna => $_)
                <th>{{ $columna }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tablahardware as $empleado)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="left">{{ $empleado['NombreEmpleado'] }}</td>
                <td class="left">{{ $empleado['NombrePuesto'] }}</td>
                @foreach($columnashardware as $columna => $_)
                    <td>$ {{ number_format((float) ($empleado[$columna] ?? 0), 0) }}</td>
                @endforeach
                <td>$ {{ number_format((float) $empleado['TotalPorEmpleado'], 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="left">TOTAL</td>
            <td></td>
            @foreach($columnashardware as $columna => $_)
                <td>$ {{ number_format((float) ($totaleshardware[$columna] ?? 0), 0) }}</td>
            @endforeach
            <td>$ {{ number_format((float) $granTotalhardware, 0) }}</td>
        </tr>
    </tbody>
</table>

<div class="section">{{ $seccion }} de Accesorios y Otros Insumos</div>
<table class="data">
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Puesto</th>
            @foreach($columnaspresup_otrosinsums as $columna => $_)
                <th>{{ $columna }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tablapresup_otrosinsums as $empleado)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="left">{{ $empleado['NombreEmpleado'] }}</td>
                <td class="left">{{ $empleado['NombrePuesto'] }}</td>
                @foreach($columnaspresup_otrosinsums as $columna => $_)
                    <td>$ {{ number_format((float) ($empleado[$columna] ?? 0), 0) }}</td>
                @endforeach
                <td>$ {{ number_format((float) $empleado['TotalPorEmpleado'], 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="left">TOTAL</td>
            <td></td>
            @foreach($columnaspresup_otrosinsums as $columna => $_)
                <td>$ {{ number_format((float) ($totalespresup_otrosinsums[$columna] ?? 0), 0) }}</td>
            @endforeach
            <td>$ {{ number_format((float) $granTotalpresup_otrosinsums, 0) }}</td>
        </tr>
    </tbody>
</table>

<div class="section">{{ $seccion }} de Telefonía</div>
<table class="data">
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Puesto</th>
            <th>Renta {{ $dato }}</th>
            <th>Fianza {{ $dato == 'Anual' ? $dato : '' }}</th>
            <th>Renovación {{ $dato == 'Anual' ? $dato : '' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($presup_acces as $presup_acce)
            <tr class="{{ $presup_acce->Orden == 1 || $loop->even ? ($presup_acce->Orden == 1 ? 'total' : 'alt') : '' }}">
                <td class="left">{{ $presup_acce->NombreEmpleado }}</td>
                <td class="left">{{ $presup_acce->NombrePuesto }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_acce->Voz_Costo_Renta_Anual : $presup_acce->Voz_Costo_Renta_Mensual), 0) }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_acce->Voz_Costo_Fianza_Anual : $presup_acce->Voz_Costo_Fianza), 0) }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_acce->Voz_Monto_Renovacion_Anual : $presup_acce->Voz_Monto_Renovacion), 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="left">TOTAL</td>
            <td></td>
            <td>$ {{ number_format(collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Costo_Renta_Anual : $item->Voz_Costo_Renta_Mensual; }), 0) }}</td>
            <td>$ {{ number_format(collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Costo_Fianza_Anual : $item->Voz_Costo_Fianza; }), 0) }}</td>
            <td>$ {{ number_format(collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Monto_Renovacion_Anual : $item->Voz_Monto_Renovacion; }), 0) }}</td>
        </tr>
    </tbody>
</table>

<div class="section">{{ $seccion }} de Datos</div>
<table class="data">
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Puesto</th>
            <th>Renta {{ $dato }}</th>
            <th>Fianza {{ $dato == 'Anual' ? $dato : '' }}</th>
            <th>Renovación {{ $dato == 'Anual' ? $dato : '' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($presup_datos as $presup_dato)
            <tr class="{{ $presup_dato->Orden == 1 || $loop->even ? ($presup_dato->Orden == 1 ? 'total' : 'alt') : '' }}">
                <td class="left">{{ $presup_dato->NombreEmpleado }}</td>
                <td class="left">{{ $presup_dato->NombrePuesto }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_dato->Datos_Costo_Renta_Anual : $presup_dato->Datos_Costo_Renta_Mensual), 0) }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_dato->Datos_Costo_Fianza_Anual : $presup_dato->Datos_Costo_Fianza), 0) }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_dato->Datos_Monto_Renovacion_Anual : $presup_dato->Datos_Monto_Renovacion), 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="left">TOTAL</td>
            <td></td>
            <td>$ {{ number_format(collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Costo_Renta_Anual : $item->Datos_Costo_Renta_Mensual; }), 0) }}</td>
            <td>$ {{ number_format(collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Costo_Fianza_Anual : $item->Datos_Costo_Fianza; }), 0) }}</td>
            <td>$ {{ number_format(collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Monto_Renovacion_Anual : $item->Datos_Monto_Renovacion; }), 0) }}</td>
        </tr>
    </tbody>
</table>

<div class="section">{{ $seccion }} de GPS</div>
<table class="data">
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Puesto</th>
            <th>Renta {{ $dato }}</th>
            <th>Fianza {{ $dato == 'Anual' ? $dato : '' }}</th>
            <th>Renovación {{ $dato == 'Anual' ? $dato : '' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($presup_gps as $presup_gp)
            <tr class="{{ $presup_gp->Orden == 1 || $loop->even ? ($presup_gp->Orden == 1 ? 'total' : 'alt') : '' }}">
                <td class="left">{{ $presup_gp->NombreEmpleado }}</td>
                <td class="left">{{ $presup_gp->NombrePuesto }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_gp->GPS_Costo_Renta_Anual : $presup_gp->GPS_Costo_Renta_Mensual), 0) }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_gp->GPS_Costo_Fianza_Anual : $presup_gp->GPS_Costo_Fianza), 0) }}</td>
                <td>$ {{ number_format((float) ($dato == 'Anual' ? $presup_gp->GPS_Monto_Renovacion_Anual : $presup_gp->GPS_Monto_Renovacion), 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="left">TOTAL</td>
            <td></td>
            <td>$ {{ number_format(collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Costo_Renta_Anual : $item->GPS_Costo_Renta_Mensual; }), 0) }}</td>
            <td>$ {{ number_format(collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Costo_Fianza_Anual : $item->GPS_Costo_Fianza; }), 0) }}</td>
            <td>$ {{ number_format(collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Monto_Renovacion_Anual : $item->GPS_Monto_Renovacion; }), 0) }}</td>
        </tr>
    </tbody>
</table>

<div class="section">Calendario de Pagos</div>
<table class="data">
    <thead>
        <tr>
            <th>Insumo</th>
            <th>Enero</th>
            <th>Febrero</th>
            <th>Marzo</th>
            <th>Abril</th>
            <th>Mayo</th>
            <th>Junio</th>
            <th>Julio</th>
            <th>Agosto</th>
            <th>Septiembre</th>
            <th>Octubre</th>
            <th>Noviembre</th>
            <th>Diciembre</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($presup_cal_pagos as $presup_cal_pago)
            <tr class="{{ $presup_cal_pago->Orden == 7 ? 'total' : ($loop->even ? 'alt' : '') }}">
                <td class="left">{{ $presup_cal_pago->NombreInsumo }}</td>
                <td>${{ $presup_cal_pago->Enero }}</td>
                <td>${{ $presup_cal_pago->Febrero }}</td>
                <td>${{ $presup_cal_pago->Marzo }}</td>
                <td>${{ $presup_cal_pago->Abril }}</td>
                <td>${{ $presup_cal_pago->Mayo }}</td>
                <td>${{ $presup_cal_pago->Junio }}</td>
                <td>${{ $presup_cal_pago->Julio }}</td>
                <td>${{ $presup_cal_pago->Agosto }}</td>
                <td>${{ $presup_cal_pago->Septiembre }}</td>
                <td>${{ $presup_cal_pago->Octubre }}</td>
                <td>${{ $presup_cal_pago->Noviembre }}</td>
                <td>${{ $presup_cal_pago->Diciembre }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
