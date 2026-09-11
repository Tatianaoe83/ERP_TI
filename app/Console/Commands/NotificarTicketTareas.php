<?php

namespace App\Console\Commands;

use App\Models\TicketTarea;
use App\Services\TicketTareaNotificacionService;
use App\Services\TicketTareaService;
use Illuminate\Console\Command;

class NotificarTicketTareas extends Command
{
    protected $signature = 'tickets:notificar-tareas
                            {--dry-run : Muestra a quién se enviaría sin mandar correos}
                            {--solo= : nuevas|recordatorio}
                            {--marcar-existentes : Da por avisadas las críticas actuales sin mandar correo (para el primer despliegue)}';

    protected $description = 'Avisa por correo las tareas que se volvieron críticas hoy (una por correo) y recuerda en un concentrado las que siguen críticas';

    public function handle(TicketTareaNotificacionService $notificaciones, TicketTareaService $tareas): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $solo = $this->option('solo');

        if ($solo !== null && ! in_array($solo, ['nuevas', 'recordatorio'], true)) {
            $this->error('--solo acepta "nuevas" o "recordatorio".');

            return self::FAILURE;
        }

        // La prioridad se recalcula aquí también: si el comando de la mañana no corrió,
        // el aviso de críticas seguiría mirando una prioridad vieja.
        $tareas->actualizarPrioridades();

        if ($this->option('marcar-existentes')) {
            return $this->marcarExistentes($notificaciones, $dryRun);
        }

        $destinatarios = $notificaciones->destinatarios();

        if ($destinatarios === []) {
            $this->error('No hay un correo válido en config/tareas.php (TAREAS_CORREO_SOPORTE).');

            return self::FAILURE;
        }

        $this->info('Se enviará a: ' . implode(', ', $destinatarios));

        // El recordatorio va primero: las recién críticas se marcan hoy al avisarse y
        // así no se cuelan también en el concentrado del mismo día.
        if ($solo !== 'nuevas') {
            $lote = $notificaciones->criticasPorRecordar();
            $this->mostrarLote('Recordatorio de críticas sin cerrar', $lote);
            $enviadas = $notificaciones->enviarRecordatorio($lote, $dryRun);
            $this->line($dryRun ? "  [dry-run] {$enviadas} tarea(s) irían en el recordatorio." : "  Recordatorio: {$enviadas} tarea(s).");
        }

        if ($solo !== 'recordatorio') {
            $lote = $notificaciones->nuevasCriticas();
            $this->mostrarLote('Recién críticas (un correo por tarea)', $lote);
            $enviadas = $notificaciones->enviarNuevasCriticas($lote, $dryRun);
            $this->line($dryRun ? "  [dry-run] Irían {$enviadas} correo(s) individuales." : "  Avisos individuales: {$enviadas}.");
        }

        return self::SUCCESS;
    }

    /**
     * Primer despliegue: las críticas que ya existían nacen sin marca y cada una sacaría
     * su correo individual de golpe. Esto las da por avisadas para que de mañana en
     * adelante entren directo al recordatorio.
     */
    private function marcarExistentes(TicketTareaNotificacionService $notificaciones, bool $dryRun): int
    {
        $criticas = $notificaciones->nuevasCriticas();

        $this->mostrarLote('Se darían por avisadas', $criticas);

        if ($dryRun) {
            $this->warn('[dry-run] No se marcó nada.');

            return self::SUCCESS;
        }

        if ($criticas->isNotEmpty()) {
            TicketTarea::whereIn('id', $criticas->pluck('id'))->update(['notificado_critica_at' => now()]);
        }

        $this->info("Marcadas sin enviar correo: {$criticas->count()} críticas.");

        return self::SUCCESS;
    }

    private function mostrarLote(string $titulo, $lote): void
    {
        $this->newLine();
        $this->info("{$titulo}: {$lote->count()}");

        foreach ($lote as $tarea) {
            $fecha = optional($tarea->fecha_compromiso)->format('d/m/Y') ?: 'sin fecha';
            $this->line("  - #{$tarea->id} {$tarea->titulo} ({$fecha})");
        }
    }
}
