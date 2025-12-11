<table>
    <!-- Encabezado principal -->
    <tr>
        <td colspan="18" style="background: linear-gradient(90deg, #1E3A8A 0%, #3B82F6 100%); color: white; font-weight: bold; text-align: center; padding: 20px; font-size: 16px; border: 2px solid #1E3A8A;">
            REPORTE MENSUAL DE TICKETS - DETALLE
            <br>
            <span style="font-size: 14px; font-weight: normal;">{{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }}</span>
            <br>
            <span style="font-size: 12px; font-weight: normal; opacity: 0.9;">Total de Registros: {{ $tickets->count() }}</span>
        </td>
    </tr>
    <thead>
        <tr>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;"># Ticket</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Descripción</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Fecha Creación</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Fecha Inicio Progreso</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Fecha Fin Progreso</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Tiempo Respuesta (h)</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Tiempo Resolución (h)</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Prioridad</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Estado</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Gerencia</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Empleado Creador</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Correo Creador</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Teléfono Creador</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Empleado Resolutor</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Clasificación</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Subtipo</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Tertipo</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Código AnyDesk</th>
            <th style="background-color: #1E3A8A; color: white; font-weight: bold; text-align: center; padding: 12px; border: 1px solid #1E40AF;">Número</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tickets as $ticket)
        @php
            $prioridadColor = $ticket->Prioridad == 'Alta' ? '#DC2626' : ($ticket->Prioridad == 'Media' ? '#D97706' : '#059669');
            $estadoColor = $ticket->Estatus == 'Cerrado' ? '#059669' : ($ticket->Estatus == 'En progreso' ? '#D97706' : '#DC2626');
            $rowColor = $loop->even ? '#F9FAFB' : 'white';
        @endphp
        <tr style="background-color: {{ $rowColor }};">
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #1E40AF;">{{ $ticket->TicketID }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">{{ $ticket->Descripcion }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center;">{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i:s') : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center;">{{ $ticket->FechaInicioProgreso ? $ticket->FechaInicioProgreso->format('d/m/Y H:i:s') : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center;">{{ $ticket->FechaFinProgreso ? $ticket->FechaFinProgreso->format('d/m/Y H:i:s') : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; color: #7C3AED; font-weight: 600;">{{ $ticket->tiempo_respuesta ? number_format($ticket->tiempo_respuesta, 2) : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; color: #EA580C; font-weight: 600;">{{ $ticket->tiempo_resolucion ? number_format($ticket->tiempo_resolucion, 2) : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: {{ $prioridadColor }};">{{ $ticket->Prioridad }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: {{ $estadoColor }};">{{ $ticket->Estatus }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">
                @if($ticket->empleado && $ticket->empleado->gerencia)
                    {{ $ticket->empleado->gerencia->NombreGerencia ?? 'Sin gerencia' }}
                @else
                    <span style="color: #9CA3AF;">Sin gerencia</span>
                @endif
            </td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">{{ $ticket->empleado ? $ticket->empleado->NombreEmpleado : '<span style="color: #9CA3AF;">Sin empleado</span>' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; color: #4B5563;">{{ $ticket->empleado && $ticket->empleado->Correo ? $ticket->empleado->Correo : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; color: #4B5563;">{{ $ticket->empleado && $ticket->empleado->NumTelefono ? $ticket->empleado->NumTelefono : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">{{ $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : '<span style="color: #9CA3AF;">Sin responsable</span>' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">
                @if($ticket->tipoticket)
                    {{ $ticket->tipoticket->NombreTipo ?? '-' }}
                @else
                    <span style="color: #9CA3AF;">-</span>
                @endif
            </td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">
                @if($ticket->tipoticket && $ticket->tipoticket->subtipoid)
                    {{ $ticket->tipoticket->subtipoid->NombreSubtipo ?? '-' }}
                @else
                    <span style="color: #9CA3AF;">-</span>
                @endif
            </td>
            <td style="padding: 10px; border: 1px solid #E5E7EB;">
                @if($ticket->tipoticket && $ticket->tipoticket->subtipoid && $ticket->tipoticket->subtipoid->tertipoid)
                    {{ $ticket->tipoticket->subtipoid->tertipoid->NombreTertipo ?? '-' }}
                @else
                    <span style="color: #9CA3AF;">-</span>
                @endif
            </td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center;">{{ $ticket->CodeAnyDesk ? $ticket->CodeAnyDesk : '-' }}</td>
            <td style="padding: 10px; border: 1px solid #E5E7EB; text-align: center;">{{ $ticket->Numero ? $ticket->Numero : '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="18" style="padding: 15px; text-align: center; color: #9CA3AF;">No hay tickets disponibles para este mes</td>
        </tr>
        @endforelse
    </tbody>
</table>

