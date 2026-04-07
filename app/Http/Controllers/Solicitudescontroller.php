<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\CotizacionTrait;
use App\Models\Solicitud;
use App\Models\Cotizacion;
use App\Models\Empleados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SolicitudesController extends Controller
{
    use CotizacionTrait;

    // Retorna datos completos de una solicitud para el panel lateral
    public function obtenerDatosSolicitud($id)
    {
        try {
            $solicitud = Solicitud::with([
                'empleadoid',
                'gerenciaid',
                'obraid',
                'puestoid',
                'pasoSupervisor.approverEmpleado',
                'pasoSupervisor.decidedByEmpleado',
                'pasoGerencia.approverEmpleado',
                'pasoGerencia.decidedByEmpleado',
                'pasoAdministracion.approverEmpleado',
                'pasoAdministracion.decidedByEmpleado',
                'cotizaciones',
            ])->findOrFail($id);

            $activosAsignados = \App\Models\SolicitudActivo::where('SolicitudID', $id)
                ->with(['empleadoAsignado', 'departamentos', 'cotizacion'])
                ->get();

            $pasoSupervisor     = $solicitud->pasoSupervisor;
            $pasoGerencia       = $solicitud->pasoGerencia;
            $pasoAdministracion = $solicitud->pasoAdministracion;

            $estatusReal   = $solicitud->Estatus ?? 'Pendiente';
            $estaRechazada = false;

            if (in_array($solicitud->Estatus, ['Cancelada', 'Cerrada'], true)) {
                $estatusReal = 'Cancelada';
            } elseif (
                ($pasoSupervisor     && $pasoSupervisor->status     === 'rejected') ||
                ($pasoGerencia       && $pasoGerencia->status       === 'rejected') ||
                ($pasoAdministracion && $pasoAdministracion->status === 'rejected')
            ) {
                $estatusReal   = 'Rechazada';
                $estaRechazada = true;
            } elseif (in_array($solicitud->Estatus, ['Aprobado', 'Aprobada'], true)) {
                $estatusReal = 'Aprobado';
            } elseif ($solicitud->Estatus === 'Cotizaciones Enviadas') {
                $estatusReal = 'Cotizaciones Enviadas';
            } elseif ($solicitud->Estatus === 'Re-cotizar') {
                $estatusReal = 'Re-cotizar';
            } elseif (in_array($solicitud->Estatus, ['Pendiente', 'En revisión', null, ''], true) || empty($solicitud->Estatus)) {
                if ($pasoSupervisor && $pasoSupervisor->status === 'approved') {
                    if ($pasoGerencia && $pasoGerencia->status === 'approved') {
                        if ($pasoAdministracion && $pasoAdministracion->status === 'approved') {
                            $todosGanadoresElegidos = $solicitud->todosProductosTienenGanador();
                            $cotizacionesCount      = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;
                            $estatusReal            = $todosGanadoresElegidos
                                ? 'Aprobado'
                                : ($cotizacionesCount >= 1 ? 'Completada' : 'Pendiente Cotización TI');
                        } else {
                            $estatusReal = 'Pendiente Aprobación Administración';
                        }
                    } else {
                        $estatusReal = 'Pendiente Aprobación Gerencia';
                    }
                } else {
                    $estatusReal = 'Pendiente Aprobación Supervisor';
                }
            }

            if ($estatusReal === 'Cancelada') {
                $estatusDisplay = 'Cancelada';
            } elseif ($estatusReal === 'Rechazada') {
                $estatusDisplay = 'Rechazada';
            } elseif ($estatusReal === 'Aprobado') {
                $estatusDisplay = 'Aprobada';
            } elseif ($estatusReal === 'Cotizaciones Enviadas') {
                $estatusDisplay = 'Cotizaciones Enviadas';
            } elseif ($estatusReal === 'Re-cotizar') {
                $estatusDisplay = 'Re-cotizar';
            } elseif ($estatusReal === 'Completada') {
                $estatusDisplay = 'En revisión';
            } elseif ($estatusReal === 'Pendiente Cotización TI') {
                $estatusDisplay = 'Pendiente';
            } elseif (in_array($estatusReal, [
                'Pendiente Aprobación Supervisor',
                'Pendiente Aprobación Gerencia',
                'Pendiente Aprobación Administración',
            ], true)) {
                $estatusDisplay = 'En revisión';
            } else {
                $estatusDisplay = 'Pendiente';
            }

            $todasFirmaron = ($pasoSupervisor     && $pasoSupervisor->status     === 'approved')
                && ($pasoGerencia       && $pasoGerencia->status       === 'approved')
                && ($pasoAdministracion && $pasoAdministracion->status === 'approved');

            $todosGanadores     = $solicitud->todosProductosTienenGanador();
            $supervisorAprobado = $pasoSupervisor && $pasoSupervisor->status === 'approved';
            $estaCancelada      = ($estatusReal === 'Cancelada');

            $puedeCotizar = !$estaCancelada
                && $supervisorAprobado
                && auth()->check()
                && !$estaRechazada
                && $estatusDisplay !== 'Aprobada'
                && !$todosGanadores;

            $puedeElegirCotizacion = !$estaCancelada
                && $todasFirmaron
                && $solicitud->cotizaciones
                && $solicitud->cotizaciones->count() > 0
                && ($estatusDisplay === 'Cotizaciones Enviadas' || $estatusDisplay === 'En revisión')
                && auth()->check()
                && auth()->user()->can('aprobar-solicitudes-gerencia');

            $stageLabels  = [
                'supervisor'     => 'Vo.bo de supervisor',
                'gerencia'       => 'Gerente: ve propuestas, elige ganador o regresa a TI para cotizar',
                'administracion' => 'Administración: ve ganadores y aprueba la solicitud',
            ];
            $statusLabels = ['approved' => 'Aprobado', 'rejected' => 'Rechazado', 'pending' => 'Pendiente'];

            $pasosAprobacion = [];
            foreach ([$pasoSupervisor, $pasoGerencia, $pasoAdministracion] as $paso) {
                if ($paso) {
                    $pasosAprobacion[] = [
                        'stage'           => $paso->stage,
                        'stageLabel'      => $stageLabels[$paso->stage] ?? ucfirst($paso->stage),
                        'status'          => $paso->status,
                        'statusLabel'     => $statusLabels[$paso->status] ?? ucfirst($paso->status),
                        'approverNombre'  => $paso->approverEmpleado  ? $paso->approverEmpleado->NombreEmpleado  : 'N/A',
                        'decidedByNombre' => $paso->decidedByEmpleado ? $paso->decidedByEmpleado->NombreEmpleado : null,
                        'decidedAt'       => $paso->decided_at ? $paso->decided_at->format('d/m/Y H:i') : null,
                        'comment'         => $paso->comment,
                    ];
                }
            }

            $proyectoNombre = $solicitud->Proyecto;
            if (!empty($proyectoNombre) && preg_match('/^([A-Z]{2})(\d+)$/i', $proyectoNombre, $matches)) {
                $prefijo    = strtoupper($matches[1]);
                $proyectoId = (int) $matches[2];
                try {
                    switch ($prefijo) {
                        case 'PR':
                            $proyecto = \App\Models\Proyecto::find($proyectoId);
                            if ($proyecto) $proyectoNombre = $proyecto->NombreProyecto ?? $proyecto->Proyecto ?? $proyectoNombre;
                            break;
                        case 'GE':
                            $gerencia = \App\Models\Gerencia::find($proyectoId);
                            if ($gerencia) $proyectoNombre = $gerencia->NombreGerencia ?? $proyectoNombre;
                            break;
                        case 'OB':
                            $obra = \App\Models\Obras::find($proyectoId);
                            if ($obra) $proyectoNombre = $obra->NombreObra ?? $proyectoNombre;
                            break;
                    }
                } catch (\Exception $e) {
                    // mantener nombre original
                }
            }

            $cotizaciones = $solicitud->cotizaciones
                ? $solicitud->cotizaciones->map(fn($cot) => [
                    'CotizacionID'    => $cot->CotizacionID,
                    'Proveedor'       => $cot->Proveedor,
                    'Descripcion'     => $cot->Descripcion,
                    'Precio'          => (float) $cot->Precio,
                    'CostoEnvio'      => (float) ($cot->CostoEnvio ?? 0),
                    'NumeroParte'     => $cot->NumeroParte,
                    'Cantidad'        => (int) ($cot->Cantidad ?? 1),
                    'Estatus'         => $cot->Estatus,
                    'TiempoEntrega'   => $cot->TiempoEntrega,
                    'Observaciones'   => $cot->Observaciones,
                    'NumeroPropuesta' => (int) ($cot->NumeroPropuesta ?? 0),
                    'NombreEquipo'    => $cot->NombreEquipo ?? '',
                ])->toArray()
                : [];

            $cotizacionesEnviadas = ($solicitud->Estatus === 'Cotizaciones Enviadas') ? 1 : 0;

            $activosConFechas = $activosAsignados->map(fn($activo) => [
                'SolicitudActivoID' => $activo->SolicitudActivoID,
                'NumeroPropuesta'   => $activo->NumeroPropuesta,
                'UnidadIndex'       => $activo->UnidadIndex,
                'FechaEntrega'      => $activo->FechaEntrega ? $activo->FechaEntrega->format('d/m/Y') : null,
                'EmpleadoAsignado'  => $activo->empleadoAsignado ? [
                    'EmpleadoID'     => $activo->empleadoAsignado->EmpleadoID,
                    'NombreEmpleado' => $activo->empleadoAsignado->NombreEmpleado,
                ] : null,
                'CotizacionID'      => $activo->CotizacionID,
            ])->toArray();

            $activosPorCotizacion = collect($activosConFechas)->groupBy('CotizacionID');

            $recotizarPropuestas = [];
            $recotizarMotivo     = '';
            if (
                $solicitud->Estatus === 'Re-cotizar'
                && $pasoGerencia
                && $pasoGerencia->comment
                && str_starts_with($pasoGerencia->comment, 'RECOTIZAR|')
            ) {
                $parts = explode('|', $pasoGerencia->comment, 3);
                if (isset($parts[1])) {
                    $recotizarPropuestas = array_values(array_filter(array_map('intval', explode(',', $parts[1]))));
                }
                $recotizarMotivo = $parts[2] ?? '';
            }

            $canceladoPorNombre = null;
            if ($solicitud->cancelado_por) {
                $userCancelo        = \App\Models\User::find($solicitud->cancelado_por);
                $canceladoPorNombre = $userCancelo?->name ?? "Usuario #{$solicitud->cancelado_por}";
            }

            return response()->json([
                'SolicitudID'           => $solicitud->SolicitudID,
                'Motivo'                => $solicitud->Motivo,
                'DescripcionMotivo'     => $solicitud->DescripcionMotivo,
                'Requerimientos'        => $solicitud->Requerimientos,
                'Estatus'               => $solicitud->Estatus,
                'estatusDisplay'        => $estatusDisplay,
                'motivo_cancelacion'    => $solicitud->motivo_cancelacion,
                'canceladoPorNombre'    => $canceladoPorNombre,
                'fecha_cancelacion'     => $solicitud->fecha_cancelacion
                    ? \Carbon\Carbon::parse($solicitud->fecha_cancelacion)->format('d/m/Y H:i')
                    : null,
                'recotizarPropuestas'   => $recotizarPropuestas,
                'recotizarMotivo'       => $recotizarMotivo,
                'fechaCreacion'         => $solicitud->created_at ? $solicitud->created_at->format('d/m/Y H:i') : 'N/A',
                'Proyecto'              => $solicitud->Proyecto,
                'ProyectoNombre'        => $proyectoNombre,
                'empleado'              => $solicitud->empleadoid ? [
                    'EmpleadoID'     => $solicitud->empleadoid->EmpleadoID,
                    'NombreEmpleado' => $solicitud->empleadoid->NombreEmpleado,
                    'Correo'         => $solicitud->empleadoid->Correo,
                ] : null,
                'gerencia'              => $solicitud->gerenciaid ? [
                    'GerenciaID'     => $solicitud->gerenciaid->GerenciaID,
                    'NombreGerencia' => $solicitud->gerenciaid->NombreGerencia,
                ] : null,
                'obra'                  => $solicitud->obraid ? [
                    'ObraID'     => $solicitud->obraid->ObraID,
                    'NombreObra' => $solicitud->obraid->NombreObra,
                ] : null,
                'puesto'                => $solicitud->puestoid ? [
                    'PuestoID'     => $solicitud->puestoid->PuestoID,
                    'NombrePuesto' => $solicitud->puestoid->NombrePuesto,
                ] : null,
                'pasosAprobacion'       => $pasosAprobacion,
                'cotizaciones'          => $cotizaciones,
                'activosAsignados'      => $activosConFechas,
                'activosPorCotizacion'  => $activosPorCotizacion->toArray(),
                'puedeCotizar'          => $puedeCotizar,
                'puedeElegirCotizacion' => $puedeElegirCotizacion,
                'cotizacionesEnviadas'  => $cotizacionesEnviadas,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        } catch (\Exception $e) {
            Log::error("Error obteniendo datos de solicitud #{$id}: " . $e->getMessage());
            return response()->json(['error' => 'Error al cargar la información de la solicitud'], 500);
        }
    }

    // Selecciona cotización ganadora por producto y notifica a administración si todos eligieron
    public function seleccionarCotizacion(Request $request, $id)
    {
        try {
            $request->validate([
                'cotizacion_id' => 'required|integer|exists:cotizaciones,CotizacionID',
                'token'         => 'nullable|string',
            ]);

            $emailAdminData = null;

            \DB::transaction(function () use ($request, $id, &$emailAdminData) {
                $solicitud = Solicitud::with([
                    'empleadoid',
                    'cotizaciones',
                    'pasoGerencia.approverEmpleado',
                    'pasoAdministracion.approverEmpleado',
                ])->findOrFail($id);

                $cotizacionGanadora = Cotizacion::findOrFail($request->input('cotizacion_id'));

                if ((int)$cotizacionGanadora->SolicitudID !== (int)$solicitud->SolicitudID) {
                    throw new \RuntimeException('La cotización no pertenece a esta solicitud');
                }

                if ($cotizacionGanadora->Estatus === 'Seleccionada') {
                    throw new \RuntimeException('Esta cotización ya fue seleccionada como ganadora para este producto.');
                }

                if (!in_array($cotizacionGanadora->Estatus, ['Pendiente', 'Seleccionada', 'Rechazada'], true)) {
                    throw new \RuntimeException('No se puede seleccionar esta cotización');
                }

                $claveProducto = $this->claveProducto($cotizacionGanadora);

                $cotizacionesMismoProducto = $solicitud->cotizaciones->filter(
                    fn($c) => $this->claveProducto($c) === $claveProducto
                );

                $cotizacionGanadora->Estatus = 'Seleccionada';
                $cotizacionGanadora->save();

                $idsRechazar = $cotizacionesMismoProducto
                    ->where('CotizacionID', '!=', $cotizacionGanadora->CotizacionID)
                    ->pluck('CotizacionID');

                if ($idsRechazar->isNotEmpty()) {
                    Cotizacion::whereIn('CotizacionID', $idsRechazar)->update(['Estatus' => 'Rechazada']);
                }

                $solicitud->refresh();
                $solicitud->load([
                    'empleadoid',
                    'cotizaciones',
                    'pasoGerencia.approverEmpleado',
                    'pasoAdministracion.approverEmpleado',
                ]);

                $todosGanadores = $solicitud->todosProductosTienenGanador();

                if (!$todosGanadores) {
                    return;
                }

                $pasoGerencia       = $solicitud->pasoGerencia;
                $pasoAdministracion = $solicitud->pasoAdministracion;

                if (!$pasoGerencia) {
                    throw new \RuntimeException('No existe el paso de gerencia para esta solicitud.');
                }

                if ($pasoGerencia->status === 'pending') {
                    $pasoGerencia->update([
                        'status'                 => 'approved',
                        'comment'                => 'Ganadores elegidos',
                        'decided_at'             => now(),
                        'decided_by_empleado_id' => $pasoGerencia->approver_empleado_id,
                    ]);

                    $token = $request->input('token');
                    if ($token) {
                        \App\Models\SolicitudTokens::where('token', $token)
                            ->where('approval_step_id', $pasoGerencia->id)
                            ->update([
                                'revoked_at' => now(),
                                'used_at'    => now(),
                            ]);
                    }
                } elseif ($pasoGerencia->status !== 'approved') {
                    throw new \RuntimeException('El paso de gerencia no está en un estado válido para continuar.');
                }

                $solicitud->Estatus = 'En revisión';
                $solicitud->save();

                if (!$pasoAdministracion) {
                    throw new \RuntimeException('No existe el paso de administración para esta solicitud.');
                }

                if ($pasoAdministracion->status !== 'pending') {
                    return;
                }

                $pasoAdministracion->load('approverEmpleado');

                if (!$pasoAdministracion->approverEmpleado) {
                    throw new \RuntimeException('No existe aprobador configurado para administración.');
                }

                $adminTokenRow = \App\Models\SolicitudTokens::where('approval_step_id', $pasoAdministracion->id)
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if (!$adminTokenRow) {
                    $adminTokenRow = \App\Models\SolicitudTokens::create([
                        'approval_step_id' => $pasoAdministracion->id,
                        'token'            => \Illuminate\Support\Str::uuid(),
                        'expires_at'       => now()->addDays(7),
                    ]);
                }

                $emailAdminData = [
                    'aprobador'  => $pasoAdministracion->approverEmpleado,
                    'solicitud'  => $solicitud->load('empleadoid'),
                    'token'      => $adminTokenRow->token,
                    'stageLabel' => 'Administración: ve ganadores y aprueba la solicitud',
                ];
            });

            $solicitud = Solicitud::with(['cotizaciones'])->findOrFail($id);
            $todosGanadores = $solicitud->todosProductosTienenGanador();

            if ($emailAdminData) {
                $enviado = app(\App\Services\SolicitudAprobacionEmailService::class)->enviarRevisionPendiente(
                    $emailAdminData['aprobador'],
                    $emailAdminData['solicitud'],
                    $emailAdminData['token'],
                    $emailAdminData['stageLabel']
                );

                if (!$enviado) {
                    Log::error("No se pudo enviar el correo a administración para la solicitud #{$id}");
                    return response()->json([
                        'success'          => false,
                        'message'          => 'Se seleccionaron los ganadores, pero no se pudo enviar el correo a Administración.',
                        'todos_completos'  => $todosGanadores,
                    ], 500);
                }
            }

            $mensaje = $todosGanadores
                ? 'Ganadores seleccionados. Se ha enviado la solicitud a Administración para su aprobación final.'
                : 'Ganador seleccionado para este producto. Elige el ganador de los demás productos para completar.';

            return response()->json([
                'success'         => true,
                'message'         => $mensaje,
                'todos_completos' => $todosGanadores,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud o cotización no encontrada',
            ], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error("Error seleccionando cotización ganadora en solicitud #{$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al seleccionar la cotización: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Confirma un ganador por propuesta en bloque y notifica a administración
    public function confirmarGanadores(Request $request, $id)
    {
        try {
            $request->validate([
                'ganadores'   => 'required|array|min:1',
                'ganadores.*' => 'integer|exists:cotizaciones,CotizacionID',
                'token'       => 'nullable|string',
            ]);

            $emailAdminData = null;

            \DB::transaction(function () use ($request, $id, &$emailAdminData) {
                $solicitud = Solicitud::with([
                    'empleadoid',
                    'cotizaciones',
                    'pasoGerencia.approverEmpleado',
                    'pasoAdministracion.approverEmpleado',
                ])->findOrFail($id);

                $ids          = array_map('intval', $request->input('ganadores'));
                $cotizaciones = $solicitud->cotizaciones ?? collect();

                $propuestas = [];
                foreach ($cotizaciones as $c) {
                    $numPropuesta = (int)($c->NumeroPropuesta ?? 1);
                    $propuestas[$numPropuesta][] = $c;
                }

                if (count($ids) !== count(array_keys($propuestas))) {
                    throw new \RuntimeException('Debes enviar exactamente un ganador por cada propuesta.');
                }

                $porPropuesta = [];
                foreach ($ids as $cid) {
                    $cot = $cotizaciones->firstWhere('CotizacionID', $cid);

                    if (!$cot || (int)$cot->SolicitudID !== (int)$solicitud->SolicitudID) {
                        throw new \RuntimeException('Una o más cotizaciones no pertenecen a esta solicitud.');
                    }

                    $numPropuesta = (int)($cot->NumeroPropuesta ?? 1);

                    if (!isset($propuestas[$numPropuesta])) {
                        throw new \RuntimeException('Cotización no coincide con ninguna propuesta de la solicitud.');
                    }

                    if (isset($porPropuesta[$numPropuesta])) {
                        throw new \RuntimeException('Solo puede haber un ganador por propuesta.');
                    }

                    $porPropuesta[$numPropuesta] = $cot;
                }

                foreach ($porPropuesta as $numPropuesta => $ganador) {
                    $ganador->Estatus = 'Seleccionada';
                    $ganador->save();

                    $idsRechazar = collect($propuestas[$numPropuesta])
                        ->pluck('CotizacionID')
                        ->filter(fn($cid) => (int)$cid !== (int)$ganador->CotizacionID);

                    if ($idsRechazar->isNotEmpty()) {
                        Cotizacion::whereIn('CotizacionID', $idsRechazar)->update([
                            'Estatus' => 'Rechazada',
                        ]);
                    }
                }

                $solicitud->refresh();
                $solicitud->load([
                    'empleadoid',
                    'cotizaciones',
                    'pasoGerencia.approverEmpleado',
                    'pasoAdministracion.approverEmpleado',
                ]);

                $todasPropuestasConGanador = true;
                foreach ($propuestas as $numPropuesta => $cotis) {
                    if (!collect($cotis)->contains('Estatus', 'Seleccionada')) {
                        $todasPropuestasConGanador = false;
                        break;
                    }
                }

                if (!$todasPropuestasConGanador) {
                    return;
                }

                $pasoGerencia       = $solicitud->pasoGerencia;
                $pasoAdministracion = $solicitud->pasoAdministracion;

                if (!$pasoGerencia) {
                    throw new \RuntimeException('No existe el paso de gerencia para esta solicitud.');
                }

                if ($pasoGerencia->status === 'pending') {
                    $pasoGerencia->update([
                        'status'                 => 'approved',
                        'comment'                => 'Ganadores elegidos',
                        'decided_at'             => now(),
                        'decided_by_empleado_id' => $pasoGerencia->approver_empleado_id,
                    ]);

                    $token = $request->input('token');
                    if ($token) {
                        \App\Models\SolicitudTokens::where('token', $token)
                            ->where('approval_step_id', $pasoGerencia->id)
                            ->update([
                                'revoked_at' => now(),
                                'used_at'    => now(),
                            ]);
                    }
                } elseif ($pasoGerencia->status !== 'approved') {
                    throw new \RuntimeException('El paso de gerencia no está en un estado válido para continuar.');
                }

                $solicitud->Estatus = 'En revisión';
                $solicitud->save();

                if (!$pasoAdministracion) {
                    throw new \RuntimeException('No existe el paso de administración para esta solicitud.');
                }

                if ($pasoAdministracion->status !== 'pending') {
                    return;
                }

                $pasoAdministracion->load('approverEmpleado');

                if (!$pasoAdministracion->approverEmpleado) {
                    throw new \RuntimeException('No existe aprobador configurado para administración.');
                }

                $adminTokenRow = \App\Models\SolicitudTokens::where('approval_step_id', $pasoAdministracion->id)
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if (!$adminTokenRow) {
                    $adminTokenRow = \App\Models\SolicitudTokens::create([
                        'approval_step_id' => $pasoAdministracion->id,
                        'token'            => \Illuminate\Support\Str::uuid(),
                        'expires_at'       => now()->addDays(7),
                    ]);
                }

                $emailAdminData = [
                    'aprobador'  => $pasoAdministracion->approverEmpleado,
                    'solicitud'  => $solicitud->load('empleadoid'),
                    'token'      => $adminTokenRow->token,
                    'stageLabel' => 'Administración: ve ganadores y aprueba la solicitud',
                ];
            });

            $solicitud = Solicitud::with(['cotizaciones'])->findOrFail($id);
            $todosGanadores = $solicitud->todosProductosTienenGanador();

            if ($emailAdminData) {
                $enviado = app(\App\Services\SolicitudAprobacionEmailService::class)->enviarRevisionPendiente(
                    $emailAdminData['aprobador'],
                    $emailAdminData['solicitud'],
                    $emailAdminData['token'],
                    $emailAdminData['stageLabel']
                );

                if (!$enviado) {
                    Log::error("No se pudo enviar el correo a administración para la solicitud #{$id}");

                    return response()->json([
                        'success'         => false,
                        'message'         => 'Los ganadores se confirmaron, pero no se pudo enviar el correo a Administración.',
                        'todos_completos' => $todosGanadores,
                    ], 500);
                }
            }

            return response()->json([
                'success'         => true,
                'message'         => 'Ganadores confirmados. Se ha enviado la solicitud a Administración para su aprobación.',
                'todos_completos' => $todosGanadores,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada',
            ], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error("Error confirmando ganadores en solicitud #{$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar ganadores: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Registra solicitud de re-cotización por parte del gerente
    public function solicitarRecotizacion(Request $request, $id)
    {
        $request->validate([
            'propuestas'   => 'required|array|min:1',
            'propuestas.*' => 'integer|min:1',
            'motivo'       => 'required|string|max:2000',
            'token'        => 'nullable|string',
        ]);

        try {
            $solicitud    = Solicitud::with(['pasoGerencia'])->findOrFail($id);
            $pasoGerencia = $solicitud->pasoGerencia;

            if (!$pasoGerencia || $pasoGerencia->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'El paso de gerencia no está pendiente o no existe.'], 400);
            }

            $token    = $request->input('token');
            $tokenRow = null;
            if ($token) {
                $tokenRow = \App\Models\SolicitudTokens::where('token', $token)
                    ->where('approval_step_id', $pasoGerencia->id)
                    ->whereNull('used_at')->whereNull('revoked_at')
                    ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->first();

                if (!$tokenRow) {
                    return response()->json(['success' => false, 'message' => 'Token no válido o expirado.'], 403);
                }
            }

            $propuestas = array_values(array_unique(array_map('intval', $request->input('propuestas'))));
            $motivo     = trim($request->input('motivo'));
            $comment    = 'RECOTIZAR|' . implode(',', $propuestas) . '|' . str_replace('|', ' ', $motivo);

            $solicitud->Estatus   = 'Re-cotizar';
            $solicitud->save();
            $pasoGerencia->comment = $comment;
            $pasoGerencia->save();

            if ($tokenRow) {
                $tokenRow->update(['revoked_at' => now()]);
            }

            Log::info("Solicitud #{$id}: gerente solicitó re-cotizar propuestas " . implode(', ', $propuestas));

            return response()->json([
                'success'  => true,
                'message'  => 'Se ha registrado la solicitud de re-cotización. Recibirás un nuevo correo con las cotizaciones actualizadas cuando TI las envíe.',
                'redirect' => $token ? route('solicitudes.recotizacion-solicitada') : null,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Solicitud no encontrada.'], 404);
        } catch (\Exception $e) {
            Log::error('Error solicitando recotización solicitud #' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar la solicitud de re-cotización.'], 500);
        }
    }

    // Muestra la vista de cotización para una solicitud
    public function mostrarPaginaCotizacion($id)
    {
        try {
            $solicitud = Solicitud::with([
                'empleadoid',
                'gerenciaid',
                'obraid',
                'puestoid',
                'pasoSupervisor',
                'pasoGerencia',
                'pasoAdministracion',
                'cotizaciones',
            ])->findOrFail($id);

            $pasoSupervisor     = $solicitud->pasoSupervisor;
            $pasoGerencia       = $solicitud->pasoGerencia;
            $pasoAdministracion = $solicitud->pasoAdministracion;
            $supervisorAprobado = $pasoSupervisor && $pasoSupervisor->status === 'approved';

            $estaRechazada = ($pasoSupervisor && $pasoSupervisor->status === 'rejected')
                || ($pasoGerencia && $pasoGerencia->status === 'rejected')
                || ($pasoAdministracion && $pasoAdministracion->status === 'rejected');

            $todosGanadores = $solicitud->todosProductosTienenGanador();
            $puedeCotizar   = $supervisorAprobado && auth()->check() && !$estaRechazada
                && $solicitud->Estatus !== 'Aprobado'
                && !$todosGanadores;

            if (!$puedeCotizar) {
                $mensaje = 'No puedes cotizar esta solicitud.';
                if ($estaRechazada) {
                    $mensaje = 'No puedes cotizar una solicitud rechazada.';
                } elseif (!$supervisorAprobado) {
                    $mensaje = 'El Vo.bo de supervisor debe estar aprobado para poder cotizar.';
                } elseif ($todosGanadores) {
                    $mensaje = 'Esta solicitud ya tiene cotizaciones ganadoras seleccionadas.';
                } elseif ($solicitud->Estatus === 'Aprobado') {
                    $mensaje = 'Esta solicitud ya fue aprobada completamente.';
                }
                return redirect()->route('tickets.index')->with('error', $mensaje);
            }

            $recotizarPropuestas = [];
            $recotizarMotivo     = '';
            if (
                $solicitud->Estatus === 'Re-cotizar'
                && $pasoGerencia && $pasoGerencia->comment
                && str_starts_with($pasoGerencia->comment, 'RECOTIZAR|')
            ) {
                $parts = explode('|', $pasoGerencia->comment, 3);
                if (isset($parts[1])) {
                    $recotizarPropuestas = array_values(array_filter(array_map('intval', explode(',', $parts[1]))));
                }
                $recotizarMotivo = $parts[2] ?? '';
            }

            return view('solicitudes.cotizar', [
                'solicitud'           => $solicitud,
                'recotizarPropuestas' => $recotizarPropuestas,
                'recotizarMotivo'     => $recotizarMotivo,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('tickets.index')->with('error', 'Solicitud no encontrada.');
        } catch (\Exception $e) {
            Log::error("Error mostrando página cotizar solicitud #{$id}: " . $e->getMessage());
            return redirect()->route('tickets.index')->with('error', 'Error al cargar la página de cotización.');
        }
    }

    // Retorna cotizaciones agrupadas por proveedor y producto para edición
    public function obtenerCotizaciones($id)
    {
        try {
            $solicitud = Solicitud::with('cotizaciones')->findOrFail($id);

            if (!$solicitud->cotizaciones || $solicitud->cotizaciones->count() === 0) {
                return response()->json([
                    'proveedores'               => [],
                    'productos'                 => [],
                    'tieneCotizacionesEnviadas' => $solicitud->Estatus === 'Cotizaciones Enviadas',
                ]);
            }

            $proveedores = $solicitud->cotizaciones->pluck('Proveedor')->unique()->values()->toArray();

            $productosMap = [];
            $cotizacionesOrdenadas = $solicitud->cotizaciones->sortBy(
                fn($c) => [(int)($c->NumeroPropuesta ?? 0), (int)($c->NumeroProducto ?? 0), (int)($c->CotizacionID ?? 0)]
            )->values();

            foreach ($cotizacionesOrdenadas as $cotizacion) {
                $numProp       = (int)($cotizacion->NumeroPropuesta ?? 1);
                $numProd       = (int)($cotizacion->NumeroProducto ?? 1);
                $claveProducto = 'prop_' . $numProp . '_prod_' . $numProd;
                $cantidad      = max(1, (int)($cotizacion->Cantidad ?? 1));

                if (!isset($productosMap[$claveProducto])) {
                    $productosMap[$claveProducto] = [
                        'numeroPropuesta' => $numProp,
                        'numeroProducto'  => $numProd,
                        'cantidad'        => $cantidad,
                        'numeroParte'     => $cotizacion->NumeroParte ?? '',
                        'descripcion'     => $cotizacion->Descripcion ?? '',
                        'nombreEquipo'    => $cotizacion->NombreEquipo ?? null,
                        'unidad'          => $cotizacion->Unidad ?? 'PIEZA',
                        'precios'         => [],
                        'descripciones'   => [],
                        'numeroPartes'    => [],
                        'tiempoEntrega'   => [],
                        'observaciones'   => [],
                    ];
                }

                $productosMap[$claveProducto]['cantidad'] = max(1, $cantidad);
                if ($cotizacion->NombreEquipo !== null && trim($cotizacion->NombreEquipo) !== '') {
                    $productosMap[$claveProducto]['nombreEquipo'] = $cotizacion->NombreEquipo;
                }
                if ($cotizacion->Unidad !== null && trim($cotizacion->Unidad) !== '') {
                    $productosMap[$claveProducto]['unidad'] = $cotizacion->Unidad;
                }

                $productosMap[$claveProducto]['precios'][$cotizacion->Proveedor] = [
                    'precio_unitario' => (float)$cotizacion->Precio,
                    'costo_envio'     => (float)($cotizacion->CostoEnvio ?? 0),
                ];
                $productosMap[$claveProducto]['descripciones'][$cotizacion->Proveedor] = $cotizacion->Descripcion ?? '';
                $productosMap[$claveProducto]['numeroPartes'][$cotizacion->Proveedor]  = $cotizacion->NumeroParte ?? '';

                if ($cotizacion->TiempoEntrega !== null) {
                    $productosMap[$claveProducto]['tiempoEntrega'][$cotizacion->Proveedor] = (int)$cotizacion->TiempoEntrega;
                }
                if ($cotizacion->Observaciones !== null && trim($cotizacion->Observaciones) !== '') {
                    $productosMap[$claveProducto]['observaciones'][$cotizacion->Proveedor] = $cotizacion->Observaciones;
                }
            }

            $productos = array_values($productosMap);
            foreach ($productos as &$producto) {
                foreach ($proveedores as $proveedor) {
                    if (!isset($producto['precios'][$proveedor])) {
                        $producto['precios'][$proveedor] = ['precio_unitario' => 0, 'costo_envio' => 0];
                    }
                    if (!isset($producto['descripciones'][$proveedor])) {
                        $producto['descripciones'][$proveedor] = $producto['descripcion'] ?? '';
                    }
                    if (!isset($producto['numeroPartes'][$proveedor])) {
                        $producto['numeroPartes'][$proveedor] = $producto['numeroParte'] ?? '';
                    }
                }
            }

            return response()->json([
                'proveedores'               => $proveedores,
                'productos'                 => $productos,
                'tieneCotizacionesEnviadas' => $solicitud->Estatus === 'Cotizaciones Enviadas',
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo cotizaciones de solicitud #{$id}: " . $e->getMessage());
            return response()->json(['proveedores' => [], 'productos' => [], 'tieneCotizacionesEnviadas' => false]);
        }
    }

    // Guarda o reemplaza todas las cotizaciones de una solicitud
    public function guardarCotizaciones(Request $request, $id)
    {
        $request->headers->set('Accept', 'application/json');

        try {   
            $solicitud = Solicitud::findOrFail($id);

            $validated = $request->validate([
                'proveedores' => 'required|array|min:1',
                'productos'   => 'required|array|min:1',
            ]);

            Cotizacion::where('SolicitudID', $solicitud->SolicitudID)->delete();

            $proveedores       = $validated['proveedores'];
            $productos         = $validated['productos'];
            $cotizacionesCreadas = 0;

            foreach ($productos as $producto) {
                $descBase        = trim($producto['descripcion'] ?? '');
                $descripciones   = $producto['descripciones'] ?? [];
                $numerosParte    = $producto['numeros_parte'] ?? $producto['numeroPartes'] ?? [];
                $precios         = $producto['precios'] ?? [];
                $cantidad        = isset($producto['cantidad']) ? (int)$producto['cantidad'] : 1;
                $numeroPropuesta = (int)($producto['numero_propuesta'] ?? 1);
                $numeroProducto  = (int)($producto['numero_producto'] ?? 1);

                foreach ($proveedores as $proveedor) {
                    $datosPrecios = $precios[$proveedor] ?? null;

                    if (!is_array($datosPrecios)) {
                        $precioUnitario = (float)($datosPrecios ?? 0);
                        $costoEnvio     = 0;
                    } else {
                        $precioUnitario = (float)($datosPrecios['precio_unitario'] ?? 0);
                        $costoEnvio     = (float)($datosPrecios['costo_envio'] ?? 0);
                    }

                    if ($precioUnitario <= 0) continue;

                    $desc = trim($descripciones[$proveedor] ?? '') ?: $descBase;
                    if ($desc === '') continue;

                    $np          = trim($numerosParte[$proveedor] ?? '') ?: trim($producto['numero_parte'] ?? $producto['numeroParte'] ?? '');
                    $nombreEquipo = trim($producto['nombre_equipo'] ?? $producto['nombreEquipo'] ?? $producto['descripcion'] ?? '');
                    $unidad      = trim($producto['unidad'] ?? '') ?: 'PIEZA';

                    Cotizacion::create([
                        'SolicitudID'     => $solicitud->SolicitudID,
                        'Proveedor'       => $proveedor,
                        'Descripcion'     => $desc,
                        'Precio'          => $precioUnitario,
                        'CostoEnvio'      => $costoEnvio,
                        'NumeroParte'     => $np !== '' ? $np : null,
                        'Cantidad'        => $cantidad,
                        'NombreEquipo'    => $nombreEquipo !== '' ? $nombreEquipo : null,
                        'Unidad'          => $unidad,
                        'TiempoEntrega'   => isset($producto['tiempo_entrega'][$proveedor]) ? (int)$producto['tiempo_entrega'][$proveedor] : null,
                        'Observaciones'   => $producto['observaciones'][$proveedor] ?? null,
                        'Estatus'         => 'Pendiente',
                        'NumeroPropuesta' => $numeroPropuesta,
                        'NumeroProducto'  => $numeroProducto,
                    ]);
                    $cotizacionesCreadas++;
                }
            }

            if ($cotizacionesCreadas === 0) {
                return response()->json(['success' => false, 'message' => 'No se crearon cotizaciones. Verifica que haya al menos un precio válido.'], 400);
            }

            return response()->json(['success' => true, 'message' => "Se guardaron {$cotizacionesCreadas} cotización(es) correctamente."]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        } catch (\Exception $e) {
            Log::error("Error guardando cotizaciones para solicitud #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar las cotizaciones: ' . $e->getMessage()], 500);
        }
    }

    // Actualiza el estatus a "Cotizaciones Enviadas" y envía correo al gerente con token
    public function enviarCotizacionesAlGerente(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::with(['empleadoid', 'cotizaciones'])->findOrFail($id);

            if (!$solicitud->cotizaciones || $solicitud->cotizaciones->count() === 0) {
                return response()->json(['success' => false, 'message' => 'No hay cotizaciones guardadas para esta solicitud'], 400);
            }

            $pasoSupervisor = $solicitud->pasoSupervisor;
            $pasoGerencia   = $solicitud->pasoGerencia;

            if (!$pasoSupervisor || $pasoSupervisor->status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'El Vo.bo de supervisor debe estar aprobado antes de enviar cotizaciones al gerente.'], 400);
            }

            if (!$pasoGerencia) {
                return response()->json(['success' => false, 'message' => 'No existe paso de gerencia para esta solicitud.'], 400);
            }

            // Permitir envío si está pending O si está approved pero sin ganadores seleccionados (re-envío)
            $todosProductosTienenGanador = $solicitud->todosProductosTienenGanador();
            $estatusGerenciaValido = $pasoGerencia->status === 'pending'
                || ($pasoGerencia->status === 'approved' && !$todosProductosTienenGanador);

            if (!$estatusGerenciaValido || $pasoGerencia->status === 'rejected') {
                return response()->json(['success' => false, 'message' => 'El paso de gerencia ya fue resuelto. No se puede reenviar cotizaciones en este estado.'], 400);
            }

            $solicitud->Estatus = 'Cotizaciones Enviadas';
            $solicitud->save();

            $pasoGerencia = $solicitud->pasoGerencia;
            if ($pasoGerencia && $pasoGerencia->comment && str_starts_with($pasoGerencia->comment, 'RECOTIZAR|')) {
                $pasoGerencia->comment = null;
                $pasoGerencia->save();
            }

            $pasoGerencia = $solicitud->pasoGerencia;
            if (!$pasoGerencia) {
                return response()->json(['success' => false, 'message' => 'No se encontró el paso de aprobación de gerencia'], 400);
            }

            $pasoGerencia->load('approverEmpleado');
            $token = \Illuminate\Support\Str::uuid()->toString();

            try {
                \App\Models\SolicitudTokens::create([
                    'approval_step_id' => $pasoGerencia->id,
                    'token'            => $token,
                    'expires_at'       => now()->addDays(7),
                ]);
            } catch (\Exception $e) {
                Log::error("No se pudo crear token para elegir ganador: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Error al crear el token de acceso: ' . $e->getMessage()], 500);
            }

            $gerente = $pasoGerencia->approverEmpleado;
            if (!$gerente) {
                if (auth()->check() && auth()->user()->can('aprobar-solicitudes-gerencia')) {
                    $gerente = Empleados::where('Correo', auth()->user()->email)->first();
                }
            }

            if (!$gerente || empty($gerente->Correo)) {
                $gerente               = new Empleados();
                $gerente->NombreEmpleado = 'Gerente';
                $gerente->Correo       = config('email_tickets.default_gerente_email', 'tordonez@proser.com.mx');
                Log::warning("No se encontró gerente para solicitud #{$id}, usando correo por defecto: {$gerente->Correo}");
            }

            $emailService  = new \App\Services\SolicitudAprobacionEmailService();
            $emailEnviado  = $emailService->enviarCotizacionesListasParaElegir($gerente, $solicitud, $token);

            if (!$emailEnviado) {
                return response()->json(['success' => false, 'message' => 'Error al enviar el correo al gerente.'], 500);
            }

            return response()->json(['success' => true, 'message' => 'Cotizaciones enviadas al gerente correctamente']);
        } catch (\Exception $e) {
            Log::error("Error enviando cotizaciones al gerente para solicitud #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al enviar las cotizaciones: ' . $e->getMessage()], 500);
        }
    }

    // Muestra la vista pública para que el gerente elija el ganador via token
    public function elegirGanadorConToken($token)
    {
        try {
            $tokenRow = \App\Models\SolicitudTokens::where('token', $token)
                ->whereNull('used_at')->whereNull('revoked_at')
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->with(['approvalStep.solicitud.cotizaciones'])
                ->first();

            if (!$tokenRow) abort(404, 'Token no encontrado o inválido');

            $paso     = $tokenRow->approvalStep;
            if (!$paso) abort(404, 'Paso de aprobación no encontrado');

            $solicitud = $paso->solicitud;
            if (!$solicitud) abort(404, 'Solicitud no encontrada');

            $solicitud->load([
                'empleadoid',
                'cotizaciones' => fn($q) => $q->orderBy('NumeroPropuesta')->orderBy('Proveedor'),
            ]);

            if (in_array($solicitud->Estatus, ['Cancelada', 'Cerrada'], true)) {
                $canceladoPor = null;
                if ($solicitud->cancelado_por) {
                    $canceladoPor = \App\Models\User::find($solicitud->cancelado_por)?->name
                        ?? "Usuario #{$solicitud->cancelado_por}";
                }
                return view('solicitudes.cancelada', [
                    'motivo'           => $solicitud->motivo_cancelacion,
                    'canceladoPor'     => $canceladoPor,
                    'fechaCancelacion' => $solicitud->fecha_cancelacion
                        ? \Carbon\Carbon::parse($solicitud->fecha_cancelacion)->format('d/m/Y H:i')
                        : null,
                ]);
            }

            $productos       = $this->agruparCotizacionesPorProducto($solicitud->cotizaciones ?? collect());
            $todosConGanador = $solicitud->todosProductosTienenGanador();
            $ganadores       = $solicitud->cotizaciones ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada') : collect();

            if ($solicitud->Estatus === 'Aprobado' || $todosConGanador) {
                $tokenInfo = [
                    'razon' => 'Ya se han seleccionado los ganadores de todos los productos de esta solicitud.',
                ];
                if ($ganadores->isNotEmpty()) {
                    $lista = $ganadores->map(
                        fn($g) => $g->Descripcion . ' – ' . $g->Proveedor . ' ($' . number_format($g->Precio, 2, '.', ',') . ')'
                    )->implode('; ');
                    $tokenInfo['proveedor_ganador']   = $lista;
                    $tokenInfo['multiple_ganadores']  = $ganadores->count() > 1;
                }
                return view('solicitudes.token-invalido', compact('tokenInfo'))->with('status', 401);
            }

            if (!$solicitud->cotizaciones || $solicitud->cotizaciones->count() === 0) {
                return view('solicitudes.elegir-ganador', [
                    'solicitud' => $solicitud,
                    'productos' => [],
                    'token'     => $token,
                    'error'     => 'No hay cotizaciones disponibles para esta solicitud',
                ]);
            }

            return view('solicitudes.elegir-ganador', [
                'solicitud' => $solicitud,
                'productos' => $productos,
                'token'     => $token,
            ]);
        } catch (\Exception $e) {
            Log::error("Error mostrando elegir ganador con token {$token}: " . $e->getMessage());
            abort(500, 'Error al cargar la página de elección de ganador');
        }
    }
}
