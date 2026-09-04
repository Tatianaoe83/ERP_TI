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
                            {--solo= : creadas|criticas}
                            {--marcar-existentes : Da por avisadas las tareas actuales sin mandar correo (para el primer despliegue)}';

    protected $description = 'Avisa por correo las tareas programadas generadas hoy y las que ya son críticas';

    public function handle(TicketTareaNotificacionService $notificaciones, TicketTareaService $tareas): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $solo = $this->option('solo');

        if ($solo !== null && ! in_array($solo, ['creadas', 'criticas'], true)) {
            $this->error('--solo acepta "creadas" o "criticas".');

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

        if ($solo !== 'criticas') {
            $lote = $notificaciones->pendientesDeAvisoCreacion();
            $this->mostrarLote('Pendientes de aviso de creación', $lote);
            $enviadas = $notificaciones->enviar($lote, 'creadas', $dryRun);
            $this->line($dryRun ? "  [dry-run] {$enviadas} tarea(s) irían en el aviso de creación." : "  Aviso de creación: {$enviadas} tarea(s).");
        }

        if ($solo !== 'creadas') {
            $lote = $notificaciones->pendientesDeAvisoCritica();
            $this->mostrarLote('Críticas sin avisar', $lote);
            $enviadas = $notificaciones->enviar($lote, 'criticas', $dryRun);
            $this->line($dryRun ? "  [dry-run] {$enviadas} tarea(s) irían en el aviso de críticas." : "  Aviso de críticas: {$enviadas} tarea(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Primer despliegue: las tareas que ya existían nacen sin marca de aviso y saldrían
     * todas juntas en un solo correo. Esto las da por avisadas para que el flujo arranque
     * limpio y de mañana en adelante solo se notifique lo del día.
     */
    private function marcarExistentes(TicketTareaNotificacionService $notificaciones, bool $dryRun): int
    {
        $creacion = $notificaciones->pendientesDeAvisoCreacion();
        $criticas = $notificaciones->pendientesDeAvisoCritica();

        $this->mostrarLote('Se darían por avisadas (creación)', $creacion);
        $this->mostrarLote('Se darían por avisadas (críticas)', $criticas);

        if ($dryRun) {
            $this->warn('[dry-run] No se marcó nada.');

            return self::SUCCESS;
        }

        $ahora = now();

        if ($creacion->isNotEmpty()) {
            TicketTarea::whereIn('id', $creacion->pluck('id'))->update(['notificado_creacion_at' => $ahora]);
        }

        if ($criticas->isNotEmpty()) {
            TicketTarea::whereIn('id', $criticas->pluck('id'))->update(['notificado_critica_at' => $ahora]);
        }

        $this->info("Marcadas sin enviar correo: {$creacion->count()} de creación, {$criticas->count()} críticas.");

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
