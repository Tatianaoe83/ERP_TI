<table>
    <tr>
        <td colspan="6">REPORTE MENSUAL DE TICKETS - RESUMEN<br>{{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }}</td>
    </tr>
    <tr>
        <td colspan="6">Total de Tickets: {{ $resumen['total_tickets'] }} | Tickets Cerrados: {{ $resumen['tickets_cerrados'] }} | Promedio Tiempo Respuesta: {{ number_format($resumen['promedio_tiempo_respuesta'], 2) }} horas | Promedio Tiempo Resolución: {{ number_format($resumen['promedio_tiempo_resolucion'], 2) }} horas | Porcentaje de Cumplimiento: {{ $resumen['porcentaje_cumplimiento'] }}%</td>
    </tr>
    <tr>
        <td colspan="6"></td>
    </tr>
    <tr>
        <th>Gerencia</th>
        <th>Total Incidencias</th>
        <th>Resueltos</th>
        <th>En Progreso</th>
        <th>Pendientes</th>
        <th>Responsable de Resolución</th>
    </tr>
    @forelse($resumen['incidencias_por_gerencia'] as $gerenciaData)
    <tr>
        <td>{{ strip_tags($gerenciaData['gerencia']) }}</td>
        <td>{{ $gerenciaData['total'] }}</td>
        <td>{{ $gerenciaData['resueltos'] }}</td>
        <td>{{ $gerenciaData['en_progreso'] ?? 0 }}</td>
        <td>{{ $gerenciaData['pendientes'] ?? 0 }}</td>
        <td>
            @if(!empty($gerenciaData['por_responsable']))
                @foreach($gerenciaData['por_responsable'] as $responsable => $cantidad)
                    {{ strip_tags($responsable) }}: {{ $cantidad }}
                    @if(!$loop->last), @endif
                @endforeach
            @else
                -
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6">No hay datos disponibles</td>
    </tr>
    @endforelse
    <tr>
        <td colspan="6"></td>
    </tr>
    <tr>
        <td colspan="6">TOTALES POR EMPLEADO</td>
    </tr>
    <tr>
        <th>Empleado</th>
        <th>Total</th>
        <th>Cerrados</th>
        <th>En Progreso</th>
        <th>Pendientes</th>
        <th>% Cierre</th>
    </tr>
    @forelse($resumen['totales_por_empleado'] as $empleado)
    @php
        $porcentajeCierre = $empleado['total'] > 0 ? round(($empleado['cerrados'] / $empleado['total']) * 100, 1) : 0;
    @endphp
    <tr>
        <td>{{ strip_tags($empleado['empleado']) }}</td>
        <td>{{ $empleado['total'] }}</td>
        <td>{{ $empleado['cerrados'] }}</td>
        <td>{{ $empleado['en_progreso'] }}</td>
        <td>{{ $empleado['pendientes'] }}</td>
        <td>{{ $porcentajeCierre }}%</td>
    </tr>
    @empty
    <tr>
        <td colspan="6">No hay datos disponibles</td>
    </tr>
    @endforelse
</table>
