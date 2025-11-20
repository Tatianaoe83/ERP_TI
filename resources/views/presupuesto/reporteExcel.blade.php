<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>REPORTE {{ $title }}</title>
   
</head>
<body>


    @php

                    $nombreDB = DB::connection()->getDatabaseName();

                    $año = (strpos($nombreDB, '2026') !== false) ? '2026' : '2025';

                    @endphp
    <table >
	<tr>
		<td >
            PRESUPUESTO DE TECNOLOGIAS {{ $title }} {{ $año }}
		</td>
		
	</tr>
	<tr>
		<td >
            Gerencia: {{$GerenciaTb->NombreGerencia ?? ''}}
		</td>
	</tr>
	<tr>
		<td >
        Nombre del Gerente: {{$GerenciaTb->NombreGerente ?? ''}}
		</td>
	</tr>
	<tr>
		<td >
            Número de empleados: {{$GerenciaTb->CantidadEmpleados ?? ''}}
		</td>
	</tr>
    @foreach ($datosheader as $datosheade )
    <tr>
		<td >
        {{$datosheade->Categoria}}:
		</td>
		<td >
        {{$datosheade->TotalCosto, 0}}
		</td>
	</tr>
    @endforeach

</table>





<p style="background-color: #cccccc;">Presupuesto de Licenciamiento</p>

