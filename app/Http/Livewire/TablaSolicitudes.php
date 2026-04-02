<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Solicitud;
use App\Models\Cotizacion;
use App\Models\Empleados;
use App\Models\SolicitudPasos;
use App\Models\DepartamentoRequerimientos;
use App\Models\SolicitudActivo;
use App\Models\SolicitudActivoCheckList;
use App\Models\Insumos;
use App\Models\Facturas;
use App\Models\Tickets;
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

    public array $usuarioSearch     = [];
    public array $usuarioOptions    = [];
    public array $usuarioSearchLock = [];

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

    private ?string $serialColumn            = null;
    private array   $checklistTemplatesCache = [];

    public function mount(): void
    {
        $this->serialColumn = $this->detectSerialColumn();
    }

    // ── HOOKS DE ARCHIVOS ─────────────────────────────────────────────────────
    // Solo XML. El PDF fue eliminado de solicitudes.

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

    // ── LIFECYCLE ─────────────────────────────────────────────────────────────

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

    public function render()
    {
        $user             = auth()->user();
        $empleadoActual   = $user ? Empleados::query()->where('Correo', $user->email)->first() : null;
        $empleadoActualId = $empleadoActual ? (int)$empleadoActual->EmpleadoID : null;

        $query = Solicitud::with(['empleadoid','cotizaciones','pasoSupervisor','pasoGerencia','pasoAdministracion']);

        if ($this->search) {
            $term = trim((string)$this->search);
            $query->where(fn($q) => $q->where('SolicitudID', 'like', "%{$term}%")
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

    // ── HYDRATION ─────────────────────────────────────────────────────────────

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
        $solicitud->facturasSubidas         = $facturasSubidas;
        $solicitud->totalFacturasNecesarias = $totalNecesarias;

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
                $activo = SolicitudActivo::query()->where('SolicitudID', $solicitud->SolicitudID)
                    ->where('CotizacionID', $cot->CotizacionID)->where('UnidadIndex', $i)->first();
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
        return ($ps && $ps->status === 'approved') && ($pg && $pg->status === 'approved') && ($pa && $pa->status === 'approved');
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

        $activos = SolicitudActivo::query()->whereIn('CotizacionID', $cotIds)
            ->whereNotNull('FacturaPath')->where('FacturaPath', '!=', '')->select('CotizacionID')->distinct()->get();

        if ($activos->isEmpty()) return [0, $totalNecesarias];

        $cotsConFactura  = $activos->pluck('CotizacionID')->toArray();
        $provsConFactura = $sel->whereIn('CotizacionID', $cotsConFactura)->pluck('Proveedor')->filter()->unique()->count();

        return [$provsConFactura, $totalNecesarias];
    }

    // ── APROBACIÓN ────────────────────────────────────────────────────────────

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

    private function decidirPaso(int $solicitudId, string $nivel, string $comentario, string $decision): void
    {
        $nivel = trim(strtolower($nivel));
        if (!in_array($nivel, self::VALID_STAGES, true)) throw new \Exception('Etapa inválida.');

        DB::transaction(function () use ($solicitudId, $nivel, $comentario, $decision) {
            $solicitud = Solicitud::findOrFail($solicitudId);
            $usuario   = auth()->user() ?? throw new \Exception('Sesión inválida.');
            $empleado  = Empleados::query()->where('Correo', $usuario->email)->firstOrFail();

            $step = SolicitudPasos::query()->where('solicitud_id', $solicitud->SolicitudID)
                ->where('stage', $nivel)->lockForUpdate()->firstOrFail();

            if ($step->status !== 'pending') throw new \Exception('Etapa ya resuelta.');

            $this->authorizeDecision($usuario, $empleado, $solicitud, $step, $nivel);

            $step->update([
                'status'                 => $decision,
                'comment'                => $comentario,
                'decided_at'             => now(),
                'decided_by_empleado_id' => (int)$empleado->EmpleadoID,
            ]);

            if ($decision === 'rejected') $solicitud->update(['Estatus' => 'Rechazada']);
        });
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

    // ── MODAL CANCELACIÓN ─────────────────────────────────────────────────────

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

        if (!$this->solicitudCancelarId) { $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']); return; }

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

    // ── MODAL ASIGNACIÓN ──────────────────────────────────────────────────────

    public function abrirModalAsignacion(int $solicitudId): void { $this->openAsignacion($solicitudId); }

    public function openAsignacion(int $solicitudId): void
    {
        try {
            $this->resetAsignacionState();
            $this->asignacionSolicitudId = $solicitudId;

            $seleccionadas = Cotizacion::query()->where('SolicitudID', $solicitudId)
                ->where('Estatus', 'Seleccionada')->orderBy('NumeroPropuesta')->get();

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
                if (!$proveedor) continue;
                if (!empty($rutasPorProveedor[$proveedor]['xml'])) continue;

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
                        'requiere_config'         => $saved->isNotEmpty(),
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

            $cotIds           = $seleccionadas->pluck('CotizacionID')->filter()->unique()->values()->all();
            $proveedoresTotal = $seleccionadas->pluck('Proveedor')->filter()->unique()->count();
            $this->modalEsSoloLectura = false;

            if ($proveedoresTotal > 0 && !empty($cotIds)) {
                $cotsConFactura = SolicitudActivo::query()->whereIn('CotizacionID', $cotIds)
                    ->whereNotNull('FacturaPath')->where('FacturaPath', '!=', '')
                    ->pluck('CotizacionID')->unique()->values()->all();
                $provsConFactura = $seleccionadas->whereIn('CotizacionID', $cotsConFactura)
                    ->pluck('Proveedor')->filter()->unique()->count();
                $this->modalEsSoloLectura = $provsConFactura >= $proveedoresTotal;
            }

            $this->modalAsignacionAbierto = true;
        } catch (\Throwable $e) {
            $this->resetAsignacionState();
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error abriendo asignación: ' . $e->getMessage()]);
        }
    }

    public function closeAsignacion(): void
    {
        if (!$this->modalEsSoloLectura && !empty($this->propuestasAsignacion) && $this->hayFacturaPendienteDeCarga()) {
            $this->confirmarCierreModalAbierto = true;
            return;
        }
        $this->resetAsignacionState();
    }

    public function forzarCloseAsignacion(): void { $this->resetAsignacionState(); }

    private function hayFacturaPendienteDeCarga(): bool
    {
        $revisados = [];
        foreach ($this->propuestasAsignacion as $pIndex => $p) {
            $prov = $p['proveedor'] ?? '';
            if (in_array($prov, $revisados, true)) continue;
            $revisados[] = $prov;

            $tieneAlgo = false;
            foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                if (!empty($u['factura_xml_path']) || !empty($this->facturaXml[$pIndex][$uIndex])) {
                    $tieneAlgo = true; break;
                }
            }
            if (!$tieneAlgo) return true;
        }
        return false;
    }

    private function handleUsuarioSearchUpdated(int $pIndex, int $uIndex, string $term): void
    {
        $term = trim($term);
        $this->usuarioOptions[$pIndex]          = $this->usuarioOptions[$pIndex] ?? [];
        $this->usuarioOptions[$pIndex][$uIndex] = [];
        if ($term === '') return;

        $this->usuarioOptions[$pIndex][$uIndex] = Empleados::query()
            ->where('Estado', true)
            ->where(fn($q) => $q->where('NombreEmpleado', 'like', "%{$term}%")
                ->orWhere('Correo', 'like', "%{$term}%")
                ->when(ctype_digit($term), fn($q) => $q->orWhere('EmpleadoID', (int)$term)))
            ->limit(8)->get(['EmpleadoID','NombreEmpleado','Correo'])
            ->map(fn($e) => ['id'=>(int)$e->EmpleadoID,'name'=>(string)$e->NombreEmpleado,'correo'=>(string)$e->Correo])
            ->toArray();
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
    }

    public function guardarAsignacion(): void { $this->persistAsignacion(false, false); }

    public function persistAsignacion($strict = false, $closeAfter = false): void
    {
        $strict = (bool)$strict; $closeAfter = (bool)$closeAfter;

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

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!$this->shouldPersistUnit($pIndex, $uIndex, $u)) continue;

                        $dataUpdate = [
                            'NumeroPropuesta' => (int)($p['numeroPropuesta'] ?? 0),
                            'FechaEntrega'    => !empty($u['fecha_entrega']) ? $u['fecha_entrega'] : null,
                            'EmpleadoID'      => !empty($u['empleado_id']) ? (int)$u['empleado_id'] : null,
                            'DepartamentoID'  => !empty($u['departamento_id']) ? (int)$u['departamento_id'] : null,
                        ];
                        if ($this->serialColumn) $dataUpdate[$this->serialColumn] = (string)($u['serial'] ?? '');

                        $activo = SolicitudActivo::updateOrCreate(
                            ['SolicitudID'=>(int)$this->asignacionSolicitudId,'CotizacionID'=>(int)($p['cotizacionId']??0),'UnidadIndex'=>(int)($u['unidadIndex']??($uIndex+1))],
                            $dataUpdate
                        );

                        $proveedor = $p['proveedor'] ?? null;
                        $baseDir   = "solicitudes/{$this->asignacionSolicitudId}/activos/{$activo->SolicitudActivoID}";
                        $rutaXml   = null;

                        $xmlFile = ($proveedor && isset($xmlPorProveedor[$proveedor])) ? $xmlPorProveedor[$proveedor] : ($this->facturaXml[$pIndex][$uIndex] ?? null);
                        if ($xmlFile) {
                            $ext  = strtolower((string)$xmlFile->getClientOriginalExtension());
                            $mime = strtolower((string)$xmlFile->getMimeType());
                            if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml','application/xml','text/plain'], true))
                                throw new \Exception('El archivo XML de factura no es válido.');
                            $rutaXml = $xmlFile->storeAs($baseDir, 'factura.xml', 'public');
                        }

                        if (!$rutaXml && !empty($u['factura_xml_path'])) $rutaXml = $u['factura_xml_path'];

                        if (!$rutaXml) {
                            $legacy = ($proveedor && isset($facturaPorProveedor[$proveedor])) ? $facturaPorProveedor[$proveedor] : ($this->facturas[$pIndex][$uIndex] ?? null);
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

                        $localParsed    = $this->xmlParseado[$pIndex][$uIndex] ?? null;
                        $providerParsed = $proveedor ? $this->buscarXmlParseadoPorProveedor($pIndex, $proveedor) : null;

                        $parsed = null;
                        if ($localParsed && empty($localParsed['error']) && empty($localParsed['es_pdf'])) $parsed = $localParsed;
                        elseif ($providerParsed && empty($providerParsed['es_pdf'])) $parsed = $providerParsed;
                        elseif ($localParsed && empty($localParsed['error'])) $parsed = $localParsed;
                        else $parsed = $providerParsed;

                        if ($parsed && empty($parsed['error']) && !empty($parsed['conceptos'])) {
                            $uuid = trim((string)($parsed['uuid'] ?? ''));
                            foreach ($parsed['conceptos'] as $concepto) {
                                $nombreConcepto = mb_substr(trim((string)($concepto['nombre'] ?? '')), 0, 300);
                                if ($nombreConcepto === '') continue;
                                try {
                                    $facturaGlobal = $uuid ? Facturas::query()->where('UUID', $uuid)->where('Nombre', $nombreConcepto)->first() : null;
                                    if ($facturaGlobal) {
                                        $upd = [];
                                        if ($rutaXml && empty($facturaGlobal->ArchivoRuta)) $upd['ArchivoRuta'] = $rutaXml;
                                        if (!empty($upd)) $facturaGlobal->update($upd);
                                        if ((int)$facturaGlobal->SolicitudID !== (int)$this->asignacionSolicitudId) {
                                            Facturas::firstOrCreate(
                                                ['SolicitudID'=>(int)$this->asignacionSolicitudId,'UUID'=>$uuid,'Nombre'=>$nombreConcepto],
                                                [
                                                    'Importe'    => is_numeric($concepto['importe'] ?? null) ? (float)$concepto['importe'] : 0,
                                                    'Costo'      => is_numeric($concepto['costo']   ?? null) ? (float)$concepto['costo']   : 0,
                                                    'Mes'        => !empty($parsed['mes'])  ? (int)$parsed['mes']  : null,
                                                    'Anio'       => !empty($parsed['anio']) ? (int)$parsed['anio'] : null,
                                                    'InsumoID'   => $concepto['insumoId'] ?? null,
                                                    'Emisor'     => $parsed['emisor'] ?? '',
                                                    'ArchivoRuta'=> $rutaXml ?? $facturaGlobal->ArchivoRuta ?? '',
                                                    'PdfRuta'    => '',
                                                ]
                                            );
                                        }
                                    } else {
                                        Facturas::updateOrCreate(
                                            ['SolicitudID'=>(int)$this->asignacionSolicitudId,'UUID'=>$uuid,'Nombre'=>$nombreConcepto],
                                            [
                                                'Importe'    => is_numeric($concepto['importe'] ?? null) ? (float)$concepto['importe'] : 0,
                                                'Costo'      => is_numeric($concepto['costo']   ?? null) ? (float)$concepto['costo']   : 0,
                                                'Mes'        => !empty($parsed['mes'])  ? (int)$parsed['mes']  : null,
                                                'Anio'       => !empty($parsed['anio']) ? (int)$parsed['anio'] : null,
                                                'InsumoID'   => $concepto['insumoId'] ?? null,
                                                'Emisor'     => $parsed['emisor'] ?? '',
                                                'ArchivoRuta'=> $rutaXml ?? '',
                                                'PdfRuta'    => '',
                                            ]
                                        );
                                    }
                                } catch (\Throwable $fe) {
                                    \Log::error('[Asignacion] ERROR factura XML', ['concepto'=>$nombreConcepto,'error'=>$fe->getMessage()]);
                                    throw $fe;
                                }
                            }
                        }

                        if (!empty($u['requiere_config'])) {
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
                        }
                    }
                }
            });

            $this->facturaXml = []; $this->facturas = []; $this->xmlParseado = [];
            $this->dispatchBrowserEvent('swal:success', ['message' => $strict ? 'Asignación finalizada' : 'Avance guardado correctamente']);
            if ($closeAfter) $this->closeAsignacion();
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error guardando: ' . $e->getMessage()]);
        }
    }

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

    private function validateAsignacionPayload(bool $strict): array
    {
        $errors = [];
        foreach ($this->propuestasAsignacion as $pIndex => $p) {
            foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                $base = "propuestasAsignacion.$pIndex.unidades.$uIndex";
                if ($strict) {
                    if (empty($u['serial']))        $errors["$base.serial"]        = 'El Serial es obligatorio.';
                    if (empty($u['fecha_entrega'])) $errors["$base.fecha_entrega"] = 'La fecha de entrega es obligatoria.';
                    if (empty($u['empleado_id']))   $errors["$base.empleado_id"]   = 'El usuario final es obligatorio.';
                }
                if (!empty($u['requiere_config'])) {
                    foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                        foreach (($u['checklist'][$catKey] ?? []) as $idx => $item) {
                            if (!empty($item['realizado']) && empty($item['responsable']))
                                $errors["$base.checklist.$catKey.$idx.responsable"] = 'Responsable obligatorio.';
                        }
                    }
                }
                if (isset($this->facturaXml[$pIndex][$uIndex]) && $this->facturaXml[$pIndex][$uIndex]) {
                    try {
                        $e = strtolower((string)$this->facturaXml[$pIndex][$uIndex]->getClientOriginalExtension());
                        if ($e !== 'xml') $errors["facturaXml.$pIndex.$uIndex"] = 'El archivo debe ser XML.';
                    } catch (\Throwable) {}
                }
                if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
                    $file = $this->facturas[$pIndex][$uIndex];
                    $ext  = strtolower((string)$file->getClientOriginalExtension());
                    $mime = strtolower((string)$file->getMimeType());
                    if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml','application/xml','text/plain'], true))
                        $errors["facturas.$pIndex.$uIndex"] = 'La factura debe ser XML.';
                }
            }
        }
        return $errors;
    }

    private function checklistTemplateByDept(?int $deptId): array
    {
        if (!$deptId) return [];
        if (isset($this->checklistTemplatesCache[$deptId])) return $this->checklistTemplatesCache[$deptId];

        $reqs = DepartamentoRequerimientos::query()->byDepartamentos($deptId)->seleccionados()
            ->orderBy('categoria')->orderBy('nombre')->get(['id','categoria','nombre']);

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
                $row = $map->get($reqId);
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
        $empleado = Empleados::query()->whereRaw('LOWER(NombreEmpleado) = ?', [mb_strtolower(trim((string)$user->name))])->first();
        return $empleado && !empty($empleado->Correo) ? (string)Str::before(strtolower($empleado->Correo), '@') : '';
    }

    private function resetAsignacionState(): void
    {
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
    }

    private function detectSerialColumn(): ?string
    {
        if (!Schema::hasTable('solicitud_activos')) return null;
        foreach (['serial','Serial','NumeroSerie','NumSerie'] as $col) {
            if (Schema::hasColumn('solicitud_activos', $col)) return $col;
        }
        return null;
    }

    private function shouldPersistUnit(int $pIndex, int $uIndex, array $u): bool
    {
        if (!empty($u['requiere_config']) || !empty($u['serial']) || !empty($u['fecha_entrega']) || !empty($u['empleado_id']) || !empty($u['departamento_id'])) return true;
        foreach (['facturas','facturaXml'] as $prop) {
            if (isset($this->$prop[$pIndex][$uIndex]) && $this->$prop[$pIndex][$uIndex]) return true;
        }
        if (isset($this->xmlParseado[$pIndex][$uIndex]) && empty($this->xmlParseado[$pIndex][$uIndex]['error'])) return true;
        return false;
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
                'departamento_id'     => $r->DepartamentoID     ? (int)$r->DepartamentoID     : null,
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
            'DepartamentoID'     => $row->DepartamentoID     ? (int)$row->DepartamentoID     : null,
            'NombreDepartamento' => $row->NombreDepartamento ? (string)$row->NombreDepartamento : null,
        ];
    }

    private function lockUsuarioSearch(int $p, int $u): void    { $this->usuarioSearchLock[$p][$u] = true; }
    private function isUsuarioSearchLocked(int $p, int $u): bool { return !empty($this->usuarioSearchLock[$p][$u]); }
    private function unlockUsuarioSearch(int $p, int $u): void   { if (isset($this->usuarioSearchLock[$p][$u])) $this->usuarioSearchLock[$p][$u] = false; }

    // ── CHECKLIST ─────────────────────────────────────────────────────────────

    public function marcarChecklist(int $pIndex, int $uIndex, string $catKey, int $idx): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx])) return;

        $realizado = !((bool)($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado'] ?? false));
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado']   = $realizado;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] = $realizado ? $this->currentUserPrefix() : '';

        $activoId = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] ?? null;
        $reqId    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['req_id'] ?? null;

        if ($activoId && $reqId) {
            try {
                SolicitudActivoCheckList::updateOrCreate(
                    ['SolicitudActivoID'=>(int)$activoId,'DepartamentoRequerimientoID'=>(int)$reqId],
                    ['completado'=>$realizado,'responsable'=>$realizado ? $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] : null]
                );
            } catch (\Throwable $e) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al guardar el checklist: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Finaliza la configuración de una unidad y crea el ticket de instalación.
     * NO requiere que el checklist esté completamente marcado — se puede finalizar
     * con cualquier estado del checklist.
     */
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
                    'FechaEntrega'            => !empty($unidad['fecha_entrega']) ? $unidad['fecha_entrega'] : null,
                    'EmpleadoID'              => !empty($unidad['empleado_id']) ? (int)$unidad['empleado_id'] : null,
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
                'message' => "Ticket de instalación creado.\n\n⚠️ No olvides subir el XML de factura.",
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
        if (!$solicitanteEmpleadoId && $this->asignacionSolicitudId) {
            $solicitanteEmpleadoId = Solicitud::find($this->asignacionSolicitudId)?->EmpleadoID;
        }

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

        if (Tickets::query()->where('Descripcion', 'like', $titulo . '%')
            ->where('Descripcion', 'like', '%Solicitud #' . $this->asignacionSolicitudId . '%')->exists()) return;

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

    // ── PARSE XML ─────────────────────────────────────────────────────────────

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
        $fecha   = (string)($attrs['Fecha'] ?? '');
        $moneda  = (string)($attrs['Moneda'] ?? 'MXN');
        $total   = (string)($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0');

        $emisorNode   = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor') ?: $xml->xpath('//cfdi:Emisor');
        $emisorNombre = $emisorNode ? (string)$emisorNode[0]['Nombre'] : '';

        $uuid = '';
        $timbre = $xml->xpath('//tfd:TimbreFiscalDigital') ?: [];
        if (!empty($timbre)) $uuid = strtoupper(trim((string)($timbre[0]['UUID'] ?? '')));

        $mes = null; $anio = null;
        if ($fecha) {
            try { $cf = Carbon::parse($fecha); $mes = (int)$cf->format('n'); $anio = (int)$cf->format('Y'); }
            catch (\Throwable) {}
        }

        $conceptoNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: $xml->xpath('//cfdi:Concepto') ?: [];
        $catalogo      = $this->getCatalogoInsumos();
        $conceptos     = [];

        foreach ($conceptoNodes as $nodo) {
            $ca          = $nodo->attributes();
            $descripcion = (string)($ca['Descripcion'] ?? '');
            $valorUnit   = (string)($ca['ValorUnitario'] ?? '0');
            $importe     = (string)($ca['Importe']       ?? '0');
            $cantidad    = (string)($ca['Cantidad']      ?? '1');

            [$best, $score] = $this->matchInsumo($descripcion, $catalogo);
            if (($best === null || $score < 60) && $emisorNombre) {
                $normEmisor = $this->normalizeText($emisorNombre);
                if (str_contains($normEmisor, 'starlink') || str_contains($normEmisor, 'space exploration')) {
                    $star = $this->matchPorKeyword('starlink', $catalogo) ?? $this->matchPorKeyword('internet satelital', $catalogo);
                    if ($star) { $best = $star; $score = 95; }
                }
            }

            $conceptos[] = ['nombre'=>$descripcion,'costo'=>$valorUnit,'importe'=>$importe,'cantidad'=>$cantidad,'insumoId'=>$best['id']??null];
        }

        return ['version'=>$version,'uuid'=>$uuid,'emisor'=>$emisorNombre,'fecha'=>$fecha,'mes'=>$mes,'anio'=>$anio,'total'=>$total,'moneda'=>$moneda,'conceptos'=>$conceptos];
    }

    private function getCatalogoInsumos(): array
    {
        return Insumos::query()->whereNull('deleted_at')->get(['ID','NombreInsumo'])
            ->map(fn($i) => ['id'=>(int)$i->ID,'nombre'=>(string)$i->NombreInsumo,'norm'=>$this->normalizeText((string)$i->NombreInsumo)])
            ->toArray();
    }

    private function matchInsumo(string $descripcion, array $catalogo): array
    {
        $dn = $this->normalizeText($descripcion);
        if ($dn === '') return [null, 0];
        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            if ($cat['norm'] === $dn || str_contains($dn, $cat['norm']) || str_contains($cat['norm'], $dn)) return [$cat, 100];
        }
        $best = null; $score = 0;
        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            similar_text($dn, $cat['norm'], $s);
            if ($s > $score) { $score = $s; $best = $cat; }
        }
        return [$best, $score];
    }

    private function matchPorKeyword(string $keyword, array $catalogo): ?array
    {
        $kw = $this->normalizeText($keyword);
        foreach ($catalogo as $cat) { if (str_contains($cat['norm'], $kw)) return $cat; }
        return null;
    }

    private function normalizeText(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'],['a','e','i','o','u','a','e','i','o','u','n'], $t);
        $t = preg_replace('/[^a-z0-9\s]/', '', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}