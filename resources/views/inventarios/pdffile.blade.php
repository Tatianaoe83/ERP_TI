<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta de Entrega</title>
    <style>
        @page {
            size: Letter; /* Tamaño carta */
            margin: 0cm 1cm 1cm 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 1cm 1cm 1cm 0cm;
            padding: 0;
            background-color: #cccccc;
        }
        .container {
            width: 100%;
            max-width: 720px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }
        .header {
            justify-content: space-between;
            align-items: center;
            /*border-bottom: 3px solid #cccccc;*/
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .logo img {
            max-width: 180px;
        }
        .invoice-date {
            font-size: 12px;
            color: #666;
            text-align:right; 
        }
        h1 {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            margin-top: 10px;
            padding-top:6px;
            padding-bottom: 6px;
            background:#cccccc;
        }
        .subheader {
            text-align: left;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .content {
            font-size: 12px;
            line-height: 1.4;
            margin-bottom: 10px;
            text-align: justify;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #444;
            color: white;
            padding: 5px;
            text-align: center;
        }
        td {
            padding: 5px;
            text-align: center;
        }
        .terms {
            width: 90%;
            margin-left: 25px;
            font-size: 12px;
            line-height: 1.4;
            background: #f8f8f8;
            padding: 10px;
            border-left: 6px solid #444;
            margin-bottom: 10px;
        }
        .signatures {
            width: 90%;
            justify-content: space-between;
            margin-top: 20px;
        }
        .signature {
            text-align: center;
            width: 45%;
        }
        .signature p {
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <div class="logo">
                <img src="{{public_path('img/logo.png')}}" alt="Logo">
            </div>
            <div class="invoice-date">
                <p>Mérida, Yucatán a {{$fecha}}</p>
            </div>
        </div>

        @if ($TipoFor == "1" or $TipoFor == "2" or $TipoFor == "4")
            <h1>CARTA DE ENTREGA DE EQUIPO TI</h1>
        @else
            <h1>CARTA DE ENTREGA DE TELEFONIA</h1>
        @endif

        <p class="subheader">Confirmo que recibo el siguiente equipo, mismo que se detalla a continuación:</p>

        <!-- Tabla de productos -->
        @if ($TipoFor == 4)
                <!-- Tabla especial para TipoFor == 4 (Equipos + Insumos con solo Número de Serie y Descripción) -->
                <table >
                    <tr>
                        <th >
                            NÚMERO DE SERIE
                        </th>
                        <th >
                            DESCRIPCIÓN
                        </th>
                    </tr>
                    <!-- Equipos -->
                    @foreach ($equipos as $equipo)
                        <tr>
                            <td >{{ $equipo->NumSerie ?? 'N/A' }}</td>
                            <td >{{ $equipo->Caracteristicas ?? 'Sin descripción' }}</td>
                        </tr>
                    @endforeach
                    <!-- Insumos -->
                    @if (!empty($insumos) && count($insumos) > 0)
                        @foreach ($insumos as $insumo)
                            <tr>
                                <td >{{ $insumo->NumSerie ?? 'N/A' }}</td>
                                <td >{{ $insumo->Comentarios ?? 'Sin descripción' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </table>
                @else
                <!-- Tabla normal para otros tipos -->
                <table >
                    <tr>
                        <th colspan="2" >
                            OBRA/UBICACION
                        </th>
                        <th colspan="2" >
                            VIGENCIA DEL COMODATO
                        </th>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            {{$obra}}
                        </td>
                        <td colspan="2" >
                            {{$acomodato}}
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2" >
                            NÚMERO DE CONTACTO
                        </th>
                        <th colspan="2" >
                            GERENCIA
                        </th>
                    </tr>
                    <tr>
                        <td colspan="2" >
                            {{$telefono}}
                        </td>
                        <td colspan="2" >
                            {{$gerencia}}
                        </td>
                    </tr>
                    <tr>
                        <th >
                            @if ($TipoFor == 3)
                                NÚMERO DE TELÉFONO
                            @else
                                FOLIO
                            @endif
                        </th>
                        <th >
                            NÚMERO DE SERIE
                        </th>
                        <th colspan="2" >
                            DESCRIPCIÓN
                        </th>
                    </tr>
                    @foreach ($equipos as $equipo)
                        <tr>
                            @if ($TipoFor == 3)
                                <td >{{ $equipo->NumTelefonico }}</td>
                            @else
                                <td >{{ $equipo->Folio }}</td>
                            @endif
                            <td >{{ $equipo->NumSerie ?? 'N/A' }}</td>
                            <td colspan="2" >{{ $equipo->Caracteristicas ?? 'Sin descripción' }}</td>
                        </tr>
                    @endforeach
                    @if (!empty($insumos) && count($insumos) > 0)
                        @foreach ($insumos as $insumo)
                            <tr>
                                <td >{{ $insumo->folio ?? 'N/A' }}</td>
                                <td >{{ $insumo->NumSerie ?? 'N/A' }}</td>
                                <td colspan="2" >{{ $insumo->Comentarios ?? 'Sin descripción' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </table>
                @endif


        <!-- Términos y condiciones -->
        <div class="terms">
        @if ($TipoFor == "4")
            <p>Por favor de firmar este documento para confirmar lo recibido.</p>
        @else   

            <p>
                El equipo o los equipos son propiedad de {{$empresa}} y se entregan en comodato 
                durante el período especificado en la parte superior de la vigencia.
            </p>
            <p>
                Me comprometo a cuidar, darle buen uso, responder por el equipo y acepto que:
            </p>
            <ul>
                <li>En caso de daño, levantar un acta administrativa detallando el hecho y responder por el importe total o parcial del costo de la reparación, según sea el caso.</li>
                <li>En caso de robo, informar de manera inmediata a la empresa e interponer ante la autoridad una denuncia por el hecho y presentar una copia de la misma.</li>
                <li>En caso de extravío, levantar un acta administrativa detallando el hecho y reponer en su totalidad el costo del equipo. </li>
                <li>En caso de extravío, levantar un acta administrativa detallando el hecho y reponer en su totalidad el costo del equipo. </li>
                <li>En caso de reposición del equipo se requiere entregarlo en las mejores condiciones posibles salvo uso cotidiano, en caso de no ser así, me comprometo a responsabilizarme por las reparaciones que se deriven.</li>
                <li> Retornar el equipo al departamento de TI al terminar la vigencia del comodato 
                    @if ($TipoFor == "2")
                        ,incluido los accesorios pertinentes (cable de alimentación, cargador y antena).
                    @elseif ($TipoFor == "3")
                    ,incluido los accesorios pertinentes (SIM).
                    @else
                       .
                    @endif </li>
            </ul>

         @endif
        </div>

        <!-- Firmas -->
        
        <div class="signatures">
            <div class="signature">
                <p>Kyrie Petrakis</p>
                <p>Administrador</p>
            </div>
            <div class="signature">
                <p>Kyrie Petrakis</p>
                <p>Administrador</p>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            
           
        </div>
    </div>
</body>
</html>