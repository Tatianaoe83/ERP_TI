<!DOCTYPE html>
<html>
<head>
    
    <title>REPORTE {{ $title }}</title>
   
</head>
<body>


    <table style="border-collapse: collapse; border: none; border-spacing: 0px;">
	<tr>
		<td >
            PRESUPUESTO DE TECNOLOGIAS {{ $title }} 2025
		</td>
		
	</tr>
	<tr>
		<td >
            Gerencia: {{$GerenciaTb['0']['NombreGerencia']}}
		</td>
	</tr>
	<tr>
		<td >
         Nombre del Gerente: {{$datosheader->NombreGerente}}
		</td>
	</tr>
	<tr>
		<td >
            Número de empleados: {{$datosheader->CantidadEmpleados}}
		</td>
	</tr>
    <tr>
		<td >
        Costo Licenciamiento:  $ {{$datosheader->Licenciamiento}}
		</td>
	</tr>
    <tr>
		<td >
            Costo Inversiones: $ {{$datosheader->Inversiones}}
		</td>
	</tr>
    <tr>
		<td >
            Costo Otros Insumos: $ {{$datosheader->{'Otros Insumos'} }}
		</td>
	</tr>
    <tr>
		<td >
        Costo Telefonía, Internet y GPS:
		</td>
	</tr>
    <tr>
		<td >
        Costo Rentas de Impresoras: $ {{$datosheader->{'Renta de Impresora'} }}
		</td>
	</tr>
    <tr>
		<td >
            Costo Internet fijo: $ {{$datosheader->Internet}}
		</td>
	</tr>
    <tr>
		<td >
            Total presupuestado: $
		</td>
	</tr>
</table>


<p>Presupuesto de Licenciamiento</p>

    <table class="table">
        <thead>
            <tr style="background-color: #191970; color:white; text-align: center;">
                @if(isset($presup_lics[0]))
                    @foreach(array_keys((array)$presup_lics[0]) as $key)
                        <th scope="col">{{ $key }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($presup_lics as $presup_lic)
                <tr class="{{ $presup_lic->NombreEmpleado == 'TOTAL' ? 'highlight-row' : '' }}">
                    @foreach((array)$presup_lic as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Presupuesto Accesorios y Otros Insumos</p>

<table class="table">
    <thead>
        <tr style="background-color: #191970; color:white; text-align: center;">
            @if(isset($presup_otrosinsums[0]))
                @foreach(array_keys((array)$presup_otrosinsums[0]) as $key)
                    <th scope="col">{{ $key }}</th>
                @endforeach
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($presup_otrosinsums as $presup_otrosinsum)
            <tr class="{{ $presup_otrosinsum->NombreEmpleado == 'TOTAL' ? 'highlight-row' : '' }}">
                @foreach((array)$presup_otrosinsum as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

    <p>Presupuesto de Telefonía</p>

    <table class="table">
        <thead>
            <tr style="background-color: #191970; color:white; text-align: center;">
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
            <tr style="background-color: #191970; color:white; text-align: center;">
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
            <tr style="background-color: #191970; color:white; text-align: center;">
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
        <tr style="background-color: #191970; color:white; text-align: center;">
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
            <tr class="{{ $presup_gp->Orden == 6  ? 'highlight-row' : '' }}">
                <td>{{$presup_cal_pago->NombreInsumo}}</td>
                <td>{{$presup_cal_pago->Enero}}</td>
                <td>{{$presup_cal_pago->Febrero}}</td>
                <td>{{$presup_cal_pago->Marzo}}</td>
                <td>{{$presup_cal_pago->Abril}}</td>
                <td>{{$presup_cal_pago->Mayo}}</td>
                <td>{{$presup_cal_pago->Junio}}</td>
                <td>{{$presup_cal_pago->Julio}}</td>
                <td>{{$presup_cal_pago->Agosto}}</td>
                <td>{{$presup_cal_pago->Septiembre}}</td>
                <td>{{$presup_cal_pago->Octubre}}</td>
                <td>{{$presup_cal_pago->Noviembre}}</td>
                <td>{{$presup_cal_pago->Diciembre}}</td>

            </tr>
        @endforeach
    </tbody>
</table>




</body>
</html>
