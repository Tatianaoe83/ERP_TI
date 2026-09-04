<?php

namespace App\Services;

use App\Mail\TareasProgramadasAviso;
use App\Models\TicketTarea;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos por correo de las tareas programadas (métricas mensuales):
 *  - el día que el sistema las genera,
 *  - y en la primera corrida después de que se vuelven críticas.
 *
 * Ambos avisos se marcan en la tarea para no repetirlos en la corrida siguiente.
 */
class TicketTareaNotificacionService
{
    /** Buzón(es) a los que se manda el aviso. */
    public function destinatarios(): array
    {
        return collect(config('tareas.destinatarios', []))
            ->map(fn ($correo) => trim((string) $correo))
            ->filter(fn ($correo) => $correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Tareas programadas recién generadas que todavía no se avisan.
     *
     * No se filtra por "creadas hoy": una métrica generada después de las 9:30 (con el
     * botón "Generar mes actual" o porque el cron de la mañana falló) se quedaría sin
     * aviso para siempre. Se toma la ventana de días de config para no revivir tareas
     * viejas si alguien limpia las marcas a mano.
     */
    public function pendientesDeAvisoCreacion(): Collection
    {
        $dias = max(1, (int) config('tareas.ventana_aviso_creacion_dias', 3));

        return TicketTarea::with('asignado')
            ->where('tipo', TicketTarea::TIPO_METRICA)
            ->whereNull('notificado_creacion_at')
            ->where('created_at', '>=', now()->subDays($dias)->startOfDay())
            ->orderBy('fecha_compromiso')
            ->get();
    }

    /** Tareas programadas que ya se volvieron críticas y siguen sin avisarse. */
    public function pendientesDeAvisoCritica(): Collection
    {
        return TicketTarea::with('asignado')
            ->pendientes()
            ->where('tipo', TicketTarea::TIPO_METRICA)
            ->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)
            ->whereNull('notificado_critica_at')
            ->orderBy('fecha_compromiso')
            ->get();
    }

    /**
     * Manda un solo correo con el lote y marca las tareas. Devuelve cuántas se avisaron.
     */
    public function enviar(Collection $tareas, string $tipo, bool $dryRun = false): int
    {
        if ($tareas->isEmpty()) {
            return 0;
        }

        $destinatarios = $this->destinatarios();

        if ($destinatarios === []) {
            Log::warning('Tareas programadas: no hay destinatarios con correo válido.', [
                'tipo' => $tipo,
                'configurados' => config('tareas.notificados'),
            ]);

            return 0;
        }

        if ($dryRun) {
            return $tareas->count();
        }

        Mail::to($destinatarios)->send(new TareasProgramadasAviso($tareas, $tipo));

        $columna = $tipo === TareasProgramadasAviso::TIPO_CRITICAS
            ? 'notificado_critica_at'
            : 'notificado_creacion_at';

        TicketTarea::whereIn('id', $tareas->pluck('id'))->update([$columna => now()]);

        return $tareas->count();
    }

    public function notificarCreadas(bool $dryRun = false): int
    {
        return $this->enviar($this->pendientesDeAvisoCreacion(), TareasProgramadasAviso::TIPO_CREADAS, $dryRun);
    }

    public function notificarCriticas(bool $dryRun = false): int
    {
        return $this->enviar($this->pendientesDeAvisoCritica(), TareasProgramadasAviso::TIPO_CRITICAS, $dryRun);
    }
}
