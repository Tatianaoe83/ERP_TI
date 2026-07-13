<?php

namespace App\Services;

use App\Mail\MantenimientoAtendido;
use App\Models\TicketMantenimiento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MantenimientoAtendidoNotificationService
{
    /**
     * Envía el aviso informativo al empleado cuando su mantenimiento queda Atendido.
     */
    public function sendNotificationForAttendedTicket(TicketMantenimiento $ticket): void
    {
        Log::info("[MantenimientoAtendidoMail] Inicio para mantenimiento #{$ticket->MantenimientoID} | Estatus={$ticket->Estatus}");

        if ($ticket->Estatus !== 'Atendido') {
            Log::warning("[MantenimientoAtendidoMail] Saltado mantenimiento #{$ticket->MantenimientoID}: estatus no es 'Atendido' (es '{$ticket->Estatus}')");
            return;
        }

        $ticket->loadMissing('empleado');

        if (!$ticket->empleado) {
            Log::warning("[MantenimientoAtendidoMail] Saltado mantenimiento #{$ticket->MantenimientoID}: empleado null (EmpleadoID=" . var_export($ticket->EmpleadoID, true) . ")");
            return;
        }

        $correo = $ticket->empleado->Correo ?? null;

        if (blank($correo)) {
            Log::warning("[MantenimientoAtendidoMail] Saltado mantenimiento #{$ticket->MantenimientoID}: empleado sin correo (EmpleadoID={$ticket->EmpleadoID})");
            return;
        }

        try {
            Mail::to($correo)->send(new MantenimientoAtendido($ticket));
            Log::info("[MantenimientoAtendidoMail] Enviado mantenimiento #{$ticket->MantenimientoID} a {$correo}");
        } catch (\Throwable $e) {
            Log::error("[MantenimientoAtendidoMail] Error mantenimiento #{$ticket->MantenimientoID}: " . $e->getMessage());
        }
    }
}
