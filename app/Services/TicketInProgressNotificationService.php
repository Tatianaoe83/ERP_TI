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
        Log::info("[ProgresoMail] Inicio para ticket #{$ticket->TicketID} | Estatus={$ticket->Estatus}");

        if ($ticket->Estatus !== 'En progreso') {
            Log::warning("[ProgresoMail] Saltado ticket #{$ticket->TicketID}: estatus no es 'En progreso' (es '{$ticket->Estatus}')");
            return;
        }

        $ticket->loadMissing('empleado');

        if (!$ticket->empleado) {
            Log::warning("[ProgresoMail] Saltado ticket #{$ticket->TicketID}: empleado null (EmpleadoID=" . var_export($ticket->EmpleadoID, true) . ")");
            return;
        }

        $correo = $ticket->empleado->Correo ?? null;

        if (blank($correo)) {
            Log::warning("[ProgresoMail] Saltado ticket #{$ticket->TicketID}: empleado sin correo (EmpleadoID={$ticket->EmpleadoID})");
            return;
        }

        try {
            Mail::to($correo)->send(new TicketInProgress($ticket));
            Log::info("[ProgresoMail] Enviado ticket #{$ticket->TicketID} a {$correo}");
        } catch (\Throwable $e) {
            Log::error("[ProgresoMail] Error ticket #{$ticket->TicketID}: " . $e->getMessage());
        }
    }
}
