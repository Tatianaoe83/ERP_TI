<?php

namespace App\Services;

use App\Mail\MantenimientoInProgress;
use App\Models\TicketMantenimiento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MantenimientoInProgressNotificationService
{
    /**
     * Envía la notificación al empleado cuando su mantenimiento pasa a En proceso.
     */
    public function sendNotificationForInProgressTicket(TicketMantenimiento $ticket): void
    {

        if ($ticket->Estatus !== 'En proceso') {
            Log::warning("[MantenimientoProgresoMail] Saltado mantenimiento #{$ticket->MantenimientoID}: estatus no es 'En proceso' (es '{$ticket->Estatus}')");
            return;
        }

        $ticket->loadMissing('empleado');

        if (!$ticket->empleado) {
            Log::warning("[MantenimientoProgresoMail] Saltado mantenimiento #{$ticket->MantenimientoID}: empleado null (EmpleadoID=" . var_export($ticket->EmpleadoID, true) . ")");
            return;
        }

        $correo = $ticket->empleado->Correo ?? null;

        if (blank($correo)) {
            Log::warning("[MantenimientoProgresoMail] Saltado mantenimiento #{$ticket->MantenimientoID}: empleado sin correo (EmpleadoID={$ticket->EmpleadoID})");
            return;
        }

        try {
            Mail::to($correo)->send(new MantenimientoInProgress($ticket));
        } catch (\Throwable $e) {
            Log::error("[MantenimientoProgresoMail] Error mantenimiento #{$ticket->MantenimientoID}: " . $e->getMessage());
        }
    }
}
