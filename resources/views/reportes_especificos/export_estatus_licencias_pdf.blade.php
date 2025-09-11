<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatus de Licencias Asignadas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        .filters {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filters h3 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 14px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        .filter-label {
            font-weight: bold;
            color: #4b5563;
        }
        .filter-value {
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #2563eb;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .status-activo {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-inactivo {
            background-color: #f3f4f6;
            color: #374151;
        }
        .status-vencido {
            background-color: #fecaca;
            color: #991b1b;
        }
        .status-suspendido {
            background-color: #fef3c7;
            color: #92400e;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #eff6ff;
            border-radius: 5px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 14px;
        }
        .summary p {
            margin: 5px 0;
            color: #1e3a8a;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Estatus de Licencias Asignadas</h1>
        <p>Reporte generado el {{ date('d/m/Y H:i:s') }}</p>
    </div>

    @if(!empty(array_filter($filtros)))
    <div class="filters">
        <h3>Filtros Aplicados:</h3>
        @if(!empty($filtros['empleado_id']))
            @php
                $empleado = \App\Models\Empleados::find($filtros['empleado_id']);
            @endphp
            <div class="filter-item">
                <span class="filter-label">Empleado:</span>
                <span class="filter-value">{{ $empleado ? $empleado->Nombre . ' ' . $empleado->ApellidoPaterno . ' ' . $empleado->ApellidoMaterno : 'N/A' }}</span>
            </div>
        @endif
        @if(!empty($filtros['estatus']))
            <div class="filter-item">
                <span class="filter-label">Estatus:</span>
                <span class="filter-value">{{ $filtros['estatus'] }}</span>
            </div>
        @endif
        @if(!empty($filtros['fecha_desde']))
            <div class="filter-item">
                <span class="filter-label">Fecha Desde:</span>
                <span class="filter-value">{{ \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d/m/Y') }}</span>
            </div>
        @endif
        @if(!empty($filtros['fecha_hasta']))
            <div class="filter-item">
                <span class="filter-label">Fecha Hasta:</span>
                <span class="filter-value">{{ \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y') }}</span>
            </div>
        @endif
    </div>
    @endif

    @if($resultado->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Insumo</th>
                    <th>Tipo</th>
                    <th>Fecha Asignación</th>
                    <th>Estatus</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultado as $item)
                    <tr>
                        <td>{{ $item->empleado_nombre }} {{ $item->empleado_apellido_paterno }} {{ $item->empleado_apellido_materno }}</td>
                        <td>{{ $item->insumo_nombre }}</td>
                        <td>{{ $item->insumo_tipo }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->FechaAsignacion)->format('d/m/Y') }}</td>
                        <td>
                            <span class="status-badge status-{{ strtolower($item->Estatus) }}">
                                {{ $item->Estatus }}
                            </span>
                        </td>
                        <td>{{ $item->Observaciones ?? 'Sin observaciones' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <h3>Resumen del Reporte</h3>
            <p><strong>Total de registros:</strong> {{ $resultado->count() }}</p>
            @php
                $estatusCounts = $resultado->groupBy('Estatus')->map->count();
            @endphp
            @foreach($estatusCounts as $estatus => $count)
                <p><strong>{{ $estatus }}:</strong> {{ $count }} licencias</p>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <h3>No se encontraron resultados</h3>
            <p>No hay licencias asignadas que coincidan con los filtros aplicados.</p>
        </div>
    @endif

    <div class="footer">
        <p>ERP TI Proser - Sistema de Gestión de Tecnologías de la Información</p>
        <p>Página generada automáticamente el {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
