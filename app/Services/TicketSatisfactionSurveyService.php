<?php

namespace App\Services;

use App\Mail\TicketSatisfactionSurveyMail;
use App\Models\Calificacion;
use App\Models\Tickets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketSatisfactionSurveyService
{
    /**
     * Envía la encuesta de satisfacción para un ticket cerrado.
     * Solo se ejecuta si el ticket está cerrado y tiene resolución válida.
     */
    public function sendSurveyForClosedTicket(Tickets $ticket): ?Calificacion
    {
        if ($ticket->Estatus !== 'Cerrado') {
            return null;
        }

        $resolution = $this->getTicketResolution($ticket);

        if ($resolution === null) {
            return null;
        }

        $ticket->loadMissing('empleado');

        if (!$ticket->empleado) {
            return null;
        }

        $correo = $ticket->empleado->Correo ?? null;

        if (blank($correo)) {
            return null;
        }

        $survey = $this->createOrGetSurveyForTicket($ticket);

        if ($survey === null) {
            return null;
        }

        // No reenviar si ya estaba enviada (sent_at existente y no es recién creada)
        try {
            Mail::to($correo)->send(
                new TicketSatisfactionSurveyMail($ticket, $survey, $resolution)
            );

            DB::transaction(function () use ($survey) {
                $survey->sent_at = now();
                $survey->save();
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando encuesta de satisfacción para ticket #' . $ticket->TicketID . ': ' . $e->getMessage());
        }

        return $survey;
    }

    /**
     * Crea o reutiliza la encuesta para un ticket.
     * Protege contra duplicados.
     */
    public function createOrGetSurveyForTicket(Tickets $ticket): ?Calificacion
    {
        $ticket->loadMissing('calificacion');

        $existing = $ticket->calificacion;

        if ($existing) {
            // Si ya está completada, no reenviar
            if ($existing->isCompleted()) {
                return null;
            }

            // Si está pendiente, reutilizar
            if ($existing->isPending()) {
                return $existing;
            }

            // Si está not_answered, no reenviar
            return null;
        }

        return DB::transaction(function () use ($ticket) {
            return Calificacion::create([
                'ticket_id' => $ticket->TicketID,
                'status' => Calificacion::STATUS_PENDING,
                'sent_at' => null,
                'expires_at' => now()->addDays(1),
            ]);
        });
    }

    /**
     * Obtiene la resolución final guardada del ticket.
     * Retorna null si no hay resolución válida.
     */
    public function getTicketResolution(Tickets $ticket): ?string
    {
        $resolution = $ticket->Resolucion ?? null;

        if (!blank($resolution)) {
            return trim($resolution);
        }

        return null;
    }

    /**
     * Marca como not_answered las encuestas pendientes vencidas.
     * Retorna la cantidad de encuestas actualizadas.
     */
    public function expirePendingSurveys(): int
    {
        return Calificacion::query()
            ->where('status', Calificacion::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNull('fastness')
            ->whereNull('resolution')
            ->whereNull('attention')
            ->update([
                'status' => Calificacion::STATUS_NOT_ANSWERED,
            ]);
    }
}
