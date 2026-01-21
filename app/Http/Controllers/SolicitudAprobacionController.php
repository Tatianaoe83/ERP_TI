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
        $tokenRow = SolicitudTokens::query()
            ->active()
            ->where('token', $token)
            ->with([
                'approvalStep.approverEmpleado',
                'approvalStep.solicitud.empleadoid',
                'approvalStep.solicitud.obraid',
                'approvalStep.solicitud.gerenciaid',
                'approvalStep.solicitud.puestoid',
            ])
            ->firstOrFail();

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
}
