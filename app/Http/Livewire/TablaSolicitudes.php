<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Solicitud;
use App\Models\Cotizacion;
use App\Models\Empleados;
use App\Models\SolicitudPasos;
use App\Models\SolicitudTokens;
use App\Models\DepartamentoRequerimientos;
use App\Models\SolicitudActivo;
use App\Models\SolicitudActivoCheckList;
use App\Models\Facturas;
use App\Models\Tickets;
use App\Services\SolicitudAprobacionEmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TablaSolicitudes extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $filtroEstatus = '';
    public string $search        = '';
    public int    $perPage       = 10;

    protected $paginationTheme = 'tailwind';

    public bool  $modalAsignacionAbierto = false;
    public bool  $modalEsSoloLectura     = false;
    public ?int  $asignacionSolicitudId  = null;

    public array $propuestasAsignacion = [];
    public array $facturas             = [];
    public array $facturaXml           = [];
    public array $xmlParseado          = [];

    public array $usuarioSearch      = [];
    public array $usuarioOptions     = [];
    public array $usuarioSearchLock  = [];

    public bool   $modalCancelacionAbierto = false;
    public ?int   $solicitudCancelarId     = null;
    public string $motivoCancelacion       = '';

    public bool $modalInfoAbierto = false;
    public $infoSolicitud         = null;

    public bool $confirmarCierreModalAbierto = false;

    public bool   $modalTicketInstalacionAbierto = false;
    public ?int   $ticketInstalacionPIndex       = null;
    public ?int   $ticketInstalacionUIndex       = null;
    public ?int   $ticketInstalacionEmpleadoId   = null;
    public string $ticketInstalacionResponsable  = '';
    public array  $ticketInstalacionOptions      = [];

    public array  $insumosDisponibles  = [];
    public string $insumoSearchQuery   = '';
    public array  $insumoSearchResults = [];

    protected $listeners = [
        'aprobarSolicitudConfirmed'  => 'aprobar',
        'rechazarSolicitudConfirmed' => 'rechazar',
        'forzarCloseAsignacion'      => 'forzarCloseAsignacion',
    ];

    private const VALID_STAGES = ['supervisor', 'gerencia', 'administracion'];

    private const STAGE_PERMISSIONS = [
        'gerencia'       => 'aprobar-solicitudes-gerencia',
        'administracion' => 'aprobar-solicitudes-administracion',
    ];

    // Etiquetas para los correos, idénticas a las del controlador
    private const STAGE_LABELS = [
        'supervisor'     => 'Vo.bo de supervisor',
        'gerencia'       => 'Gerente: ve propuestas, elige ganador o regresa a TI para cotizar',
        'administracion' => 'Administración: ve ganadores y aprueba la solicitud',
    ];

    private ?string $serialColumn            = null;
    private array   $checklistTemplatesCache = [];

    public function mount(): void
    {
        $this->serialColumn = $this->detectSerialColumn();
    }

    // =========================================================================
    // FILE UPLOAD WATCHERS
    // =========================================================================

    public function updatedFacturaXml($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        [$pIndex, $uIndex] = [(int)$parts[0], (int)$parts[1]];
        $file = $this->facturaXml[$pIndex][$uIndex] ?? null;
        if (!$file) return;

        try {
            $this->xmlParseado[$pIndex][$uIndex] = $this->parsearCfdi($file->getRealPath());
        } catch (\Throwable $e) {
            $this->xmlParseado[$pIndex][$uIndex] = ['error' => $e->getMessage()];
        }

        // 🔥 Guardar archivo al storage
        if ($this->asignacionSolicitudId) {
            try {
                $solicitudId = $this->asignacionSolicitudId;
                $dir = "solicitudes/{$solicitudId}/temp";
                $filename = "factura_{$pIndex}_{$uIndex}.xml";
                $ruta = $file->storeAs($dir, $filename, 'public');
                
                // Actualizar ruta en propuestasAsignacion para todas las unidades del proveedor
                $proveedor = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
                if ($proveedor) {
                    foreach ($this->propuestasAsignacion as $pi => $prop) {
                        if (($prop['proveedor'] ?? '') !== $proveedor) continue;
                        foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                            $this->propuestasAsignacion[$pi]['unidades'][$ui]['factura_xml_path'] = $ruta;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Error guardando XML en storage', ['error' => $e->getMessage()]);
            }
        }

        $proveedor = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
        if (!$proveedor) return;

        foreach ($this->propuestasAsignacion as $pi => $prop) {
            if (($prop['proveedor'] ?? '') !== $proveedor) continue;
            foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                if ($pi === $pIndex && $ui === $uIndex) continue;
                $this->facturaXml[$pi][$ui]  = $file;
                $this->xmlParseado[$pi][$ui] = $this->xmlParseado[$pIndex][$uIndex];
            }
        }

        $this->dispatchBrowserEvent('swal:info', ['message' => "XML aplicado a todas las unidades del proveedor: {$proveedor}"]);
    }

    public function updatedFacturas($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        [$pIndexOrigen, $uIndexOrigen] = [(int)$parts[0], (int)$parts[1]];
        $facturaSubida = $this->facturas[$pIndexOrigen][$uIndexOrigen] ?? null;
        if (!$facturaSubida) return;

        // 🔥 Guardar archivo PDF al storage
        if ($this->asignacionSolicitudId) {
            try {
                $solicitudId = $this->asignacionSolicitudId;
                $dir = "solicitudes/{$solicitudId}/temp";
                $filename = "factura_{$pIndexOrigen}_{$uIndexOrigen}.pdf";
                $ruta = $facturaSubida->storeAs($dir, $filename, 'public');
                
                // Actualizar ruta en propuestasAsignacion para todas las unidades del proveedor
                $proveedor = $this->propuestasAsignacion[$pIndexOrigen]['proveedor'] ?? null;
                if ($proveedor) {
                    foreach ($this->propuestasAsignacion as $pIndex => $prop) {
                        if (($prop['proveedor'] ?? '') !== $proveedor) continue;
                        foreach (array_keys($prop['unidades'] ?? []) as $uIndex) {
                            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['factura_pdf_path'] = $ruta;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Error guardando PDF en storage', ['error' => $e->getMessage()]);
            }
        }

        $proveedor = $this->propuestasAsignacion[$pIndexOrigen]['proveedor'] ?? null;
        if (!$proveedor) return;

        foreach ($this->propuestasAsignacion as $pIndex => $prop) {
            if (($prop['proveedor'] ?? '') !== $proveedor) continue;
            foreach (array_keys($prop['unidades'] ?? []) as $uIndex) {
                $this->facturas[$pIndex][$uIndex] = $facturaSubida;
            }
        }

        $this->dispatchBrowserEvent('swal:info', ['message' => "Factura aplicada a todas las unidades de: {$proveedor}"]);
    }

    public function updatingFiltroEstatus(): void { $this->resetPage(); }
    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingPerPage(): void       { $this->resetPage(); }

    public function updated($name, $value): void
    {
        $name = (string)$name;

        if (str_starts_with($name, 'usuarioSearch.')) {
            $parts = explode('.', $name);
            if (count($parts) === 3) {
                [$pIndex, $uIndex] = [(int)$parts[1], (int)$parts[2]];
                if ($this->isUsuarioSearchLocked($pIndex, $uIndex)) {
                    $this->unlockUsuarioSearch($pIndex, $uIndex);
                    return;
                }
                $this->handleUsuarioSearchUpdated($pIndex, $uIndex, (string)$value);
            }
            return;
        }

        if (str_starts_with($name, 'ticketInstalacionResponsable')) {
            $this->buscarResponsableTicket((string)$value);
        }
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function render()
    {
        $user             = auth()->user();
        $empleadoActual   = $user ? Empleados::query()->where('Correo', $user->email)->first() : null;
        $empleadoActualId = $empleadoActual ? (int)$empleadoActual->EmpleadoID : null;

        $query = Solicitud::with(['empleadoid','cotizaciones','pasoSupervisor','pasoGerencia','pasoAdministracion']);

        if ($this->search) {
            $term = trim((string)$this->search);
            $query->where(fn($q) => $q
                ->where('SolicitudID', 'like', "%{$term}%")
                ->orWhere('Motivo', 'like', "%{$term}%")
                ->orWhereHas('empleadoid', fn($s) => $s->where('NombreEmpleado', 'like', "%{$term}%")));
        }

        $raw = $query->orderBy('created_at', 'desc')->get();

        $procesadas = $raw->map(fn($s) => $this->hydrateSolicitudRow($s, $user, $empleadoActualId));

        if ($this->filtroEstatus) {
            $procesadas = $procesadas->filter(fn($s) => $s->estatusDisplay === $this->filtroEstatus)->values();
        }

        $page      = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $items     = $procesadas->slice(($page - 1) * $this->perPage, $this->perPage)->values();
        $paginador = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $procesadas->count(), $this->perPage, $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.tabla-solicitudes', ['todasSolicitudes' => $paginador]);
    }

    // =========================================================================
    // ROW HYDRATION
    // =========================================================================

    private function hydrateSolicitudRow($solicitud, $user, ?int $empleadoActualId)
    {
        $solicitud->nombreFormateado = $this->formatNombreEmpleado((string)($solicitud->empleadoid->NombreEmpleado ?? ''));

        [$estatusReal, $estaRechazada]   = $this->resolveEstatusReal($solicitud);
        [$estatusDisplay, $colorEstatus] = $this->resolveEstatusDisplay($solicitud, $estatusReal);

        $solicitud->estatusReal    = $estatusReal;
        $solicitud->estatusDisplay = $estatusDisplay;
        $solicitud->colorEstatus   = $colorEstatus;
        $solicitud->recotizarPropuestasText = '';

        if ($estatusReal === 'Re-cotizar' && $solicitud->pasoGerencia?->comment) {
            $comment = $solicitud->pasoGerencia->comment;
            if (str_starts_with($comment, 'RECOTIZAR|')) {
                $parts = explode('|', $comment, 3);
                $nums  = isset($parts[1]) ? array_filter(array_map('trim', explode(',', $parts[1]))) : [];
                $solicitud->recotizarPropuestasText = $nums ? ' (Prop. ' . implode(', ', $nums) . ')' : '';
            }
        }

        $todasFirmaron     = $this->allStepsApproved($solicitud);
        $supAprobado       = $solicitud->pasoSupervisor && $solicitud->pasoSupervisor->status === 'approved';
        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);
        $todosGanadores    = $solicitud->todosProductosTienenGanador();
        $estaCancelada     = ($estatusReal === 'Cancelada');

        $solicitud->puedeCotizar      = (bool)(!$estaCancelada && $supAprobado && $user && !$estaRechazada && $estatusDisplay !== 'Aprobada' && !$todosGanadores);
        $solicitud->puedeSubirFactura = (bool)(!$estaCancelada && $todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAsignar      = (bool)(!$estaCancelada && $todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAprobar      = false;
        $solicitud->nivelAprobacion   = '';

        [$facturasSubidas, $totalNecesarias] = $this->contarFacturas($solicitud);
        $solicitud->facturasSubidas          = $facturasSubidas;
        $solicitud->totalFacturasNecesarias  = $totalNecesarias;

        if ($user && !$estaRechazada && !$estaCancelada) {
            $ps = $solicitud->pasoSupervisor;
            $pg = $solicitud->pasoGerencia;
            $pa = $solicitud->pasoAdministracion;

            if ($estatusReal === 'Pendiente Aprobación Supervisor' && $ps && (int)$ps->approver_empleado_id === (int)$empleadoActualId) {
                $solicitud->puedeAprobar = true; $solicitud->nivelAprobacion = 'supervisor';
            } elseif ($estatusReal === 'Pendiente Aprobación Gerencia' && $solicitud->GerenciaID && $user->can(self::STAGE_PERMISSIONS['gerencia'])) {
                $solicitud->puedeAprobar = true; $solicitud->nivelAprobacion = 'gerencia';
            } elseif ($estatusReal === 'Pendiente Aprobación Administración' && $user->can(self::STAGE_PERMISSIONS['administracion'])) {
                $solicitud->puedeAprobar = true; $solicitud->nivelAprobacion = 'administracion';
            }
        }

        return $solicitud;
    }

    private function formatNombreEmpleado(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre));
        if (is_array($partes) && count($partes) >= 3) array_splice($partes, 1, 1);
        return (string)Str::of(implode(' ', $partes ?? []))->title();
    }

    private function resolveEstatusReal($solicitud): array
    {
        $ps = $solicitud->pasoSupervisor;
        $pg = $solicitud->pasoGerencia;
        $pa = $solicitud->pasoAdministracion;

        if (in_array($solicitud->Estatus, ['Cancelada','Cerrada'], true)) return ['Cancelada', false];
        if (($ps && $ps->status === 'rejected') || ($pg && $pg->status === 'rejected') || ($pa && $pa->status === 'rejected')) return ['Rechazada', true];
        if (in_array($solicitud->Estatus, ['Aprobado','Aprobada'], true)) return ['Aprobado', false];
        if ($solicitud->Estatus === 'Re-cotizar') return ['Re-cotizar', false];

        $estatus = $solicitud->Estatus ?? 'Pendiente';

        if (in_array($solicitud->Estatus, ['Pendiente','En revisión',null,''], true) || empty($solicitud->Estatus)) {
            if ($ps && $ps->status === 'approved') {
                if ($pg && $pg->status === 'approved') {
                    if ($pa && $pa->status === 'approved') {
                        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);
                        $cotCount          = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;
                        if ($tieneSeleccionada && $this->todasUnidadesFinalizadas($solicitud)) return ['Listo', false];
                        $estatus = $tieneSeleccionada ? 'Aprobado' : ($cotCount >= 1 ? 'Completada' : 'Pendiente Cotización TI');
                    } else { $estatus = 'Pendiente Aprobación Administración'; }
                } else { $estatus = 'Pendiente Aprobación Gerencia'; }
            } else { $estatus = 'Pendiente Aprobación Supervisor'; }
        }

        return [$estatus, false];
    }

    private function todasUnidadesFinalizadas($solicitud): bool
    {
        $seleccionadas = $solicitud->cotizaciones ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada') : collect();
        if ($seleccionadas->isEmpty()) return false;

        foreach ($seleccionadas as $cot) {
            $qty = max(1, (int)($cot->Cantidad ?? 1));
            for ($i = 1; $i <= $qty; $i++) {
                $activo = SolicitudActivo::query()
                    ->where('SolicitudID', $solicitud->SolicitudID)
                    ->where('CotizacionID', $cot->CotizacionID)
                    ->where('UnidadIndex', $i)
                    ->first();
                if (!$activo || empty($activo->fecha_fin_configuracion)) return false;
            }
        }
        return true;
    }

    private function resolveEstatusDisplay($solicitud, string $estatusReal): array
    {
        return match($estatusReal) {
            'Cancelada'  => ['Cancelada',   'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-700'],
            'Rechazada'  => ['Rechazada',   'bg-red-50 text-red-800 border border-red-200'],
            'Aprobado'   => ['Aprobada',    'bg-emerald-50 text-emerald-800 border border-emerald-200'],
            'Cotizaciones Enviadas' => ['Cotizaciones Enviadas', 'bg-blue-50 text-blue-800 border border-blue-200'],
            'Re-cotizar' => ['Re-cotizar',  'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700'],
            'Completada' => ['En revisión', 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700'],
            'Pendiente Cotización TI' => ['Pendiente', 'bg-amber-50 text-amber-800 border border-amber-200'],
            'Listo'      => ['Listo',       'bg-teal-50 text-teal-800 border border-teal-300 dark:bg-teal-900/30 dark:text-teal-200 dark:border-teal-700'],
            'Pendiente Aprobación Supervisor','Pendiente Aprobación Gerencia','Pendiente Aprobación Administración'
                         => ['En revisión', 'bg-white text-purple-700 border border-purple-200 dark:text-purple-700 dark:border-purple-700'],
            default      => ['Pendiente',   'bg-gray-50 text-gray-700 border border-gray-200'],
        };
    }

    private function hasSelectedCotizacion($solicitud): bool
    {
        return (bool)($solicitud->cotizaciones?->where('Estatus', 'Seleccionada')->isNotEmpty());
    }

    private function allStepsApproved($solicitud): bool
    {
        $ps = $solicitud->pasoSupervisor;
        $pg = $solicitud->pasoGerencia;
        $pa = $solicitud->pasoAdministracion;
        return ($ps && $ps->status === 'approved')
            && ($pg && $pg->status === 'approved')
            && ($pa && $pa->status === 'approved');
    }

    private function contarFacturas($solicitud): array
    {
        if (!$solicitud->cotizaciones || $solicitud->cotizaciones->isEmpty()) return [0, 0];
        $sel = $solicitud->cotizaciones->where('Estatus', 'Seleccionada');
        if ($sel->isEmpty()) return [0, 0];

        $proveedoresUnicos = $sel->pluck('Proveedor')->filter()->unique();
        $totalNecesarias   = $proveedoresUnicos->count();
        if ($totalNecesarias === 0) return [0, 0];

        $cotIds = $sel->pluck('CotizacionID')->filter()->unique()->toArray();
        if (empty($cotIds)) return [0, $totalNecesarias];

        $activos = SolicitudActivo::query()
            ->whereIn('CotizacionID', $cotIds)
            ->whereNotNull('FacturaPath')->where('FacturaPath', '!=', '')
            ->select('CotizacionID')->distinct()->get();

        if ($activos->isEmpty()) return [0, $totalNecesarias];

        $cotsConFactura  = $activos->pluck('CotizacionID')->toArray();
        $provsConFactura = $sel->whereIn('CotizacionID', $cotsConFactura)->pluck('Proveedor')->filter()->unique()->count();

        return [$provsConFactura, $totalNecesarias];
    }

    // =========================================================================
    // APROBAR / RECHAZAR
    // =========================================================================

    public function aprobar($id, $nivel, $comentario)
    {
        try {
            $this->decidirPaso((int)$id, (string)$nivel, (string)($comentario ?? ''), 'approved');
            $this->dispatchBrowserEvent('swal:success', ['message' => 'Solicitud aprobada correctamente']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => $e->getMessage()]);
        }
    }

    public function rechazar($id, $nivel, $comentario)
    {
        try {
            $this->decidirPaso((int)$id, (string)$nivel, (string)($comentario ?? ''), 'rejected');
            $this->dispatchBrowserEvent('swal:success', ['message' => 'Solicitud rechazada correctamente']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Decide un paso de aprobación y, si corresponde, envía correo al siguiente aprobador.
     * Replica la misma lógica de SolicitudAprobacionController::decide() para que los correos
     * se disparen tanto desde la vista pública (token) como desde el panel interno (Livewire).
     */
    private function decidirPaso(int $solicitudId, string $nivel, string $comentario, string $decision): void
    {
        $nivel = trim(strtolower($nivel));
        if (!in_array($nivel, self::VALID_STAGES, true)) throw new \Exception('Etapa inválida.');

        // Se rellena dentro de la transacción y se usa fuera para disparar el correo
        $emailRevisionData = null;

        DB::transaction(function () use ($solicitudId, $nivel, $comentario, $decision, &$emailRevisionData) {
            $solicitud = Solicitud::findOrFail($solicitudId);
            $usuario   = auth()->user() ?? throw new \Exception('Sesión inválida.');
            $empleado  = Empleados::query()->where('Correo', $usuario->email)->firstOrFail();

            $step = SolicitudPasos::query()
                ->where('solicitud_id', $solicitud->SolicitudID)
                ->where('stage', $nivel)
                ->lockForUpdate()
                ->firstOrFail();

            if ($step->status !== 'pending') throw new \Exception('Etapa ya resuelta.');

            $this->authorizeDecision($usuario, $empleado, $solicitud, $step, $nivel);

            // 1. Actualizar el paso actual
            $step->update([
                'status'                 => $decision,
                'comment'                => $comentario,
                'decided_at'             => now(),
                'decided_by_empleado_id' => (int)$empleado->EmpleadoID,
            ]);

            // Revocar cualquier token activo de este paso para evitar correos fantasma
            SolicitudTokens::where('approval_step_id', $step->id)
                ->whereNull('revoked_at')
                ->whereNull('used_at')
                ->update(['revoked_at' => now()]);

            // 2. Si se rechaza, cerrar todo y salir
            if ($decision === 'rejected') {
                $solicitud->update(['Estatus' => 'Rechazada']);
                return;
            }

            // 3. Auto-aprobación en cascada (misma persona en pasos siguientes)
            $this->procesarAutoAprobacionEnCascada($solicitud->SolicitudID, (int)$empleado->EmpleadoID);

            // 4. Recalcular pasos pendientes tras la cascada
            $pasosPendientes = SolicitudPasos::where('solicitud_id', $solicitud->SolicitudID)
                ->where('status', 'pending')
                ->orderBy('step_order')
                ->get();

            $solicitud->update([
                'Estatus' => $pasosPendientes->isNotEmpty() ? 'En revisión' : 'Aprobada',
            ]);

            // 5. Preparar correo para el siguiente aprobador REAL
            if ($pasosPendientes->isNotEmpty()) {
                $nextStep = $pasosPendientes->first();
                $nextStep->load('approverEmpleado');

                // No enviar correo si el siguiente paso es gerencia o administración
                // Gerencia: se enviará cuando TI cargue y envíe las cotizaciones
                // Administración: se enviará cuando gerencia confirme ganadores
                if ($nextStep->stage === 'gerencia' || $nextStep->stage === 'administracion') {
                    // Solo crear token para cuando sea el momento de notificar
                    $existeToken = SolicitudTokens::where('approval_step_id', $nextStep->id)
                        ->whereNull('used_at')->whereNull('revoked_at')
                        ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->exists();

                    if (!$existeToken) {
                        SolicitudTokens::create([
                            'approval_step_id' => $nextStep->id,
                            'token'            => Str::uuid(),
                            'expires_at'       => now()->addDays(7),
                        ]);
                    }
                } else {
                    // Para supervisor y otros pasos: crear/reutilizar token y enviar correo
                    $nextTokenRow = SolicitudTokens::where('approval_step_id', $nextStep->id)
                        ->whereNull('used_at')->whereNull('revoked_at')
                        ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->first();

                    if (!$nextTokenRow) {
                        $nextTokenRow = SolicitudTokens::create([
                            'approval_step_id' => $nextStep->id,
                            'token'            => Str::uuid(),
                            'expires_at'       => now()->addDays(7),
                        ]);
                    }

                    if ($nextTokenRow && $nextStep->approverEmpleado) {
                        $emailRevisionData = [
                            'aprobador'  => $nextStep->approverEmpleado,
                            'solicitud'  => $solicitud->load('empleadoid'),
                            'token'      => $nextTokenRow->token,
                            'stageLabel' => self::STAGE_LABELS[$nextStep->stage] ?? $nextStep->stage,
                        ];
                    }
                }
            }
        });

        // 6. Enviar correo FUERA de la transacción para no bloquearla en caso de fallo SMTP
        if ($emailRevisionData) {
            app(SolicitudAprobacionEmailService::class)->enviarRevisionPendiente(
                $emailRevisionData['aprobador'],
                $emailRevisionData['solicitud'],
                $emailRevisionData['token'],
                $emailRevisionData['stageLabel']
            );
        }
    }

    private function authorizeDecision($user, Empleados $empleado, Solicitud $solicitud, SolicitudPasos $step, string $nivel): void
    {
        $approverId = (int)($step->approver_empleado_id ?? 0);
        if ($approverId > 0 && $approverId !== (int)$empleado->getAttribute('EmpleadoID'))
            throw new \Exception('No tienes permiso para resolver esta etapa.');
        if ($approverId > 0) return;
        if ($nivel === 'supervisor') throw new \Exception('No tienes permiso para resolver esta etapa.');
        $perm = self::STAGE_PERMISSIONS[$nivel] ?? null;
        if ($perm && !$user->can($perm)) throw new \Exception('No tienes permiso para resolver esta etapa.');
        if ($nivel === 'gerencia' && empty($solicitud->GerenciaID)) throw new \Exception('Solicitud sin gerencia asignada.');
    }

    /**
     * Lógica de efecto dominó (Cascada): idéntica a la del controlador.
     * Si el mismo empleado aparece en pasos consecutivos, se auto-aprueba.
     * Gerencia NUNCA se auto-aprueba porque el gerente debe elegir ganador.
     */
    private function procesarAutoAprobacionEnCascada(int $solicitudId, int $empleadoIdQueAprobo): void
    {
        $pasosPendientes = SolicitudPasos::where('solicitud_id', $solicitudId)
            ->where('status', 'pending')
            ->orderBy('step_order', 'asc')
            ->get();

        foreach ($pasosPendientes as $siguientePaso) {
            if ($siguientePaso->stage === 'gerencia') break;

            if ($siguientePaso->approver_empleado_id == $empleadoIdQueAprobo) {
                $siguientePaso->update([
                    'status'                 => 'approved',
                    'comment'                => 'Aprobación automática: Validado previamente por el mismo usuario en el nivel anterior.',
                    'decided_at'             => now(),
                    'decided_by_empleado_id' => $empleadoIdQueAprobo,
                ]);

                // Revocar tokens de este paso para no mandar correos innecesarios
                SolicitudTokens::where('approval_step_id', $siguientePaso->id)
                    ->whereNull('revoked_at')
                    ->whereNull('used_at')
                    ->update(['revoked_at' => now()]);
            } else {
                break;
            }
        }
    }

    // =========================================================================
    // CANCELACIÓN
    // =========================================================================

    public function abrirModalCancelacion(int $solicitudId): void
    {
        $this->resetErrorBag();
        $this->motivoCancelacion       = '';
        $this->solicitudCancelarId     = $solicitudId;
        $this->modalCancelacionAbierto = true;
    }

    public function cerrarModalCancelacion(): void { $this->resetCancelacionState(); }

    public function confirmarCancelacion(): void
    {
        $this->validate(
            ['motivoCancelacion' => 'required|string|min:10|max:1000'],
            [
                'motivoCancelacion.required' => 'El motivo de cancelación es obligatorio.',
                'motivoCancelacion.min'      => 'El motivo debe tener al menos 10 caracteres.',
                'motivoCancelacion.max'      => 'El motivo no puede exceder 1000 caracteres.',
            ]
        );

        if (!$this->solicitudCancelarId) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']);
            return;
        }

        try {
            $usuario = auth()->user() ?? throw new \Exception('Sesión inválida.');

            DB::transaction(function () use ($usuario) {
                Solicitud::findOrFail($this->solicitudCancelarId)->update([
                    'Estatus'            => 'Cancelada',
                    'motivo_cancelacion' => trim($this->motivoCancelacion),
                    'fecha_cancelacion'  => now(),
                    'cancelado_por'      => $usuario->id,
                ]);
            });

            $this->dispatchBrowserEvent('swal:success', ['message' => "Solicitud #{$this->solicitudCancelarId} cancelada correctamente."]);
            $this->resetCancelacionState();
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al cancelar: ' . $e->getMessage()]);
        }
    }

    private function resetCancelacionState(): void
    {
        $this->modalCancelacionAbierto = false;
        $this->solicitudCancelarId     = null;
        $this->motivoCancelacion       = '';
        $this->resetErrorBag('motivoCancelacion');
    }

    // =========================================================================
    // MODAL ASIGNACIÓN - ABRIR
    // =========================================================================

    public function abrirModalAsignacion(int $solicitudId): void { $this->openAsignacion($solicitudId); }

    public function openAsignacion(int $solicitudId): void
    {
        try {
            $this->resetAsignacionState();
            $this->asignacionSolicitudId = $solicitudId;

            $this->insumosDisponibles = DB::table('cortes')
                ->whereNull('deleted_at')
                ->select(DB::raw('MAX(CortesID) as id, NombreInsumo as nombre'))
                ->groupBy('NombreInsumo')
                ->orderBy('NombreInsumo')
                ->get()
                ->map(fn($c) => ['id' => (int)$c->id, 'nombre' => (string)$c->nombre])
                ->toArray();

            $seleccionadas = Cotizacion::query()
                ->where('SolicitudID', $solicitudId)
                ->where('Estatus', 'Seleccionada')
                ->orderBy('NumeroPropuesta')
                ->get();

            if ($seleccionadas->isEmpty()) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'No hay cotizaciones Seleccionadas para asignar.']);
                return;
            }

            $activos       = SolicitudActivo::query()->where('SolicitudID', $solicitudId)->get();
            $activosPorKey = $activos->keyBy(fn($a) => (int)$a->CotizacionID . ':' . (int)$a->UnidadIndex);
            $activoIds     = $activos->pluck('SolicitudActivoID')->filter()->values()->all();

            $checklists = empty($activoIds) ? collect()
                : SolicitudActivoCheckList::query()->whereIn('SolicitudActivoID', $activoIds)->get()->groupBy('SolicitudActivoID');

            $empleadosMap = $this->loadEmpleadosDeptMap($activos->pluck('EmpleadoID')->filter()->unique()->values()->all());

            $facturasDelSolicitud = Facturas::query()->where('SolicitudID', $solicitudId)
                ->where(fn($q) => $q->whereNotNull('ArchivoRuta')->where('ArchivoRuta', '!=', '')
                    ->orWhereNotNull('PdfRuta')->where('PdfRuta', '!=', ''))
                ->get()->unique('UUID')->keyBy('UUID');

            $rutasPorProveedor = [];
            foreach ($seleccionadas as $cot) {
                $proveedor = (string)($cot->Proveedor ?? '');
                if ($proveedor && !isset($rutasPorProveedor[$proveedor]))
                    $rutasPorProveedor[$proveedor] = ['xml' => '', 'pdf' => ''];
            }

            foreach ($seleccionadas as $cot) {
                $proveedor = (string)($cot->Proveedor ?? '');
                if (!$proveedor || !empty($rutasPorProveedor[$proveedor]['xml'])) continue;

                $qty = max(1, (int)($cot->Cantidad ?? 1));
                for ($i = 1; $i <= $qty; $i++) {
                    $activo = $activosPorKey->get((int)$cot->CotizacionID . ':' . $i);
                    if (!$activo) continue;

                    $dir = "solicitudes/{$solicitudId}/activos/{$activo->SolicitudActivoID}";
                    if (empty($rutasPorProveedor[$proveedor]['xml']) && Storage::disk('public')->exists("{$dir}/factura.xml"))
                        $rutasPorProveedor[$proveedor]['xml'] = "{$dir}/factura.xml";

                    if (empty($rutasPorProveedor[$proveedor]['xml'])) {
                        $fp = (string)($activo->FacturaPath ?? '');
                        if ($fp && Storage::disk('public')->exists($fp) && strtolower(pathinfo($fp, PATHINFO_EXTENSION)) === 'xml')
                            $rutasPorProveedor[$proveedor]['xml'] = $fp;
                    }
                }

                if (empty($rutasPorProveedor[$proveedor]['xml'])) {
                    $match = $facturasDelSolicitud->first();
                    if ($match && !empty($match->ArchivoRuta))
                        $rutasPorProveedor[$proveedor]['xml'] = (string)$match->ArchivoRuta;
                }
            }

            $out = [];
            foreach ($seleccionadas->groupBy(fn($c) => (int)($c->NumeroPropuesta ?? 0)) as $numeroPropuesta => $items) {
                $cot       = $items->first(); if (!$cot) continue;
                $proveedor = (string)($cot->Proveedor ?? '');
                $qty       = max(1, (int)($cot->Cantidad ?? 1));
                $unidades  = [];

                for ($i = 1; $i <= $qty; $i++) {
                    $activo      = $activosPorKey->get((int)$cot->CotizacionID . ':' . $i);
                    $empleadoId  = $activo ? (int)($activo->EmpleadoID ?? 0) : 0;
                    $empleadoRow = $empleadoId && isset($empleadosMap[$empleadoId]) ? $empleadosMap[$empleadoId] : null;

                    $deptId = $activo && !empty($activo->DepartamentoID)
                        ? (int)$activo->DepartamentoID
                        : ($empleadoRow['departamento_id'] ?? null);

                    $template = $this->checklistTemplateByDept($deptId);
                    $saved    = $activo ? ($checklists->get((int)$activo->SolicitudActivoID) ?? collect()) : collect();

                    // Calcular requiere_config: solo true si hay al menos un checkbox marcado en BD
                    $algunCheckMarcado = $saved->where('completado', true)->isNotEmpty();

                    $xmlSavedPath = $rutasPorProveedor[$proveedor]['xml'] ?? '';

                    if (empty($xmlSavedPath) && $activo) {
                        $dir = "solicitudes/{$solicitudId}/activos/{$activo->SolicitudActivoID}";
                        if (Storage::disk('public')->exists("{$dir}/factura.xml")) $xmlSavedPath = "{$dir}/factura.xml";
                    }

                    $serialVal = ($activo && $this->serialColumn) ? (string)($activo->{$this->serialColumn} ?? '') : '';

                    $unidades[] = [
                        'unidadIndex'             => $i,
                        'activoId'                => $activo ? (int)$activo->SolicitudActivoID : null,
                        'serial'                  => $serialVal,
                        'factura_xml_path'        => $xmlSavedPath,
                        'factura_path'            => $activo ? (string)($activo->FacturaPath ?? '') : '',
                        'fecha_entrega'           => $activo && $activo->FechaEntrega ? $activo->FechaEntrega->format('Y-m-d') : null,
                        'empleado_id'             => $empleadoId ?: null,
                        'empleado_nombre'         => $empleadoRow['nombre'] ?? null,
                        'departamento_id'         => $deptId,
                        'departamento_nombre'     => $empleadoRow['departamento_nombre'] ?? null,
                        'checklist_open'          => true,
                        'checklist'               => $this->applySavedChecklist($template, $saved),
                        'requiere_config'         => $algunCheckMarcado,
                        'fecha_fin_configuracion' => $activo ? (string)($activo->fecha_fin_configuracion ?? '') : '',
                    ];
                }

                $out[] = [
                    'numeroPropuesta' => (int)$numeroPropuesta,
                    'cotizacionId'    => (int)$cot->CotizacionID,
                    'nombreEquipo'    => (string)($cot->NombreEquipo ?? 'Sin nombre'),
                    'proveedor'       => $proveedor,
                    'precioUnitario'  => (string)($cot->Precio ?? '0.00'),
                    'itemsTotal'      => $qty,
                    'unidades'        => $unidades,
                ];
            }

            $this->propuestasAsignacion = $out;
            $this->usuarioSearch        = [];
            $this->usuarioOptions       = [];

            foreach ($this->propuestasAsignacion as $pIndex => $p) {
                foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                    $this->usuarioSearch[$pIndex][$uIndex]  = (string)($u['empleado_nombre'] ?? '');
                    $this->usuarioOptions[$pIndex][$uIndex] = [];
                }
            }

            $this->modalEsSoloLectura     = false;
            $this->modalAsignacionAbierto = true;
        } catch (\Throwable $e) {
            $this->resetAsignacionState();
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error abriendo asignación: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // INSUMO SEARCH
    // =========================================================================

    public function buscarInsumos(string $term): void
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) { $this->insumoSearchResults = []; return; }

        $this->insumoSearchResults = collect($this->insumosDisponibles)
            ->filter(fn($i) => str_contains(mb_strtolower($i['nombre']), mb_strtolower($term)))
            ->values()->take(10)->toArray();
    }

    // CLOSE ASIGNACIÓN

    public function closeAsignacion(): void 
    { 
        // Limpiar registros en BD que no cumplen las 3 condiciones antes de cerrar
        $this->limpiarRegistrosHuerfanos();
        $this->resetAsignacionState();
    }

    public function forzarCloseAsignacion(): void { $this->resetAsignacionState(); }

    // USUARIO SEARCH

    private function handleUsuarioSearchUpdated(int $pIndex, int $uIndex, string $term): void
    {
        $term = trim($term);
        $this->usuarioOptions[$pIndex]          = $this->usuarioOptions[$pIndex] ?? [];
        $this->usuarioOptions[$pIndex][$uIndex] = [];
        
        // Requerir al menos 2 caracteres para buscar
        if (strlen($term) < 2) return;

        $this->usuarioOptions[$pIndex][$uIndex] = Empleados::query()
            ->select('EmpleadoID', 'NombreEmpleado', 'Correo')
            ->where('Estado', true)
            ->where(fn($q) => $q
                ->where('NombreEmpleado', 'like', "{$term}%")
                ->orWhere('Correo', 'like', "{$term}%")
                ->when(ctype_digit($term), fn($q) => $q->orWhere('EmpleadoID', (int)$term)))
            ->orderBy('NombreEmpleado')
            ->limit(10)
            ->get()
            ->map(fn($e) => ['id'=>(int)$e->EmpleadoID,'name'=>(string)$e->NombreEmpleado,'correo'=>(string)$e->Correo])
            ->toArray();
    }

    private function shouldPersistUnit(int $pIndex, int $uIndex, array $u): bool
    {
        // Persistir si tiene serial o fecha de entrega (datos de finalización)
        if (!empty($u['serial']) || !empty($u['fecha_entrega'])) return true;
        
        // Persistir si tiene usuario final asignado (independientemente de requiere_config)
        if (!empty($u['empleado_id'])) return true;
        
        // Persistir si tiene facturas o XML
        foreach (['facturas','facturaXml'] as $prop) {
            if (isset($this->$prop[$pIndex][$uIndex]) && $this->$prop[$pIndex][$uIndex]) return true;
        }
        if (isset($this->xmlParseado[$pIndex][$uIndex]) && empty($this->xmlParseado[$pIndex][$uIndex]['error'])) return true;
        
        return false;
    }

    private function validateAsignacionPayload(bool $strict): array
    {
        $errors = [];
        foreach ($this->propuestasAsignacion as $pIndex => $p) {
            foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                $base = "propuestasAsignacion.$pIndex.unidades.$uIndex";
                if ($strict && !empty($u['empleado_id']) && empty($u['fecha_entrega']))
                    $errors["$base.fecha_entrega"] = 'La fecha de entrega es obligatoria cuando hay usuario asignado.';
                if ($strict && !empty($u['fecha_entrega']) && empty($u['empleado_id']))
                    $errors["$base.empleado_id"] = 'El usuario final es obligatorio cuando hay fecha de entrega.';
                if (!empty($u['requiere_config'])) {
                    foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                        foreach (($u['checklist'][$catKey] ?? []) as $idx => $item) {
                            if (!empty($item['realizado']) && empty($item['responsable']))
                                $errors["$base.checklist.$catKey.$idx.responsable"] = 'Responsable obligatorio en tareas marcadas.';
                        }
                    }
                }
                if (isset($this->facturaXml[$pIndex][$uIndex]) && $this->facturaXml[$pIndex][$uIndex]) {
                    try {
                        if (strtolower((string)$this->facturaXml[$pIndex][$uIndex]->getClientOriginalExtension()) !== 'xml')
                            $errors["facturaXml.$pIndex.$uIndex"] = 'El archivo debe ser XML.';
                    } catch (\Throwable) {}
                }
                if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
                    $file = $this->facturas[$pIndex][$uIndex];
                    $ext  = strtolower((string)$file->getClientOriginalExtension());
                    $mime = strtolower((string)$file->getMimeType());
                    if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml','application/xml','text/plain'], true))
                        $errors["facturas.$pIndex.$uIndex"] = 'La factura debe ser XML.';
                }
                
                // Validación: si hay XML parseado, debe tener insumo, fecha y XML
                if (isset($this->xmlParseado[$pIndex][$uIndex])) {
                    $parsed = $this->xmlParseado[$pIndex][$uIndex];
                    
                    if (empty($parsed['insumoId'])) {
                        $errors["$base.insumo"] = 'Debe asignar un insumo a la factura antes de guardar.';
                    }
                    
                    if (empty($u['fecha_entrega'])) {
                        $errors["$base.fecha_entrega_xml"] = 'La fecha de entrega es obligatoria cuando hay factura XML.';
                    }
                    
                    // Verificar que existe el XML: o está en memoria temporal o ya guardado
                    $tieneXml = !empty($this->facturaXml[$pIndex][$uIndex]) 
                             || !empty($this->facturas[$pIndex][$uIndex])
                             || !empty($u['factura_xml_path']);
                    
                    if (!$tieneXml) {
                        $errors["$base.xml_archivo"] = 'Debe subir el archivo XML de la factura.';
                    }
                }
            }
        }
        return $errors;
    }

    public function guardarAsignacion(): void { $this->persistAsignacion(false, false); }

    public function persistAsignacion($strict = false, $closeAfter = false): void
    {
        $strict     = (bool)$strict;
        $closeAfter = (bool)$closeAfter;

        if (!$this->asignacionSolicitudId) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']);
            return;
        }

        try {
            $errors = $this->validateAsignacionPayload($strict);
            if (!empty($errors)) {
                $this->dispatchBrowserEvent('swal:error', ['message' => array_values($errors)[0] ?? 'Error de validación']);
                throw ValidationException::withMessages($errors);
            }

            DB::transaction(function () {
                // ===================================================================
                // FASE 1: Recolectar XMLs únicos por proveedor
                // ===================================================================
                $xmlPorProveedor     = [];
                $facturaPorProveedor = [];

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    $proveedor = $p['proveedor'] ?? null; if (!$proveedor) continue;
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!isset($xmlPorProveedor[$proveedor]) && !empty($this->facturaXml[$pIndex][$uIndex]))
                            $xmlPorProveedor[$proveedor] = $this->facturaXml[$pIndex][$uIndex];
                        if (!isset($facturaPorProveedor[$proveedor]) && !empty($this->facturas[$pIndex][$uIndex]))
                            $facturaPorProveedor[$proveedor] = $this->facturas[$pIndex][$uIndex];
                    }
                }

                // ===================================================================
                // FASE 2: Insertar FACTURAS ÚNICAS (1 por cotización ganadora)
                // ===================================================================
                $facturasInsertadas = []; // control de cotizaciones ya procesadas

                // Agrupar por cotización para insertar UNA factura por cotización
                $cotizacionesUnicas = [];
                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    $cotizacionId = (int)($p['cotizacionId'] ?? 0);
                    if (!$cotizacionId) continue;
                    
                    // Si ya procesamos esta cotización, skip
                    if (isset($cotizacionesUnicas[$cotizacionId])) continue;
                    
                    $cotizacionesUnicas[$cotizacionId] = [
                        'pIndex'    => $pIndex,
                        'proveedor' => $p['proveedor'] ?? null,
                        'propuesta' => $p,
                    ];
                }

                // Insertar una factura por cada cotización ganadora
                foreach ($cotizacionesUnicas as $cotizacionId => $data) {
                    $pIndex    = $data['pIndex'];
                    $proveedor = $data['proveedor'];
                    if (!$proveedor) continue;

                    // 🔥 Obtener GerenciaID del departamento asignado en SolicitudActivo
                    $gerenciaID = null;
                    foreach ($this->propuestasAsignacion as $prop) {
                        foreach (($prop['unidades'] ?? []) as $u) {
                            $deptId = $u['departamento_id'] ?? null;
                            if ($deptId) {
                                $dept = \App\Models\Departamentos::find($deptId);
                                if ($dept) {
                                    $gerenciaID = $dept->GerenciaID ?? null;
                                }
                                break 2; // Salir de ambos foreach
                            }
                        }
                    }

                    // Buscar el XML parseado para este proveedor
                    $parsed = $this->buscarXmlParseadoPorProveedor($pIndex, $proveedor);
                    if (!$parsed || !empty($parsed['error'])) continue;

                    $uuid = trim((string)($parsed['uuid'] ?? ''));
                    if (!$uuid) continue;

                    // Soft-delete facturas anteriores con este UUID para esta solicitud
                    Facturas::query()
                        ->where('SolicitudID', (int)$this->asignacionSolicitudId)
                        ->where('UUID', $uuid)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);

                    // Preparar datos de la factura
                    $insumoId     = isset($parsed['insumoId']) ? (int)$parsed['insumoId'] : null;
                    $insumoNombre = null;
                    if ($insumoId) {
                        $insumoEncontrado = collect($this->insumosDisponibles)->firstWhere('id', $insumoId);
                        $insumoNombre     = $insumoEncontrado['nombre'] ?? null;
                    }

                    // Nombre de la factura: descripcion del CFDI, nombre del insumo o emisor como fallback
                    $descripcion = mb_substr(trim((string)($parsed['descripcion'] ?? '')), 0, 300);
                    if ($descripcion === '') $descripcion = $insumoNombre ?? mb_substr(trim((string)($parsed['emisor'] ?? '')), 0, 300);
                    if ($descripcion === '') $descripcion = 'Factura ' . $uuid;
                    $descripcion = mb_substr($descripcion, 0, 300);

                    $subtotal = is_numeric($parsed['subtotal'] ?? null) ? (float)$parsed['subtotal'] : 0;
                    $cantidad = isset($parsed['cantidad']) ? (int)$parsed['cantidad'] : 1;

                    // Buscar las rutas XML y PDF de este proveedor
                    $rutaXmlFactura = '';
                    $rutaPdfFactura = '';
                    foreach ($this->propuestasAsignacion as $pi => $propuesta) {
                        if (($propuesta['proveedor'] ?? '') !== $proveedor) continue;
                        foreach (($propuesta['unidades'] ?? []) as $ui => $unidad) {
                            if (empty($rutaXmlFactura) && !empty($unidad['factura_xml_path'])) {
                                $rutaXmlFactura = $unidad['factura_xml_path'];
                            }
                            if (empty($rutaPdfFactura) && !empty($unidad['factura_pdf_path'])) {
                                $rutaPdfFactura = $unidad['factura_pdf_path'];
                            }
                            if (!empty($rutaXmlFactura) && !empty($rutaPdfFactura)) {
                                break 2;
                            }
                        }
                    }

                    try {
                        Facturas::create([
                            'SolicitudID'  => (int)$this->asignacionSolicitudId,
                            'CotizacionID' => $cotizacionId,
                            'GerenciaID'   => $gerenciaID,  // 🔥 AGREGAR GERENCIA
                            'UUID'         => $uuid,
                            'Nombre'       => $descripcion,
                            'Importe'      => $subtotal,
                            'Costo'        => $cantidad > 0 ? ($subtotal / $cantidad) : $subtotal,
                            'Mes'          => !empty($parsed['mes'])  ? (int)$parsed['mes']  : null,
                            'Anio'         => !empty($parsed['anio']) ? (int)$parsed['anio'] : null,
                            'InsumoID'     => $insumoId ?: null,
                            'InsumoNombre' => $insumoNombre,
                            'Emisor'       => $parsed['emisor'] ?? '',
                            'ArchivoRuta'  => $rutaXmlFactura,
                            'PdfRuta'      => $rutaPdfFactura,
                        ]);

                        $facturasInsertadas[$cotizacionId] = true;

                        \Log::info('[Asignacion] Factura insertada por cotización', [
                            'solicitudId'  => $this->asignacionSolicitudId,
                            'cotizacionId' => $cotizacionId,
                            'uuid'         => $uuid,
                            'proveedor'    => $proveedor,
                        ]);

                    } catch (\Throwable $fe) {
                        \Log::error('[Asignacion] ERROR factura XML', [
                            'cotizacionId' => $cotizacionId,
                            'proveedor'    => $proveedor,
                            'uuid'         => $uuid,
                            'descripcion'  => $descripcion,
                            'error'        => $fe->getMessage()
                        ]);
                        throw $fe;
                    }
                }

                // ===================================================================
                // FASE 3: Guardar SolicitudActivo y Checklist por unidad
                // ===================================================================
                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        // Si NO cumple condiciones de persistencia
                        if (!$this->shouldPersistUnit($pIndex, $uIndex, $u)) {
                            // Si tiene activoId (registro existente)
                            $activoId = $u['activoId'] ?? null;
                            if ($activoId) {
                                try {
                                    // Si tiene serial/fecha_entrega, SOLO limpiar empleado
                                    if (!empty($u['serial']) || !empty($u['fecha_entrega'])) {
                                        SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->update([
                                            'EmpleadoID'     => null,
                                            'DepartamentoID' => null,
                                        ]);
                                    } else {
                                        // Si no tiene serial/fecha_entrega, eliminar completo
                                        SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->delete();
                                        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = null;
                                    }
                                } catch (\Throwable $e) {
                                    \Log::error('[Asignacion] Error limpiando SolicitudActivo', [
                                        'activoId' => $activoId,
                                        'error'    => $e->getMessage(),
                                    ]);
                                }
                            }
                            continue;
                        }

                        $dataUpdate = ['NumeroPropuesta' => (int)($p['numeroPropuesta'] ?? 0)];
                        if (!empty($u['fecha_entrega']))   $dataUpdate['FechaEntrega']   = $u['fecha_entrega'];
                        if ($this->serialColumn)           $dataUpdate[$this->serialColumn] = (string)($u['serial'] ?? '');

                        // Verificar si cumple las 3 condiciones COMPLETAS (empleado + requiere_config + checklist)
                        $cumpleCondicionesCompletas = false;
                        if (!empty($u['empleado_id']) && !empty($u['requiere_config'])) {
                            foreach (($u['checklist'] ?? []) as $items) {
                                foreach ($items as $item) {
                                    if (!empty($item['realizado'])) {
                                        $cumpleCondicionesCompletas = true;
                                        break 2;
                                    }
                                }
                            }
                        }

                        // Actualizar empleado/departamento si tiene empleado asignado (con o sin config completa)
                        if (!empty($u['empleado_id'])) {
                            $dataUpdate['EmpleadoID']     = (int)$u['empleado_id'];
                            $dataUpdate['DepartamentoID'] = !empty($u['departamento_id']) ? (int)$u['departamento_id'] : null;
                        } else {
                            // Si NO tiene empleado, limpiar empleado/departamento
                            $dataUpdate['EmpleadoID']     = null;
                            $dataUpdate['DepartamentoID'] = null;
                        }

                        $activo = SolicitudActivo::updateOrCreate(
                            [
                                'SolicitudID'  => (int)$this->asignacionSolicitudId,
                                'CotizacionID' => (int)($p['cotizacionId'] ?? 0),
                                'UnidadIndex'  => (int)($u['unidadIndex'] ?? ($uIndex + 1)),
                            ],
                            $dataUpdate
                        );

                        $proveedor = $p['proveedor'] ?? null;
                        $baseDir   = "solicitudes/{$this->asignacionSolicitudId}/activos/{$activo->SolicitudActivoID}";
                        $rutaXml   = null;

                        $xmlFile = ($proveedor && isset($xmlPorProveedor[$proveedor]))
                            ? $xmlPorProveedor[$proveedor]
                            : ($this->facturaXml[$pIndex][$uIndex] ?? null);

                        if ($xmlFile) {
                            $ext  = strtolower((string)$xmlFile->getClientOriginalExtension());
                            $mime = strtolower((string)$xmlFile->getMimeType());
                            if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml','application/xml','text/plain'], true))
                                throw new \Exception('El archivo XML de factura no es válido.');
                            $rutaXml = $xmlFile->storeAs($baseDir, 'factura.xml', 'public');
                        }

                        if (!$rutaXml && !empty($u['factura_xml_path'])) $rutaXml = $u['factura_xml_path'];

                        if (!$rutaXml) {
                            $legacy = ($proveedor && isset($facturaPorProveedor[$proveedor]))
                                ? $facturaPorProveedor[$proveedor]
                                : ($this->facturas[$pIndex][$uIndex] ?? null);
                            if ($legacy) {
                                $ext  = strtolower((string)$legacy->getClientOriginalExtension());
                                $mime = strtolower((string)$legacy->getMimeType());
                                if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml','application/xml','text/plain'], true))
                                    throw new \Exception('La factura debe ser XML.');
                                $rutaXml = $legacy->storeAs($baseDir, 'factura.xml', 'public');
                            }
                        }

                        if ($rutaXml) {
                            $activo->update(['FacturaPath' => $rutaXml]);
                            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['factura_xml_path'] = $rutaXml;
                            if ($proveedor) {
                                foreach ($this->propuestasAsignacion as $pi => $prop) {
                                    if (($prop['proveedor'] ?? '') !== $proveedor) continue;
                                    foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                                        if ($pi === $pIndex && $ui === $uIndex) continue;
                                        $this->propuestasAsignacion[$pi]['unidades'][$ui]['factura_xml_path'] = $rutaXml;
                                    }
                                }
                            }
                        }

                        // Guardar o limpiar checklist según cumplimiento de condiciones COMPLETAS
                        if ($cumpleCondicionesCompletas && !empty($u['checklist'])) {
                            // SI cumple las 3 condiciones: guardar checklist completo
                            foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                                foreach (($u['checklist'][$catKey] ?? []) as $item) {
                                    $reqId = (int)($item['req_id'] ?? 0); if (!$reqId) continue;
                                    $realizado = !empty($item['realizado']);
                                    SolicitudActivoCheckList::updateOrCreate(
                                        ['SolicitudActivoID'=>(int)$activo->SolicitudActivoID,'DepartamentoRequerimientoID'=>$reqId],
                                        ['completado'=>(bool)$realizado,'responsable'=>$realizado ? (string)($item['responsable']??'') : null]
                                    );
                                }
                            }
                        } else {
                            // NO cumple las 3 condiciones: eliminar cualquier checklist existente
                            // (solo se guarda usuario final, no configuración)
                            SolicitudActivoCheckList::where('SolicitudActivoID', (int)$activo->SolicitudActivoID)->delete();
                        }
                    }
                }
            });

            $this->facturaXml  = [];
            $this->facturas    = [];
            $this->xmlParseado = [];

            $this->dispatchBrowserEvent('swal:success', [
                'message' => $strict ? 'Asignación guardada correctamente' : 'Avance guardado correctamente',
            ]);

            if ($closeAfter) $this->closeAsignacion();

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error guardando: ' . $e->getMessage()]);
        }
    }

    public function actualizarInsumoConcepto(int $pIndex, int $uIndex, $insumoId): void
    {
        if (!isset($this->xmlParseado[$pIndex][$uIndex])) return;

        //$this->skipRender();

        $insumoId = ($insumoId !== null && $insumoId > 0) ? (int)$insumoId : null;
        $this->xmlParseado[$pIndex][$uIndex]['insumoId'] = $insumoId;

        $proveedor = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
        if (!$proveedor) return;

        foreach ($this->propuestasAsignacion as $pi => $prop) {
            if (($prop['proveedor'] ?? '') !== $proveedor) continue;
            foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                if ($pi === $pIndex && $ui === $uIndex) continue;
                if (isset($this->xmlParseado[$pi][$ui]))
                    $this->xmlParseado[$pi][$ui]['insumoId'] = $insumoId;
            }
        }
    }

    public function seleccionarEmpleado(int $pIndex, int $uIndex, int $empleadoId): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex])) throw new \Exception('Ítem inválido.');

        $row = $this->getEmpleadoConDept($empleadoId);

        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex] = array_merge(
            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex],
            [
                'empleado_id'         => (int)$row['EmpleadoID'],
                'empleado_nombre'     => (string)$row['NombreEmpleado'],
                'departamento_id'     => $row['DepartamentoID'] ? (int)$row['DepartamentoID'] : null,
                'departamento_nombre' => $row['NombreDepartamento'] ?: null,
                'checklist'           => $this->checklistTemplateByDept($row['DepartamentoID'] ? (int)$row['DepartamentoID'] : null),
            ]
        );

        $this->lockUsuarioSearch($pIndex, $uIndex);
        $this->usuarioSearch[$pIndex][$uIndex]  = (string)$row['NombreEmpleado'];
        $this->usuarioOptions[$pIndex][$uIndex] = [];

        // PERSISTIR INMEDIATAMENTE el usuario final en BD
        if ($this->asignacionSolicitudId) {
            try {
                $propuesta = $this->propuestasAsignacion[$pIndex];
                $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex];

                $activo = SolicitudActivo::updateOrCreate(
                    [
                        'SolicitudID'  => (int)$this->asignacionSolicitudId,
                        'CotizacionID' => (int)($propuesta['cotizacionId'] ?? 0),
                        'UnidadIndex'  => (int)($unidad['unidadIndex'] ?? ($uIndex + 1)),
                    ],
                    [
                        'NumeroPropuesta' => (int)($propuesta['numeroPropuesta'] ?? 0),
                        'EmpleadoID'      => (int)$row['EmpleadoID'],
                        'DepartamentoID'  => $row['DepartamentoID'] ? (int)$row['DepartamentoID'] : null,
                    ]
                );

                // Guardar activoId en memoria para futuras actualizaciones
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = (int)$activo->SolicitudActivoID;

                \Log::info('[Asignacion] Usuario final persistido inmediatamente', [
                    'solicitudId' => $this->asignacionSolicitudId,
                    'activoId'    => $activo->SolicitudActivoID,
                    'empleadoId'  => $row['EmpleadoID'],
                    'pIndex'      => $pIndex,
                    'uIndex'      => $uIndex,
                ]);

            } catch (\Throwable $e) {
                \Log::error('[Asignacion] Error persistiendo usuario final', [
                    'solicitudId' => $this->asignacionSolicitudId,
                    'empleadoId'  => $empleadoId,
                    'error'       => $e->getMessage(),
                ]);
                $this->dispatchBrowserEvent('swal:error', [
                    'message' => 'Error guardando usuario final: ' . $e->getMessage()
                ]);
            }
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function buscarXmlParseadoPorProveedor(int $pIndex, string $proveedor): ?array
    {
        foreach ($this->propuestasAsignacion as $pi => $prop) {
            if (($prop['proveedor'] ?? '') !== $proveedor) continue;
            foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                $data = $this->xmlParseado[$pi][$ui] ?? null;
                if ($data && empty($data['error'])) return $data;
            }
        }
        return null;
    }

    private function checklistTemplateByDept(?int $deptId): array
    {
        if (!$deptId) return [];
        if (isset($this->checklistTemplatesCache[$deptId])) return $this->checklistTemplatesCache[$deptId];

        $reqs = DepartamentoRequerimientos::query()->byDepartamentos($deptId)->seleccionados()
            ->orderBy('categoria')->orderBy('nombre')
            ->get(['id','categoria','nombre']);

        $payload = [];
        foreach ($reqs as $r) {
            $catKey = (string)$r->categoria;
            if (!isset($payload[$catKey])) $payload[$catKey] = [];
            $payload[$catKey][] = ['req_id'=>(int)$r->id,'nombre'=>(string)$r->nombre,'realizado'=>false,'responsable'=>''];
        }

        $this->checklistTemplatesCache[$deptId] = $payload;
        return $payload;
    }

    private function applySavedChecklist(array $template, $checklistRows): array
    {
        $map = collect($checklistRows)->keyBy(fn($x) => (int)($x->DepartamentoRequerimientoID ?? 0));
        foreach (array_keys($template) as $catKey) {
            foreach ($template[$catKey] as $idx => $item) {
                $reqId = (int)($item['req_id'] ?? 0); if (!$reqId) continue;
                $row   = $map->get($reqId);
                if ($row) {
                    $template[$catKey][$idx]['realizado']   = (bool)($row->completado  ?? false);
                    $template[$catKey][$idx]['responsable'] = (string)($row->responsable ?? '');
                }
            }
        }
        return $template;
    }

    private function currentUserPrefix(): string
    {
        $user = auth()->user();
        if (!$user || empty($user->name)) return '';
        $empleado = Empleados::query()
            ->whereRaw('LOWER(NombreEmpleado) = ?', [mb_strtolower(trim((string)$user->name))])
            ->first();
        return $empleado && !empty($empleado->Correo) ? (string)Str::before(strtolower($empleado->Correo), '@') : '';
    }

    /**
     * Limpia registros SolicitudActivo en BD que no cumplen las 3 condiciones de persistencia.
     * Se ejecuta al cerrar el modal sin guardar para eliminar registros creados automáticamente.
     */
    private function limpiarRegistrosHuerfanos(): void
    {
        if (!$this->asignacionSolicitudId) return;

        try {
            foreach ($this->propuestasAsignacion as $pIndex => $p) {
                foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                    // Si NO cumple condiciones de persistencia pero tiene activoId
                    if (!$this->shouldPersistUnit($pIndex, $uIndex, $u)) {
                        $activoId = $u['activoId'] ?? null;
                        if ($activoId) {
                            // Si tiene serial/fecha_entrega, SOLO limpiar empleado
                            if (!empty($u['serial']) || !empty($u['fecha_entrega'])) {
                                SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->update([
                                    'EmpleadoID'     => null,
                                    'DepartamentoID' => null,
                                ]);
                            } else {
                                // Si no tiene serial/fecha_entrega, eliminar completo
                                SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->delete();
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('[Asignacion] Error limpiando registros huérfanos', [
                'solicitudId' => $this->asignacionSolicitudId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function resetAsignacionState(): void
    {
        $this->resetErrorBag();
        $this->modalAsignacionAbierto      = false;
        $this->modalEsSoloLectura          = false;
        $this->asignacionSolicitudId       = null;
        $this->propuestasAsignacion        = [];
        $this->facturas                    = [];
        $this->facturaXml                  = [];
        $this->xmlParseado                 = [];
        $this->usuarioSearch               = [];
        $this->usuarioOptions              = [];
        $this->usuarioSearchLock           = [];
        $this->checklistTemplatesCache     = [];
        $this->confirmarCierreModalAbierto = false;
        $this->insumosDisponibles          = [];
        $this->insumoSearchQuery           = '';
        $this->insumoSearchResults         = [];
    }

    private function detectSerialColumn(): ?string
    {
        if (!Schema::hasTable('solicitud_activos')) return null;
        foreach (['serial','Serial','NumeroSerie','NumSerie'] as $col) {
            if (Schema::hasColumn('solicitud_activos', $col)) return $col;
        }
        return null;
    }

    private function loadEmpleadosDeptMap(array $empleadoIds): array
    {
        if (empty($empleadoIds)) return [];
        $map = [];
        foreach (
            Empleados::query()->withTrashed()->from('empleados as e')
                ->leftJoin('puestos as p',      'p.PuestoID',       '=', 'e.PuestoID')
                ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
                ->whereIn('e.EmpleadoID', $empleadoIds)
                ->get(['e.EmpleadoID','e.NombreEmpleado','e.Correo','d.DepartamentoID','d.NombreDepartamento'])
            as $r
        ) {
            $map[(int)$r->EmpleadoID] = [
                'nombre'              => (string)($r->NombreEmpleado    ?? ''),
                'correo'              => (string)($r->Correo            ?? ''),
                'departamento_id'     => $r->DepartamentoID      ? (int)$r->DepartamentoID      : null,
                'departamento_nombre' => $r->NombreDepartamento ? (string)$r->NombreDepartamento : null,
            ];
        }
        return $map;
    }

    private function getEmpleadoConDept(int $empleadoId): array
    {
        $row = Empleados::query()->withTrashed()->from('empleados as e')
            ->leftJoin('puestos as p',      'p.PuestoID',       '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->where('e.EmpleadoID', $empleadoId)
            ->first(['e.EmpleadoID','e.NombreEmpleado','e.Correo','d.DepartamentoID','d.NombreDepartamento']);

        if (!$row) throw new \Exception('Empleado no encontrado.');

        return [
            'EmpleadoID'         => (int)$row->EmpleadoID,
            'NombreEmpleado'     => (string)($row->NombreEmpleado    ?? ''),
            'Correo'             => (string)($row->Correo            ?? ''),
            'DepartamentoID'     => $row->DepartamentoID      ? (int)$row->DepartamentoID      : null,
            'NombreDepartamento' => $row->NombreDepartamento ? (string)$row->NombreDepartamento : null,
        ];
    }

    private function lockUsuarioSearch(int $p, int $u): void     { $this->usuarioSearchLock[$p][$u] = true; }
    private function isUsuarioSearchLocked(int $p, int $u): bool { return !empty($this->usuarioSearchLock[$p][$u]); }
    private function unlockUsuarioSearch(int $p, int $u): void   { if (isset($this->usuarioSearchLock[$p][$u])) $this->usuarioSearchLock[$p][$u] = false; }

    public function toggleRequiereConfig(int $pIndex, int $uIndex): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex])) return;

        $this->skipRender();

        $current = (bool)($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['requiere_config'] ?? false);
        $nuevo = !$current;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['requiere_config'] = $nuevo;

        // Si se DESACTIVA requiere_config, desmarcar TODOS los checkboxes en memoria Y en BD
        if (!$nuevo) {
            foreach (array_keys($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'] ?? []) as $catKey) {
                foreach (array_keys($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey] ?? []) as $idx) {
                    $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado'] = false;
                    $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] = '';
                }
            }
            
            // Si ya existe activoId, eliminar checkboxes de BD
            $activoId = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] ?? null;
            if ($activoId && $this->asignacionSolicitudId) {
                try {
                    SolicitudActivoCheckList::where('SolicitudActivoID', (int)$activoId)->delete();
                    
                    $u = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex];
                    // Si tampoco tiene serial ni fecha_entrega, eliminar el SolicitudActivo completo
                    if (empty($u['serial']) && empty($u['fecha_entrega'])) {
                        SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->delete();
                        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = null;
                    } else {
                        // Si tiene serial/fecha_entrega, SOLO actualizar empleado a NULL
                        SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->update([
                            'EmpleadoID'     => null,
                            'DepartamentoID' => null,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al desactivar configuración: ' . $e->getMessage()]);
                }
            }
            
            return;
        }

        // Si se ACTIVA requiere_config y tiene empleado asignado, insertar TODA la checklist en BD
        if ($nuevo && !empty($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_id']) && $this->asignacionSolicitudId) {
            try {
                $propuesta = $this->propuestasAsignacion[$pIndex] ?? null;
                $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex] ?? null;
                
                if (!$propuesta || !$unidad) return;

                $activoId = $unidad['activoId'] ?? null;
                $empleadoId = (int)($unidad['empleado_id'] ?? 0);

                // SIEMPRE actualizar o crear SolicitudActivo con los datos actuales del empleado
                $activo = SolicitudActivo::updateOrCreate(
                    [
                        'SolicitudID'     => (int)$this->asignacionSolicitudId,
                        'CotizacionID'    => (int)($propuesta['cotizacionId'] ?? 0),
                        'UnidadIndex'     => (int)($unidad['unidadIndex'] ?? ($uIndex + 1)),
                    ],
                    [
                        'NumeroPropuesta' => (int)($propuesta['numeroPropuesta'] ?? 0),
                        'EmpleadoID'      => $empleadoId,
                        'DepartamentoID'  => !empty($unidad['departamento_id']) ? (int)$unidad['departamento_id'] : null,
                    ]
                );
                
                $activoId = (int)$activo->SolicitudActivoID;
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = $activoId;

                // Insertar TODA la checklist con completado=0, responsable=null
                foreach ($unidad['checklist'] as $categoria => $items) {
                    foreach ($items as $item) {
                        $reqId = (int)($item['req_id'] ?? 0);
                        if (!$reqId) continue;

                        SolicitudActivoCheckList::updateOrCreate(
                            ['SolicitudActivoID' => (int)$activoId, 'DepartamentoRequerimientoID' => $reqId],
                            ['completado' => false, 'responsable' => null]
                        );
                    }
                }

            } catch (\Throwable $e) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al activar configuración: ' . $e->getMessage()]);
            }
        }
    }

    public function marcarChecklist(int $pIndex, int $uIndex, string $catKey, int $idx): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx])) return;

        // Evitar re-render completo del componente (el toggle visual se hace en JS)
        $this->skipRender();

        // 1. Actualizar estado en memoria
        $realizado = !((bool)($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado'] ?? false));
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado']   = $realizado;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] = $realizado ? $this->currentUserPrefix() : '';

        // 2. Calcular si requiere_config (al menos un checkbox marcado en todo el checklist)
        $tieneChecksMarcados = false;
        foreach ($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'] as $items) {
            foreach ($items as $c) {
                if (!empty($c['realizado'])) {
                    $tieneChecksMarcados = true;
                    break 2;
                }
            }
        }
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['requiere_config'] = $tieneChecksMarcados;

        // 3. Obtener datos necesarios
        $activoId     = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] ?? null;
        $empleadoId   = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_id'] ?? null;
        $reqConfig    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['requiere_config'] ?? false;
        $reqId        = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['req_id'] ?? null;

        // 4. PERSISTIR SOLO EL CHECKBOX ESPECÍFICO si cumple condiciones:
        //    - Tiene empleado asignado
        //    - requiere_config está activo
        //    - Tiene al menos un checkbox marcado (para crear activoId si no existe)
        //    - Tiene reqId válido
        if ($empleadoId && $reqConfig && $tieneChecksMarcados && $this->asignacionSolicitudId && $reqId) {
            try {
                $propuesta = $this->propuestasAsignacion[$pIndex] ?? null;
                $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex] ?? null;
                
                if (!$propuesta || !$unidad) return;

                // SIEMPRE actualizar o crear SolicitudActivo con los datos actuales del empleado
                $activo = SolicitudActivo::updateOrCreate(
                    [
                        'SolicitudID'     => (int)$this->asignacionSolicitudId,
                        'CotizacionID'    => (int)($propuesta['cotizacionId'] ?? 0),
                        'UnidadIndex'     => (int)($unidad['unidadIndex'] ?? ($uIndex + 1)),
                    ],
                    [
                        'NumeroPropuesta' => (int)($propuesta['numeroPropuesta'] ?? 0),
                        'EmpleadoID'      => (int)$empleadoId,
                        'DepartamentoID'  => !empty($unidad['departamento_id']) ? (int)$unidad['departamento_id'] : null,
                    ]
                );
                
                $activoId = (int)$activo->SolicitudActivoID;
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = $activoId;

                // Persistir SOLO el checkbox que cambió (no toda la lista)
                SolicitudActivoCheckList::updateOrCreate(
                    ['SolicitudActivoID' => (int)$activoId, 'DepartamentoRequerimientoID' => (int)$reqId],
                    [
                        'completado'  => $realizado,
                        'responsable' => $realizado ? $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] : null,
                    ]
                );

            } catch (\Throwable $e) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al guardar el checklist: ' . $e->getMessage()]);
            }
        }
        // Si ya existe activoId pero se desmarcó todo, actualizar solo este item
        elseif ($activoId && $empleadoId && $reqConfig && $this->asignacionSolicitudId && $reqId) {
            try {
                // Actualizar el SolicitudActivo con los datos actuales del empleado
                $propuesta = $this->propuestasAsignacion[$pIndex] ?? null;
                $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex] ?? null;
                
                if ($propuesta && $unidad) {
                    SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->update([
                        'EmpleadoID'     => (int)$empleadoId,
                        'DepartamentoID' => !empty($unidad['departamento_id']) ? (int)$unidad['departamento_id'] : null,
                    ]);
                }

                // Actualizar el checkbox específico
                SolicitudActivoCheckList::updateOrCreate(
                    ['SolicitudActivoID' => (int)$activoId, 'DepartamentoRequerimientoID' => (int)$reqId],
                    [
                        'completado'  => $realizado,
                        'responsable' => $realizado ? $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] : null,
                    ]
                );
            } catch (\Throwable $e) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al actualizar el checklist: ' . $e->getMessage()]);
            }
        }

        // Si después de marcar/desmarcar ya NO cumple condiciones de persistencia
        if ($activoId && !$tieneChecksMarcados) {
            $u = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex];
            try {
                if (empty($u['serial']) && empty($u['fecha_entrega'])) {
                    // Si no tiene serial/fecha_entrega, eliminar el SolicitudActivo completo
                    SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->delete();
                    $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = null;
                } else {
                    // Si tiene serial/fecha_entrega, SOLO actualizar empleado a NULL
                    SolicitudActivo::where('SolicitudActivoID', (int)$activoId)->update([
                        'EmpleadoID'     => null,
                        'DepartamentoID' => null,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('[Asignacion] Error limpiando SolicitudActivo sin checks', [
                    'activoId' => $activoId,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    public function finalizarConfiguracionUnidad(int $pIndex, int $uIndex): void
    {
        try {
            $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex] ?? null;
            $propuesta = $this->propuestasAsignacion[$pIndex] ?? null;

            if (!$unidad || !$propuesta) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'No se encontró la unidad.']);
                return;
            }

            if (empty($unidad['empleado_id'])) {
                $this->dispatchBrowserEvent('swal:warning', ['message' => 'Debes asignar un Usuario final antes de finalizar la configuración.']);
                return;
            }

            $activoId = $unidad['activoId'] ?? null;

            DB::transaction(function () use ($pIndex, $uIndex, $propuesta, &$unidad, &$activoId) {
                $dataUpdate = [
                    'NumeroPropuesta'         => (int)($propuesta['numeroPropuesta'] ?? 0),
                    'FechaEntrega'            => !empty($unidad['fecha_entrega'])   ? $unidad['fecha_entrega']   : null,
                    'EmpleadoID'              => !empty($unidad['empleado_id'])     ? (int)$unidad['empleado_id']     : null,
                    'DepartamentoID'          => !empty($unidad['departamento_id']) ? (int)$unidad['departamento_id'] : null,
                    'fecha_fin_configuracion' => now(),
                ];
                if ($this->serialColumn && !empty($unidad['serial'])) $dataUpdate[$this->serialColumn] = (string)$unidad['serial'];

                $activo = SolicitudActivo::updateOrCreate(
                    ['SolicitudID'=>(int)$this->asignacionSolicitudId,'CotizacionID'=>(int)($propuesta['cotizacionId']??0),'UnidadIndex'=>(int)($unidad['unidadIndex']??($uIndex+1))],
                    $dataUpdate
                );
                $activoId = (int)$activo->SolicitudActivoID;
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId']                = $activoId;
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['fecha_fin_configuracion'] = now()->toDateTimeString();

                foreach (array_keys($unidad['checklist'] ?? []) as $catKey) {
                    foreach (($unidad['checklist'][$catKey] ?? []) as $item) {
                        $reqId = (int)($item['req_id'] ?? 0); if (!$reqId) continue;
                        $realizado = !empty($item['realizado']);
                        SolicitudActivoCheckList::updateOrCreate(
                            ['SolicitudActivoID'=>$activoId,'DepartamentoRequerimientoID'=>$reqId],
                            ['completado'=>$realizado,'responsable'=>$realizado ? (string)($item['responsable']??'') : null]
                        );
                    }
                }
            });

            $this->crearTicketPorEquipo($pIndex, $uIndex, $activoId);
            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist_open']  = false;
            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['config_lista_ui'] = true;

            $this->dispatchBrowserEvent('swal:info', [
                'message' => "Instalación registrada correctamente.\nPuedes subir el XML de factura ahora o más tarde.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al finalizar configuración: ' . $e->getMessage()]);
        }
    }

    private function crearTicketPorEquipo(int $pIndex, int $uIndex, int $activoId): void
    {
        $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex];
        $propuesta = $this->propuestasAsignacion[$pIndex];

        $solicitanteEmpleadoId = $unidad['empleado_id'] ?? null;
        if (!$solicitanteEmpleadoId && $this->asignacionSolicitudId)
            $solicitanteEmpleadoId = Solicitud::find($this->asignacionSolicitudId)?->EmpleadoID;

        $checklistLineas = [];
        foreach (($unidad['checklist'] ?? []) as $categoria => $items) {
            $realizados = array_filter($items, fn($i) => !empty($i['realizado']));
            if (empty($realizados)) continue;
            $checklistLineas[] = ">> {$categoria}:";
            foreach ($realizados as $item) {
                $resp              = !empty($item['responsable']) ? " [{$item['responsable']}]" : '';
                $checklistLineas[] = "   - {$item['nombre']}{$resp}";
            }
        }

        $lineas = array_filter([
            'Instalacion y configuracion de equipo.', '',
            'Equipo: '      . ($propuesta['nombreEquipo'] ?? 'Sin nombre'),
            'Proveedor: '   . ($propuesta['proveedor']    ?? 'Sin proveedor'),
            !empty($unidad['serial'])              ? 'Serial: '        . $unidad['serial']              : null,
            !empty($unidad['departamento_nombre']) ? 'Departamento: '  . $unidad['departamento_nombre'] : null,
            !empty($unidad['empleado_nombre'])     ? 'Usuario final: ' . $unidad['empleado_nombre']     : null,
            $this->asignacionSolicitudId           ? 'Solicitud #'     . $this->asignacionSolicitudId   : null,
        ]);

        if (!empty($checklistLineas)) {
            $lineas[] = ''; $lineas[] = 'Tareas de configuracion realizadas:';
            foreach ($checklistLineas as $l) $lineas[] = $l;
        }

        $numeroUnidad  = $unidad['unidadIndex'] ?? ($uIndex + 1);
        $totalUnidades = (int)($propuesta['itemsTotal'] ?? 1);
        $sufijo        = $totalUnidades > 1 ? " (Unidad {$numeroUnidad}/{$totalUnidades})" : '';
        $titulo        = 'Instalacion - ' . ($propuesta['nombreEquipo'] ?? 'Equipo') . $sufijo;

        if (Tickets::query()
            ->where('Descripcion', 'like', $titulo . '%')
            ->where('Descripcion', 'like', '%Solicitud #' . $this->asignacionSolicitudId . '%')
            ->exists()) return;

        Tickets::create([
            'EmpleadoID'    => $solicitanteEmpleadoId,
            'ResponsableTI' => null,
            'Descripcion'   => $titulo . "\n\n" . implode("\n", $lineas),
            'Estatus'       => 'Pendiente',
            'Prioridad'     => 'Media',
        ]);
    }

    private function buscarResponsableTicket(string $term): void
    {
        $this->ticketInstalacionOptions = [];
        $term = trim($term); if ($term === '') return;

        $this->ticketInstalacionOptions = Empleados::query()->where('Estado', true)
            ->where(fn($q) => $q->where('NombreEmpleado', 'like', "%{$term}%")->orWhere('Correo', 'like', "%{$term}%"))
            ->limit(8)->get(['EmpleadoID','NombreEmpleado','Correo'])
            ->map(fn($e) => ['id'=>(int)$e->EmpleadoID,'name'=>(string)$e->NombreEmpleado,'correo'=>(string)$e->Correo])
            ->toArray();
    }

    public function seleccionarResponsableTicket(int $empleadoId, string $nombre): void
    {
        $this->ticketInstalacionEmpleadoId  = $empleadoId > 0 ? $empleadoId : null;
        $this->ticketInstalacionResponsable = $empleadoId > 0 ? $nombre : '';
        $this->ticketInstalacionOptions     = [];
    }

    public function cerrarModalTicketInstalacion(): void
    {
        $this->modalTicketInstalacionAbierto = false;
        $this->ticketInstalacionPIndex       = null;
        $this->ticketInstalacionUIndex       = null;
        $this->ticketInstalacionEmpleadoId   = null;
        $this->ticketInstalacionResponsable  = '';
        $this->ticketInstalacionOptions      = [];
    }

    public function verDetallesSLA($solicitudId)
    {
        $this->infoSolicitud    = Solicitud::with(['empleadoid','pasoSupervisor','pasoGerencia','pasoAdministracion'])->find($solicitudId);
        $this->modalInfoAbierto = true;
    }

    public function cerrarModalInfo()
    {
        $this->modalInfoAbierto = false;
        $this->infoSolicitud    = null;
    }

    public function calcularHorasLaborales($inicio, $fin)
    {
        if (!$inicio || !$fin) return 'Pendiente';
        $inicio = Carbon::parse($inicio); $fin = Carbon::parse($fin);
        if ($inicio->gt($fin)) return '0 h';

        $totalMinutos = 0; $actual = $inicio->copy();
        while ($actual->lt($fin)) {
            $dia = $actual->dayOfWeek;
            if ($dia == 0) { $actual->addDay()->setTime(9,0,0); continue; }
            $hi = $actual->copy()->setTime(9,0,0);
            $hf = ($dia == 6) ? $actual->copy()->setTime(14,0,0) : $actual->copy()->setTime(18,0,0);
            if ($actual->lt($hi)) $actual = $hi->copy();
            if ($actual->gte($hf)) { $actual->addDay()->setTime(9,0,0); continue; }
            $limite       = $fin->lt($hf) ? $fin : $hf;
            $totalMinutos += $actual->diffInMinutes($limite);
            $actual        = $hf->copy();
        }

        if ($totalMinutos == 0) return '0 h';
        $h = floor($totalMinutos / 60); $m = $totalMinutos % 60;
        return $h > 0 ? "{$h} h " . ($m > 0 ? "{$m} m" : '') : "{$m} m";
    }

    private function parsearCfdi(string $ruta): array
    {
        $contenido = file_get_contents($ruta);
        if ($contenido === false) throw new \Exception('No se pudo leer el archivo XML.');

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $e = array_map(fn($e) => $e->message, libxml_get_errors()); libxml_clear_errors();
            throw new \Exception('XML inválido: ' . implode(', ', $e));
        }

        $ns      = $xml->getDocNamespaces(true);
        $cfdiUri = $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
        $xml->registerXPathNamespace('cfdi', $cfdiUri);
        $xml->registerXPathNamespace('tfd',  'http://www.sat.gob.mx/TimbreFiscalDigital');

        $attrs   = $xml->attributes();
        $version = (string)($attrs['Version'] ?? $attrs['version'] ?? '3.3');
        $fecha   = (string)($attrs['Fecha']   ?? '');
        $moneda  = (string)($attrs['Moneda']  ?? 'MXN');
        $subtotal = (string)($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0');

        $emisorNode   = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor') ?: $xml->xpath('//cfdi:Emisor');
        $emisorNombre = $emisorNode ? (string)$emisorNode[0]['Nombre'] : '';

        $uuid   = '';
        $timbre = $xml->xpath('//tfd:TimbreFiscalDigital') ?: [];
        if (!empty($timbre)) $uuid = strtoupper(trim((string)($timbre[0]['UUID'] ?? '')));

        $mes = null; $anio = null;
        if ($fecha) {
            try { $cf = Carbon::parse($fecha); $mes = (int)$cf->format('n'); $anio = (int)$cf->format('Y'); }
            catch (\Throwable) {}
        }

        // Extraer descripciones de conceptos para el campo descripcion general
        $conceptoNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: $xml->xpath('//cfdi:Concepto') ?: [];
        $cantidadTotal = 0;
        $descripcionCompleta = [];

        foreach ($conceptoNodes as $nodo) {
            $ca = $nodo->attributes();
            $cantidad = (float)((string)($ca['Cantidad'] ?? '1'));
            $cantidadTotal += $cantidad;
            $desc = (string)($ca['Descripcion'] ?? '');
            if ($desc) $descripcionCompleta[] = $desc;
        }

        // Retorna datos de cabecera del CFDI; un solo insumoId se asigna a nivel de factura
        return [
            'version'     => $version,
            'uuid'        => $uuid,
            'emisor'      => $emisorNombre,
            'fecha'       => $fecha,
            'mes'         => $mes,
            'anio'        => $anio,
            'subtotal'    => $subtotal,
            'moneda'      => $moneda,
            'cantidad'    => (int)$cantidadTotal,
            'descripcion' => implode(', ', array_slice($descripcionCompleta, 0, 3)) . (count($descripcionCompleta) > 3 ? '...' : ''),
            'insumoId'    => null,
            'total'       => $subtotal, // compatibilidad
        ];
    }

    private function getCatalogoCortes(): array
    {
        return DB::table('cortes')
            ->whereNull('deleted_at')
            ->select(DB::raw('MAX(CortesID) as id, NombreInsumo as nombre'))
            ->groupBy('NombreInsumo')
            ->get()
            ->map(fn($c) => ['id'=>(int)$c->id,'nombre'=>(string)$c->nombre,'norm'=>$this->normalizeText((string)$c->nombre)])
            ->toArray();
    }

    private function matchInsumo(string $descripcion, array $catalogo, string $emisor = ''): array
    {
        $dn = $this->normalizeText($descripcion);
        $en = $this->normalizeText($emisor);
        if ($dn === '' && $en === '') return [null, 0];

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            if ($cat['norm'] === $dn) return [$cat, 100];
        }

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            if (str_contains($dn, $cat['norm']) || str_contains($cat['norm'], $dn)) return [$cat, 95];
        }

        $palabrasDesc   = array_filter(explode(' ', $dn), fn($w) => mb_strlen($w) > 2);
        $palabrasEmisor = array_filter(explode(' ', $en), fn($w) => mb_strlen($w) > 2);
        $todasPalabras  = array_unique(array_merge($palabrasDesc, $palabrasEmisor));

        $mejorScore = 0;
        $mejorCat   = null;

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            $palabrasCat = array_filter(explode(' ', $cat['norm']), fn($w) => mb_strlen($w) > 2);
            if (empty($palabrasCat)) continue;

            $hits = 0;
            foreach ($palabrasCat as $pw) {
                foreach ($todasPalabras as $dw) {
                    if ($pw === $dw || str_contains($dw, $pw) || str_contains($pw, $dw)) { $hits++; break; }
                }
            }

            if ($hits === 0) continue;
            $pct = ($hits / count($palabrasCat)) * 100;
            if ($pct > $mejorScore) { $mejorScore = $pct; $mejorCat = $cat; }
        }

        if ($mejorScore >= 50) return [$mejorCat, $mejorScore];
        return [null, 0];
    }

    private function normalizeText(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'],['a','e','i','o','u','a','e','i','o','u','n'], $t);
        $t = preg_replace('/[^a-z0-9\s]/', '', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}