<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte</title>
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
            padding: 3px;
            text-align: center;
        }
        td {
            padding: 3px;
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
                <p>Mérida, Yucatán a {{ date('m-d-y')}}</p>
            </div>
        </div>

            <h1>Reporte {{$nombre_reporte}}</h1>
   

      

                <table>
                <thead>
                    <tr>
                        @foreach($columns as $column)
                        <th>{{ $column['title'] }}</th> <!-- Add column titles dynamically -->
                    @endforeach
                       
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr>
                        @foreach($columns as $column)
                            <td>{{ $row[$column['field']] ?? '' }}</td> <!-- Match field with data -->
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>

        <!-- Pie de página -->
        <div class="footer">
            
           
        </div>
    </div>
</body>
</html>