<table class="table" style="background-color: #cccccc;">
        <thead>
                <tr >
                    <th>NombreEmpleado</th>
                    <th>NombrePuesto</th>
                    @foreach($columnaspresup_lics as $columna => $_)
                        <th>{{ $columna }}</th>
                    @endforeach
                    <th>TotalPorEmpleado</th>
                </tr>
            </thead>
        <tbody>

       
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

                 <!-- Fila de totales -->
            <tr class= "highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                @foreach($columnaspresup_lics as $columna => $_)
                    <td><strong>{{ $totalespresup_lics[$columna] ?? 0 }}</strong></td>
                @endforeach
                <td><strong>{{ $granTotalpresup_lics }}</strong></td>
            </tr>

        </tbody>
    </table>

    <p>Presupuesto Inversiones</p>

    <table class="table">
        <thead>
                <tr style="background-color: #191970; color:white; text-align: center;">
                    <th>NombreEmpleado</th>
                    <th>NombrePuesto</th>
                    @foreach($columnashardware as $columna => $_)
                        <th>{{ $columna }}</th>
                    @endforeach
                    <th>TotalPorEmpleado</th>
                </tr>
            </thead>
        <tbody>

    
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

                <!-- Fila de totales -->
            <tr class= "highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                @foreach($columnashardware as $columna => $_)
                    <td><strong>{{ $totaleshardware[$columna] ?? 0 }}</strong></td>
                @endforeach

                <td><strong>{{ $granTotalhardware }}</strong></td>
            </tr>

        </tbody>
    </table>

    <p>Presupuesto Accesorios y Otros Insumos</p>

    <table class="table">
        <thead>
                <tr >
                    <th>NombreEmpleado</th>
                    <th>NombrePuesto</th>
                    @foreach($columnaspresup_otrosinsums as $columna => $_)
                        <th>{{ $columna }}</th>
                    @endforeach
                    <th>TotalPorEmpleado</th>
                </tr>
            </thead>
        <tbody>

       
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

                 <!-- Fila de totales -->
            <tr >
                <td><strong>TOTAL</strong></td>
                <td></td>
                @foreach($columnaspresup_otrosinsums as $columna => $_)
                    <td><strong>{{ $totalespresup_otrosinsums[$columna] ?? 0 }}</strong></td>
                @endforeach
                <td> <strong>{{ $granTotalpresup_otrosinsums }}</strong> </td>
            </tr>

        </tbody>
    </table>

    <p>Presupuesto de Telefonía</p>

    <table class="table">
        <thead>
            <tr >
                <th scope="col">NombreEmpleado</th>
                <th scope="col">NombrePuesto</th>
                <th scope="col">Voz Costo Renta {{$dato}}</th>
                <th scope="col">Voz Costo Fianza {{$dato == 'Anual' ? $dato : ''}}</th>
                <th scope="col">Voz Monto Renovacion {{$dato == 'Anual' ? $dato : ''}}</th>
                
            </tr>
        </thead>
        <tbody>
            @foreach ($presup_acces as $presup_acce)
                <tr class="{{ $presup_acce->Orden == 1 ? 'highlight-row' : '' }}">
                    <th>{{$presup_acce->NombreEmpleado}}</th>
                    <td>{{$presup_acce->NombrePuesto}}</td>
                    <td>{{ $dato == 'Anual' ? $presup_acce->Voz_Costo_Renta_Anual : $presup_acce->Voz_Costo_Renta_Mensual }}</td>
                    <td>{{ $dato == 'Anual' ? $presup_acce->Voz_Costo_Fianza_Anual : $presup_acce->Voz_Costo_Fianza }}  </td>
                    <td>{{ $dato == 'Anual' ? $presup_acce->Voz_Monto_Renovacion_Anual : $presup_acce->Voz_Monto_Renovacion }}</td>
                   
                </tr>
            @endforeach
            
            <!-- Fila de totales verticales -->
            <tr class="highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                <td><strong>{{ number_format(collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Costo_Renta_Anual : $item->Voz_Costo_Renta_Mensual; }), 0) }}</strong></td>
                <td><strong>{{ number_format(collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Costo_Fianza_Anual : $item->Voz_Costo_Fianza; }), 0) }}</strong></td>
                <td><strong>{{ number_format(collect($presup_acces)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Voz_Monto_Renovacion_Anual : $item->Voz_Monto_Renovacion; }), 0) }}</strong></td>
               
            </tr>
        </tbody>
    </table>

    <p>Presupuesto de Datos</p>

    <table class="table">
        <thead>
            <tr >
                <th scope="col">NombreEmpleado</th>
                <th scope="col">NombrePuesto</th>
                <th scope="col">Datos Costo Renta {{$dato}} </th>
                <th scope="col">Datos Costo Fianza {{$dato == 'Anual' ? $dato : ''}}</th>
                <th scope="col">Datos Monto Renovacion {{$dato == 'Anual' ? $dato : ''}}</th>
               
            </tr>
        </thead>
        <tbody>
            @foreach ($presup_datos as $presup_dato)

                <tr class="{{ $presup_dato->Orden == 1 ? 'highlight-row' : '' }}">
                    <th>{{$presup_dato->NombreEmpleado}}</th>
                    <td>{{$presup_dato->NombrePuesto}}</td>
                    <td>{{ $dato == 'Anual' ? $presup_dato->Datos_Costo_Renta_Anual : $presup_dato->Datos_Costo_Renta_Mensual }} </td>
                    <td>{{ $dato == 'Anual' ? $presup_dato->Datos_Costo_Fianza_Anual : $presup_dato->Datos_Costo_Fianza }}  </td>
                    <td>{{ $dato == 'Anual' ? $presup_dato->Datos_Monto_Renovacion_Anual : $presup_dato->Datos_Monto_Renovacion }} </td>
                    
                </tr>
            @endforeach
            
            <!-- Fila de totales verticales -->
            <tr class="highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                <td><strong>{{ number_format(collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Costo_Renta_Anual : $item->Datos_Costo_Renta_Mensual; }), 0) }}</strong></td>
                <td><strong>{{ number_format(collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Costo_Fianza_Anual : $item->Datos_Costo_Fianza; }), 0) }}</strong></td>
                <td><strong>{{ number_format(collect($presup_datos)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->Datos_Monto_Renovacion_Anual : $item->Datos_Monto_Renovacion; }), 0) }}</strong></td>
                
            </tr>
        </tbody>
    </table>

    <p>Presupuesto de GPS</p>

    <table class="table">
        <thead>
            <tr >
                <th scope="col">NombreEmpleado</th>
                <th scope="col">NombrePuesto</th>
                <th scope="col">GPS Costo Renta {{$dato}} </th>
                <th scope="col">GPS Costo Fianza  {{$dato == 'Anual' ? $dato : ''}}</th>
                <th scope="col">GPS Monto Renovacion  {{$dato == 'Anual' ? $dato : ''}}</th>
                
            </tr>
        </thead>
        <tbody>
            @foreach ($presup_gps as $presup_gp)
                <tr class="{{ $presup_gp->Orden == 1 ? 'highlight-row' : '' }}">
                    <th>{{$presup_gp->NombreEmpleado}}</th>
                    <td>{{$presup_gp->NombrePuesto}}</td>
                    <td>{{ $dato == 'Anual' ? $presup_gp->GPS_Costo_Renta_Anual : $presup_gp->GPS_Costo_Renta_Mensual }} </td>
                    <td>{{ $dato == 'Anual' ? $presup_gp->GPS_Costo_Fianza_Anual : $presup_gp->GPS_Costo_Fianza }} </td>
                    <td>{{ $dato == 'Anual' ? $presup_gp->GPS_Monto_Renovacion_Anual : $presup_gp->GPS_Monto_Renovacion }} </td>
                    
                </tr>
            @endforeach
            
            <!-- Fila de totales verticales -->
            <tr class="highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                <td><strong>{{ number_format(collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Costo_Renta_Anual : $item->GPS_Costo_Renta_Mensual; }), 0) }}</strong></td>
                <td><strong>{{ number_format(collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Costo_Fianza_Anual : $item->GPS_Costo_Fianza; }), 0) }}</strong></td>
                <td><strong>{{ number_format(collect($presup_gps)->sum(function($item) use ($dato) { return $dato == 'Anual' ? $item->GPS_Monto_Renovacion_Anual : $item->GPS_Monto_Renovacion; }), 0) }}</strong></td>
                
            </tr>
        </tbody>
    </table>

    <p>Calendario de Pagos</p>

<table class="table">
    <thead>
        <tr >
            <th scope="col">Nombre Insumo</th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            

        </tr>
    </thead>
    <tbody>
        @foreach ($presup_cal_pagos as $presup_cal_pago)
            <tr class="{{ $presup_cal_pago->Orden == 7  ? 'highlight-row' : '' }}">
                <td>{{$presup_cal_pago->NombreInsumo}}</td>
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
    </tbody>
</table>




</body>
</html>
