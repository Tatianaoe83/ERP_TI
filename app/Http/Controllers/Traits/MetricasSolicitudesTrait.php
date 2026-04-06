<?php

namespace App\Http\Controllers\Traits;

use App\Models\Solicitud;
use App\Models\SolicitudActivo;
use Carbon\Carbon;

trait MetricasSolicitudesTrait
{
    //L-V 9-18, Sab 9-14
    private function calcularHorasLaboralesFloat($inicio, $fin)
    {
        if (!$inicio || !$fin) return null;

        $inicio = Carbon::parse($inicio);
        $fin = Carbon::parse($fin);

        if ($inicio->gt($fin)) return 0.0;

        $totalMinutos = 0;
        $actual = $inicio->copy();

        while ($actual->lt($fin)) {
            $diaSemana = $actual->dayOfWeek;
            if ($diaSemana == 0) {
                $actual->addDay()->setTime(9, 0, 0);
                continue;
            }

            $horaInicio = $actual->copy()->setTime(9, 0, 0);
            $horaFin = ($diaSemana == 6) ? $actual->copy()->setTime(14, 0, 0) : $actual->copy()->setTime(18, 0, 0);

            if ($actual->lt($horaInicio)) $actual = $horaInicio->copy();
            
            if ($actual->gte($horaFin)) {
                $actual->addDay()->setTime(9, 0, 0);
                continue;
            }

            $limite = $fin->lt($horaFin) ? $fin : $horaFin;
            $totalMinutos += $actual->diffInMinutes($limite);
            
            $actual = $horaFin->copy();
        }

        return round($totalMinutos / 60, 1);
    }

    // Calcula los tiempos de cotización, compra y configuración de las Solicitudes
    public function calcularMetricasSolicitudes($mes = null, $anio = null)
    {
        $mes  = $mes  ?? now()->month;
        $anio = $anio ?? now()->year;

        $fechaInicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFinMes    = Carbon::create($anio, $mes, 1)->endOfMonth();

        // 1. Obtener solicitudes del mes con las relaciones clave
        // SOLO solicitudes que tengan activos con checklist (configuración completada)
        $solicitudes = Solicitud::with(['cotizaciones', 'pasoAdministracion', 'empleadoid', 'gerenciaid'])
            ->whereHas('activos', function($q) {
                $q->whereHas('checklists');
            })
            ->whereBetween('created_at', [$fechaInicioMes, $fechaFinMes])
            ->get();

        // 2. Traer las fechas de configuración de los activos de esas solicitudes
        $solicitudIds = $solicitudes->pluck('SolicitudID')->toArray();
        $activos = SolicitudActivo::whereIn('SolicitudID', $solicitudIds)
            ->whereNotNull('fecha_fin_configuracion')
            ->get()
            ->groupBy('SolicitudID');

        $desglose = [];
        $totalCotizacionHoras = $totalConfiguracionHoras = 0;
        $countCotizacion = $countConfiguracion = 0;

        foreach ($solicitudes as $sol) {
            $fechaCreacion = Carbon::parse($sol->created_at);
            
            // --- A) Tiempo de Cotización ---
            $primeraCotizacion = $sol->cotizaciones->sortBy('created_at')->first();
            $fechaCotizacion = $primeraCotizacion ? Carbon::parse($primeraCotizacion->created_at) : null;
            
            $tiempoCotizacionHoras = $this->calcularHorasLaboralesFloat($fechaCreacion, $fechaCotizacion);
            if ($tiempoCotizacionHoras !== null) {
                $totalCotizacionHoras += $tiempoCotizacionHoras;
                $countCotizacion++;
            }

            $pasoAdmin = $sol->pasoAdministracion;
            $fechaAprobacion = ($fechaCotizacion && $pasoAdmin && $pasoAdmin->status === 'approved' && $pasoAdmin->decided_at) 
                ? Carbon::parse($pasoAdmin->decided_at) 
                : null;

            $fechaConfiguracion = null;
            if ($fechaAprobacion && isset($activos[$sol->SolicitudID])) {
                $ultimaConfig = $activos[$sol->SolicitudID]->max('fecha_fin_configuracion');
                if ($ultimaConfig) {
                    $fechaConfiguracion = Carbon::parse($ultimaConfig);
                }
            }

            $tiempoConfiguracionHoras = $this->calcularHorasLaboralesFloat($fechaAprobacion, $fechaConfiguracion);
            if ($tiempoConfiguracionHoras !== null) {
                $totalConfiguracionHoras += $tiempoConfiguracionHoras;
                $countConfiguracion++;
            }

            $endTotal = $fechaConfiguracion ?? $fechaAprobacion;
            $tiempoTotalHoras = $this->calcularHorasLaboralesFloat($fechaCreacion, $endTotal);

            $empleadoNombre = $sol->empleadoid ? $sol->empleadoid->NombreEmpleado : 'Desconocido';
            $gerenciaNombre = $sol->gerenciaid ? $sol->gerenciaid->NombreGerencia : 'Sin Gerencia';

            $desglose[] = [
                'id'                  => $sol->SolicitudID,
                'fecha_creacion'      => $fechaCreacion->format('d/m/Y H:i'),
                'fecha_actualizacion' => $sol->updated_at ? Carbon::parse($sol->updated_at)->format('d/m/Y H:i') : '',
                'empleado'            => $empleadoNombre,
                'proyecto'            => $sol->Proyecto ?? 'Sin Proyecto',
                'gerencia_nombre'     => $gerenciaNombre,
                'motivo'              => $sol->Motivo ?? 'N/A',
                'descripcion_motivo'  => $sol->DescripcionMotivo ?? '',
                'estatus'             => $sol->Estatus ?? 'Pendiente',
                'tiempo_cotizacion_horas'   => $tiempoCotizacionHoras,
                'tiempo_configuracion_dias' => $tiempoConfiguracionHoras,
                'tiempo_total_dias'         => $tiempoTotalHoras,
            ];
        }

        return [
            'promedio_cotizacion_horas'   => $countCotizacion > 0 ? $totalCotizacionHoras / $countCotizacion : 0,
            'promedio_configuracion_dias' => $countConfiguracion > 0 ? $totalConfiguracionHoras / $countConfiguracion : 0,
            'desglose'                    => collect($desglose)->sortByDesc('id')->values()->all(),
        ];
    }
}