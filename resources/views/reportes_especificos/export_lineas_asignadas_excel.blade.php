<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Correo del Empleado</th>
            <th>Número de Línea</th>
            <th>Tipo</th>
            <th>Obra</th>
            <th>Fecha Asignación</th>
            <th>Costo Renta Mensual</th>
            <th>Cuenta Padre</th>
            <th>Cuenta Hija</th>
            <th>Monto Renovación Fianza</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos as $item)
            <tr>
                <td>{{ $item->empleado_nombre }}</td>
                <td>{{ $item->empleado_correo ?? 'N/A' }}</td>
                <td>{{ $item->linea_numero }}</td>
                <td>{{ $item->linea_tipo }}</td>
                <td>{{ $item->obra_nombre }}</td>
                <td>{{ $item->fecha_asignacion ? \Carbon\Carbon::parse($item->fecha_asignacion)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $item->costo_renta_mensual ? '$' . number_format($item->costo_renta_mensual, 2) : 'N/A' }}</td>
                <td>{{ $item->cuenta_padre ?? 'N/A' }}</td>
                <td>{{ $item->cuenta_hija ?? 'N/A' }}</td>
                <td>{{ $item->monto_renovacion_fianza ? '$' . number_format($item->monto_renovacion_fianza, 2) : 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
