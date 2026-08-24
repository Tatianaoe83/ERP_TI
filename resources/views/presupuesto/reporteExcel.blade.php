@php
    $seccion = $etiquetaSeccion ?? 'Presupuesto';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>REPORTE {{ $title }}</title>
</head>
<body>
<table>
    <tr>
        <td>{{ $tipoDocumento ?? 'PRESUPUESTO' }} DE TECNOLOGÍAS {{ $title }} {{ $anioDocumento ?? (now()->year + 1) }}</td>
    </tr>
    <tr>
        <td>Gerencia</td>
        <td>{{ $GerenciaTb->NombreGerencia ?? '' }}</td>
    </tr>
    <tr>
        <td>Gerente</td>
        <td>{{ $GerenciaTb->NombreGerente ?? '' }}</td>
    </tr>
    <tr>
        <td>Empleados</td>
        <td>{{ $GerenciaTb->CantidadEmpleados ?? '' }}</td>
    </tr>
    <tr>
        <td>Alcance</td>
        <td>{{ $leyendaInclusion ?? '' }}</td>
    </tr>
    @foreach ($datosheader as $datosheade)
    <tr>
        <td>{{ $datosheade->Categoria }}</td>
        <td>{{ $datosheade->TotalCosto }}</td>
    </tr>
    @endforeach
</table>

<table>
    <tr>
        <td>{{ $seccion }} de Licenciamiento</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Puesto</th>
        @foreach($columnaspresup_lics as $columna => $_)
            <th>{{ $columna }}</th>
        @endforeach
        <th>Total</th>
    </tr>
    @foreach($tablapresup_lics as $empleado)
    <tr>
        <td>{{ $empleado['NombreEmpleado'] }}</td>
        <td>{{ $empleado['NombrePuesto'] }}</td>
        @foreach($columnaspresup_lics as $columna => $_)
            <td>{{ $empleado[$columna] ?? 0 }}</td>
        @endforeach
        <td>{{ $empleado['TotalPorEmpleado'] }}</td>
    </tr>
    @endforeach
    <tr>
        <td>TOTAL</td>
        <td></td>
        @foreach($columnaspresup_lics as $columna => $_)
            <td>{{ $totalespresup_lics[$columna] ?? 0 }}</td>
        @endforeach
        <td>{{ $granTotalpresup_lics }}</td>
    </tr>
</table>

<table>
    <tr>
        <td>{{ $seccion }} de Inversiones</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Puesto</th>
        @foreach($columnashardware as $columna => $_)
            <th>{{ $columna }}</th>
        @endforeach
        <th>Total</th>
    </tr>
    @foreach($tablahardware as $empleado)
    <tr>
        <td>{{ $empleado['NombreEmpleado'] }}</td>
        <td>{{ $empleado['NombrePuesto'] }}</td>
        @foreach($columnashardware as $columna => $_)
            <td>{{ $empleado[$columna] ?? 0 }}</td>
        @endforeach
        <td>{{ $empleado['TotalPorEmpleado'] }}</td>
    </tr>
    @endforeach
    <tr>
        <td>TOTAL</td>
        <td></td>
        @foreach($columnashardware as $columna => $_)
            <td>{{ $totaleshardware[$columna] ?? 0 }}</td>
        @endforeach
        <td>{{ $granTotalhardware }}</td>
    </tr>
</table>

<table>
    <tr>
        <td>{{ $seccion }} de Accesorios y Otros Insumos</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Puesto</th>
        @foreach($columnaspresup_otrosinsums as $columna => $_)
            <th>{{ $columna }}</th>
        @endforeach
        <th>Total</th>
    </tr>
    @foreach($tablapresup_otrosinsums as $empleado)
    <tr>
        <td>{{ $empleado['NombreEmpleado'] }}</td>
        <td>{{ $empleado['NombrePuesto'] }}</td>
        @foreach($columnaspresup_otrosinsums as $columna => $_)
            <td>{{ $empleado[$columna] ?? 0 }}</td>
        @endforeach
        <td>{{ $empleado['TotalPorEmpleado'] }}</td>
    </tr>
    @endforeach
    <tr>
        <td>TOTAL</td>
        <td></td>
        @foreach($columnaspresup_otrosinsums as $columna => $_)
            <td>{{ $totalespresup_otrosinsums[$columna] ?? 0 }}</td>
        @endforeach
        <td>{{ $granTotalpresup_otrosinsums }}</td>
    </tr>
</table>

