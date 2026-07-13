<?php

namespace App\Mail;

use App\Models\TicketMantenimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MantenimientoAtendido extends Mailable
{
    use Queueable;
    use SerializesModels;

    public TicketMantenimiento $ticket;

    public function __construct(TicketMantenimiento $ticket)
    {
        $this->ticket = $ticket;
    }

    public function build(): self
    {
        return $this
            ->subject('Tu solicitud de mantenimiento fue atendida - Mantenimiento #' . $this->ticket->MantenimientoID)
            ->view('emails.mantenimiento_atendido')
            ->with([
                'ticket' => $this->ticket,
            ]);
    }
}
