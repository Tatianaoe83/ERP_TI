<?php

namespace App\Http\Controllers;

use App\Models\SolicitudPasos;
use App\Models\SolicitudTokens;
use App\Models\Solicitud;
use App\Models\Empleados;
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
        ]);

        $step = $tokenRow->approvalStep;
        $solicitud = $step->solicitud;

        $prevNotApproved = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
            ->where('step_order', '<', $step->step_order)
            ->where('status', '!=', 'approved')
            ->exists();

        $canDecide = ! $prevNotApproved && $step->status === 'pending';

        return view('solicitudes.revision-publica', [
            'solicitud' => $solicitud,
            'step'      => $step,
            'tokenRow'  => $tokenRow,
            'canDecide' => $canDecide,
            'waitingFor' => $prevNotApproved ? $this->waitingLabel($solicitud, $step) : null,
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
            default => 'Esperando aprobación previa',
        };
    }

    public function decide(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'comment'  => 'nullable|string|max:5000',
        ]);

        try {
            DB::transaction(function () use ($data, $token) {

                $tokenRow = SolicitudTokens::query()
                    ->active()
                    ->where('token', $token)
                    ->lockForUpdate()
                    ->with(['approvalStep', 'approvalStep.solicitud'])
                    ->firstOrFail();

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

                $step->update([
                    'status' => $data['decision'],
                    'comment' => $data['comment'] ?? null,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $step->approver_empleado_id,
                ]);

                $tokenRow->update([
                    'used_at' => now(),
                ]);

                if ($data['decision'] === 'rejected') {
                    $solicitud->update(['Estatus' => 'Rechazada']);
                    return;
                }

                $pending = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('status', 'pending')
                    ->exists();

                $solicitud->update([
                    'Estatus' => $pending ? 'En revisión' : 'Aprobada',
                ]);
            });

            return redirect()
                ->route('solicitudes.public.decide', ['token' => $token])
                ->with('swal_success', 'Decisión registrada correctamente.');
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('solicitudes.public.decide', ['token' => $token])
                ->with('swal_error', 'El enlace no es válido, ya expiró o ya fue usado.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('solicitudes.public.decide', ['token' => $token])
                ->with('swal_error', $e->getMessage() ?: 'Ocurrió un error al registrar la decisión.');
        }
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

                $step->update([
                    'status' => 'approved',
                    'comment' => $data['comentario'] ?? null,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $usuarioEmpleado->EmpleadoID,
                ]);

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

        try {
            $nuevoToken = DB::transaction(function () use ($data, $token) {
                $tokenRow = SolicitudTokens::query()
                    ->where('token', $token)
                    ->whereNull('used_at') // Solo permitir transferir si el token no ha sido usado
                    ->lockForUpdate()
                    ->with(['approvalStep', 'approvalStep.solicitud'])
                    ->firstOrFail();

                $step = $tokenRow->approvalStep;
                $solicitud = $step->solicitud;

                if ($step->status !== 'pending') {
                    throw new \RuntimeException('Esta etapa ya fue resuelta.');
                }

                // Verificar que el nuevo aprobador sea diferente al actual
                if ($step->approver_empleado_id == $data['nuevo_aprobador_id']) {
                    throw new \RuntimeException('Debe seleccionar un aprobador diferente al actual.');
                }

                // Verificar que el nuevo aprobador existe
                $nuevoAprobador = Empleados::findOrFail($data['nuevo_aprobador_id']);

                // Actualizar el paso con el nuevo aprobador
                $step->update([
                    'approver_empleado_id' => $data['nuevo_aprobador_id'],
                    'comment' => $data['comentario'] ?? null,
                ]);

                // Invalidar el token actual
                $tokenRow->update([
                    'used_at' => now(),
                    'revoked_at' => now(),
                ]);

                // Crear nuevo token para el nuevo aprobador
                $nuevoTokenRow = SolicitudTokens::create([
                    'approval_step_id' => $step->id,
                    'token' => \Illuminate\Support\Str::uuid(),
                    'expires_at' => now()->addDays(7),
                ]);

                return $nuevoTokenRow->token;
            });

            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'La aprobación ha sido transferida correctamente.',
                ]);
            }

            // Si no, redirigir (aunque el token actual ya no funcionará)
            return redirect()
                ->route('solicitudes.public.show', ['token' => $nuevoToken])
                ->with('swal_success', 'La aprobación ha sido transferida correctamente. El nuevo aprobador recibirá un enlace para revisar la solicitud.');
        } catch (ModelNotFoundException $e) {
            $message = 'El enlace no es válido, ya expiró o ya fue usado.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 404);
            }
            return redirect()
                ->route('solicitudes.public.show', ['token' => $token])
                ->with('swal_error', $message);
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Ocurrió un error al transferir la aprobación.';
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

        $empleados = Empleados::query()
            ->where('Estado', true)
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('EmpleadoID', '!=', $excludeId);
            })
            ->when($query, function ($q) use ($query) {
                $q->where('NombreEmpleado', 'like', '%' . $query . '%')
                  ->orWhere('Correo', 'like', '%' . $query . '%');
            })
            ->select('EmpleadoID', 'NombreEmpleado', 'Correo')
            ->orderBy('NombreEmpleado')
            ->limit(20)
            ->get();

        return response()->json($empleados);
    }
}
