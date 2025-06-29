<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte - {{ $reportes->title }}</title>
    <style>
        @page {
            size: Letter;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .logo img {
            max-width: 180px;
        }

        .invoice-date {
            font-size: 11px;
            color: #666;
            text-align: right;
        }

        h1 {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            padding: 6px 0;
            background: #cccccc;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th,
        td {
            padding: 4px;
            font-size: 10px;
            word-break: break-word;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #444;
            color: white;
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
        <div class="header">
            <div class="logo">
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            </div>
            <div class="invoice-date">
                <p>Mérida, Yucatán a {{ \Carbon\Carbon::now()->format('d-m-Y') }}</p>
            </div>
        </div>

        <h1>Reporte {{ $reportes->title }}</h1>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        @foreach (array_keys((array)$resultado[0] ?? []) as $col)
                        <th>{{ ucfirst($col) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resultado as $fila)
                    <tr>
                        @foreach ((array)$fila as $valor)
                        <td>{{ $valor }}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="100%" class="text-center">No hay datos para mostrar</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
           
        </div>
    </div>
</body>

</html>