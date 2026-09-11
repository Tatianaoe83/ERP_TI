<?php

namespace App\Services;

use App\Mail\TareaAsignadaAviso;
use App\Mail\TareasProgramadasAviso;
use App\Models\TicketTarea;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos por correo de las tareas de TI (métricas y eventos):
 *  - al responsable, en cuanto se le asigna una tarea (un correo por tarea),
 *  - al buzón de soporte, el día que una tarea se vuelve crítica (un correo por tarea),
 *  - y al buzón de soporte, de ahí en adelante, un recordatorio diario con el concentrado
 *    de las críticas que siguen sin cerrar.
 *
 * notificado_critica_at guarda el último aviso de crítica: si está vacío toca el aviso
 * individual; si es de un día anterior toca entrar al recordatorio.
 */
class TicketTareaNotificacionService
{
    /** Buzón(es) de soporte a los que se mandan los avisos de críticas. */
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
     * Avisa al responsable que se le asignó la tarea. Las métricas nacen sin responsable
     * y un evento también puede quedar sin él: mientras no tenga, no hay a quién avisar y
     * el correo sale hasta que se le asigne. No lanza excepción: si el correo falla la
     * asignación ya quedó guardada y no debe revertirse por eso.
     */
    public function notificarAsignacion(TicketTarea $tarea): bool
    {
        if (! $tarea->asignado_id) {
            return false;
        }

        $tarea->load('asignado');
        $correo = trim((string) optional($tarea->asignado)->Correo);

        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Tareas: el responsable no tiene correo válido, no se avisó la asignación.', [
                'tarea_id' => $tarea->id,
                'asignado_id' => $tarea->asignado_id,
            ]);

            return false;
        }

        try {
            Mail::to($correo)->send(new TareaAsignadaAviso($tarea));
        } catch (\Throwable $e) {
            Log::error('Tareas: falló el correo de asignación.', [
                'tarea_id' => $tarea->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Tareas que se volvieron críticas y todavía no tienen su aviso individual.
     *
     * Entran los dos tipos, métricas y eventos. Al reagendar se limpia la marca, así que
     * si vuelve a caer en crítica se avisa de nuevo como recién crítica.
     */
    public function nuevasCriticas(): Collection
    {
        return TicketTarea::with('asignado')
            ->pendientes()
            ->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)
            ->whereNull('notificado_critica_at')
            ->orderBy('fecha_compromiso')
            ->get();
    }

    /**
     * Críticas que ya se avisaron un día anterior y siguen abiertas: van al recordatorio.
     *
     * Deja de recordarse sola al completarse (sale de pendientes()) o al reagendarse
     * (vuelve a prioridad normal). La marca de hoy evita dos recordatorios el mismo día.
     */
    public function criticasPorRecordar(): Collection
    {
        return TicketTarea::with('asignado')
            ->pendientes()
            ->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)
            ->whereNotNull('notificado_critica_at')
            ->whereDate('notificado_critica_at', '<', Carbon::today())
            ->orderBy('fecha_compromiso')
            ->get();
    }

    /** Un correo por cada tarea recién crítica. Devuelve cuántas se avisaron. */
    public function enviarNuevasCriticas(Collection $tareas, bool $dryRun = false): int
    {
        if ($tareas->isEmpty() || ! $this->hayDestinatarios('critica')) {
            return 0;
        }

        if ($dryRun) {
            return $tareas->count();
        }

        foreach ($tareas as $tarea) {
            Mail::to($this->destinatarios())
                ->send(new TareasProgramadasAviso(collect([$tarea]), TareasProgramadasAviso::TIPO_CRITICA));

            $tarea->update(['notificado_critica_at' => now()]);
        }

        return $tareas->count();
    }

    /** Un solo correo con el concentrado de críticas sin cerrar. Devuelve cuántas iban. */
    public function enviarRecordatorio(Collection $tareas, bool $dryRun = false): int
    {
        if ($tareas->isEmpty() || ! $this->hayDestinatarios('recordatorio')) {
            return 0;
        }

        if ($dryRun) {
            return $tareas->count();
        }

        Mail::to($this->destinatarios())
            ->send(new TareasProgramadasAviso($tareas, TareasProgramadasAviso::TIPO_RECORDATORIO));

        TicketTarea::whereIn('id', $tareas->pluck('id'))->update(['notificado_critica_at' => now()]);

        return $tareas->count();
    }

    private function hayDestinatarios(string $tipo): bool
    {
        if ($this->destinatarios() !== []) {
            return true;
        }

        Log::warning('Tareas: no hay destinatarios con correo válido.', [
            'tipo' => $tipo,
            'configurados' => config('tareas.destinatarios'),
        ]);

        return false;
    }
}
