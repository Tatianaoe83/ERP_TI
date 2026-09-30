<table>
    <tr>
        <td colspan="9" style="background-color: #1E3A8A; color: #FFFFFF; font-size: 18px; font-weight: bold; text-align: center; padding: 20px; border: 2px solid #1E40AF;">
            TIEMPO DE RESOLUCIÓN POR CATEGORÍA Y RESPONSABLE
        </td>
    </tr>
    <tr>
        <td colspan="9" style="background-color: #EFF6FF; padding: 15px; border: 1px solid #BFDBFE; text-align: center; font-size: 12px;">
            <strong>Período:</strong> {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }} | 
            <strong>Total de Registros:</strong> <span style="color: #1E40AF; font-weight: bold;">{{ count($datos) }}</span>
        </td>
    </tr>
    <tr>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Tipo</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Subtipo</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Tertipo</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Responsable</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Total Tickets</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Tiempo Promedio</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Tiempo Mínimo</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Tiempo Máximo</th>
        <th style="background-color: #4472C4; color: #FFFFFF; font-weight: bold; padding: 12px; border: 1px solid #1E40AF; text-align: center;">Tiempo Total</th>
    </tr>
    @forelse($datos as $index => $dato)
    <tr style="{{ $index % 2 == 0 ? 'background-color: #FFFFFF;' : 'background-color: #F9FAFB;' }}">
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: left; font-weight: bold; color: #1E40AF;">{{ $dato['tipo_nombre'] }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: left; color: #2563EB;">{{ $dato['subtipo_nombre'] }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: left; color: #7C3AED;">{{ $dato['tertipo_nombre'] }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: left; font-weight: bold; color: #2563EB;">{{ $dato['responsable'] }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #059669;">{{ $dato['total_tickets'] }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #2563EB;">{{ \App\Models\Tickets::formatearDuracion($dato['tiempo_promedio']) }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; color: #059669;">{{ \App\Models\Tickets::formatearDuracion($dato['tiempo_minimo']) }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; color: #DC2626;">{{ \App\Models\Tickets::formatearDuracion($dato['tiempo_maximo']) }}</td>
        <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #1E40AF;">{{ \App\Models\Tickets::formatearDuracion($dato['tiempo_total']) }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="9" style="padding: 20px; text-align: center; color: #9CA3AF; font-style: italic;">
            No hay datos disponibles para el período seleccionado.
        </td>
    </tr>
    @endforelse
</table>

