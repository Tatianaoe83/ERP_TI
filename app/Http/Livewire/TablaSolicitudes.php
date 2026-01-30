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
    public $search = ''; // <--- NUEVO: Variable para el buscador

    // Variables para el Modal de Detalles
    public $modalDetallesAbierto = false;
    public $solicitudSeleccionada = null;

    protected $listeners = ['aprobarSolicitudConfirmed' => 'aprobar', 'rechazarSolicitudConfirmed' => 'rechazar'];

    public function render()
    {
        $user = auth()->user();
        $empleadoActual = $user ? Empleados::where('Correo', $user->email)->first() : null;
        $empleadoActualId = $empleadoActual ? $empleadoActual->EmpleadoID : null;

        // Consulta base
        $solicitudesRaw = Solicitud::with([
            'empleadoid', 
            'cotizaciones', 
            'pasoSupervisor', 
            'pasoGerencia', 
            'pasoAdministracion'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // Procesamiento de datos
        $solicitudesProcesadas = $solicitudesRaw->map(function($solicitud) use ($user, $empleadoActualId) {
            
            // Formateo de Nombre
            $nombreEmpleado = $solicitud->empleadoid->NombreEmpleado ?? '';
            $partes = preg_split('/\s+/', trim($nombreEmpleado));
            if (count($partes) >= 3) array_splice($partes, 1, 1);
            $solicitud->nombreFormateado = Str::of(implode(' ', $partes))->title();

            // Lógica de Estatus Real
            $pasoSupervisor = $solicitud->pasoSupervisor;
            $pasoGerencia = $solicitud->pasoGerencia;
            $pasoAdministracion = $solicitud->pasoAdministracion;

            $estatusReal = $solicitud->Estatus ?? 'Pendiente';
            $estaRechazada = false;

            if (($pasoSupervisor && $pasoSupervisor->status === 'rejected') ||
                ($pasoGerencia && $pasoGerencia->status === 'rejected') ||
                ($pasoAdministracion && $pasoAdministracion->status === 'rejected')) {
                $estatusReal = 'Rechazada';
                $estaRechazada = true;
            } elseif ($solicitud->Estatus === 'Aprobado') {
                $estatusReal = 'Aprobado';
            } elseif (in_array($solicitud->Estatus, ['Pendiente', null, ''], true) || empty($solicitud->Estatus)) {
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

            // Mapeo a Estatus Visual
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
                $estatusDisplay = 'En revisión';
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

            $solicitud->estatusReal = $estatusReal;
            $solicitud->estatusDisplay = $estatusDisplay;
            $solicitud->colorEstatus = $colorEstatus;

            // Permisos
            $todasFirmaron = ($pasoSupervisor && $pasoSupervisor->status === 'approved')
                && ($pasoGerencia && $pasoGerencia->status === 'approved')
                && ($pasoAdministracion && $pasoAdministracion->status === 'approved');
            
            $solicitud->puedeCotizar = $todasFirmaron && $user && $estatusDisplay !== 'Aprobada';
            $solicitud->puedeSubirFactura = $estatusDisplay === 'Aprobada' && $user;

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

        // --- FILTROS ---

        // 1. Filtro por Estatus (Dropdown)
        if ($this->filtroEstatus) {
            $solicitudesProcesadas = $solicitudesProcesadas->filter(function($item) {
                return $item->estatusDisplay === $this->filtroEstatus;
            });
        }

        // 2. Filtro por Búsqueda (Texto) <--- NUEVO
        if ($this->search) {
            $term = strtolower($this->search);
            $solicitudesProcesadas = $solicitudesProcesadas->filter(function($item) use ($term) {
                return str_contains(strtolower($item->SolicitudID), $term) || 
                       str_contains(strtolower($item->nombreFormateado), $term) ||
                       str_contains(strtolower($item->Motivo), $term);
            });
        }

        return view('livewire.tabla-solicitudes', [
            'todasSolicitudes' => $solicitudesProcesadas
        ]);
    }

    public function aprobar($id, $nivel, $comentario)
    {
        try {
            DB::transaction(function () use ($id, $nivel, $comentario) {
                $solicitud = Solicitud::findOrFail($id);
                $usuarioActual = auth()->user();
                $usuarioEmpleado = Empleados::where('Correo', $usuarioActual->email)->firstOrFail();

                $step = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                    ->where('stage', $nivel)->firstOrFail();

                if ($step->status !== 'pending') throw new \Exception('Etapa ya resuelta.');
                
                if ($nivel === 'supervisor' && $step->approver_empleado_id != $usuarioEmpleado->EmpleadoID) {
                    throw new \Exception('No tienes permiso para aprobar.');
                }

                $step->update([
                    'status' => 'approved',
                    'comment' => $comentario,
                    'decided_at' => now(),
                    'decided_by_empleado_id' => $usuarioEmpleado->EmpleadoID,
                ]);
            });
            $this->dispatch('swal:success', ['message' => 'Solicitud aprobada correctamente']);
        } catch (\Exception $e) {
            $this->dispatch('swal:error', ['message' => $e->getMessage()]);
        }
    }

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