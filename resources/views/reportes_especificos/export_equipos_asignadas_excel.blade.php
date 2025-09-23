<table>
    <thead>
        <tr>
            <th>Empleado</th>
            <th>Gerencia</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Folio</th>
            <th>Caracteristicas</th>
            <th>Número de Serie</th>
            <th>Fecha Asignación</th>
            <th>Categoria</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos as $item)
            <tr>
                <td>{{ $item->empleado_nombre }}</td>
                <td>{{ $item->GerenciaEquipo }}</td>
                <td>{{ $item->Marca }}</td>
                <td>{{ $item->Modelo }}</td>
                <td>{{ $item->Folio }}</td>
                <td>{{ $item->Caracteristicas }}</td>
                <td>{{ $item->NumSerie }}</td>
                <td>{{ $item->FechaAsignacion ? \Carbon\Carbon::parse($item->FechaAsignacion)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $item->CategoriaEquipo }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
