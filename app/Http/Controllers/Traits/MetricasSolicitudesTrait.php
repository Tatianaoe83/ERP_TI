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
    public function calcularMetricasSolicitudes($mesInicio = null, $anioInicio = null, $mesFin = null, $anioFin = null)
    {
        $mesInicio = $mesInicio ?? now()->month;
        $anioInicio = $anioInicio ?? now()->year;
        $mesFin = $mesFin ?? $mesInicio;
        $anioFin = $anioFin ?? $anioInicio;

        $fechaInicioMes = Carbon::create($anioInicio, $mesInicio, 1)->startOfMonth();
        $fechaFinMes    = Carbon::create($anioFin, $mesFin, 1)->endOfMonth();

        $solicitudesConFacturasPeriodo = \App\Models\Facturas::query()
            ->where(function ($query) use ($fechaInicioMes, $fechaFinMes) {
                $query->whereBetween('created_at', [$fechaInicioMes, $fechaFinMes])
                    ->orWhereBetween('updated_at', [$fechaInicioMes, $fechaFinMes]);
            })
            ->whereNotNull('SolicitudID')
            ->pluck('SolicitudID')
            ->unique()
            ->values()
            ->all();

        // 1. Obtener solicitudes con actividad del periodo.
        $solicitudes = Solicitud::with(['cotizaciones', 'pasoSupervisor', 'pasoAdministracion', 'empleadoid', 'gerenciaid'])
            ->where(function ($query) use ($fechaInicioMes, $fechaFinMes, $solicitudesConFacturasPeriodo) {
                $query->whereBetween('created_at', [$fechaInicioMes, $fechaFinMes])
                    ->orWhereBetween('fecha_cancelacion', [$fechaInicioMes, $fechaFinMes])
                    ->orWhereIn('SolicitudID', $solicitudesConFacturasPeriodo)
                    ->orWhereHas('activos', function ($q) use ($fechaInicioMes, $fechaFinMes) {
                        $q->whereBetween('created_at', [$fechaInicioMes, $fechaFinMes])
                            ->orWhereBetween('updated_at', [$fechaInicioMes, $fechaFinMes])
                            ->orWhereBetween('FechaEntrega', [$fechaInicioMes, $fechaFinMes])
                            ->orWhereBetween('fecha_fin_configuracion', [$fechaInicioMes, $fechaFinMes]);
                    })
                    ->orWhere(function ($q) use ($fechaInicioMes, $fechaFinMes) {
                        $q->whereIn('Estatus', ['Cancelada', 'Cerrada'])
                            ->whereNull('fecha_cancelacion')
                            ->whereBetween('updated_at', [$fechaInicioMes, $fechaFinMes]);
                    });
            })
            ->get();

        // 2. Traer las fechas de configuración de los activos de esas solicitudes
        $solicitudIds = $solicitudes->pluck('SolicitudID')->toArray();
        $activos = SolicitudActivo::with(['empleadoAsignado', 'departamentos', 'checklists'])
            ->whereIn('SolicitudID', $solicitudIds)
            ->get()
            ->groupBy('SolicitudID');

        // 3. Traer facturas subidas desde la tabla oficial de facturas
        $facturasTieneCotizacion = \Illuminate\Support\Facades\Schema::hasColumn('facturas', 'CotizacionID');
        $facturasSelect = ['SolicitudID', 'FacturasID', 'created_at', 'updated_at'];
        if ($facturasTieneCotizacion) {
            $facturasSelect[] = 'CotizacionID';
        }

        $facturasPorSolicitud = \App\Models\Facturas::whereIn('SolicitudID', $solicitudIds)
            ->where(fn($q) => $q->whereNotNull('ArchivoRuta')->where('ArchivoRuta', '!=', '')
                ->orWhereNotNull('PdfRuta')->where('PdfRuta', '!=', ''))
            ->get($facturasSelect)
            ->groupBy('SolicitudID');

        $activosConFactura = $facturasTieneCotizacion
            ? $facturasPorSolicitud->map(fn($rows) => $rows->whereNotNull('CotizacionID')->unique('CotizacionID')->values())
            : $facturasPorSolicitud;

        $desglose = [];
        $totalCotizacionHoras = $totalConfiguracionHoras = 0;
        $countCotizacion = $countConfiguracion = 0;

        foreach ($solicitudes as $sol) {
            $fechaCreacion = Carbon::parse($sol->created_at);

            // --- A) Tiempo de Cotización TI ---
            // Desde el primer Vo.Bo. de supervisor hasta la primera cotización registrada por TI.
            $pasoSupervisor = $sol->pasoSupervisor;
            $fechaVoboSupervisor = ($pasoSupervisor && $pasoSupervisor->status === 'approved' && $pasoSupervisor->decided_at)
                ? Carbon::parse($pasoSupervisor->decided_at)
                : null;

            $primeraCotizacion = $sol->cotizaciones->sortBy('created_at')->first();
            $fechaCotizacion = $primeraCotizacion ? Carbon::parse($primeraCotizacion->created_at) : null;

            $tiempoCotizacionHoras = $this->calcularHorasLaboralesFloat($fechaVoboSupervisor, $fechaCotizacion);
            if ($tiempoCotizacionHoras !== null) {
                $totalCotizacionHoras += $tiempoCotizacionHoras;
                $countCotizacion++;
            }

            $pasoAdmin = $sol->pasoAdministracion;
            $fechaAprobacion = ($pasoAdmin && $pasoAdmin->status === 'approved' && $pasoAdmin->decided_at)
                ? Carbon::parse($pasoAdmin->decided_at)
                : null;

            $fechaConfiguracion = null;
            $tieneConfiguracion = isset($activos[$sol->SolicitudID])
                && $activos[$sol->SolicitudID]->contains(fn($activo) => $activo->checklists && $activo->checklists->isNotEmpty());

            if ($fechaAprobacion && isset($activos[$sol->SolicitudID])) {
                $ultimaConfig = $activos[$sol->SolicitudID]
                    ->whereNotNull('fecha_fin_configuracion')
                    ->max('fecha_fin_configuracion');
                if ($ultimaConfig) {
                    $fechaConfiguracion = Carbon::parse($ultimaConfig);
                }
            }

            $tiempoConfiguracionHoras = $this->calcularHorasLaboralesFloat($fechaAprobacion, $fechaConfiguracion);
            if ($tiempoConfiguracionHoras !== null) {
                $totalConfiguracionHoras += $tiempoConfiguracionHoras;
                $countConfiguracion++;
            }
            $estadoConfiguracion = $fechaConfiguracion
                ? 'Completada'
                : ($tieneConfiguracion ? 'Pendiente' : 'Sin configuración');

            $fechaCierre = null;
            if (in_array((string)$sol->Estatus, ['Cancelada', 'Cerrada'], true)) {
                $fechaCierre = $sol->fecha_cancelacion
                    ? Carbon::parse($sol->fecha_cancelacion)
                    : ($sol->updated_at ? Carbon::parse($sol->updated_at) : null);
            }

            // --- D) Facturas ---
            $cotSeleccionadas = $sol->cotizaciones->where('Estatus', 'Seleccionada');
            $totalNecesarias  = $cotSeleccionadas->pluck('Proveedor')->filter()->unique()->count();
            $facturasSubidas  = 0;
            if ($totalNecesarias > 0 && isset($activosConFactura[$sol->SolicitudID])) {
                if ($facturasTieneCotizacion) {
                    $cotsConFactura = $activosConFactura[$sol->SolicitudID]->pluck('CotizacionID')->toArray();
                    $facturasSubidas = $cotSeleccionadas->whereIn('CotizacionID', $cotsConFactura)
                        ->pluck('Proveedor')->filter()->unique()->count();
                } else {
                    $facturasSubidas = min($activosConFactura[$sol->SolicitudID]->count(), $totalNecesarias);
                }
            }

            $ultimaFacturaFecha = null;
            if (isset($facturasPorSolicitud[$sol->SolicitudID])) {
                $ultimaFacturaFecha = $facturasPorSolicitud[$sol->SolicitudID]
                    ->flatMap(fn($factura) => array_filter([$factura->updated_at, $factura->created_at]))
                    ->map(fn($fecha) => Carbon::parse($fecha))
                    ->sort()
                    ->last();
            }

            $ultimaActividadActivo = null;
            if (isset($activos[$sol->SolicitudID])) {
                $ultimaActividadActivo = $activos[$sol->SolicitudID]
                    ->flatMap(fn($activo) => array_filter([
                        $activo->fecha_fin_configuracion,
                        $activo->FechaEntrega,
                        $activo->updated_at,
                        $activo->created_at,
                    ]))
                    ->map(fn($fecha) => Carbon::parse($fecha))
                    ->sort()
                    ->last();
            }

            // Tiempo total: desde Vo.Bo. supervisor hasta el último hito real alcanzado.
            $inicioTotal = $fechaVoboSupervisor ?? $fechaCreacion;
            $endTotal = collect([
                    $fechaConfiguracion,
                    $ultimaFacturaFecha,
                    $ultimaActividadActivo,
                    $fechaCierre,
                    $fechaAprobacion,
                    $fechaCotizacion,
                ])
                ->filter()
                ->sort()
                ->last();
            $tiempoTotalHoras = $this->calcularHorasLaboralesFloat($inicioTotal, $endTotal);

            // --- E) Usuario Final y Gerencia ---
            $usuarioFinal = null;
            $gerenciaUsuarioFinal = null;
            if (isset($activos[$sol->SolicitudID])) {
                $primerActivo = $activos[$sol->SolicitudID]->first();
                if ($primerActivo) {
                    $usuarioFinal = $primerActivo->empleadoAsignado
                        ? $primerActivo->empleadoAsignado->NombreEmpleado
                        : null;
                    $gerenciaUsuarioFinal = $primerActivo->departamentos
                        ? $primerActivo->departamentos->NombreDepartamento
                        : null;
                }
            }

            $empleadoNombre = $sol->empleadoid ? $sol->empleadoid->NombreEmpleado : 'Desconocido';
            $gerenciaNombre = $sol->gerenciaid ? $sol->gerenciaid->NombreGerencia : 'Sin Gerencia';

            $desglose[] = [
                'id'                  => $sol->SolicitudID,
                'fecha_creacion'      => $fechaCreacion->format('d/m/Y H:i'),
                'fecha_actualizacion' => $sol->updated_at ? Carbon::parse($sol->updated_at)->format('d/m/Y H:i') : '',
                'fecha_cierre'        => $fechaCierre ? $fechaCierre->format('d/m/Y H:i') : '',
                'fecha_vobo_supervisor' => $fechaVoboSupervisor ? $fechaVoboSupervisor->format('d/m/Y H:i') : '',
                'fecha_cotizacion_ti' => $fechaCotizacion ? $fechaCotizacion->format('d/m/Y H:i') : '',
                'fecha_ultimo_hito'    => $endTotal ? $endTotal->format('d/m/Y H:i') : '',
                'estado_configuracion' => $estadoConfiguracion,
                'empleado'            => $empleadoNombre,
                'proyecto'            => $sol->Proyecto ?? 'Sin Proyecto',
                'gerencia_nombre'     => $gerenciaNombre,
                'motivo'              => $sol->Motivo ?? 'N/A',
                'descripcion_motivo'  => $sol->DescripcionMotivo ?? '',
                'estatus'             => $sol->Estatus ?? 'Pendiente',
                'tiempo_cotizacion_horas'   => $tiempoCotizacionHoras,
                'tiempo_configuracion_dias' => $tiempoConfiguracionHoras,
                'tiempo_total_dias'         => $tiempoTotalHoras,
                'facturas_subidas'          => $facturasSubidas,
                'facturas_necesarias'       => $totalNecesarias,
                'facturas_faltantes'        => max(0, $totalNecesarias - $facturasSubidas),
                'usuario_final'             => $usuarioFinal,
                'gerencia_usuario_final'    => $gerenciaUsuarioFinal,
            ];
        }

        return [
            'promedio_cotizacion_horas'   => $countCotizacion > 0 ? $totalCotizacionHoras / $countCotizacion : 0,
            'promedio_configuracion_dias' => $countConfiguracion > 0 ? $totalConfiguracionHoras / $countConfiguracion : 0,
            'desglose'                    => collect($desglose)->sortByDesc('id')->values()->all(),
        ];
    }
}
