<?php

namespace App\Console\Commands;

use App\Models\SolicitudPasos;
use App\Models\SolicitudTokens;
use App\Services\SolicitudAprobacionEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Recordatorio diario (10:00 am) para los enlaces de aprobación que siguen activos.
 *
 * Sólo se recuerda la etapa donde se quedó el flujo (supervisor → gerencia → administración):
 * un token con pasos previos sin aprobar todavía no le toca a esa persona.
 * El primer recordatorio sale hasta el día siguiente al envío del correo original.
 */
class EnviarRecordatoriosSolicitudes extends Command
{
    protected $signature = 'solicitudes:recordatorios
                            {--dry-run : Muestra a quién se enviaría sin mandar correos}
                            {--solicitud= : Limitar a una solicitud específica}';

    protected $description = 'Envía recordatorios diarios a los aprobadores con enlaces de solicitud aún activos';

    public function handle(SolicitudAprobacionEmailService $emailService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $soloSolicitud = $this->option('solicitud');
        $inicioDelDia = now()->startOfDay();

        $tokens = SolicitudTokens::query()
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->whereNotNull('notified_at')
            // Nunca el mismo día en que se envió el correo original: el primero sale al día siguiente.
            ->where('notified_at', '<', $inicioDelDia)
            ->where(function ($q) use ($inicioDelDia) {
                $q->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', $inicioDelDia);
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with([
                'approvalStep.approverEmpleado',
                'approvalStep.solicitud.empleadoid',
                'approvalStep.solicitud.cotizaciones',
            ])
            ->when($soloSolicitud, function ($q) use ($soloSolicitud) {
                $q->whereHas('approvalStep', fn($s) => $s->where('solicitud_id', $soloSolicitud));
            })
            ->orderBy('id')
            ->get();

        $enviados = 0;
        $omitidos = 0;
        $fallidos = 0;

        foreach ($tokens as $tokenRow) {
            $motivo = $this->motivoParaOmitir($tokenRow);

            if ($motivo !== null) {
                $omitidos++;
                $this->line("  - Token #{$tokenRow->id} omitido: {$motivo}");
                continue;
            }

            $step = $tokenRow->approvalStep;
            $solicitud = $step->solicitud;
            $aprobador = $step->approverEmpleado;

            $ganadores = $step->stage === 'administracion'
                ? ($solicitud->cotizaciones ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada') : collect())
                : null;

            if ($dryRun) {
                $this->info(
                    "  [dry-run] Solicitud #{$solicitud->SolicitudID} etapa {$step->stage} → {$aprobador->Correo}"
                );
                $enviados++;
                continue;
            }

            $ok = $emailService->enviarRecordatorio(
                $aprobador,
                $solicitud,
                $tokenRow->token,
                $step->stage,
                $ganadores
            );

            if ($ok) {
                $tokenRow->update([
                    'last_reminder_at' => now(),
                    'reminders_sent' => ($tokenRow->reminders_sent ?? 0) + 1,
                ]);
                $enviados++;
                $this->info("  Recordatorio enviado: solicitud #{$solicitud->SolicitudID} ({$step->stage}) → {$aprobador->Correo}");
            } else {
                $fallidos++;
                $this->error("  Falló el recordatorio de la solicitud #{$solicitud->SolicitudID} ({$step->stage})");
            }
        }

        $resumen = "Recordatorios de solicitudes: {$enviados} enviado(s), {$omitidos} omitido(s), {$fallidos} fallido(s).";
        $this->info($resumen);
        Log::info($resumen . ($dryRun ? ' [dry-run]' : ''));

        return self::SUCCESS;
    }

    /**
     * Devuelve el motivo por el que NO se debe recordar este token, o null si sí procede.
     */
    private function motivoParaOmitir(SolicitudTokens $tokenRow): ?string
    {
        $step = $tokenRow->approvalStep;

        if (!$step) {
            return 'sin paso de aprobación';
        }

        if ($step->status !== 'pending') {
            return "el paso {$step->stage} ya fue resuelto ({$step->status})";
        }

        // El enlace caduca a los 7 días y no se renueva. Recordar el último día servía de
        // poco: el correo llegaba de mañana y el enlace moría esa misma tarde, dejando al
        // aprobador con "enlace expirado" y sin salida.
        if ($tokenRow->expires_at
            && $tokenRow->expires_at->lt(now()->addHours(SolicitudTokens::MIN_HORAS_PARA_RECORDAR))) {
            return 'al enlace le quedan menos de ' . SolicitudTokens::MIN_HORAS_PARA_RECORDAR . ' horas de vigencia';
        }

        $enviados = (int) ($tokenRow->reminders_sent ?? 0);
        if ($enviados >= SolicitudTokens::MAX_RECORDATORIOS) {
            return "ya se enviaron {$enviados} recordatorios (tope alcanzado)";
        }

        $aprobador = $step->approverEmpleado;
        if (!$aprobador || empty($aprobador->Correo)) {
            return "el aprobador del paso {$step->stage} no tiene correo";
        }

        $solicitud = $step->solicitud;
        if (!$solicitud) {
            return 'sin solicitud asociada';
        }

        if (in_array($solicitud->Estatus, ['Cancelada', 'Cerrada', 'Rechazada'], true)) {
            return "la solicitud #{$solicitud->SolicitudID} está {$solicitud->Estatus}";
        }

        // El flujo es progresivo: sólo se recuerda la etapa donde realmente se quedó.
        $faltanPrevios = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
            ->where('step_order', '<', $step->step_order)
            ->where('status', '!=', 'approved')
            ->exists();

        if ($faltanPrevios) {
            return "la solicitud #{$solicitud->SolicitudID} aún está en una etapa anterior a {$step->stage}";
        }

        $cotizaciones = $solicitud->cotizaciones ?? collect();

        if ($step->stage === 'gerencia') {
            // El enlace del gerente sólo sirve si TI ya cargó las propuestas.
            if ($cotizaciones->isEmpty()) {
                return "la solicitud #{$solicitud->SolicitudID} todavía no tiene cotizaciones cargadas";
            }
            if ($solicitud->todosProductosTienenGanador()) {
                return "la solicitud #{$solicitud->SolicitudID} ya tiene ganador en todos los productos";
            }
        }

        if ($step->stage === 'administracion' && $cotizaciones->where('Estatus', 'Seleccionada')->isEmpty()) {
            return "la solicitud #{$solicitud->SolicitudID} todavía no tiene ganadores seleccionados";
        }

        return null;
    }
}
