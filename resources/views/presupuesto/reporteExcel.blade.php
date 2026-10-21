@php
    // Mismo diseño que presupuesto.reporte (PDF), sin logo. Estilos inline: los lee el lector HTML de PhpSpreadsheet.
    $seccion = $etiquetaSeccion ?? 'Presupuesto';
    $kpis = collect($datosheader ?? []);
    $kpiTotal = $kpis->last();
    $kpiItems = $kpis->slice(0, max(0, $kpis->count() - 1))->values();
    $n = $totalColumnas ?? 13;
    $moneda = '$#,##0';

    $sKicker = 'background-color:#101d49;color:#bfdbfe;font-size:8;vertical-align:center';
    $sTitulo = 'background-color:#101d49;color:#ffffff;font-size:14;font-weight:bold;vertical-align:center';
    $sMetaLbl = 'background-color:#f8fafc;color:#6b7280;font-size:8;border:1px solid #e5e7eb';
    $sMetaVal = 'background-color:#f8fafc;color:#101d49;font-size:10;font-weight:bold;border:1px solid #e5e7eb;text-align:left';
    $sDesglose = 'background-color:#f8fafc;color:#475569;font-size:8;border:1px solid #e5e7eb';
    $sKpiLbl = 'color:#6b7280;font-size:8;border:1px solid #e5e7eb';
    $sKpiVal = 'color:#101d49;font-size:12;font-weight:bold;border:1px solid #e5e7eb;text-align:left';
    $sKpiTotalLbl = 'background-color:#101d49;color:#bfdbfe;font-size:8';
    $sKpiTotalVal = 'background-color:#101d49;color:#ffffff;font-size:12;font-weight:bold;text-align:left';
    $sSeccion = 'background-color:#101d49;color:#ffffff;font-size:10;font-weight:bold;vertical-align:center';
    $sTh = 'background-color:#101d49;color:#ffffff;font-size:9;font-weight:bold;text-align:center;border:1px solid #0c1638';
    $sTd = 'color:#1f2937;font-size:9;border:1px solid #e5e7eb;text-align:center';
    $sTdIzq = 'color:#1f2937;font-size:9;border:1px solid #e5e7eb;text-align:left';
    $fondoAlt = ';background-color:#f8fafc';
    $sTotal = ';background-color:#dbeafe;color:#101d49;font-weight:bold';

    $nombresTipo = ['FISICA' => 'Física', 'REFERENCIADO' => 'Referenciado', 'EXTRAORDINARIO' => 'Extraordinario'];
    $desglose = collect($GerenciaTb->EmpleadosPorTipo ?? [])
        ->map(fn ($total, $tipo) => ($nombresTipo[$tipo] ?? ucfirst(strtolower($tipo))) . ': ' . $total)
        ->implode('   ·   ');

    // Fila de estilo según el PDF: total (Orden 1 en líneas / 7 en calendario), alterna o normal
    $fila = fn ($esTotal, $esPar) => $esTotal ? $sTotal : ($esPar ? $fondoAlt : '');

    $tablasEmpleado = [
        ['titulo' => 'Licenciamiento', 'columnas' => $columnaspresup_lics, 'filas' => $tablapresup_lics, 'totales' => $totalespresup_lics, 'granTotal' => $granTotalpresup_lics],
        ['titulo' => 'Inversiones', 'columnas' => $columnashardware, 'filas' => $tablahardware, 'totales' => $totaleshardware, 'granTotal' => $granTotalhardware],
        ['titulo' => 'Accesorios y Otros Insumos', 'columnas' => $columnaspresup_otrosinsums, 'filas' => $tablapresup_otrosinsums, 'totales' => $totalespresup_otrosinsums, 'granTotal' => $granTotalpresup_otrosinsums],
    ];

    $anual = $dato == 'Anual';
    $tablasLineas = [
        ['titulo' => 'Telefonía', 'filas' => $presup_acces, 'renta' => $anual ? 'Voz_Costo_Renta_Anual' : 'Voz_Costo_Renta_Mensual', 'fianza' => $anual ? 'Voz_Costo_Fianza_Anual' : 'Voz_Costo_Fianza', 'renovacion' => $anual ? 'Voz_Monto_Renovacion_Anual' : 'Voz_Monto_Renovacion'],
        ['titulo' => 'Datos', 'filas' => $presup_datos, 'renta' => $anual ? 'Datos_Costo_Renta_Anual' : 'Datos_Costo_Renta_Mensual', 'fianza' => $anual ? 'Datos_Costo_Fianza_Anual' : 'Datos_Costo_Fianza', 'renovacion' => $anual ? 'Datos_Monto_Renovacion_Anual' : 'Datos_Monto_Renovacion'],
        ['titulo' => 'GPS', 'filas' => $presup_gps, 'renta' => $anual ? 'GPS_Costo_Renta_Anual' : 'GPS_Costo_Renta_Mensual', 'fianza' => $anual ? 'GPS_Costo_Fianza_Anual' : 'GPS_Costo_Fianza', 'renovacion' => $anual ? 'GPS_Monto_Renovacion_Anual' : 'GPS_Monto_Renovacion'],
    ];

    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>REPORTE {{ $title }}</title>
