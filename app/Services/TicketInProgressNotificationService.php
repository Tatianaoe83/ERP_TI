<?php

namespace App\Services;

use App\Mail\TicketInProgress;
use App\Models\Tickets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketInProgressNotificationService
{
    /**
     * Envía la notificación al empleado cuando su ticket pasa a En progreso.
     */
    public function sendNotificationForInProgressTicket(Tickets $ticket): void
    {
        if ($ticket->Estatus !== 'En progreso') {
            return;
        }

        $ticket->loadMissing('empleado');

        if (!$ticket->empleado) {
            return;
        }

        $correo = $ticket->empleado->Correo ?? null;

        if (blank($correo)) {
            return;
        }

        try {
            Mail::to($correo)->send(new TicketInProgress($ticket));
        } catch (\Throwable $e) {
            Log::error("Error enviando aviso de En progreso del ticket #{$ticket->TicketID}: " . $e->getMessage());
        }
    }
}
