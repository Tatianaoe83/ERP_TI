<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Avisos de críticas al buzón de soporte: individual el día que la tarea se vuelve
 * crítica y concentrado en los recordatorios de los días siguientes.
 */
class TareasProgramadasAviso extends Mailable
{
    use Queueable;
    use SerializesModels;

    public const TIPO_CRITICA = 'critica';
    public const TIPO_RECORDATORIO = 'recordatorio';

    public Collection $tareas;
    public string $tipo;

    public function __construct(Collection $tareas, string $tipo)
    {
        $this->tareas = $tareas;
        $this->tipo = $tipo === self::TIPO_RECORDATORIO ? self::TIPO_RECORDATORIO : self::TIPO_CRITICA;
    }

    public function build(): self
    {
        $esRecordatorio = $this->tipo === self::TIPO_RECORDATORIO;
        $total = $this->tareas->count();

        $asunto = $esRecordatorio
            ? "Recordatorio: tareas críticas sin cerrar ({$total})"
            : 'Tarea en estado crítico: ' . $this->tareas->first()->titulo;

        return $this
            ->subject($asunto)
            ->view('emails.tareas_programadas')
            ->with([
                'tareas' => $this->tareas,
                'esRecordatorio' => $esRecordatorio,
            ]);
    }
}