</head>
<body>
<table>
    {{-- Encabezado --}}
    <tr>
        <td colspan="{{ $n }}" style="{{ $sKicker }}">{{ $tipoDocumento ?? 'PRESUPUESTO' }} · {{ $title }} · {{ $anioDocumento ?? (now()->year + 1) }}</td>
    </tr>
    <tr>
        <td colspan="{{ $n }}" style="{{ $sTitulo }}">{{ $tipoDocumento ?? 'PRESUPUESTO' }} DE TECNOLOGÍAS</td>
    </tr>
    <tr><td colspan="{{ $n }}"></td></tr>

    {{-- Gerencia / Gerente / Empleados --}}
    <tr>
        <td style="{{ $sMetaLbl }}">GERENCIA</td>
        <td style="{{ $sMetaLbl }}">GERENTE</td>
        <td colspan="4" style="{{ $sMetaLbl }}">EMPLEADOS</td>
    </tr>
    <tr>
        <td style="{{ $sMetaVal }}">{{ $GerenciaTb->NombreGerencia ?? '' }}</td>
        <td style="{{ $sMetaVal }}">{{ $GerenciaTb->NombreGerente ?? '' }}</td>
        <td colspan="4" style="{{ $sMetaVal }}" data-format="0">{{ $GerenciaTb->CantidadEmpleados ?? '' }}</td>
    </tr>
    @if($desglose !== '')
    <tr>
        <td style="{{ $sDesglose }}"></td>
        <td style="{{ $sDesglose }}"></td>
        <td colspan="4" style="{{ $sDesglose }}">{{ $desglose }}</td>
    </tr>
    @endif
    <tr><td colspan="{{ $n }}"></td></tr>

    {{-- KPIs: 4 por fila (A, B, C:D, E:F) como en el PDF --}}
    @foreach($kpiItems->chunk(4) as $grupo)
        @php $grupo = $grupo->values(); @endphp
        <tr>
            @for($i = 0; $i < 4; $i++)
                <td @if($i >= 2) colspan="2" @endif style="{{ $sKpiLbl }}">{{ isset($grupo[$i]) ? mb_strtoupper($grupo[$i]->Categoria) : '' }}</td>
            @endfor
        </tr>
        <tr>
            @for($i = 0; $i < 4; $i++)
                <td @if($i >= 2) colspan="2" @endif style="{{ $sKpiVal }}" data-format="{{ $moneda }}">{{ isset($grupo[$i]) ? (float) $grupo[$i]->TotalCosto : '' }}</td>
            @endfor
        </tr>
    @endforeach
    @if($kpiTotal)
        <tr>
            <td colspan="6" style="{{ $sKpiTotalLbl }}">{{ mb_strtoupper($kpiTotal->Categoria) }}</td>
        </tr>
        <tr>
            <td colspan="6" style="{{ $sKpiTotalVal }}" data-format="{{ $moneda }}">{{ (float) $kpiTotal->TotalCosto }}</td>
        </tr>
    @endif

    {{-- Licenciamiento, Inversiones, Accesorios --}}
    @foreach($tablasEmpleado as $tabla)
        @php $ancho = count($tabla['columnas']) + 3; @endphp
        <tr><td colspan="{{ $n }}"></td></tr>
        <tr>
            <td colspan="{{ $ancho }}" style="{{ $sSeccion }}">{{ $seccion }} de {{ $tabla['titulo'] }}</td>
        </tr>
        <tr>
            <th style="{{ $sTh }}">Empleado</th>
            <th style="{{ $sTh }}">Puesto</th>
            @foreach($tabla['columnas'] as $columna => $_)
                <th style="{{ $sTh }}">{{ $columna }}</th>
            @endforeach
            <th style="{{ $sTh }}">Total</th>
        </tr>
        @foreach($tabla['filas'] as $empleado)
            @php $extra = $fila(false, $loop->even); @endphp
            <tr>
                <td style="{{ $sTdIzq . $extra }}">{{ $empleado['NombreEmpleado'] }}</td>
                <td style="{{ $sTdIzq . $extra }}">{{ $empleado['NombrePuesto'] }}</td>
                @foreach($tabla['columnas'] as $columna => $_)
                    <td style="{{ $sTd . $extra }}" data-format="{{ $moneda }}">{{ (float) ($empleado[$columna] ?? 0) }}</td>
                @endforeach
                <td style="{{ $sTd . $extra }}" data-format="{{ $moneda }}">{{ (float) $empleado['TotalPorEmpleado'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="{{ $sTdIzq . $sTotal }}">TOTAL</td>
            <td style="{{ $sTd . $sTotal }}"></td>
            @foreach($tabla['columnas'] as $columna => $_)
                <td style="{{ $sTd . $sTotal }}" data-format="{{ $moneda }}">{{ (float) ($tabla['totales'][$columna] ?? 0) }}</td>
            @endforeach
            <td style="{{ $sTd . $sTotal }}" data-format="{{ $moneda }}">{{ (float) $tabla['granTotal'] }}</td>
        </tr>
    @endforeach

    {{-- Telefonía, Datos, GPS --}}
    @foreach($tablasLineas as $tabla)
        <tr><td colspan="{{ $n }}"></td></tr>
        <tr>
            <td colspan="5" style="{{ $sSeccion }}">{{ $seccion }} de {{ $tabla['titulo'] }}</td>
        </tr>
        <tr>
            <th style="{{ $sTh }}">Empleado</th>
            <th style="{{ $sTh }}">Puesto</th>
            <th style="{{ $sTh }}">Renta {{ $dato }}</th>
            <th style="{{ $sTh }}">Fianza {{ $anual ? $dato : '' }}</th>
            <th style="{{ $sTh }}">Renovación {{ $anual ? $dato : '' }}</th>
        </tr>
        @foreach($tabla['filas'] as $linea)
            @php $extra = $fila(($linea->Orden ?? null) == 1, $loop->even); @endphp
            <tr>
                <td style="{{ $sTdIzq . $extra }}">{{ $linea->NombreEmpleado }}</td>
                <td style="{{ $sTdIzq . $extra }}">{{ $linea->NombrePuesto }}</td>
                <td style="{{ $sTd . $extra }}" data-format="{{ $moneda }}">{{ (float) $linea->{$tabla['renta']} }}</td>
                <td style="{{ $sTd . $extra }}" data-format="{{ $moneda }}">{{ (float) $linea->{$tabla['fianza']} }}</td>
                <td style="{{ $sTd . $extra }}" data-format="{{ $moneda }}">{{ (float) $linea->{$tabla['renovacion']} }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="{{ $sTdIzq . $sTotal }}">TOTAL</td>
            <td style="{{ $sTd . $sTotal }}"></td>
            @foreach(['renta', 'fianza', 'renovacion'] as $campo)
                <td style="{{ $sTd . $sTotal }}" data-format="{{ $moneda }}">{{ (float) collect($tabla['filas'])->sum($tabla[$campo]) }}</td>
            @endforeach
        </tr>
    @endforeach

    {{-- Calendario de pagos --}}
    <tr><td colspan="{{ $n }}"></td></tr>
    <tr>
        <td colspan="13" style="{{ $sSeccion }}">Calendario de Pagos</td>
    </tr>
    <tr>
        <th style="{{ $sTh }}">Insumo</th>
        @foreach($meses as $mes)
            <th style="{{ $sTh }}">{{ $mes }}</th>
        @endforeach
    </tr>
    @foreach($presup_cal_pagos as $pago)
        @php $extra = $fila(($pago->Orden ?? null) == 7, $loop->even); @endphp
        <tr>
            <td style="{{ $sTdIzq . $extra }}">{{ $pago->NombreInsumo }}</td>
            @foreach($meses as $mes)
                <td style="{{ $sTd . $extra }}" data-format="{{ $moneda }}">{{ is_numeric($pago->{$mes}) ? (float) $pago->{$mes} : $pago->{$mes} }}</td>
            @endforeach
        </tr>
    @endforeach
</table>
</body>
</html>
