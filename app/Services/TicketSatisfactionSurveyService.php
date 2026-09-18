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
    /** Prefijo de los logs de este flujo, para poder filtrarlos en laravel.log. */
    private const LOG = '[EncuestaMail]';

    /**
     * Envía la encuesta de satisfacción para un ticket cerrado.
     * Solo se ejecuta si el ticket está cerrado y tiene resolución válida.
     */
    public function sendSurveyForClosedTicket(Tickets $ticket): ?Calificacion
    {
        $id = $ticket->TicketID;

        if ($ticket->Estatus !== 'Cerrado') {
            Log::warning(self::LOG . " ticket #{$id} omitido: estatus '{$ticket->Estatus}', no 'Cerrado'");
            return null;
        }

        $resolution = $this->getTicketResolution($ticket);

        if ($resolution === null) {
            Log::warning(self::LOG . " ticket #{$id} omitido: se cerró sin Resolucion");
            return null;
        }

        $ticket->loadMissing('empleado');

        if (!$ticket->empleado) {
            Log::warning(self::LOG . " ticket #{$id} omitido: sin empleado (EmpleadoID={$ticket->EmpleadoID})");
            return null;
        }

        $correo = $ticket->empleado->Correo ?? null;

        if (blank($correo)) {
            Log::warning(self::LOG . " ticket #{$id} omitido: el empleado {$ticket->empleado->EmpleadoID} no tiene Correo");
            return null;
        }

        $survey = $this->createOrGetSurveyForTicket($ticket);

        if ($survey === null) {
            $estadoPrevio = optional($ticket->calificacion)->status ?? 'desconocido';
            Log::warning(self::LOG . " ticket #{$id} omitido: ya tenía encuesta en estado '{$estadoPrevio}'");
            return null;
        }

        Log::info(self::LOG . " ticket #{$id} config | " . SmtpDiagnostico::config());

        $mailable = new TicketSatisfactionSurveyMail($ticket, $survey, $resolution);
        $diag = SmtpDiagnostico::para($mailable);

        // No reenviar si ya estaba enviada (sent_at existente y no es recién creada)
        try {
            Mail::to($correo)->send($mailable);

            DB::transaction(function () use ($survey) {
                $survey->sent_at = now();
                $survey->save();
            });

            // Sin uuid de la encuesta: es el token del enlace y el log no es lugar para credenciales.
            Log::info(self::LOG . " ticket #{$id} aceptado por SMTP | para={$correo}"
                . " | encuesta {$survey->survey_id}"
                . " | reenvio=" . ($survey->wasRecentlyCreated ? 'no' : 'si')
                . " | " . $diag->resumen());
        } catch (\Throwable $e) {
            Log::error(self::LOG . " ticket #{$id} FALLO al enviar a {$correo}: " . $e->getMessage() . " | " . $diag->resumen());
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
