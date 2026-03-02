<?php

namespace App\Http\Controllers;

use App\Models\SolicitudPasos;
use App\Models\SolicitudTokens;
use App\Models\Solicitud;
use App\Models\Empleados;
use App\Models\Proyecto;
use App\Models\Gerencia;
use App\Models\Obras;
use App\Services\SolicitudAprobacionEmailService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SolicitudAprobacionController extends Controller
{
    /**
     * Vista pública por token (sin login)
     */
    public function show(string $token): View
    {
        // Buscar el token sin filtrar por activo para poder detectar el motivo
        $tokenRow = SolicitudTokens::query()
            ->where('token', $token)
            ->with([
                'approvalStep.approverEmpleado',
                'approvalStep.solicitud.empleadoid',
            ])
            ->first();

        // Si no existe el token
        if (!$tokenRow) {
            abort(404, 'Token no encontrado');
        }

        // Verificar si el token está usado
        if ($tokenRow->used_at) {
            $tokenInfo = [
                'razon' => 'Este enlace ya fue utilizado para firmar la solicitud',
                'fecha_usado' => $tokenRow->used_at->translatedFormat('d M Y, H:i'),
            ];
            return view('solicitudes.token-invalido', compact('tokenInfo'))->with('status', 401);
        }

        // Verificar si el token está revocado
        if ($tokenRow->revoked_at) {
            $tokenInfo = [
                'razon' => 'Este enlace fue revocado. La aprobación fue transferida a otra persona',
                'fecha_usado' => $tokenRow->revoked_at->translatedFormat('d M Y, H:i'),
            ];
            return view('solicitudes.token-invalido', compact('tokenInfo'))->with('status', 401);
        }

        // Verificar si el token expiró
        if ($tokenRow->expires_at && now()->greaterThan($tokenRow->expires_at)) {
            $tokenInfo = [
                'razon' => 'Este enlace ha expirado. El tiempo límite para revisar esta solicitud ha finalizado',
                'fecha_expiracion' => $tokenRow->expires_at->translatedFormat('d M Y, H:i'),
            ];
            return view('solicitudes.token-invalido', compact('tokenInfo'))->with('status', 401);
        }

        // Si el token está activo, cargar relaciones completas y mostrar la vista normal
        $tokenRow->load([
            'approvalStep.approverEmpleado',
            'approvalStep.solicitud.empleadoid',
            'approvalStep.solicitud.obraid',
            'approvalStep.solicitud.gerenciaid',
            'approvalStep.solicitud.puestoid',
            'approvalStep.solicitud.cotizaciones',
        ]);

        $step = $tokenRow->approvalStep;
        $solicitud = $step->solicitud;
        $ganadores = ($step->stage === 'administracion' && $solicitud->cotizaciones)
            ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada')
            : collect();

        $prevNotApproved = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
            ->where('step_order', '<', $step->step_order)
            ->where('status', '!=', 'approved')
            ->exists();

        $canDecide = ! $prevNotApproved && $step->status === 'pending';

        $proyectoNombre = $this->obtenerNombreProyecto($solicitud->Proyecto);
        
        return view('solicitudes.revision-publica', [
            'solicitud' => $solicitud,
            'step'      => $step,
            'tokenRow'  => $tokenRow,
            'canDecide' => $canDecide,
            'waitingFor' => $prevNotApproved ? $this->waitingLabel($solicitud, $step) : null,
            'proyectoNombre' => $proyectoNombre,
            'ganadores' => $ganadores,
        ]);
    }

    private function waitingLabel($solicitud, $currentStep): string
    {
        $prevStep = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
            ->where('step_order', '<', $currentStep->step_order)
            ->where('status', '!=', 'approved')
            ->orderBy('step_order')
            ->first();

        return match ($prevStep?->stage) {
            'supervisor' => 'Esperando aprobación del Supervisor',
            'gerencia' => 'Esperando aprobación de Gerencia',
            'administracion' => 'Esperando aprobación de Administración',
            'default' => 'Esperando aprobación previa',
        };
    }

    private const STAGE_LABELS = [
        'supervisor' => 'Vo.bo de supervisor',
        'gerencia' => 'Gerente: ve propuestas, elige ganador o regresa a TI para cotizar',
        'administracion' => 'Administración: ve ganadores y aprueba la solicitud',
    ];

    public function decide(Request $request, string $token)
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'comment'  => 'nullable|string|max:5000',
        ]);

        $emailRevisionData = null;

        try {
            DB::transaction(function () use ($data, $token, &$emailRevisionData) {
                // Buscar el token
                $tokenRow = SolicitudTokens::query()
                    ->where('token', $token)
                    ->lockForUpdate()
                    ->with(['approvalStep', 'approvalStep.solicitud', 'approvalStep.approverEmpleado'])
                    ->first();
                
                if (!$tokenRow) throw new \RuntimeException('Token no encontrado');
                if ($tokenRow->used_at) throw new \RuntimeException('Este enlace ya fue utilizado para firmar la solicitud');
                if ($tokenRow->revoked_at) throw new \RuntimeException('Este enlace fue revocado.');
                if ($tokenRow->expires_at && now()->greaterThan($tokenRow->expires_at)) throw new \RuntimeException('Este enlace ha expirado.');

                $step = $tokenRow->approvalStep;
                $solicitud = $step->solicitud;

                if ($step->status !== 'pending') {
                    throw new \RuntimeException('Esta etapa ya fue resuelta.');
                }

                $prevNotApproved = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('step_order', '<', $step->step_order)
                    ->where('status', '!=', 'approved')
                    ->exists();

                if ($prevNotApproved) {
                    throw new \RuntimeException('Aún faltan aprobaciones previas antes de poder firmar esta etapa.');
                }

                // 1. Actualizar el paso actual
                $step->update([
                    'status' => $data['decision'],
                    'comment' => $data['comment'] ?? null,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $step->approver_empleado_id,
                ]);

                $tokenRow->update([
                    'used_at' => now(),
                ]);

                // Si se rechaza, se cancela todo el flujo
                if ($data['decision'] === 'rejected') {
                    $solicitud->update(['Estatus' => 'Rechazada']);
                    return;
                }

                // =====================================================================
                // NUEVA LÓGICA: AUTO-APROBACIÓN EN CASCADA
                // Si aprobó, verificamos si el siguiente paso lo tiene la misma persona
                // =====================================================================
                $this->procesarAutoAprobacionEnCascada($solicitud->SolicitudID, $step->approver_empleado_id);

                // 2. Buscar pasos pendientes (después de la posible cascada)
                $pending = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('status', 'pending')
                    ->orderBy('step_order')
                    ->get();

                $solicitud->update([
                    'Estatus' => $pending->isNotEmpty() ? 'En revisión' : 'Aprobada',
                ]);

                // 3. Preparar correo para el SIGUIENTE aprobador REAL (si existe)
                // No se envía correo al gerente aquí: el gerente recibe el correo cuando TI sube
                // cotizaciones y las envía (enviarCotizacionesAlGerente). Así el gerente solo ve
                // la solicitud cuando ya hay propuestas para elegir ganador o devolver a TI.
                if ($pending->isNotEmpty()) {
                    $nextStep = $pending->first();
                    $nextStep->load('approverEmpleado');

                    // No enviar correo si el siguiente paso es gerencia: se enviará cuando TI suba cotizaciones
                    if ($nextStep->stage === 'gerencia') {
                        // Opcional: crear/renovar token para cuando TI envíe las cotizaciones
                        $nextTokenRow = SolicitudTokens::where('approval_step_id', $nextStep->id)
                            ->whereNull('used_at')
                            ->whereNull('revoked_at')
                            ->where(function ($q) {
                                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            })
                            ->first();
                        if (!$nextTokenRow) {
                            SolicitudTokens::create([
                                'approval_step_id' => $nextStep->id,
                                'token' => \Illuminate\Support\Str::uuid(),
                                'expires_at' => now()->addDays(7)
                            ]);
                        }
                    } else {
                        $nextTokenRow = SolicitudTokens::where('approval_step_id', $nextStep->id)
                            ->whereNull('used_at')
                            ->whereNull('revoked_at')
                            ->where(function ($q) {
                                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            })
                            ->first();

                        if (!$nextTokenRow) {
                            $nextTokenRow = SolicitudTokens::create([
                                'approval_step_id' => $nextStep->id,
                                'token' => \Illuminate\Support\Str::uuid(),
                                'expires_at' => now()->addDays(7)
                            ]);
                        }

                        if ($nextTokenRow && $nextStep->approverEmpleado) {
                            $stageLabel = self::STAGE_LABELS[$nextStep->stage] ?? $nextStep->stage;
                            $emailRevisionData = [
                                'aprobador' => $nextStep->approverEmpleado,
                                'solicitud' => $solicitud->load('empleadoid'),
                                'token' => $nextTokenRow->token,
                                'stageLabel' => $stageLabel,
                            ];
                        }
                    }
                }
            });

            if ($emailRevisionData) {
                app(SolicitudAprobacionEmailService::class)->enviarRevisionPendiente(
                    $emailRevisionData['aprobador'],
                    $emailRevisionData['solicitud'],
                    $emailRevisionData['token'],
                    $emailRevisionData['stageLabel']
                );
            }

            // Si es una petición AJAX, retornar JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Decisión registrada correctamente.'
                ]);
            }
            
            return redirect()
                ->route('solicitudes.public.show', ['token' => $token])
                ->with('swal_success', 'Decisión registrada correctamente.');
        } catch (ModelNotFoundException $e) {
            return $this->handleTokenError($request, $token, 'El enlace no es válido, ya expiró o ya fue usado.');
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            
            if (str_contains($message, 'expirado') || 
                str_contains($message, 'utilizado') || 
                str_contains($message, 'revocado') ||
                str_contains($message, 'no encontrado')) {
                return $this->handleTokenError($request, $token, $message);
            }
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message ?: 'Ocurrió un error al registrar la decisión.'
                ], 400);
            }
            
            return redirect()
                ->route('solicitudes.public.show', ['token' => $token])
                ->with('swal_error', $message ?: 'Ocurrió un error al registrar la decisión.');
        }
    }
    
    /**
     * Manejar errores de token y mostrar vista apropiada
     */
    private function handleTokenError(Request $request, string $token, string $message)
    {
        // Buscar el token para obtener información adicional
        $tokenRow = SolicitudTokens::where('token', $token)->first();
        
        $tokenInfo = [
            'razon' => $message,
        ];
        
        if ($tokenRow) {
            if ($tokenRow->used_at) {
                $tokenInfo['fecha_usado'] = $tokenRow->used_at->translatedFormat('d M Y, H:i');
            }
            if ($tokenRow->revoked_at) {
                $tokenInfo['fecha_usado'] = $tokenRow->revoked_at->translatedFormat('d M Y, H:i');
            }
            if ($tokenRow->expires_at && now()->greaterThan($tokenRow->expires_at)) {
                $tokenInfo['fecha_expiracion'] = $tokenRow->expires_at->translatedFormat('d M Y, H:i');
            }
        }
        
        // Si es una petición AJAX, retornar JSON con indicador de token expirado
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'token_expired' => true
            ], 401);
        }
        
        // Retornar vista de token inválido
        return view('solicitudes.token-invalido', compact('tokenInfo'))->with('status', 401);
    }

    /**
     * Aprobar solicitud por nivel (desde el panel interno)
     */
    public function aprobarPorNivel(Request $request, $id, $nivel): JsonResponse
    {
        $data = $request->validate([
            'comentario' => 'nullable|string|max:5000',
        ]);

        try {
            DB::transaction(function () use ($data, $id, $nivel) {
                $solicitud = Solicitud::findOrFail($id);
                $usuarioActual = auth()->user();
                $usuarioEmpleado = Empleados::where('Correo', $usuarioActual->email)->firstOrFail();

                $step = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('stage', $nivel)
                    ->firstOrFail();

                if ($step->status !== 'pending') {
                    throw new \RuntimeException('Esta etapa ya fue resuelta.');
                }

                // Verificar permisos
                if ($nivel === 'supervisor' && $step->approver_empleado_id != $usuarioEmpleado->EmpleadoID) {
                    throw new \RuntimeException('No tienes permiso para aprobar en este nivel.');
                }

                $prevNotApproved = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('step_order', '<', $step->step_order)
                    ->where('status', '!=', 'approved')
                    ->exists();

                if ($prevNotApproved) {
                    throw new \RuntimeException('Aún faltan aprobaciones previas.');
                }

                // 1. Aprobar paso actual
                $step->update([
                    'status' => 'approved',
                    'comment' => $data['comentario'] ?? null,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $usuarioEmpleado->EmpleadoID,
                ]);

                // Invalidad token de este paso si existiera (limpieza)
                SolicitudTokens::where('approval_step_id', $step->id)->update(['revoked_at' => now()]);

                // =====================================================================
                // NUEVA LÓGICA: AUTO-APROBACIÓN EN CASCADA
                // Verifica si el mismo empleado es el aprobador del siguiente paso
                // =====================================================================
                $this->procesarAutoAprobacionEnCascada($solicitud->SolicitudID, $usuarioEmpleado->EmpleadoID);

                // 2. Actualizar estado global
                $pending = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('status', 'pending')
                    ->exists();

                if (!$pending) {
                    $solicitud->update(['Estatus' => 'Aprobada']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ocurrió un error al aprobar la solicitud.',
            ], 400);
        }
    }

    /**
     * Rechazar solicitud por nivel (desde el panel interno)
     */
    public function rechazarPorNivel(Request $request, $id, $nivel): JsonResponse
    {
        $data = $request->validate([
            'comentario' => 'required|string|max:5000',
        ]);

        try {
            DB::transaction(function () use ($data, $id, $nivel) {
                $solicitud = Solicitud::findOrFail($id);
                $usuarioActual = auth()->user();
                $usuarioEmpleado = Empleados::where('Correo', $usuarioActual->email)->firstOrFail();

                $step = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('stage', $nivel)
                    ->firstOrFail();

                if ($step->status !== 'pending') {
                    throw new \RuntimeException('Esta etapa ya fue resuelta.');
                }

                // Verificar permisos
                if ($nivel === 'supervisor' && $step->approver_empleado_id != $usuarioEmpleado->EmpleadoID) {
                    throw new \RuntimeException('No tienes permiso para rechazar en este nivel.');
                }

                $step->update([
                    'status' => 'rejected',
                    'comment' => $data['comentario'],
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $usuarioEmpleado->EmpleadoID,
                ]);

                $solicitud->update(['Estatus' => 'Rechazada']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ocurrió un error al rechazar la solicitud.',
            ], 400);
        }
    }

    /**
     * Transferir aprobación a otra persona (desde vista pública)
     */
    public function transferir(Request $request, string $token): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nuevo_aprobador_id' => 'required|integer|exists:empleados,EmpleadoID',
            'comentario' => 'nullable|string|max:5000',
        ]);

        $emailTransferData = null;

        try {
            $nuevoToken = DB::transaction(function () use ($data, $token, &$emailTransferData) {
                // Buscar el token sin filtrar por usado para poder detectar el motivo
                $tokenRow = SolicitudTokens::query()
                    ->where('token', $token)
                    ->lockForUpdate()
                    ->with(['approvalStep', 'approvalStep.solicitud'])
                    ->first();
                
                // Si no existe el token
                if (!$tokenRow) {
                    throw new \RuntimeException('Token no encontrado');
                }
                
                // Verificar si el token está usado
                if ($tokenRow->used_at) {
                    throw new \RuntimeException('Este enlace ya fue utilizado para firmar la solicitud');
                }
                
                // Verificar si el token está revocado
                if ($tokenRow->revoked_at) {
                    throw new \RuntimeException('Este enlace fue revocado. La aprobación fue transferida a otra persona');
                }
                
                // Verificar si el token expiró
                if ($tokenRow->expires_at && now()->greaterThan($tokenRow->expires_at)) {
                    throw new \RuntimeException('Este enlace ha expirado. El tiempo límite para revisar esta solicitud ha finalizado');
                }

                $step = $tokenRow->approvalStep;
                $solicitud = $step->solicitud;

                if ($step->status !== 'pending') {
                    throw new \RuntimeException('Esta etapa ya fue resuelta.');
                }

                if ($step->approver_empleado_id == $data['nuevo_aprobador_id']) {
                    throw new \RuntimeException('Debe seleccionar un aprobador diferente al actual.');
                }

                $nuevoAprobador = Empleados::findOrFail($data['nuevo_aprobador_id']);

                $step->update([
                    'approver_empleado_id' => $data['nuevo_aprobador_id'],
                    'comment' => $data['comentario'] ?? null,
                ]);

                $tokenRow->update([
                    'used_at' => now(),
                    'revoked_at' => now(),
                ]);

                $nuevoTokenRow = SolicitudTokens::create([
                    'approval_step_id' => $step->id,
                    'token' => \Illuminate\Support\Str::uuid(),
                    'expires_at' => now()->addDays(7),
                ]);

                $stageLabel = self::STAGE_LABELS[$step->stage] ?? $step->stage;
                $emailTransferData = [
                    'aprobador' => $nuevoAprobador,
                    'solicitud' => $solicitud->load('empleadoid'),
                    'token' => $nuevoTokenRow->token,
                    'stageLabel' => $stageLabel,
                ];

                return $nuevoTokenRow->token;
            });

            if ($emailTransferData) {
                app(SolicitudAprobacionEmailService::class)->enviarRevisionPendiente(
                    $emailTransferData['aprobador'],
                    $emailTransferData['solicitud'],
                    $emailTransferData['token'],
                    $emailTransferData['stageLabel']
                );
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'La aprobación ha sido transferida correctamente.',
                ]);
            }

            return redirect()
                ->route('solicitudes.public.show', ['token' => $nuevoToken])
                ->with('swal_success', 'La aprobación ha sido transferida correctamente. El nuevo aprobador recibirá un enlace para revisar la solicitud.');
        } catch (ModelNotFoundException $e) {
            return $this->handleTokenError($request, $token, 'El enlace no es válido, ya expiró o ya fue usado.');
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Ocurrió un error al transferir la aprobación.';
            
            // Si el error es relacionado con token expirado/usado/revocado, mostrar vista de token inválido
            if (str_contains($message, 'expirado') || 
                str_contains($message, 'utilizado') || 
                str_contains($message, 'revocado') ||
                str_contains($message, 'no encontrado')) {
                return $this->handleTokenError($request, $token, $message);
            }
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }
            return redirect()
                ->route('solicitudes.public.show', ['token' => $token])
                ->with('swal_error', $message);
        }
    }

    /**
     * Obtener empleados disponibles para transferir (AJAX)
     */
    public function obtenerEmpleadosParaTransferir(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $stage = $request->get('stage', '');
        $excludeId = $request->get('exclude_id', null);

        // Palabras clave para filtrar puestos
        $puestosKeywords = ['coordinador', 'jefe', 'gerente', 'director'];

        $empleados = Empleados::query()
            ->join('puestos', 'empleados.PuestoID', '=', 'puestos.PuestoID')
            ->where('empleados.Estado', true)
            ->where('empleados.tipo_persona', 'FISICA')
            ->where(function ($q) use ($puestosKeywords) {
                foreach ($puestosKeywords as $keyword) {
                    $q->orWhere('puestos.NombrePuesto', 'like', '%' . $keyword . '%');
                }
            })
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('empleados.EmpleadoID', '!=', $excludeId);
            })
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQ) use ($query) {
                    $subQ->where('empleados.NombreEmpleado', 'like', '%' . $query . '%')
                         ->orWhere('empleados.Correo', 'like', '%' . $query . '%');
                });
            })
            ->select('empleados.EmpleadoID', 'empleados.NombreEmpleado', 'empleados.Correo')
            ->orderBy('empleados.NombreEmpleado')
            ->get();

        return response()->json($empleados);
    }

    /**
     * Obtener el nombre completo del proyecto basado en la nomenclatura
     * Formato: PREFIJO + ID (ej: PR2, GE5, OB10)
     * PR = Proyecto, GE = Gerencia, OB = Obra
     */
    private function obtenerNombreProyecto($proyecto)
    {
        if (empty($proyecto)) {
            return 'N/A';
        }

        // Extraer prefijo y ID
        if (preg_match('/^([A-Z]{2})(\d+)$/i', $proyecto, $matches)) {
            $prefijo = strtoupper($matches[1]);
            $id = (int) $matches[2];

            try {
                switch ($prefijo) {
                    case 'PR':
                        // Buscar en tabla proyectos
                        $proyectoModel = Proyecto::find($id);
                        if ($proyectoModel) {
                            return $proyectoModel->NombreProyecto ?? $proyectoModel->Proyecto ?? $proyecto;
                        }
                        break;
                    
                    case 'GE':
                        // Buscar en tabla gerencia
                        $gerencia = Gerencia::find($id);
                        if ($gerencia) {
                            return $gerencia->NombreGerencia ?? $proyecto;
                        }
                        break;
                    
                    case 'OB':
                        // Buscar en tabla obras
                        $obra = Obras::find($id);
                        if ($obra) {
                            return $obra->NombreObra ?? $proyecto;
                        } 
                        break;
                }
            } catch (\Exception $e) {
                // En caso de error, retornar el valor original
            }
        }

        // Si no se pudo parsear o no se encontró, retornar el valor original
        return $proyecto;
    }

    /**
     * Lógica de efecto dominó (Cascada)
     * Revisa los pasos siguientes pendientes. Si el aprobador es el mismo que acaba de firmar,
     * se auto-aprueba. Se detiene en cuanto encuentra a alguien diferente.
     * El paso "gerencia" NUNCA se auto-aprueba: el gerente debe elegir un ganador de la cotización
     * o regresar a TI, aunque sea la misma persona que el supervisor.
     */
    private function procesarAutoAprobacionEnCascada($solicitudId, $empleadoIdQueAprobo)
    {
        // Buscamos TODOS los pasos pendientes de esta solicitud, ordenados por secuencia
        $pasosPendientes = SolicitudPasos::where('solicitud_id', $solicitudId)
            ->where('status', 'pending')
            ->orderBy('step_order', 'asc')
            ->get();

        foreach ($pasosPendientes as $siguientePaso) {
            // Gerencia nunca se auto-aprueba: el gerente debe ver propuestas, elegir ganador o regresar a TI.
            if ($siguientePaso->stage === 'gerencia') {
                break;
            }

            // Verificamos si el responsable de este siguiente paso es LA MISMA PERSONA
            if ($siguientePaso->approver_empleado_id == $empleadoIdQueAprobo) {

                // ¡ES EL MISMO! Auto-aprobar (solo para pasos que no sean gerencia)
                $siguientePaso->update([
                    'status' => 'approved',
                    'comment' => 'Aprobación automática: Validado previamente por el mismo usuario en el nivel anterior.',
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $empleadoIdQueAprobo
                ]);

                // IMPORTANTE: Revocar cualquier token existente para este paso,
                // para que no le llegue un correo invitándolo a firmar algo que el sistema ya firmó.
                SolicitudTokens::where('approval_step_id', $siguientePaso->id)
                    ->whereNull('revoked_at')
                    ->whereNull('used_at')
                    ->update(['revoked_at' => now()]);

            } else {
                // Si encontramos un paso donde el responsable es DIFERENTE, rompemos el ciclo.
                break;
            }
        }
    }
}