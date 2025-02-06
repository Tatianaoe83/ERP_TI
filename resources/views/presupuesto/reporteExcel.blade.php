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
         Nombre del Gerente: {{$GerenciaTb['0']['NombreGerente']}}
		</td>
	</tr>
	<tr>
		<td >
            Número de empleados: 
		</td>
	</tr>
    <tr>
		<td >
        Costo Licenciamiento: 
		</td>
	</tr>
    <tr>
		<td >
            Costo Inversiones: $
		</td>
	</tr>
    <tr>
		<td >
            Costo Otros Insumos: $
		</td>
	</tr>
    <tr>
		<td >
        Costo Telefonía, Internet y GPS:
		</td>
	</tr>
    <tr>
		<td >
        Costo Rentas de Impresoras: $
		</td>
	</tr>
    <tr>
		<td >
            Costo Internet fijo: $
		</td>
	</tr>
    <tr>
		<td >
            Total presupuestado: $
		</td>
	</tr>
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




</body>
</html>
