<?php

namespace App\Mail;

use App\Models\Tickets;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketInProgress extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Tickets $ticket;

    public function __construct(Tickets $ticket)
    {
        $this->ticket = $ticket;
    }

    public function build(): self
    {
        return $this
            ->subject('Tu ticket esta en progreso - Ticket #' . $this->ticket->TicketID)
            ->view('emails.ticket_in_progress')
            ->with([
                'ticket' => $this->ticket,
            ]);
    }
}
