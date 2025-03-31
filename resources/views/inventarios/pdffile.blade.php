<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta de Entrega</title>
    <style>
        @page {
            size: Letter; /* Tamaño carta */
            margin: 0cm 0cm 1cm 1cm;
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
            font-size: 11px;
            color: #666;
            text-align:right; 
        }
        h1 {
            text-align: center;
            font-size: 16px;
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
            font-size: 11px;
            margin-bottom: 10px;
        }
        .content {
            font-size: 11px;
            line-height: 1.4;
            margin-bottom: 10px;
            text-align: justify;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
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
        td[rowspan] {
        border-bottom: 1px solid #ddd; 
        }
        .terms {
            width: 90%;
            margin-left: 25px;
            font-size: 11px;
            line-height: 1.4;
            background: #f8f8f8;
            padding: 10px;
            border-left: 6px solid #444;
            margin-bottom: 10px;
        }
        .signature-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            border: none;
           
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            padding-top: 0px;
            font-weight: bold;
            font-size: 11px;
           
        }
        .footer {
            text-align: center;
            font-size: 9px;
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

            <h1>CARTA DE ENTREGA DE EQUIPO TI</h1>
   

        <p class="subheader">
            Por medio del presente documento, hago constar que he recibido en comodato el siguiente equipo, bajo las siguientes condiciones:  <br>
        </p>

            <ul style="text-align: justify;font-size: 12px;">
            <li> Nombre empleado: {{$entrega}} </li>
            <li> Puesto: {{$entregapuesto}} </li>
            <li> Obra/Ubicación: {{$obraubi}}  </li>
            <li> Vigencia del Comodato: TERMINACIÓN DE LA RELACIÓN LABORAL</li>
            <li> Número de Contacto: {{$entreganumero}} </li>
            <li> Gerencia:  {{$gerencia}} </li>
        </ul>
        <p style="text-align: left;font-size: 12px;">
            Confirmo que he recibido el siguiente equipo, cuyo detalle se menciona a continuación:  
        </p>

                <table>
                <thead>
                    <tr>
                       
                        <th>Categoría</th>
                        <th>Marca/Nombre</th>
                        <th>Características</th>
                        <th>Modelo</th>
                        <th>Número de Serie</th>
                        <th>Folio / Num. Asignado</th>
                       
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datosInventario as $item)
                    <tr>
                      
                       
                        <td>{{ $item->categoria }}</td>
                        <td>{{ $item->Marca }}</td>
                        <td>{{ $item->Caracteristicas ?? 'N/A' }}</td>
                        <td>{{ $item->Modelo ?? 'N/A' }}</td>
                        <td>{{ $item->NumSerie ?? 'N/A' }}</td>
                        <td>{{ $item->FechaAsignacion ?? 'N/A' }}</td>
                       
                    </tr>
                    @endforeach
                </tbody>
            </table>




        <!-- Términos y condiciones -->
        <div class="terms">
    
            <p>
                El equipo o los equipos son propiedad de {{$obra}} y se entregan en comodato 
                durante el período especificado en la parte superior de la vigencia.
            </p>
            <p>
                Me comprometo a cuidar, darle buen uso, responder por el equipo y acepto que:
            </p>
            <ul>
                <li>En caso de daño, levantar un acta administrativa detallando el hecho y responder por el importe total o parcial del costo de la reparación, según sea el caso.</li>
                <li>En caso de robo, informar de manera inmediata a la empresa e interponer ante la autoridad una denuncia por el hecho y presentar una copia de la misma.</li>
                <li>En caso de extravío, levantar un acta administrativa detallando el hecho y reponer en su totalidad el costo del equipo. </li>
                <li>En caso de reposición del equipo se requiere entregarlo en las mejores condiciones posibles salvo uso cotidiano, en caso de no ser así, me comprometo a responsabilizarme por las reparaciones que se deriven.</li>
                <li> Retornar el equipo al departamento de TI al termino de la relación laboral.
               
            </ul>

      
        </div>

        <!-- Firmas -->
        
       
        <!-- Tabla de Firmas -->
        <table class="signature-table">

            
            <tr>
                <td>
        
                <p style="margin-bottom: 57px;">Entrega:</p> <br>
                {{$recibe}}<br>
                {{$recibepuesto}}
                </td>
                <td> 

                <p style="margin-bottom: 57px;">Recibe:</p><br>
                {{$entrega}}<br>
                {{$entregapuesto}}
                </td>
            </tr>
        </table>

        <!-- Pie de página -->
        <div class="footer">
            
           
        </div>
    </div>
</body>
</html>