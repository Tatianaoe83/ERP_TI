<?php

namespace App\Mail;

use App\Models\TicketTarea;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Aviso al responsable de que se le asignó una tarea (métrica o evento). */
class TareaAsignadaAviso extends Mailable
{
    use Queueable;
    use SerializesModels;

    public TicketTarea $tarea;

    /** Quién asignó, tomado al construir (en la petición web hay sesión; en cola no). */
    public ?string $asignadoPor;

    public function __construct(TicketTarea $tarea)
    {
        $this->tarea = $tarea;
        $this->asignadoPor = optional(auth()->user())->name;
    }

    public function build(): self
    {
        return $this
            ->subject('Nueva tarea asignada: ' . $this->tarea->titulo)
            ->view('emails.tarea_asignada')
            ->with([
                'tarea' => $this->tarea,
                'asignadoPor' => $this->asignadoPor,
            ]);
    }
}
