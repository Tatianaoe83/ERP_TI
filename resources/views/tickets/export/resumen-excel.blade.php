<table>
    <!-- Encabezado principal -->
    <tr>
        <td colspan="6" style="background: linear-gradient(90deg, #1E3A8A 0%, #3B82F6 100%); color: white; font-weight: bold; text-align: center; padding: 20px; font-size: 16px; border: 2px solid #1E3A8A;">
            REPORTE MENSUAL DE TICKETS - RESUMEN
            <br>
            <span style="font-size: 14px; font-weight: normal;">{{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }}</span>
        </td>
    </tr>
    <!-- Información general -->
    <tr>
        <td colspan="6" style="background-color: #F3F4F6; padding: 15px; border: 1px solid #E5E7EB;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px; font-weight: bold; width: 25%;">Total de Tickets:</td>
                    <td style="padding: 5px; color: #1E40AF; font-weight: bold;">{{ $resumen['total_tickets'] }}</td>
                    <td style="padding: 5px; font-weight: bold; width: 25%;">Tickets Cerrados:</td>
                    <td style="padding: 5px; color: #059669; font-weight: bold;">{{ $resumen['tickets_cerrados'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px; font-weight: bold;">Promedio Tiempo Respuesta:</td>
                    <td style="padding: 5px; color: #7C3AED; font-weight: bold;">{{ number_format($resumen['promedio_tiempo_respuesta'], 2) }} horas</td>
                    <td style="padding: 5px; font-weight: bold;">Promedio Tiempo Resolución:</td>
                    <td style="padding: 5px; color: #EA580C; font-weight: bold;">{{ number_format($resumen['promedio_tiempo_resolucion'], 2) }} horas</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 5px; font-weight: bold;">Porcentaje de Cumplimiento:</td>
                    <td colspan="2" style="padding: 5px; color: {{ $resumen['porcentaje_cumplimiento'] >= 70 ? '#059669' : ($resumen['porcentaje_cumplimiento'] >= 50 ? '#D97706' : '#DC2626') }}; font-weight: bold; font-size: 14px;">{{ $resumen['porcentaje_cumplimiento'] }}%</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td colspan="6" style="height: 10px;"></td></tr>
    <thead>
        <tr>
        <tr>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Gerencia</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Total Incidencias</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Resueltos</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">En Progreso</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Pendientes</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Responsable de Resolución</th>
        </tr>
    </thead>
    <tbody>
        @forelse($resumen['incidencias_por_gerencia'] as $gerenciaData)
        <tr style="background-color: {{ $loop->even ? '#F9FAFB' : 'white' }};">
            <td style="padding: 10px; border: 1px solid #E5E7EB; font-weight: 600;">{{ $gerenciaData['gerencia'] }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #1E40AF;">{{ $gerenciaData['total'] }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #059669;">{{ $gerenciaData['resueltos'] }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #D97706;">{{ $gerenciaData['en_progreso'] ?? 0 }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #DC2626;">{{ $gerenciaData['pendientes'] ?? 0 }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">
                @foreach($gerenciaData['por_responsable'] as $responsable => $cantidad)
                    <strong>{{ $responsable }}:</strong> <span style="color: #059669; font-weight: bold;">{{ $cantidad }}</span><br>
                @endforeach
                @if(empty($gerenciaData['por_responsable']))
                    <span style="color: #9CA3AF;">-</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="padding: 15px; text-align: center; color: #9CA3AF;">No hay datos disponibles</td>
        </tr>
        @endforelse
    </tbody>
</table>

<tr><td colspan="6" style="height: 20px;"></td></tr>
<tr>
    <td colspan="6" style="background: linear-gradient(90deg, #1E3A8A 0%, #3B82F6 100%); color: white; font-weight: bold; text-align: center; padding: 15px; font-size: 14px; border: 2px solid #1E3A8A;">
        TOTALES POR EMPLEADO
    </td>
</tr>
<tr>
    <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Empleado</th>
    <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Total</th>
    <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Cerrados</th>
    <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">En Progreso</th>
    <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Pendientes</th>
    <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">% Cierre</th>
</tr>
@forelse($resumen['totales_por_empleado'] as $empleado)
@php
    $porcentajeCierre = $empleado['total'] > 0 ? round(($empleado['cerrados'] / $empleado['total']) * 100, 1) : 0;
    $colorCierre = $porcentajeCierre >= 70 ? '#059669' : ($porcentajeCierre >= 50 ? '#D97706' : '#DC2626');
@endphp
<tr style="background-color: {{ $loop->even ? '#F9FAFB' : 'white' }};">
    <td style="padding: 10px; border: 1px solid #E5E7EB; font-weight: 600;">{{ $empleado['empleado'] }}</td>
    <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #1E40AF;">{{ $empleado['total'] }}</td>
    <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #059669;">{{ $empleado['cerrados'] }}</td>
    <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #D97706;">{{ $empleado['en_progreso'] }}</td>
    <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #DC2626;">{{ $empleado['pendientes'] }}</td>
    <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: {{ $colorCierre }};">{{ $porcentajeCierre }}%</td>
</tr>
@empty
<tr>
    <td colspan="6" style="padding: 15px; text-align: center; color: #9CA3AF;">No hay datos disponibles</td>
</tr>
@endforelse

