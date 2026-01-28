<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Solicitud;
use App\Models\Empleados;
use App\Models\SolicitudPasos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TablaSolicitudes extends Component
{
    // Filtros
    public $filtroEstatus = '';

    // Variables para el Modal de Detalles (Solo lectura)
    public $modalDetallesAbierto = false;
    public $solicitudSeleccionada = null;

    // Listeners para eventos de JS
    protected $listeners = ['aprobarSolicitudConfirmed' => 'aprobar', 'rechazarSolicitudConfirmed' => 'rechazar'];

    public function render()
    {
        // 1. Obtener usuario actual para validaciones de permisos
        $user = auth()->user();
        $empleadoActual = $user ? Empleados::where('Correo', $user->email)->first() : null;
        $empleadoActualId = $empleadoActual ? $empleadoActual->EmpleadoID : null;

        // 2. Consulta optimizada
        $solicitudesRaw = Solicitud::with([
            'empleadoid', 
            'cotizaciones', 
            'pasoSupervisor', 
            'pasoGerencia', 
            'pasoAdministracion'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // 3. Procesar los datos (Transformar la lógica de Blade a PHP puro)
        $solicitudesProcesadas = $solicitudesRaw->map(function($solicitud) use ($user, $empleadoActualId) {
            
            // --- A. Formateo de Nombre ---
            $nombreEmpleado = $solicitud->empleadoid->NombreEmpleado ?? '';
            $partes = preg_split('/\s+/', trim($nombreEmpleado));
            if (count($partes) >= 3) array_splice($partes, 1, 1);
            $solicitud->nombreFormateado = Str::of(implode(' ', $partes))->title();

            // --- B. Lógica de Estatus Real ---
            $pasoSupervisor = $solicitud->pasoSupervisor;
            $pasoGerencia = $solicitud->pasoGerencia;
            $pasoAdministracion = $solicitud->pasoAdministracion;

            $estatusReal = $solicitud->Estatus ?? 'Pendiente';
            $estaRechazada = false;

            // Detectar si está rechazada en algún paso
            if (($pasoSupervisor && $pasoSupervisor->status === 'rejected') ||
                ($pasoGerencia && $pasoGerencia->status === 'rejected') ||
                ($pasoAdministracion && $pasoAdministracion->status === 'rejected')) {
                $estatusReal = 'Rechazada';
                $estaRechazada = true;
            } elseif ($solicitud->Estatus === 'Aprobado') {
                $estatusReal = 'Aprobado';
            } elseif (in_array($solicitud->Estatus, ['Pendiente', null, ''], true) || empty($solicitud->Estatus)) {
                // Lógica de cascada de aprobaciones
                if ($pasoSupervisor && $pasoSupervisor->status === 'approved') {
                    if ($pasoGerencia && $pasoGerencia->status === 'approved') {
                        if ($pasoAdministracion && $pasoAdministracion->status === 'approved') {
                            $tieneSeleccionada = $solicitud->cotizaciones && $solicitud->cotizaciones->where('Estatus', 'Seleccionada')->isNotEmpty();
                            $cotizacionesCount = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;
                            $estatusReal = $tieneSeleccionada ? 'Aprobado' : ($cotizacionesCount >= 1 ? 'Completada' : 'Pendiente Cotización TI');
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

            // --- C. Mapeo a Estatus Visual (Display) ---
            if ($estatusReal === 'Rechazada') {
                $estatusDisplay = 'Rechazada';
                $colorEstatus = 'bg-red-50 text-red-800 border border-red-200';
            } elseif ($estatusReal === 'Aprobado' || ($solicitud->cotizaciones && $solicitud->cotizaciones->where('Estatus', 'Seleccionada')->isNotEmpty())) {
                $estatusDisplay = 'Aprobada';
                $colorEstatus = 'bg-emerald-50 text-emerald-800 border border-emerald-200';
            } elseif ($estatusReal === 'Cotizaciones Enviadas') {
                $estatusDisplay = 'Cotizaciones Enviadas';
                $colorEstatus = 'bg-blue-50 text-blue-800 border border-blue-200';
            } elseif ($estatusReal === 'Completada') {
                $estatusDisplay = 'En revisión'; // Ya tiene cotizaciones pero no ha sido enviada al gerente
                $colorEstatus = 'bg-sky-50 text-sky-800 border border-sky-200';
            } elseif ($estatusReal === 'Pendiente Cotización TI') {
                $estatusDisplay = 'Pendiente';
                $colorEstatus = 'bg-amber-50 text-amber-800 border border-amber-200';
            } elseif (in_array($estatusReal, ['Pendiente Aprobación Supervisor', 'Pendiente Aprobación Gerencia', 'Pendiente Aprobación Administración'], true)) {
                $estatusDisplay = 'En revisión';
                $colorEstatus = 'bg-sky-50 text-sky-800 border border-sky-200';
            } else {
                $estatusDisplay = 'Pendiente';
                $colorEstatus = 'bg-gray-50 text-gray-700 border border-gray-200';
            }

            // Asignar variables al objeto solicitud para usar en la vista
            $solicitud->estatusReal = $estatusReal;
            $solicitud->estatusDisplay = $estatusDisplay;
            $solicitud->colorEstatus = $colorEstatus;

            // --- D. Lógica de Permisos (Botones) ---
            $todasFirmaron = ($pasoSupervisor && $pasoSupervisor->status === 'approved')
                && ($pasoGerencia && $pasoGerencia->status === 'approved')
                && ($pasoAdministracion && $pasoAdministracion->status === 'approved');
            
            $solicitud->puedeCotizar = $todasFirmaron && $user && $estatusDisplay !== 'Aprobada';
            $solicitud->puedeSubirFactura = $estatusDisplay === 'Aprobada' && $user;

            // Lógica de aprobación según usuario
            $solicitud->puedeAprobar = false;
            $solicitud->nivelAprobacion = '';

            if ($user && !$estaRechazada) {
                if ($estatusReal === 'Pendiente Aprobación Supervisor' && $pasoSupervisor && $pasoSupervisor->approver_empleado_id == $empleadoActualId) {
                    $solicitud->puedeAprobar = true;
                    $solicitud->nivelAprobacion = 'supervisor';
                } elseif ($estatusReal === 'Pendiente Aprobación Gerencia' && $solicitud->GerenciaID && $user->can('aprobar-solicitudes-gerencia')) {
                    $solicitud->puedeAprobar = true;
                    $solicitud->nivelAprobacion = 'gerencia';
                } elseif ($estatusReal === 'Pendiente Aprobación Administración' && $user->can('aprobar-solicitudes-administracion')) {
                    $solicitud->puedeAprobar = true;
                    $solicitud->nivelAprobacion = 'administracion';
                }
            }

            return $solicitud;
        });

        // 4. Filtrado
        if ($this->filtroEstatus) {
            $solicitudesProcesadas = $solicitudesProcesadas->filter(function($item) {
                return $item->estatusDisplay === $this->filtroEstatus;
            });
        }

        return view('livewire.tabla-solicitudes', [
            'todasSolicitudes' => $solicitudesProcesadas // Nota el cambio de nombre de variable
        ]);
    }

    // Acción: Ver Detalles
    public function verDetalles($id)
    {
        $this->solicitudSeleccionada = Solicitud::with(['empleadoid', 'gerenciaid', 'obraid', 'puestoid'])->find($id);
        
        // Replicamos lógica mínima para el detalle si la necesitas ahí también
        // (Opcional, depende de qué muestres en el modal)
        
        $this->modalDetallesAbierto = true;
    }

    public function cerrarModalDetalles()
    {
        $this->modalDetallesAbierto = false;
        $this->solicitudSeleccionada = null;
    }

    // Acción: Aprobar
    public function aprobar($id, $nivel, $comentario)
    {
        try {
            DB::transaction(function () use ($id, $nivel, $comentario) {
                $solicitud = Solicitud::findOrFail($id);
                $usuarioActual = auth()->user();
                $usuarioEmpleado = Empleados::where('Correo', $usuarioActual->email)->firstOrFail();

                $step = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('stage', $nivel)
                    ->firstOrFail();

                if ($step->status !== 'pending') throw new \Exception('Etapa ya resuelta.');
                
                // Validación estricta para supervisor
                if ($nivel === 'supervisor' && $step->approver_empleado_id != $usuarioEmpleado->EmpleadoID) {
                    throw new \Exception('No tienes permiso para aprobar como supervisor en esta solicitud.');
                }

                $step->update([
                    'status' => 'approved',
                    'comment' => $comentario,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $usuarioEmpleado->EmpleadoID,
                ]);

                // Verificar si todos los pasos pendientes se han completado
                // Nota: Tu lógica original actualizaba a 'Aprobada' si no había pendientes.
                // Asegúrate si requieres validación extra de Gerencia/Admin aquí.
                $pendientes = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('status', 'pending')->exists();
                
                // Si no hay pasos pendientes, y el último fue administración, podría marcarse como listo para cotizar
                // Depende de tu flujo exacto, pero mantendré tu lógica original:
                if (!$pendientes) {
                    // Opcional: Cambiar estatus a algo intermedio si falta cotización
                    // $solicitud->update(['Estatus' => 'Aprobado']); 
                }
            });

            $this->dispatch('swal:success', ['message' => 'Solicitud aprobada correctamente']);

        } catch (\Exception $e) {
            $this->dispatch('swal:error', ['message' => $e->getMessage()]);
        }
    }

    // Acción: Rechazar
    public function rechazar($id, $nivel, $comentario)
    {
        try {
            DB::transaction(function () use ($id, $nivel, $comentario) {
                $solicitud = Solicitud::findOrFail($id);
                $usuarioActual = auth()->user();
                $usuarioEmpleado = Empleados::where('Correo', $usuarioActual->email)->firstOrFail();

                $step = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('stage', $nivel)->firstOrFail();

                $step->update([
                    'status' => 'rejected',
                    'comment' => $comentario,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $usuarioEmpleado->EmpleadoID,
                ]);

                $solicitud->update(['Estatus' => 'Rechazada']);
            });

            $this->dispatch('swal:success', ['message' => 'Solicitud rechazada correctamente']);

        } catch (\Exception $e) {
            $this->dispatch('swal:error', ['message' => $e->getMessage()]);
        }
    }
}