<table>
    <tr>
        <td colspan="18">REPORTE MENSUAL DE TICKETS - DETALLE<br>{{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY') }}<br>Total de Registros: {{ $tickets->count() }}</td>
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
    <tr>
        <td>{{ $ticket->TicketID ?? '-' }}</td>
        <td>{{ strip_tags($ticket->Descripcion ?? '-') }}</td>
        <td>{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i:s') : '-' }}</td>
        <td>{{ $ticket->FechaInicioProgreso ? $ticket->FechaInicioProgreso->format('d/m/Y H:i:s') : '-' }}</td>
        <td>{{ $ticket->FechaFinProgreso ? $ticket->FechaFinProgreso->format('d/m/Y H:i:s') : '-' }}</td>
        <td>{{ $ticket->tiempo_respuesta ? number_format($ticket->tiempo_respuesta, 2) : '-' }}</td>
        <td>{{ $ticket->tiempo_resolucion ? number_format($ticket->tiempo_resolucion, 2) : '-' }}</td>
        <td>{{ $ticket->Prioridad }}</td>
        <td>{{ $ticket->Estatus }}</td>
        <td>
            @if($ticket->empleado && $ticket->empleado->gerencia)
                {{ $ticket->empleado->gerencia->NombreGerencia ?? 'Sin gerencia' }}
            @else
                Sin gerencia
            @endif
        </td>
        <td>{{ $ticket->empleado ? strip_tags($ticket->empleado->NombreEmpleado) : 'Sin empleado' }}</td>
        <td>{{ $ticket->empleado && $ticket->empleado->Correo ? strip_tags($ticket->empleado->Correo) : '-' }}</td>
        <td>{{ $ticket->empleado && $ticket->empleado->NumTelefono ? strip_tags($ticket->empleado->NumTelefono) : '-' }}</td>
        <td>{{ $ticket->responsableTI ? strip_tags($ticket->responsableTI->NombreEmpleado) : 'Sin responsable' }}</td>
        <td>
            @if($ticket->tipoticket && $ticket->tipoticket->NombreTipo)
                {{ strip_tags($ticket->tipoticket->NombreTipo) }}
            @else
                -
            @endif
        </td>
        <td>
            @if($ticket->tipoticket && $ticket->tipoticket->subtipoid && $ticket->tipoticket->subtipoid->NombreSubtipo)
                {{ strip_tags($ticket->tipoticket->subtipoid->NombreSubtipo) }}
            @else
                -
            @endif
        </td>
        <td>
            @if($ticket->tipoticket && $ticket->tipoticket->subtipoid && $ticket->tipoticket->subtipoid->tertipoid && $ticket->tipoticket->subtipoid->tertipoid->NombreTertipo)
                {{ strip_tags($ticket->tipoticket->subtipoid->tertipoid->NombreTertipo) }}
            @else
                -
            @endif
        </td>
        <td>{{ $ticket->CodeAnyDesk ? strip_tags($ticket->CodeAnyDesk) : '-' }}</td>
        <td>{{ $ticket->Numero ? strip_tags($ticket->Numero) : '-' }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="18">No hay tickets disponibles para este mes</td>
    </tr>
    @endforelse
</table>
