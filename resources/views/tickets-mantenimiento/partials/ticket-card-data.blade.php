@php
    $attrs = [
        'data-ticket-id' => $ticket['id'],
        'data-categoria' => $columna ?? '',
        'data-ticket-asunto' => htmlspecialchars($ticket['asunto'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-descripcion' => htmlspecialchars($ticket['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-prioridad' => $ticket['prioridad'] ?? '',
        'data-ticket-estatus' => $ticket['estatus'] ?? '',
        'data-ticket-categoria' => $ticket['categoria'] ?? '',
        'data-ticket-responsable' => $ticket['responsable_id'] ?? '',
        'data-ticket-solicitante' => htmlspecialchars($ticket['solicitante'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-correo' => $ticket['correo'] ?? '',
        'data-ticket-area' => htmlspecialchars($ticket['area'] ?? '', ENT_QUOTES, 'UTF-8'),
        'data-ticket-fecha' => \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i:s'),
        'data-ticket-imagen' => htmlspecialchars(is_array($ticket['imagen'] ?? null) ? json_encode($ticket['imagen']) : ($ticket['imagen'] ?? ''), ENT_QUOTES, 'UTF-8'),
    ];
@endphp
@foreach($attrs as $name => $value)
    {{ $name }}="{{ $value }}"
@endforeach
@if(!empty($ticket['sla']))
    data-ticket-sla="{{ htmlspecialchars(json_encode($ticket['sla']), ENT_QUOTES, 'UTF-8') }}"
@endif
