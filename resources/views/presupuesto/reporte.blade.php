<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0.5cm 0.5cm 0.5cm 0.5cm;
            font-family: system-ui, sans-serif, Georgia;
            font-size: x-small;
            color: #fffff;
        }

        body {
            margin: 0;
            padding: 0;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: none;
        }

        /* Estilos específicos para las tablas dentro de <main> */
        main table {
            border: 1px solid #ddd;
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
            font-size: xx-small;
            text-align: center;
        }

        main table th, main table td {
            border: 1px solid #ddd;
            padding: 4px;
        }

        p {
            margin: 4px;
            padding: 10px;
            font-weight: bold;
            background-color: darkgray;
        }

        .highlight-row {
            background-color: #ADD8E6;
        }

    </style>

    <title>REPORTE {{ $title }}</title>
</head>
<body>

<header>
    <table class="table" style="border-collapse: collapse; border: none; width: 100%; margin: 0; padding: 0;">
        <tr>
            <td style="padding: 0; width: 70%; vertical-align: top;">
                <div style="margin: 0; padding: 0;">
                  
                    <h3 style="margin: 0; padding: 1px;">PRESUPUESTO DE TECNOLOGIAS {{ $title }} 2025</h3>
                    <h5 style="margin: 0; padding: 1px;">Gerencia: {{$GerenciaTb['0']['NombreGerencia']}}</h5>
                    <h5 style="margin: 0; padding: 1px;">Nombre del Gerente: {{$GerenciaTb['0']['NombreGerente']}}</h5>
                    <h5 style="margin: 0; padding: 1px;">Número de empleados: </h5>
                    <h5 style="margin: 0; padding: 1px;">Costo Licenciamiento: $</h5>
                    <h5 style="margin: 0; padding: 1px;">Costo Inversiones: $</h5>
                    <h5 style="margin: 0; padding: 1px;">Costo Otros Insumos: $</h5>
                    <h5 style="margin: 0; padding: 1px;">Costo Telefonía, Internet y GPS: $</h5>
                    <h5 style="margin: 0; padding: 1px;">Costo Rentas de Impresoras: $</h5>
                    <h5 style="margin: 0; padding: 1px;">Costo Internet fijo: $</h5>
                    <h5 style="margin: 0; padding: 1px;">Total presupuestado: $</h5>
                </div>
            </td>
            <td style="padding: 0; width: 30%; vertical-align: top;">
                <img src="{{ public_path('img/logo.png') }}" alt="Logo Derecho" style="width: 100%; height: auto; display: block;">
            </td>
        </tr>
    </table>
</header>

<main>
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


</main>

</body>
</html>
