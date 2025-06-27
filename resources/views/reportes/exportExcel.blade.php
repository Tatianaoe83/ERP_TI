<table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 12px;">
    {{-- Fila 1: Logo centrado --}}
    <tr>
        <td colspan="{{ count($columnas) }}" style="text-align: center; padding: 15px 0; background-color: #F9F9F9;">
            <img src="{{ public_path('img/logo.png') }}"
                style="height: 80px; display: block; margin: 0 auto;" />
        </td>
    </tr>

    {{-- Encabezados --}}
    <tr>
        @foreach($columnas as $columna)
        <th style="
                background-color: #4F81BD;
                color: white;
                border: 1px solid #000;
                text-align: center;
                padding: 8px;
                font-weight: bold;
                white-space: nowrap;
            ">
            {{ $columna }}
        </th>
        @endforeach
    </tr>

    {{-- Datos --}}
    @foreach($datos as $fila)
    <tr>
        @foreach($columnas as $col)
        <td style="
                    border: 1px solid #000;
                    padding: 6px;
                    text-align: center;
                    vertical-align: top;
                    white-space: pre-wrap;
                    word-break: break-word;
                ">
            {{ $fila->$col ?? '' }}
        </td>
        @endforeach
    </tr>
    @endforeach
</table>