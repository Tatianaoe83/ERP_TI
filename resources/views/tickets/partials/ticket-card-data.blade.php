@php
    $attrs = [
        'data-ticket-id' => $ticket['id'],
        'data-categoria' => $columna ?? '',
        'data-ticket-asunto' => 'Ticket #' . ($ticket['id'] ?? ''),
        'data-ticket-descripcion' => htmlspecialchars($ticket['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-prioridad' => $ticket['prioridad'] ?? '',
        'data-ticket-empleado' => htmlspecialchars($ticket['empleado_corto'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-responsable' => htmlspecialchars($ticket['responsable_nombre'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-correo' => $ticket['correo'] ?? '',
        'data-ticket-fecha' => \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i:s'),
        'data-ticket-numero' => $ticket['numero'] ?? '',
        'data-ticket-anydesk' => $ticket['code_anydesk'] ?? '',
        'data-ticket-tiempo-estado' => $ticket['tiempo_estado'] ?? '',
        'data-ticket-estatus' => $ticket['estatus'] ?? '',
    ];
@endphp
@foreach($attrs as $name => $value)
    {{ $name }}="{{ $value }}"
@endforeach
