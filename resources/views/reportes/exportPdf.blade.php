<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte - {{ $reportes->title ?? 'Exportación' }}</title>
    <style>
        @page {
            size: Letter landscape;
            margin: 1cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #222222;
        }

        /* ── Header ── */
        .header {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #191970;
            padding-bottom: 5px;
        }

        .header-logo {
            float: left;
            width: 50%;
        }

        .header-logo img {
            max-width: 150px;
            max-height: 50px;
        }

        .header-date {
            float: right;
            width: 50%;
            text-align: right;
            font-size: 9px;
            color: #555555;
            padding-top: 10px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* ── Título ── */
        h1 {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0 10px 0;
            padding: 5px 0;
            background-color: #191970;
            color: #ffffff;
            letter-spacing: 1px;
        }

        /* ── Tabla ── */
        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            /* CRÍTICO PARA DOMPDF: Fuerza a respetar el ancho de la tabla */
            table-layout: fixed; 
            font-size: 7px; /* Letra pequeña para que quepa mucha info */
        }

        thead tr th {
            background-color: #191970;
            color: #ffffff;
            padding: 4px 2px;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            border: 1px solid #0f0f50;
            
            /* Permite que el título de la columna baje de línea */
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        tbody tr td {
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #cccccc;
            
            /* CRÍTICO: Obliga al texto a bajar a la siguiente línea si no cabe */
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        tbody tr:nth-child(even) td {
            background-color: #f2f4ff;
        }

        tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        .no-data td {
            text-align: center;
            padding: 12px;
            color: #888888;
            font-style: italic;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 10px;
            color: #999999;
            border-top: 1px solid #dddddd;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header clearfix">
        <div class="header-logo">
            <img src="file://{{ public_path('img/logo.png') }}" alt="Logo">
        </div>
        <div class="header-date">
            Mérida, Yucatán a {{ \Carbon\Carbon::now()->format('d/m/Y') }}
        </div>
    </div>

    {{-- Título --}}
    <h1>{{ $nombre_reporte ?? 'Reporte' }}</h1>

    {{-- Cálculos para forzar el ancho de columnas en DomPDF --}}
    @php
        $primeraFila = clone $resultado;
        $primeraFila = $primeraFila->first();
        $encabezados = $primeraFila ? array_keys((array) $primeraFila) : [];
        $totalColumnas = count($encabezados);
        // Dividimos 100% entre el total de columnas. 
        // Si hay 10 columnas, cada una medirá 10% exacto.
        $anchoColumna = $totalColumnas > 0 ? (100 / $totalColumnas) : 100;
    @endphp

    {{-- Tabla de datos --}}
    <table>
        <thead>
            <tr>
                @foreach ($encabezados as $col)
                    {{-- Imprimimos el estilo en línea (width: X%) para forzar a DomPDF --}}
                    <th style="width: {{ $anchoColumna }}%;">
                        {{ ucfirst(str_replace('_', ' ', $col)) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($resultado as $fila)
                <tr>
                    @foreach ((array) $fila as $valor)
                        <td>
                            @if ($valor !== null && $valor !== '')
                                {{ $valor }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr class="no-data">
                    <td colspan="{{ $totalColumnas > 0 ? $totalColumnas : 1 }}">No hay datos para mostrar</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
        &nbsp;|&nbsp;
        Total de registros: {{ $resultado->count() }}
    </div>

</body>

</html>