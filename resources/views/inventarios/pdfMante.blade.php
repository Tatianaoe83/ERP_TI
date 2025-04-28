<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta de Mantenimiento</title>
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
            font-size: 10px;
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

            <h1>CARTA DE CONFORMIDAD</h1>
   

        <p class="subheader">
                Recibí de manera satisfactoria el mantenimiento preventivo a mi equipo de cómputo con folio {{$equipofolio}} <br>
        </p>

         
        <p style="text-align: left;font-size: 12px;">
            Las actividades realizadas son las siguientes:  
        </p>

        <table>
        <thead>
            <tr>
                <th>Realizado</th>
                <th>Actividad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tareas as $numero => $actividad)
                <tr>
                 
                    <td style="text-align: center;">
                        @if (in_array($numero, $seleccionados))
                            <div id="logo"><img src="{{public_path('img/check.png')}}" ></img></div>
                        @endif
                    </td>
                    <td>{{ $actividad }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php

    @endphp 


        <!-- Términos y condiciones -->
        <div class="terms">
    
            <p>
                Nota:
            </p>
            <p>
                Las actividades marcadas son las que se realizaron en el equipo de cómputo.
            </p>
        
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