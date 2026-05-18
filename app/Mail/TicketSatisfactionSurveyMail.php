<?php

namespace App\Mail;

use App\Models\Calificacion;
use App\Models\Tickets;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketSatisfactionSurveyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Tickets $ticket;
    public Calificacion $survey;
    public string $resolution;

    public function __construct(Tickets $ticket, Calificacion $survey, string $resolution)
    {
        $this->ticket     = $ticket;
        $this->survey     = $survey;
        $this->resolution = $resolution;
    }

    public function build(): self
    {
        return $this
            ->subject('Cuéntanos cómo fue tu experiencia - Ticket #' . $this->ticket->Numero)
            ->view('emails.ticket_satisfaction_survey')
            ->with([
                'ticket'     => $this->ticket,
                'survey'     => $this->survey,
                'resolution' => $this->resolution,
            ]);
    }
}
