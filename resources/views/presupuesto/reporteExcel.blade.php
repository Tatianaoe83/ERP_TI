<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>REPORTE {{ $title }}</title>
   
</head>
<body>


    <table >
	<tr>
		<td >
            PRESUPUESTO DE TECNOLOGIAS {{ $title }} 2025
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
        Costo {{$datosheade->Categoria}}: $ {{$datosheade->TotalCosto}}
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
                        <td> $ {{ $empleado[$columna] ?? 0 }}</td>
                    @endforeach
                    <td>$ {{ number_format($empleado['TotalPorEmpleado'], 0) }}</td>
                </tr>
            @endforeach

                 <!-- Fila de totales -->
            <tr class= "highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                @foreach($columnaspresup_lics as $columna => $_)
                    <td><strong>$ {{ number_format($totalespresup_lics[$columna] ?? 0) }}</strong></td>
                @endforeach
                <td><strong>$ {{ number_format($granTotalpresup_lics, 0) }}</strong></td>
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
                        <td> $ {{ $empleado[$columna] ?? 0 }}</td>
                    @endforeach
                    <td>$ {{ number_format($empleado['TotalPorEmpleado'], 0) }}</td>
                </tr>
            @endforeach

                <!-- Fila de totales -->
            <tr class= "highlight-row">
                <td><strong>TOTAL</strong></td>
                <td></td>
                @foreach($columnashardware as $columna => $_)
                    <td><strong>$ {{ number_format($totaleshardware[$columna] ?? 0) }}</strong></td>
                @endforeach

                <td><strong>$ {{ number_format($granTotalhardware, 0) }}</strong></td>
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
                        <td> $ {{ $empleado[$columna] ?? 0 }}</td>
                    @endforeach
                    <td>$ {{ number_format($empleado['TotalPorEmpleado'], 0) }}</td>
                </tr>
            @endforeach

                 <!-- Fila de totales -->
            <tr >
                <td><strong>TOTAL</strong></td>
                <td></td>
                @foreach($columnaspresup_otrosinsums as $columna => $_)
                    <td><strong>$ {{ number_format($totalespresup_otrosinsums[$columna] ?? 0) }}</strong></td>
                @endforeach
                <td> <strong>$ {{ number_format($granTotalpresup_otrosinsums, 0) }}</strong> </td>
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
                    <td>$ {{$dato == 'Anual' ? $presup_acce->Voz_Costo_Renta_Anual : $presup_acce->Voz_Costo_Renta_Mensual }}</td>
                    <td>$ {{$dato == 'Anual' ? $presup_acce->Voz_Costo_Fianza_Anual : $presup_acce->Voz_Costo_Fianza }}  </td>
                    <td>$ {{$dato == 'Anual' ? $presup_acce->Voz_Monto_Renovacion_Anual : $presup_acce->Voz_Monto_Renovacion }}</td>
                </tr>
            @endforeach
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
                    <td>$ {{$dato == 'Anual' ? $presup_dato->Datos_Costo_Renta_Anual : $presup_dato->Datos_Costo_Renta_Mensual }} </td>
                    <td>$ {{$dato == 'Anual' ? $presup_dato->Datos_Costo_Fianza_Anual : $presup_dato->Datos_Costo_Fianza }}  </td>
                    <td>$ {{$dato == 'Anual' ? $presup_dato->Datos_Monto_Renovacion_Anual : $presup_dato->Datos_Monto_Renovacion }} </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Presupuesto de GPS</p>

    <table class="table">
        <thead>
            <tr >
                <th scope="col">NombreEmpleado</th>
                <th scope="col">NombrePuesto</th>
                <th scope="col">Datos Costo Renta {{$dato}} </th>
                <th scope="col">Datos Costo Fianza  {{$dato == 'Anual' ? $dato : ''}}</th>
                <th scope="col">Datos Monto Renovacion  {{$dato == 'Anual' ? $dato : ''}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($presup_gps as $presup_gp)
                <tr class="{{ $presup_gp->Orden == 1 ? 'highlight-row' : '' }}">
                    <th>{{$presup_gp->NombreEmpleado}}</th>
                    <td>{{$presup_gp->NombrePuesto}}</td>
                    <td>$ {{$dato == 'Anual' ? $presup_gp->GPS_Costo_Renta_Anual : $presup_gp->GPS_Costo_Renta_Mensual }} </td>
                    <td>$ {{$dato == 'Anual' ? $presup_gp->GPS_Costo_Fianza_Anual : $presup_gp->GPS_Costo_Fianza }} </td>
                    <td>$ {{$dato == 'Anual' ? $presup_gp->GPS_Monto_Renovacion_Anual : $presup_gp->GPS_Monto_Renovacion }} </td>
                </tr>
            @endforeach
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
                <td>${{$presup_cal_pago->Enero}}</td>
                <td>${{$presup_cal_pago->Febrero}}</td>
                <td>${{$presup_cal_pago->Marzo}}</td>
                <td>${{$presup_cal_pago->Abril}}</td>
                <td>${{$presup_cal_pago->Mayo}}</td>
                <td>${{$presup_cal_pago->Junio}}</td>
                <td>${{$presup_cal_pago->Julio}}</td>
                <td>${{$presup_cal_pago->Agosto}}</td>
                <td>${{$presup_cal_pago->Septiembre}}</td>
                <td>${{$presup_cal_pago->Octubre}}</td>
                <td>${{$presup_cal_pago->Noviembre}}</td>
                <td>${{$presup_cal_pago->Diciembre}}</td>

            </tr>
        @endforeach
    </tbody>
</table>




</body>
</html>
