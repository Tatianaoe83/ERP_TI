<table>
    <tr>
        <td colspan="19" style="background-color: #1E3A8A; color: #FFFFFF; font-size: 18px; font-weight: bold; text-align: center; padding: 20px; border: 2px solid #1E40AF;">
            REPORTE MENSUAL DE TICKETS - DETALLE
        </td>
    </tr>
    <tr>
        <td colspan="19" style="background-color: #EFF6FF; padding: 15px; border: 1px solid #BFDBFE; text-align: center; font-size: 12px;">
            <strong>Período:</strong> {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }} | 
            <strong>Total de Tickets:</strong> <span style="color: #1E40AF; font-weight: bold;">{{ $tickets->count() }}</span> | 
            <strong>Cerrados:</strong> <span style="color: #059669; font-weight: bold;">{{ $resumen['tickets_cerrados'] ?? 0 }}</span> | 
            <strong>Promedio Respuesta:</strong> <span style="color: #D97706; font-weight: bold;">{{ number_format($resumen['promedio_tiempo_respuesta'] ?? 0, 2) }}h</span> | 
            <strong>Promedio Resolución:</strong> <span style="color: #2563EB; font-weight: bold;">{{ number_format($resumen['promedio_tiempo_resolucion'] ?? 0, 2) }}h</span>
        </td>
    </tr>
    <tr>
        <th># Ticket</th>
        <th>Descripción</th>
        <th>Fecha Creación</th>
        <th>Fecha Inicio Progreso</th>
        <th>Fecha Fin Progreso</th>
        <th>Tiempo Respuesta (h)</th>
        <th>Tiempo Resolución (h)</th>
        <th>Prioridad</th>
        <th>Estado</th>
        <th>Gerencia</th>
        <th>Empleado Creador</th>
        <th>Correo Creador</th>
        <th>Teléfono Creador</th>
        <th>Empleado Resolutor</th>
        <th>Clasificación</th>
        <th>Subtipo</th>
        <th>Tertipo</th>
        <th>Código AnyDesk</th>
        <th>Número</th>
    </tr>
    @forelse($tickets as $ticket)
    <tr style="{{ $loop->even ? 'background-color: #F9FAFB;' : 'background-color: #FFFFFF;' }}">
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->TicketID ?? '-' }}</td>
        <td style="padding: 8px; border: 1px solid #E5E7EB; max-width: 300px;">{{ strip_tags($ticket->Descripcion ?? '-') }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i:s') : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->FechaInicioProgreso ? $ticket->FechaInicioProgreso->format('d/m/Y H:i:s') : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->FechaFinProgreso ? $ticket->FechaFinProgreso->format('d/m/Y H:i:s') : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->tiempo_respuesta ? number_format($ticket->tiempo_respuesta, 2) : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->tiempo_resolucion ? number_format($ticket->tiempo_resolucion, 2) : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; font-weight: bold; 
            @if($ticket->Prioridad == 'Alta') color: #DC2626; 
            @elseif($ticket->Prioridad == 'Media') color: #D97706; 
            @else color: #059669; 
            @endif">{{ $ticket->Prioridad }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB; font-weight: bold;
            @if($ticket->Estatus == 'Cerrado') color: #059669; 
            @elseif($ticket->Estatus == 'En progreso') color: #2563EB; 
            @else color: #D97706; 
            @endif">{{ $ticket->Estatus }}</td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">
            @if($ticket->empleado)
                @php
                    // Obtener la gerencia pasando por: Empleado -> Puesto -> Departamento -> Gerencia
                    $gerencia = null;
                    if ($ticket->empleado->puestos && $ticket->empleado->puestos->departamentos && $ticket->empleado->puestos->departamentos->gerencia) {
                        $gerencia = $ticket->empleado->puestos->departamentos->gerencia->NombreGerencia ?? null;
                    } elseif ($ticket->empleado->gerencia) {
                        // Fallback a la relación directa si existe
                        $gerencia = $ticket->empleado->gerencia->NombreGerencia ?? null;
                    }
                @endphp
                {{ $gerencia ?? 'Sin gerencia' }}
            @else
                Sin empleado
            @endif
        </td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->empleado ? strip_tags($ticket->empleado->NombreEmpleado) : 'Sin empleado' }}</td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->empleado && $ticket->empleado->Correo ? strip_tags($ticket->empleado->Correo) : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->empleado && $ticket->empleado->NumTelefono ? strip_tags($ticket->empleado->NumTelefono) : '-' }}</td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->responsableTI ? strip_tags($ticket->responsableTI->NombreEmpleado) : 'Sin responsable' }}</td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">
            @if($ticket->tipoticket && $ticket->tipoticket->NombreTipo)
                {{ strip_tags($ticket->tipoticket->NombreTipo) }}
            @else
                -
            @endif
        </td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">
            @if($ticket->subtipo && $ticket->subtipo->NombreSubtipo)
                {{ strip_tags($ticket->subtipo->NombreSubtipo) }}
            @else
                -
            @endif
        </td>
        <td style="padding: 8px; border: 1px solid #E5E7EB;">
            @if($ticket->tertipo && $ticket->tertipo->NombreTertipo)
                {{ strip_tags($ticket->tertipo->NombreTertipo) }}
            @else
                -
            @endif
        </td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->CodeAnyDesk ? strip_tags($ticket->CodeAnyDesk) : '-' }}</td>
        <td style="text-align: center; padding: 8px; border: 1px solid #E5E7EB;">{{ $ticket->Numero ? strip_tags($ticket->Numero) : '-' }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="19" style="text-align: center; padding: 20px; border: 1px solid #E5E7EB; color: #6B7280; font-style: italic;">No hay tickets disponibles para este mes</td>
    </tr>
    @endforelse
</table>
