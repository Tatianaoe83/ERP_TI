<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Insumo</th>
            <th>Tipo</th>
            <th>Fecha Asignación</th>
            <th>Número de Serie</th>
            <th>Frecuencia de Pago</th>
            <th>Costo Mensual</th>
            <th>Costo Anual</th>
            <th>Mes de Pago</th>
            <th>Observaciones</th>
            <th>Comentarios</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos as $item)
            <tr>
                <td>{{ $item->empleado_nombre }}</td>
                <td>{{ $item->insumo_nombre }}</td>
                <td>{{ $item->insumo_tipo }}</td>
                <td>{{ $item->FechaAsignacion ? \Carbon\Carbon::parse($item->FechaAsignacion)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $item->num_serie }}</td>
                <td>{{ $item->frecuencia_pago }}</td>
                <td>{{ $item->costo_mensual }}</td>
                <td>{{ $item->costo_anual }}</td>
                <td>{{ $item->mes_pago }}</td>
                <td>{{ $item->observaciones }}</td>
                <td>{{ $item->comentarios }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
