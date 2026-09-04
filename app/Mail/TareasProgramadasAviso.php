<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TareasProgramadasAviso extends Mailable
{
    use Queueable;
    use SerializesModels;

    public const TIPO_CREADAS = 'creadas';
    public const TIPO_CRITICAS = 'criticas';

    public Collection $tareas;
    public string $tipo;

    public function __construct(Collection $tareas, string $tipo)
    {
        $this->tareas = $tareas;
        $this->tipo = $tipo === self::TIPO_CRITICAS ? self::TIPO_CRITICAS : self::TIPO_CREADAS;
    }

    public function build(): self
    {
        $total = $this->tareas->count();

        $asunto = $this->tipo === self::TIPO_CRITICAS
            ? "Tareas programadas en estado crítico ({$total})"
            : "Tareas programadas generadas hoy ({$total})";

        return $this
            ->subject($asunto)
            ->view('emails.tareas_programadas')
            ->with([
                'tareas' => $this->tareas,
                'esCritico' => $this->tipo === self::TIPO_CRITICAS,
                'urlTareas' => route('tickets.index', ['tab' => 'tareas']),
            ]);
    }
}
