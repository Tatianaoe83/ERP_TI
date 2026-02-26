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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TablaSolicitudes extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $filtroEstatus = '';
    public string $search = '';
    public int $perPage = 10;

    protected $paginationTheme = 'tailwind';

    public bool $modalAsignacionAbierto = false;
    public ?int $asignacionSolicitudId = null;

    public array $propuestasAsignacion = [];
    public array $facturas = [];

    public array $usuarioSearch = [];
    public array $usuarioOptions = [];

    public array $usuarioSearchLock = [];

    protected $listeners = [
        'aprobarSolicitudConfirmed' => 'aprobar',
        'rechazarSolicitudConfirmed' => 'rechazar',
    ];

    public function updatedFacturas($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) {
            return;
        }

        $pIndexOrigen = (int) $parts[0];
        $uIndexOrigen = (int) $parts[1];

        $facturaSubida = $this->facturas[$pIndexOrigen][$uIndexOrigen] ?? null;

        if (!$facturaSubida) {
            return;
        }

        $proveedorOrigen = $this->propuestasAsignacion[$pIndexOrigen]['proveedor'] ?? null;

        if (!$proveedorOrigen) {
            return;
        }

        $propuestasConMismoProveedor = collect($this->propuestasAsignacion)
            ->filter(fn($p) => ($p['proveedor'] ?? '') === $proveedorOrigen)
            ->count();

        if ($propuestasConMismoProveedor <= 1) {
            return;
        }

        foreach ($this->propuestasAsignacion as $pIndex => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $uIndex) {
                    $this->facturas[$pIndex][$uIndex] = $facturaSubida;
                }
            }
        }

        $this->dispatchBrowserEvent('swal:info', [
            'message' => "Factura aplicada a todas las unidades de: {$proveedorOrigen}"
        ]);
    }

    private const VALID_STAGES = ['supervisor', 'gerencia', 'administracion'];

    private const STAGE_PERMISSIONS = [
        'gerencia' => 'aprobar-solicitudes-gerencia',
        'administracion' => 'aprobar-solicitudes-administracion',
    ];

    private ?string $serialColumn = null;
    private array $checklistTemplatesCache = [];

    public function mount(): void
    {
        $this->serialColumn = $this->detectSerialColumn();
    }

    public function updatingFiltroEstatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updated($name, $value): void
    {
        $name = (string) $name;

        if (str_starts_with($name, 'usuarioSearch.')) {
            $parts = explode('.', $name);
            if (count($parts) === 3) {
                $pIndex = (int) $parts[1];
                $uIndex = (int) $parts[2];

                if ($this->isUsuarioSearchLocked($pIndex, $uIndex)) {
                    $this->unlockUsuarioSearch($pIndex, $uIndex);
                    return;
                }

                $this->handleUsuarioSearchUpdated($pIndex, $uIndex, (string) $value);
            }
            return;
        }

        if (str_starts_with($name, 'propuestasAsignacion.')) {
            $parts = explode('.', $name);

            if (
                count($parts) === 8 &&
                $parts[2] === 'unidades' &&
                $parts[4] === 'checklist' &&
                $parts[7] === 'realizado'
            ) {
                $pIndex = (int) $parts[1];
                $uIndex = (int) $parts[3];
                $catKey = (string) $parts[5];
                $idx = (int) $parts[6];

                if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx])) {
                    return;
                }

                $item = &$this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx];

                if (!empty($item['realizado']) && empty($item['responsable'])) {
                    $item['responsable'] = $this->currentUserPrefix();
                }

                if (empty($item['realizado'])) {
                    $item['responsable'] = '';
                }
            }
        }
    }

    public function render()
    {
        $user = auth()->user();
        $empleadoActual = $user ? Empleados::query()->where('Correo', $user->email)->first() : null;
        $empleadoActualId = $empleadoActual ? (int) $empleadoActual->EmpleadoID : null;

        $query = Solicitud::with([
            'empleadoid',
            'cotizaciones',
            'pasoSupervisor',
            'pasoGerencia',
            'pasoAdministracion',
        ]);

        if ($this->search) {
            $term = trim((string) $this->search);
            $query->where(function ($q) use ($term) {
                $q->where('SolicitudID', 'like', "%{$term}%")
                    ->orWhere('Motivo', 'like', "%{$term}%")
                    ->orWhereHas('empleadoid', function ($subQ) use ($term) {
                        $subQ->where('NombreEmpleado', 'like', "%{$term}%");
                    });
            });
        }

        $query->orderBy('created_at', 'desc');

        // Obtener todas las solicitudes (para poder filtrar por estatusDisplay)
        $solicitudesRaw = $query->get();

        // Procesar solicitudes
        $solicitudesProcesadas = $solicitudesRaw->map(function ($solicitud) use ($user, $empleadoActualId) {
            return $this->hydrateSolicitudRow($solicitud, $user, $empleadoActualId);
        });

        // Filtrar por estatus display si es necesario
        if ($this->filtroEstatus) {
            $solicitudesProcesadas = $solicitudesProcesadas->filter(function ($item) {
                return $item->estatusDisplay === $this->filtroEstatus;
            })->values();
        }

        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $items = $solicitudesProcesadas->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $solicitudesProcesadas->count(),
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.tabla-solicitudes', [
            'todasSolicitudes' => $paginator,
        ]);
    }

    private function hydrateSolicitudRow($solicitud, $user, ?int $empleadoActualId)
    {
        $nombreEmpleado = $solicitud->empleadoid->NombreEmpleado ?? '';
        $solicitud->nombreFormateado = $this->formatNombreEmpleado((string) $nombreEmpleado);

        [$estatusReal, $estaRechazada] = $this->resolveEstatusReal($solicitud);
        [$estatusDisplay, $colorEstatus] = $this->resolveEstatusDisplay($solicitud, $estatusReal);

        $solicitud->estatusReal = $estatusReal;
        $solicitud->estatusDisplay = $estatusDisplay;
        $solicitud->colorEstatus = $colorEstatus;
        $solicitud->recotizarPropuestasText = '';
        if ($estatusReal === 'Re-cotizar' && $solicitud->pasoGerencia && $solicitud->pasoGerencia->comment) {
            $comment = $solicitud->pasoGerencia->comment;
            if (str_starts_with($comment, 'RECOTIZAR|')) {
                $parts = explode('|', $comment, 3);
                $nums = isset($parts[1]) ? array_filter(array_map('trim', explode(',', $parts[1]))) : [];
                $solicitud->recotizarPropuestasText = $nums ? ' (Prop. ' . implode(', ', $nums) . ')' : '';
            }
        }

        $todasFirmaron = $this->allStepsApproved($solicitud);
        $supervisorAprobado = $solicitud->pasoSupervisor && $solicitud->pasoSupervisor->status === 'approved';
        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);

        // Verificar si todos los productos tienen ganador (consistente con el controller)
        $todosGanadores = $solicitud->todosProductosTienenGanador();

        // Cotizar se habilita cuando pasa el supervisor: TI sube cotizaciones, envía al gerente y sigue el flujo.
        $solicitud->puedeCotizar = (bool) (
            $supervisorAprobado
            && $user
            && !$estaRechazada
            && $estatusDisplay !== 'Aprobada'
            && !$todosGanadores
        );
        $solicitud->puedeSubirFactura = (bool) ($todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAsignar = (bool) ($todasFirmaron && $tieneSeleccionada && $user);

        $solicitud->puedeAprobar = false;
        $solicitud->nivelAprobacion = '';

        [$facturasSubidas, $totalNecesarias] = $this->contarFacturas($solicitud);
        $solicitud->facturasSubidas = $facturasSubidas;
        $solicitud->totalFacturasNecesarias = $totalNecesarias;

        $pasoSupervisor = $solicitud->pasoSupervisor;
        $pasoGerencia = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        if ($user && !$estaRechazada) {
            if (
                $estatusReal === 'Pendiente Aprobación Supervisor'
                && $pasoSupervisor
                && (int) $pasoSupervisor->approver_empleado_id === (int) $empleadoActualId
            ) {
                $solicitud->puedeAprobar = true;
                $solicitud->nivelAprobacion = 'supervisor';
            } elseif (
                $estatusReal === 'Pendiente Aprobación Gerencia'
                && $solicitud->GerenciaID
                && $user->can(self::STAGE_PERMISSIONS['gerencia'])
            ) {
                $solicitud->puedeAprobar = true;
                $solicitud->nivelAprobacion = 'gerencia';
            } elseif (
                $estatusReal === 'Pendiente Aprobación Administración'
                && $user->can(self::STAGE_PERMISSIONS['administracion'])
            ) {
                $solicitud->puedeAprobar = true;
                $solicitud->nivelAprobacion = 'administracion';
            }
        }

        return $solicitud;
    }

    private function formatNombreEmpleado(string $nombreEmpleado): string
    {
        $partes = preg_split('/\s+/', trim($nombreEmpleado));
        if (is_array($partes) && count($partes) >= 3) {
            array_splice($partes, 1, 1);
        }
        return (string) Str::of(implode(' ', $partes ?? []))->title();
    }

    private function resolveEstatusReal($solicitud): array
    {
        $pasoSupervisor = $solicitud->pasoSupervisor;
        $pasoGerencia = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        $estatusReal = $solicitud->Estatus ?? 'Pendiente';
        $estaRechazada = false;

        if (
            ($pasoSupervisor && $pasoSupervisor->status === 'rejected') ||
            ($pasoGerencia && $pasoGerencia->status === 'rejected') ||
            ($pasoAdministracion && $pasoAdministracion->status === 'rejected')
        ) {
            return ['Rechazada', true];
        }

        if (in_array($solicitud->Estatus, ['Aprobado', 'Aprobada'], true)) {
            return ['Aprobado', false];
        }

        if ($solicitud->Estatus === 'Re-cotizar') {
            return ['Re-cotizar', false];
        }

        if (in_array($solicitud->Estatus, ['Pendiente', 'En revisión', null, ''], true) || empty($solicitud->Estatus)) {
            if ($pasoSupervisor && $pasoSupervisor->status === 'approved') {
                if ($pasoGerencia && $pasoGerencia->status === 'approved') {
                    if ($pasoAdministracion && $pasoAdministracion->status === 'approved') {
                        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);
                        $cotizacionesCount = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;

                        $estatusReal = $tieneSeleccionada
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

        return [$estatusReal, $estaRechazada];
    }

    private function resolveEstatusDisplay($solicitud, string $estatusReal): array
    {
        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);

        if ($estatusReal === 'Rechazada') {
            return ['Rechazada', 'bg-red-50 text-red-800 border border-red-200'];
        }

        if ($estatusReal === 'Aprobado') {
            return ['Aprobada', 'bg-emerald-50 text-emerald-800 border border-emerald-200'];
        }

        if ($estatusReal === 'Cotizaciones Enviadas') {
            return ['Cotizaciones Enviadas', 'bg-blue-50 text-blue-800 border border-blue-200'];
        }

        if ($estatusReal === 'Re-cotizar') {
            return ['Re-cotizar', 'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700'];
        }

        if ($estatusReal === 'Completada') {
            return ['En revisión', 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700'];
        }

        if ($estatusReal === 'Pendiente Cotización TI') {
            return ['Pendiente', 'bg-amber-50 text-amber-800 border border-amber-200'];
        }

        if (in_array($estatusReal, [
            'Pendiente Aprobación Supervisor',
            'Pendiente Aprobación Gerencia',
            'Pendiente Aprobación Administración',
        ], true)) {
            return ['En revisión', 'bg-white text-purple-700 border border-purple-200 dark:text-purple-700 dark:border-purple-700'];
        }

        return ['Pendiente', 'bg-gray-50 text-gray-700 border border-gray-200'];
    }

    private function hasSelectedCotizacion($solicitud): bool
    {
        return (bool) ($solicitud->cotizaciones
            ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada')->isNotEmpty()
            : false);
    }

    private function allStepsApproved($solicitud): bool
    {
        $pasoSupervisor = $solicitud->pasoSupervisor;
        $pasoGerencia = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        return ($pasoSupervisor && $pasoSupervisor->status === 'approved')
            && ($pasoGerencia && $pasoGerencia->status === 'approved')
            && ($pasoAdministracion && $pasoAdministracion->status === 'approved');
    }

    private function contarFacturas($solicitud): array
    {
        if (!$solicitud->cotizaciones || $solicitud->cotizaciones->isEmpty()) {
            return [0, 0];
        }

        $seleccionadas = $solicitud->cotizaciones->where('Estatus', 'Seleccionada');

        if ($seleccionadas->isEmpty()) {
            return [0, 0];
        }

        // Contar proveedores únicos (total de facturas necesarias)
        $proveedoresUnicos = $seleccionadas->pluck('Proveedor')->filter()->unique();
        $totalNecesarias = $proveedoresUnicos->count();

        if ($totalNecesarias === 0) {
            return [0, 0];
        }

        // Obtener los IDs de las cotizaciones seleccionadas
        $cotizacionIds = $seleccionadas->pluck('CotizacionID')->filter()->unique()->toArray();

        if (empty($cotizacionIds)) {
            return [0, $totalNecesarias];
        }

        // Buscar en solicitud_activos qué proveedores ya tienen factura subida
        $activos = SolicitudActivo::query()
            ->whereIn('CotizacionID', $cotizacionIds)
            ->whereNotNull('FacturaPath')
            ->where('FacturaPath', '!=', '')
            ->select('CotizacionID')
            ->distinct()
            ->get();

        if ($activos->isEmpty()) {
            return [0, $totalNecesarias];
        }

        // Obtener los proveedores de las cotizaciones que tienen factura
        $cotizacionesConFactura = $activos->pluck('CotizacionID')->toArray();
        $proveedoresConFactura = $seleccionadas
            ->whereIn('CotizacionID', $cotizacionesConFactura)
            ->pluck('Proveedor')
            ->filter()
            ->unique()
            ->count();

        return [$proveedoresConFactura, $totalNecesarias];
    }

    public function aprobar($id, $nivel, $comentario)
    {
        try {
            $this->decidirPaso((int) $id, (string) $nivel, (string) ($comentario ?? ''), 'approved');
            $this->dispatchBrowserEvent('swal:success', ['message' => 'Solicitud aprobada correctamente']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => $e->getMessage()]);
        }
    }

    public function rechazar($id, $nivel, $comentario)
    {
        try {
            $this->decidirPaso((int) $id, (string) $nivel, (string) ($comentario ?? ''), 'rejected');
            $this->dispatchBrowserEvent('swal:success', ['message' => 'Solicitud rechazada correctamente']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => $e->getMessage()]);
        }
    }

    private function decidirPaso(int $solicitudId, string $nivel, string $comentario, string $decision): void
    {
        $nivel = trim(strtolower($nivel));
        $this->assertValidStage($nivel);

        DB::transaction(function () use ($solicitudId, $nivel, $comentario, $decision) {
            $solicitud = Solicitud::findOrFail($solicitudId);

            $usuarioActual = auth()->user();
            if (!$usuarioActual) {
                throw new \Exception('Sesión inválida.');
            }

            $usuarioEmpleado = Empleados::query()
                ->where('Correo', $usuarioActual->email)
                ->firstOrFail();

            $step = SolicitudPasos::query()
                ->where('solicitud_id', $solicitud->SolicitudID)
                ->where('stage', $nivel)
                ->lockForUpdate()
                ->firstOrFail();

            if ($step->status !== 'pending') {
                throw new \Exception('Etapa ya resuelta.');
            }

            $this->authorizeDecision($usuarioActual, $usuarioEmpleado, $solicitud, $step, $nivel);

            $step->update([
                'status' => $decision,
                'comment' => $comentario,
                'decided_at' => now(),
                'decided_by_empleado_id' => (int) $usuarioEmpleado->EmpleadoID,
            ]);

            if ($decision === 'rejected') {
                $solicitud->update(['Estatus' => 'Rechazada']);
            }
        });
    }

    private function assertValidStage(string $nivel): void
    {
        if (!in_array($nivel, self::VALID_STAGES, true)) {
            throw new \Exception('Etapa inválida.');
        }
    }

    private function authorizeDecision($user, Empleados $empleado, Solicitud $solicitud, SolicitudPasos $step, string $nivel): void
    {
        $approverId = (int) ($step->approver_empleado_id ?? 0);

        if ($approverId > 0 && $approverId !== (int) $empleado->getAttribute('EmpleadoID')) {
            throw new \Exception('No tienes permiso para resolver esta etapa.');
        }

        if ($approverId > 0) {
            return;
        }

        if ($nivel === 'supervisor') {
            throw new \Exception('No tienes permiso para resolver esta etapa.');
        }

        $perm = self::STAGE_PERMISSIONS[$nivel] ?? null;
        if ($perm && !$user->can($perm)) {
            throw new \Exception('No tienes permiso para resolver esta etapa.');
        }

        if ($nivel === 'gerencia' && empty($solicitud->GerenciaID)) {
            throw new \Exception('Solicitud sin gerencia asignada.');
        }
    }

    public function abrirModalAsignacion(int $solicitudId): void
    {
        $this->openAsignacion($solicitudId);
    }

    public function openAsignacion(int $solicitudId): void
    {
        try {
            $this->resetAsignacionState();
            $this->asignacionSolicitudId = $solicitudId;

            $seleccionadas = Cotizacion::query()
                ->where('SolicitudID', $solicitudId)
                ->where('Estatus', 'Seleccionada')
                ->orderBy('NumeroPropuesta')
                ->get();

            if ($seleccionadas->isEmpty()) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'No hay cotizaciones Seleccionadas para asignar.']);
                return;
            }

            $activos = SolicitudActivo::query()
                ->where('SolicitudID', $solicitudId)
                ->get();

            $activosPorKey = $activos->keyBy(function ($a) {
                return (int) $a->CotizacionID . ':' . (int) $a->UnidadIndex;
            });

            $activoIds = $activos->pluck('SolicitudActivoID')->filter()->values()->all();

            $checklists = empty($activoIds)
                ? collect()
                : SolicitudActivoCheckList::query()
                ->whereIn('SolicitudActivoID', $activoIds)
                ->get()
                ->groupBy('SolicitudActivoID');

            $empleadosMap = $this->loadEmpleadosDeptMap(
                $activos->pluck('EmpleadoID')->filter()->unique()->values()->all()
            );

            $agrupadas = $seleccionadas->groupBy(function ($c) {
                return (int) ($c->NumeroPropuesta ?? 0);
            });

            $out = [];

            foreach ($agrupadas as $numeroPropuesta => $items) {
                $cot = $items->first();
                if (!$cot) {
                    continue;
                }

                $qty = max(1, (int) ($cot->Cantidad ?? 1));
                $unidades = [];

                for ($i = 1; $i <= $qty; $i++) {
                    $key = (int) $cot->CotizacionID . ':' . $i;
                    $activo = $activosPorKey->get($key);

                    $empleadoId = $activo ? (int) ($activo->EmpleadoID ?? 0) : 0;
                    $empleadoRow = $empleadoId && isset($empleadosMap[$empleadoId]) ? $empleadosMap[$empleadoId] : null;

                    $deptId = $activo && !empty($activo->DepartamentoID)
                        ? (int) $activo->DepartamentoID
                        : ($empleadoRow['departamento_id'] ?? null);

                    $template = $this->checklistTemplateByDept($deptId);
                    $saved = $activo ? ($checklists->get((int) $activo->SolicitudActivoID) ?? collect()) : collect();
                    $unidadChecklist = $this->applySavedChecklist($template, $saved);

                    $serialVal = '';
                    if ($activo && $this->serialColumn) {
                        $serialVal = (string) ($activo->{$this->serialColumn} ?? '');
                    }

                    $unidades[] = [
                        'unidadIndex' => $i,
                        'activoId' => $activo ? (int) $activo->SolicitudActivoID : null,
                        'serial' => $serialVal,
                        'factura_path' => $activo ? (string) ($activo->FacturaPath ?? '') : '',
                        'fecha_entrega' => $activo && $activo->FechaEntrega ? $activo->FechaEntrega->format('Y-m-d') : null,
                        'empleado_id' => $empleadoId ?: null,
                        'empleado_nombre' => $empleadoRow['nombre'] ?? null,
                        'departamento_id' => $deptId,
                        'departamento_nombre' => $empleadoRow['departamento_nombre'] ?? null,
                        'checklist_open' => true,
                        'checklist' => $unidadChecklist,
                    ];
                }

                $out[] = [
                    'numeroPropuesta' => (int) $numeroPropuesta,
                    'cotizacionId' => (int) $cot->CotizacionID,
                    'nombreEquipo' => (string) ($cot->NombreEquipo ?? 'Sin nombre'),
                    'proveedor' => (string) ($cot->Proveedor ?? 'Sin proveedor'),
                    'precioUnitario' => (string) ($cot->Precio ?? '0.00'),
                    'itemsTotal' => $qty,
                    'unidades' => $unidades,
                ];
            }

            $this->propuestasAsignacion = $out;

            $this->usuarioSearch = [];
            $this->usuarioOptions = [];
            foreach ($this->propuestasAsignacion as $pIndex => $p) {
                foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                    $this->usuarioSearch[$pIndex][$uIndex] = (string) ($u['empleado_nombre'] ?? '');
                    $this->usuarioOptions[$pIndex][$uIndex] = [];
                }
            }

            $this->modalAsignacionAbierto = true;
        } catch (\Throwable $e) {
            $this->resetAsignacionState();
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error abriendo asignación: ' . $e->getMessage()]);
        }
    }

    public function closeAsignacion(): void
    {
        $this->resetAsignacionState();
    }

    private function handleUsuarioSearchUpdated(int $pIndex, int $uIndex, string $term): void
    {
        $term = trim($term);

        $this->usuarioOptions[$pIndex] = $this->usuarioOptions[$pIndex] ?? [];
        $this->usuarioOptions[$pIndex][$uIndex] = [];

        if ($term === '') {
            return;
        }

        $rows = Empleados::query()
            ->where('Estado', true)
            ->where(function ($q) use ($term) {
                $q->where('NombreEmpleado', 'like', "%{$term}%")
                    ->orWhere('Correo', 'like', "%{$term}%");
                if (ctype_digit($term)) {
                    $q->orWhere('EmpleadoID', (int) $term);
                }
            })
            ->limit(8)
            ->get(['EmpleadoID', 'NombreEmpleado', 'Correo'])
            ->map(function ($e) {
                return [
                    'id' => (int) $e->EmpleadoID,
                    'name' => (string) $e->NombreEmpleado,
                    'correo' => (string) $e->Correo,
                ];
            })
            ->toArray();

        $this->usuarioOptions[$pIndex][$uIndex] = $rows;
    }

    public function seleccionarEmpleado(int $pIndex, int $uIndex, int $empleadoId): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex])) {
            throw new \Exception('Ítem inválido para asignación.');
        }

        $row = $this->getEmpleadoConDept($empleadoId);

        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_id'] = (int) $row['EmpleadoID'];
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_nombre'] = (string) $row['NombreEmpleado'];
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['departamento_id'] = $row['DepartamentoID'] ? (int) $row['DepartamentoID'] : null;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['departamento_nombre'] = $row['NombreDepartamento'] ?: null;

        $deptId = $row['DepartamentoID'] ? (int) $row['DepartamentoID'] : null;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'] = $this->checklistTemplateByDept($deptId);

        $this->lockUsuarioSearch($pIndex, $uIndex);
        $this->usuarioSearch[$pIndex] = $this->usuarioSearch[$pIndex] ?? [];
        $this->usuarioSearch[$pIndex][$uIndex] = (string) $row['NombreEmpleado'];

        $this->usuarioOptions[$pIndex] = $this->usuarioOptions[$pIndex] ?? [];
        $this->usuarioOptions[$pIndex][$uIndex] = [];
    }

    public function guardarAsignacion(): void
    {
        $this->persistAsignacion(false, false);
    }

    private function persistAsignacion(bool $strict, bool $closeAfter): void
    {
        if (!$this->asignacionSolicitudId) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']);
            return;
        }

        $errors = $this->validateAsignacionPayload($strict);
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        try {
            DB::transaction(function () {
                $facturaPorProveedor = [];

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    $proveedor = $p['proveedor'] ?? null;

                    if (!$proveedor) {
                        continue;
                    }

                    if (!isset($facturaPorProveedor[$proveedor])) {
                        foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                            if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
                                $facturaPorProveedor[$proveedor] = $this->facturas[$pIndex][$uIndex];
                                break;
                            }
                        }
                    }
                }

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!$this->shouldPersistUnit($pIndex, $uIndex, $u)) {
                            continue;
                        }

                        $dataUpdate = [
                            'NumeroPropuesta' => (int) ($p['numeroPropuesta'] ?? 0),
                            'FechaEntrega' => !empty($u['fecha_entrega']) ? $u['fecha_entrega'] : null,
                            'EmpleadoID' => !empty($u['empleado_id']) ? (int) $u['empleado_id'] : null,
                            'DepartamentoID' => !empty($u['departamento_id']) ? (int) $u['departamento_id'] : null,
                        ];

                        if ($this->serialColumn) {
                            $dataUpdate[$this->serialColumn] = (string) ($u['serial'] ?? '');
                        }

                        $activo = SolicitudActivo::updateOrCreate(
                            [
                                'SolicitudID' => (int) $this->asignacionSolicitudId,
                                'CotizacionID' => (int) ($p['cotizacionId'] ?? 0),
                                'UnidadIndex' => (int) ($u['unidadIndex'] ?? ($uIndex + 1)),
                            ],
                            $dataUpdate
                        );

                        $proveedor = $p['proveedor'] ?? null;
                        $facturaAGuardar = null;

                        if ($proveedor && isset($facturaPorProveedor[$proveedor])) {
                            $facturaAGuardar = $facturaPorProveedor[$proveedor];
                        } elseif (isset($this->facturas[$pIndex][$uIndex])) {
                            $facturaAGuardar = $this->facturas[$pIndex][$uIndex];
                        }

                        if ($facturaAGuardar) {
                            $file = $facturaAGuardar;

                            $ext = strtolower((string) $file->getClientOriginalExtension());
                            $mime = strtolower((string) $file->getMimeType());

                            $allowedMimes = ['application/pdf', 'application/x-pdf'];
                            if ($ext !== 'pdf' || !in_array($mime, $allowedMimes, true)) {
                                throw new \Exception('La factura debe ser PDF.');
                            }

                            $path = $file->storeAs(
                                "solicitudes/{$this->asignacionSolicitudId}/activos/{$activo->SolicitudActivoID}",
                                "factura.pdf",
                                'public'
                            );
                            $activo->update(['FacturaPath' => $path]);
                        }

                        foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                            foreach (($u['checklist'][$catKey] ?? []) as $item) {
                                $reqId = (int) ($item['req_id'] ?? 0);
                                if (!$reqId) {
                                    continue;
                                }

                                $realizado = !empty($item['realizado']);

                                SolicitudActivoCheckList::updateOrCreate(
                                    [
                                        'SolicitudActivoID' => (int) $activo->SolicitudActivoID,
                                        'DepartamentoRequerimientoID' => $reqId,
                                    ],
                                    [
                                        'completado' => (bool) $realizado,
                                        'responsable' => $realizado ? (string) ($item['responsable'] ?? '') : null,
                                    ]
                                );
                            }
                        }
                    }
                }
            });

            $this->dispatchBrowserEvent('swal:success', [
                'message' => $strict ? 'Asignación finalizada' : 'Avance guardado correctamente',
            ]);

            if ($closeAfter) {
                $this->closeAsignacion();
            }
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error guardando: ' . $e->getMessage()]);
        }
    }

    private function validateAsignacionPayload(bool $strict): array
    {
        $errors = [];

        foreach ($this->propuestasAsignacion as $pIndex => $p) {
            foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                $basePath = "propuestasAsignacion.$pIndex.unidades.$uIndex";

                if ($strict) {
                    if (empty($u['serial'])) {
                        $errors["$basePath.serial"] = 'El Serial es obligatorio.';
                    }
                    if (empty($u['fecha_entrega'])) {
                        $errors["$basePath.fecha_entrega"] = 'La fecha de entrega es obligatoria.';
                    }
                    if (empty($u['empleado_id'])) {
                        $errors["$basePath.empleado_id"] = 'El usuario final es obligatorio.';
                    }
                }

                foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                    foreach (($u['checklist'][$catKey] ?? []) as $idx => $item) {
                        if (!empty($item['realizado']) && empty($item['responsable'])) {
                            $errors["$basePath.checklist.$catKey.$idx.responsable"] = 'Responsable obligatorio si la tarea está marcada.';
                        }
                    }
                }

                if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
                    $file = $this->facturas[$pIndex][$uIndex];
                    $ext = strtolower((string) $file->getClientOriginalExtension());
                    $mime = strtolower((string) $file->getMimeType());
                    $allowedMimes = ['application/pdf', 'application/x-pdf'];

                    if ($ext !== 'pdf' || !in_array($mime, $allowedMimes, true)) {
                        $errors["facturas.$pIndex.$uIndex"] = 'La factura debe ser PDF.';
                    }
                }
            }
        }

        return $errors;
    }

    private function checklistTemplateByDept(?int $departamentoId): array
    {
        $payload = [];

        if (!$departamentoId) {
            return $payload;
        }

        if (isset($this->checklistTemplatesCache[$departamentoId])) {
            return $this->checklistTemplatesCache[$departamentoId];
        }

        $reqs = DepartamentoRequerimientos::query()
            ->byDepartamentos($departamentoId)
            ->seleccionados()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get(['id', 'categoria', 'nombre']);

        foreach ($reqs as $r) {
            $catKey = (string) $r->categoria;
            if (!isset($payload[$catKey])) {
                $payload[$catKey] = [];
            }
            $payload[$catKey][] = [
                'req_id' => (int) $r->id,
                'nombre' => (string) $r->nombre,
                'realizado' => false,
                'responsable' => '',
            ];
        }

        $this->checklistTemplatesCache[$departamentoId] = $payload;

        return $payload;
    }

    private function applySavedChecklist(array $template, $checklistRows): array
    {
        $map = collect($checklistRows)->keyBy(function ($x) {
            return (int) ($x->DepartamentoRequerimientoID ?? 0);
        });

        foreach (array_keys($template) as $catKey) {
            foreach ($template[$catKey] as $idx => $item) {
                $reqId = (int) ($item['req_id'] ?? 0);
                if (!$reqId) {
                    continue;
                }

                $row = $map->get($reqId);
                if ($row) {
                    $template[$catKey][$idx]['realizado'] = (bool) ($row->completado ?? false);
                    $template[$catKey][$idx]['responsable'] = (string) ($row->responsable ?? '');
                }
            }
        }

        return $template;
    }

    private function currentUserPrefix(): string
    {
        $user = auth()->user();
        if (!$user || empty($user->name)) {
            return '';
        }

        $nombreUser = trim((string) $user->name);

        $empleado = Empleados::query()->whereRaw('LOWER(NombreEmpleado) = ?', [mb_strtolower($nombreUser)])->first();
        $correo = $empleado?->Correo ?: $user->email;

        if (!$empleado || empty($empleado->Correo)) {
            return '';
        }

        return (string) Str::before(strtolower($empleado->Correo), '@');
    }

    public function getProveedorUnico(): ?string
    {
        $proveedores = collect($this->propuestasAsignacion)->pluck('proveedor')->unique();
        return $proveedores->count() === 1 ? $proveedores->first() : null;
    }

    private function resetAsignacionState(): void
    {
        $this->modalAsignacionAbierto = false;
        $this->asignacionSolicitudId = null;
        $this->propuestasAsignacion = [];
        $this->facturas = [];
        $this->usuarioSearch = [];
        $this->usuarioOptions = [];
        $this->usuarioSearchLock = [];
        $this->checklistTemplatesCache = [];
    }

    private function detectSerialColumn(): ?string
    {
        if (!Schema::hasTable('solicitud_activos')) {
            return null;
        }
        if (Schema::hasColumn('solicitud_activos', 'serial')) {
            return 'serial';
        }
        if (Schema::hasColumn('solicitud_activos', 'Serial')) {
            return 'Serial';
        }
        if (Schema::hasColumn('solicitud_activos', 'NumeroSerie')) {
            return 'NumeroSerie';
        }
        if (Schema::hasColumn('solicitud_activos', 'NumSerie')) {
            return 'NumSerie';
        }

        return null;
    }

    private function shouldPersistUnit(int $pIndex, int $uIndex, array $u): bool
    {
        if (!empty($u['serial'])) {
            return true;
        }
        if (!empty($u['fecha_entrega'])) {
            return true;
        }
        if (!empty($u['empleado_id'])) {
            return true;
        }
        if (!empty($u['departamento_id'])) {
            return true;
        }

        if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
            return true;
        }

        foreach (array_keys($u['checklist'] ?? []) as $catKey) {
            foreach (($u['checklist'][$catKey] ?? []) as $item) {
                if (!empty($item['realizado']) || !empty($item['responsable'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function loadEmpleadosDeptMap(array $empleadoIds): array
    {
        if (empty($empleadoIds)) {
            return [];
        }

        $rows = Empleados::query()
            ->withTrashed()
            ->from('empleados as e')
            ->leftJoin('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->whereIn('e.EmpleadoID', $empleadoIds)
            ->get([
                'e.EmpleadoID',
                'e.NombreEmpleado',
                'e.Correo',
                'd.DepartamentoID',
                'd.NombreDepartamento',
            ]);

        $map = [];
        foreach ($rows as $r) {
            $id = (int) $r->EmpleadoID;
            $map[$id] = [
                'nombre' => (string) ($r->NombreEmpleado ?? ''),
                'correo' => (string) ($r->Correo ?? ''),
                'departamento_id' => $r->DepartamentoID ? (int) $r->DepartamentoID : null,
                'departamento_nombre' => $r->NombreDepartamento ? (string) $r->NombreDepartamento : null,
            ];
        }
        return $map;
    }

    private function getEmpleadoConDept(int $empleadoId): array
    {
        $row = Empleados::query()
            ->withTrashed()
            ->from('empleados as e')
            ->leftJoin('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->where('e.EmpleadoID', $empleadoId)
            ->first([
                'e.EmpleadoID',
                'e.NombreEmpleado',
                'e.Correo',
                'd.DepartamentoID',
                'd.NombreDepartamento',
            ]);

        if (!$row) {
            throw new \Exception('Empleado no encontrado.');
        }

        return [
            'EmpleadoID' => (int) $row->EmpleadoID,
            'NombreEmpleado' => (string) ($row->NombreEmpleado ?? ''),
            'Correo' => (string) ($row->Correo ?? ''),
            'DepartamentoID' => $row->DepartamentoID ? (int) $row->DepartamentoID : null,
            'NombreDepartamento' => $row->NombreDepartamento ? (string) $row->NombreDepartamento : null,
        ];
    }

    private function lockUsuarioSearch(int $pIndex, int $uIndex): void
    {
        $this->usuarioSearchLock[$pIndex] = $this->usuarioSearchLock[$pIndex] ?? [];
        $this->usuarioSearchLock[$pIndex][$uIndex] = true;
    }

    private function isUsuarioSearchLocked(int $pIndex, int $uIndex): bool
    {
        return !empty($this->usuarioSearchLock[$pIndex][$uIndex]);
    }

    private function unlockUsuarioSearch(int $pIndex, int $uIndex): void
    {
        if (isset($this->usuarioSearchLock[$pIndex][$uIndex])) {
            $this->usuarioSearchLock[$pIndex][$uIndex] = false;
        }
    }
}
