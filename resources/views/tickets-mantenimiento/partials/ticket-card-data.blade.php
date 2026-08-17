@php
    // Los valores NO se pasan por htmlspecialchars: Blade ya escapa con {{ }}.
    // Hacerlo dos veces deja &quot; literal en el dataset y rompe JSON.parse en el modal.
    $attrs = [
        'data-ticket-id' => $ticket['id'],
        'data-categoria' => $columna ?? '',
        'data-ticket-asunto' => $ticket['asunto'] ?? '',
        'data-ticket-descripcion' => $ticket['descripcion'] ?? '',
        'data-ticket-prioridad' => $ticket['prioridad'] ?? '',
        'data-ticket-estatus' => $ticket['estatus'] ?? '',
        'data-ticket-categoria' => $ticket['categoria'] ?? '',
        'data-ticket-responsable' => $ticket['responsable_id'] ?? '',
        'data-ticket-solicitante' => $ticket['solicitante'] ?? '',
        'data-ticket-correo' => $ticket['correo'] ?? '',
        'data-ticket-area' => $ticket['area'] ?? '',
        'data-ticket-fecha' => \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i:s'),
        'data-ticket-imagen' => is_array($ticket['imagen'] ?? null)
            ? json_encode($ticket['imagen'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : ($ticket['imagen'] ?? ''),
    ];
@endphp
@foreach($attrs as $name => $value)
    {{ $name }}="{{ $value }}"
@endforeach
@if(!empty($ticket['sla']))
    data-ticket-sla="{{ json_encode($ticket['sla'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}"
@endif
