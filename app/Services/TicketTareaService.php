<?php

namespace App\Services;

use App\Models\TicketTarea;
use App\Models\TicketTareaHistorial;
use App\Models\TicketTareaMetrica;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketTareaService
{
    public function registrarHistorial(
        TicketTarea $tarea,
        string $accion,
        ?string $motivo = null,
        array $extra = []
    ): TicketTareaHistorial {
        return TicketTareaHistorial::create(array_merge([
            'tarea_id' => $tarea->id,
            'user_id' => Auth::id(),
            'accion' => $accion,
            'motivo' => $motivo,
        ], $extra));
    }

    public function crearEvento(array $data): TicketTarea
    {
        return DB::transaction(function () use ($data) {
            $tarea = TicketTarea::create([
                'titulo' => $data['titulo'],
                'razon' => $data['razon'] ?? null,
                'asignado_id' => $data['asignado_id'],
                'creado_por_user_id' => Auth::id(),
                'fecha_compromiso' => $data['fecha_compromiso'] ?? null,
                'estatus' => TicketTarea::ESTATUS_PENDIENTE,
                'tipo' => TicketTarea::TIPO_EVENTO,
                'prioridad' => TicketTarea::PRIORIDAD_NORMAL,
            ]);

            $this->registrarHistorial($tarea, 'creada', null, [
                'fecha_compromiso_nueva' => $tarea->fecha_compromiso,
                'asignado_nuevo_id' => $tarea->asignado_id,
                'notas' => 'Tarea creada manualmente.',
            ]);

            $this->actualizarPrioridades();

            return $tarea->fresh(['asignado', 'historial']);
        });
    }

    public function reagendar(TicketTarea $tarea, string $nuevaFecha, string $motivo): TicketTarea
    {
        return DB::transaction(function () use ($tarea, $nuevaFecha, $motivo) {
            $fechaAnterior = $tarea->fecha_compromiso;

            $tarea->update([
                'fecha_compromiso' => $nuevaFecha,
                'prioridad' => TicketTarea::PRIORIDAD_NORMAL,
            ]);

            $this->registrarHistorial($tarea, 'reagendada', $motivo, [
                'fecha_compromiso_anterior' => $fechaAnterior,
                'fecha_compromiso_nueva' => $nuevaFecha,
            ]);

            $this->actualizarPrioridades();

            return $tarea->fresh(['asignado', 'historial']);
        });
    }

    public function completar(TicketTarea $tarea, ?string $notas = null): TicketTarea
    {
        return DB::transaction(function () use ($tarea, $notas) {
            $tarea->update([
                'estatus' => TicketTarea::ESTATUS_COMPLETADA,
                'completada_at' => now(),
                'prioridad' => TicketTarea::PRIORIDAD_NORMAL,
            ]);

            $this->registrarHistorial($tarea, 'completada', null, [
                'notas' => $notas ?: 'Tarea marcada como completada.',
            ]);

            return $tarea->fresh(['asignado', 'historial']);
        });
    }

    public function generarTareasMetricas(?Carbon $fecha = null): int
    {
        $fecha = $fecha ?: now();
        $mes = (int) $fecha->month;
        $anio = (int) $fecha->year;
        $creadas = 0;

        $metricas = TicketTareaMetrica::query()
            ->where('activo', true)
            ->get();

        foreach ($metricas as $metrica) {
            $existe = TicketTarea::query()
                ->where('metrica_id', $metrica->id)
                ->where('periodo_mes', $mes)
                ->where('periodo_anio', $anio)
                ->exists();

            if ($existe) {
                continue;
            }

            $dia = min(max((int) $metrica->dia_compromiso, 1), 28);
            $fechaCompromiso = Carbon::create($anio, $mes, 1)->day($dia);

            // Solo genera cuando ya llegó el día programado del mes
            if ($fecha->copy()->startOfDay()->lt($fechaCompromiso->copy()->startOfDay())) {
                continue;
            }

            DB::transaction(function () use ($metrica, $mes, $anio, $fechaCompromiso, &$creadas) {
                $tarea = TicketTarea::create([
                    'titulo' => $metrica->nombre . ' — ' . $fechaCompromiso->translatedFormat('F Y'),
                    'razon' => $metrica->descripcion,
                    'asignado_id' => null,
                    'creado_por_user_id' => null,
                    'fecha_compromiso' => $fechaCompromiso->toDateString(),
                    'estatus' => TicketTarea::ESTATUS_PENDIENTE,
                    'tipo' => TicketTarea::TIPO_METRICA,
                    'metrica_id' => $metrica->id,
                    'periodo_mes' => $mes,
                    'periodo_anio' => $anio,
                    'prioridad' => TicketTarea::PRIORIDAD_NORMAL,
                ]);

                $this->registrarHistorial($tarea, 'creada', null, [
                    'fecha_compromiso_nueva' => $tarea->fecha_compromiso,
                    'notas' => 'Generada automáticamente por métrica mensual.',
                ]);

                $creadas++;
            });
        }

        $this->actualizarPrioridades();

        return $creadas;
    }

    public function actualizarPrioridades(): int
    {
        $hoy = Carbon::today();
        $actualizadas = 0;

        TicketTarea::pendientes()->chunkById(100, function ($tareas) use ($hoy, &$actualizadas) {
            foreach ($tareas as $tarea) {
                $debeSerCritica = $tarea->fecha_compromiso
                    && $tarea->fecha_compromiso->copy()->addDays(2)->lt($hoy);

                $nuevaPrioridad = $debeSerCritica
                    ? TicketTarea::PRIORIDAD_CRITICA
                    : TicketTarea::PRIORIDAD_NORMAL;

                if ($tarea->prioridad !== $nuevaPrioridad) {
                    $tarea->update(['prioridad' => $nuevaPrioridad]);
                    $actualizadas++;
                }
            }
        });

        return $actualizadas;
    }
}
