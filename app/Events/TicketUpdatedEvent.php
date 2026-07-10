<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // <-- Asegúrate de que tenga esta interfaz
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct()
    {
        // Lo dejamos vacío, solo lo usaremos para avisar a los componentes que se refresquen
    }

    public function broadcastOn()
    {
        // Canal público idéntico al que pusimos en los listeners de tus Updaters
        return new Channel('tickets-channel');
    }
}