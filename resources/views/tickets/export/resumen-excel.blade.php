<table>
    <tr>
        <td colspan="6" style="background-color: #1E3A8A; color: #FFFFFF; font-size: 18px; font-weight: bold; text-align: center; padding: 20px; border: 2px solid #1E40AF;">
            REPORTE MENSUAL DE TICKETS - RESUMEN
        </td>
    </tr>
    <tr>
        <td colspan="6" style="background-color: #EFF6FF; padding: 15px; border: 1px solid #BFDBFE; text-align: center; font-size: 12px;">
            <strong>Período:</strong> {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }} | 
            <strong>Total de Tickets:</strong> <span style="color: #1E40AF; font-weight: bold;">{{ $resumen['total_tickets'] }}</span> | 
            <strong>Cerrados:</strong> <span style="color: #059669; font-weight: bold;">{{ $resumen['tickets_cerrados'] }}</span> | 
            <strong>Promedio Respuesta:</strong> <span style="color: #D97706; font-weight: bold;">{{ number_format($resumen['promedio_tiempo_respuesta'] ?? 0, 2) }}h</span> | 
            <strong>Promedio Resolución:</strong> <span style="color: #2563EB; font-weight: bold;">{{ number_format($resumen['promedio_tiempo_resolucion'] ?? 0, 2) }}h</span>
        </td>
    </tr>
    <tr>
        <td colspan="6" style="background-color: #EFF6FF; padding: 12px; border: 1px solid #BFDBFE; font-weight: bold; text-align: center;">
            Total de Tickets: <span style="color: #1E40AF;">{{ $resumen['total_tickets'] }}</span> | 
            Tickets Cerrados: <span style="color: #059669;">{{ $resumen['tickets_cerrados'] }}</span> | 
            Promedio Tiempo Respuesta: <span style="color: #D97706;">{{ number_format($resumen['promedio_tiempo_respuesta'], 2) }} horas</span> | 
            Promedio Tiempo Resolución: <span style="color: #2563EB;">{{ number_format($resumen['promedio_tiempo_resolucion'], 2) }} horas</span> | 
            Porcentaje de Cumplimiento: <span style="color: #059669;">{{ $resumen['porcentaje_cumplimiento'] }}%</span>
        </td>
    </tr>
    <tr>
        <td colspan="6" style="height: 10px;"></td>
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
    <tr style="{{ $loop->even ? 'background-color: #F9FAFB;' : 'background-color: #FFFFFF;' }}">
        <td style="padding: 8px; border: 1px solid #E5E7EB; font-weight: bold;">Todas las Gerencias</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $gerenciaData['total'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #059669; font-weight: bold;">{{ $gerenciaData['resueltos'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #2563EB; font-weight: bold;">{{ $gerenciaData['en_progreso'] ?? 0 }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #D97706; font-weight: bold;">{{ $gerenciaData['pendientes'] ?? 0 }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB;">
            @if(!empty($gerenciaData['por_responsable']))
                @foreach($gerenciaData['por_responsable'] as $responsable => $cantidad)
                    {{ strip_tags($responsable) }} → {{ $cantidad }} ticket(s)@if(!$loop->last){{ "\n" }}@endif
                @endforeach
            @else
                Sin responsable asignado
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align: center; padding: 20px; border: 1px solid #E5E7EB; color: #6B7280; font-style: italic;">No hay datos disponibles</td>
    </tr>
    @endforelse
    <tr>
        <td colspan="6" style="height: 20px;"></td>
    </tr>
    <tr>
        <td colspan="6" style="background-color: #1E3A8A; color: #FFFFFF; font-size: 14px; font-weight: bold; text-align: center; padding: 12px; border: 2px solid #1E40AF;">TICKETS POR GERENCIA Y RESPONSABLE</td>
    </tr>
    <tr>
        <th>Gerencia</th>
        <th>Responsable</th>
        <th>Total</th>
        <th>Cerrados</th>
        <th>En Progreso</th>
        <th>Pendientes</th>
    </tr>
    @forelse($resumen['tickets_por_gerencia_responsable'] ?? [] as $item)
    <tr style="{{ $loop->even ? 'background-color: #F9FAFB;' : 'background-color: #FFFFFF;' }}">
        <td style="padding: 8px; border: 1px solid #E5E7EB; font-weight: bold;">{{ strip_tags($item['gerencia']) }}</td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">{{ strip_tags($item['responsable']) }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $item['total'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #059669; font-weight: bold;">{{ $item['cerrados'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #2563EB; font-weight: bold;">{{ $item['en_progreso'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #D97706; font-weight: bold;">{{ $item['pendientes'] }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align: center; padding: 20px; border: 1px solid #E5E7EB; color: #6B7280; font-style: italic;">No hay datos disponibles</td>
    </tr>
    @endforelse
    <tr>
        <td colspan="6" style="height: 20px;"></td>
    </tr>
    <tr>
        <td colspan="6" style="background-color: #1E3A8A; color: #FFFFFF; font-size: 14px; font-weight: bold; text-align: center; padding: 12px; border: 2px solid #1E40AF;">TOTALES POR EMPLEADO</td>
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
    <tr style="{{ $loop->even ? 'background-color: #F9FAFB;' : 'background-color: #FFFFFF;' }}">
        <td style="padding: 8px; border: 1px solid #E5E7EB; font-weight: bold;">{{ strip_tags($empleado['empleado']) }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $empleado['total'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #059669; font-weight: bold;">{{ $empleado['cerrados'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #2563EB; font-weight: bold;">{{ $empleado['en_progreso'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; color: #D97706; font-weight: bold;">{{ $empleado['pendientes'] }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; font-weight: bold; 
            @if($porcentajeCierre >= 80) color: #059669; 
            @elseif($porcentajeCierre >= 50) color: #D97706; 
            @else color: #DC2626; 
            @endif">{{ $porcentajeCierre }}%</td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align: center; padding: 20px; border: 1px solid #E5E7EB; color: #6B7280; font-style: italic;">No hay datos disponibles</td>
    </tr>
    @endforelse
</table>