<table>
    <tr>
        <td>{{ $seccion }} de Telefonía</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Puesto</th>
        <th>Renta {{ $dato }}</th>
        <th>Fianza {{ $dato == 'Anual' ? $dato : '' }}</th>
        <th>Renovación {{ $dato == 'Anual' ? $dato : '' }}</th>
    </tr>
    @foreach ($presup_acces as $presup_acce)
    <tr>
        <td>{{ $presup_acce->NombreEmpleado }}</td>
        <td>{{ $presup_acce->NombrePuesto }}</td>
        <td>{{ $dato == 'Anual' ? $presup_acce->Voz_Costo_Renta_Anual : $presup_acce->Voz_Costo_Renta_Mensual }}</td>
        <td>{{ $dato == 'Anual' ? $presup_acce->Voz_Costo_Fianza_Anual : $presup_acce->Voz_Costo_Fianza }}</td>
        <td>{{ $dato == 'Anual' ? $presup_acce->Voz_Monto_Renovacion_Anual : $presup_acce->Voz_Monto_Renovacion }}</td>
    </tr>
    @endforeach
    <tr>
        <td>TOTAL</td>
        <td></td>
        <td>{{ collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Costo_Renta_Anual : $item->Voz_Costo_Renta_Mensual; }) }}</td>
        <td>{{ collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Costo_Fianza_Anual : $item->Voz_Costo_Fianza; }) }}</td>
        <td>{{ collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Monto_Renovacion_Anual : $item->Voz_Monto_Renovacion; }) }}</td>
    </tr>
</table>

<table>
    <tr>
        <td>{{ $seccion }} de Datos</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Puesto</th>
        <th>Renta {{ $dato }}</th>
        <th>Fianza {{ $dato == 'Anual' ? $dato : '' }}</th>
        <th>Renovación {{ $dato == 'Anual' ? $dato : '' }}</th>
    </tr>
    @foreach ($presup_datos as $presup_dato)
    <tr>
        <td>{{ $presup_dato->NombreEmpleado }}</td>
        <td>{{ $presup_dato->NombrePuesto }}</td>
        <td>{{ $dato == 'Anual' ? $presup_dato->Datos_Costo_Renta_Anual : $presup_dato->Datos_Costo_Renta_Mensual }}</td>
        <td>{{ $dato == 'Anual' ? $presup_dato->Datos_Costo_Fianza_Anual : $presup_dato->Datos_Costo_Fianza }}</td>
        <td>{{ $dato == 'Anual' ? $presup_dato->Datos_Monto_Renovacion_Anual : $presup_dato->Datos_Monto_Renovacion }}</td>
    </tr>
    @endforeach
    <tr>
        <td>TOTAL</td>
        <td></td>
        <td>{{ collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Costo_Renta_Anual : $item->Datos_Costo_Renta_Mensual; }) }}</td>
        <td>{{ collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Costo_Fianza_Anual : $item->Datos_Costo_Fianza; }) }}</td>
        <td>{{ collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Monto_Renovacion_Anual : $item->Datos_Monto_Renovacion; }) }}</td>
    </tr>
</table>

<table>
    <tr>
        <td>{{ $seccion }} de GPS</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Puesto</th>
        <th>Renta {{ $dato }}</th>
        <th>Fianza {{ $dato == 'Anual' ? $dato : '' }}</th>
        <th>Renovación {{ $dato == 'Anual' ? $dato : '' }}</th>
    </tr>
    @foreach ($presup_gps as $presup_gp)
    <tr>
        <td>{{ $presup_gp->NombreEmpleado }}</td>
        <td>{{ $presup_gp->NombrePuesto }}</td>
        <td>{{ $dato == 'Anual' ? $presup_gp->GPS_Costo_Renta_Anual : $presup_gp->GPS_Costo_Renta_Mensual }}</td>
        <td>{{ $dato == 'Anual' ? $presup_gp->GPS_Costo_Fianza_Anual : $presup_gp->GPS_Costo_Fianza }}</td>
        <td>{{ $dato == 'Anual' ? $presup_gp->GPS_Monto_Renovacion_Anual : $presup_gp->GPS_Monto_Renovacion }}</td>
    </tr>
    @endforeach
    <tr>
        <td>TOTAL</td>
        <td></td>
        <td>{{ collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Costo_Renta_Anual : $item->GPS_Costo_Renta_Mensual; }) }}</td>
        <td>{{ collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Costo_Fianza_Anual : $item->GPS_Costo_Fianza; }) }}</td>
        <td>{{ collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Monto_Renovacion_Anual : $item->GPS_Monto_Renovacion; }) }}</td>
    </tr>
</table>

<table>
    <tr>
        <td>Calendario de Pagos</td>
    </tr>
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
    @foreach ($presup_cal_pagos as $presup_cal_pago)
    <tr>
        <td>{{ $presup_cal_pago->NombreInsumo }}</td>
        <td>{{ $presup_cal_pago->Enero }}</td>
        <td>{{ $presup_cal_pago->Febrero }}</td>
        <td>{{ $presup_cal_pago->Marzo }}</td>
        <td>{{ $presup_cal_pago->Abril }}</td>
        <td>{{ $presup_cal_pago->Mayo }}</td>
        <td>{{ $presup_cal_pago->Junio }}</td>
        <td>{{ $presup_cal_pago->Julio }}</td>
        <td>{{ $presup_cal_pago->Agosto }}</td>
        <td>{{ $presup_cal_pago->Septiembre }}</td>
        <td>{{ $presup_cal_pago->Octubre }}</td>
        <td>{{ $presup_cal_pago->Noviembre }}</td>
        <td>{{ $presup_cal_pago->Diciembre }}</td>
    </tr>
    @endforeach
</table>
</body>
</html>
