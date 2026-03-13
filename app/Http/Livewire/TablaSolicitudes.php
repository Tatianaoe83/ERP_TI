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
    public ?int  $asignacionSolicitudId  = null;

    public array $propuestasAsignacion = [];
    public array $facturas             = [];   // legacy

    public array $facturaXml  = [];   // $facturaXml[pIndex][uIndex]
    public array $facturaPdf  = [];   // $facturaPdf[pIndex][uIndex]
    public array $xmlParseado = [];   // $xmlParseado[pIndex][uIndex]

    public array $usuarioSearch     = [];
    public array $usuarioOptions    = [];
    public array $usuarioSearchLock = [];

    // ── Cancelación ──────────────────────────────────────────────────────────
    public bool   $modalCancelacionAbierto = false;
    public ?int   $solicitudCancelarId     = null;
    public string $motivoCancelacion       = '';

    protected $listeners = [
        'aprobarSolicitudConfirmed'  => 'aprobar',
        'rechazarSolicitudConfirmed' => 'rechazar',
    ];

    // ══════════════════════════════════════════════════════════════════════════
    // Hooks de archivos
    // ══════════════════════════════════════════════════════════════════════════

    public function updatedFacturaXml($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        $pIndex = (int) $parts[0];
        $uIndex = (int) $parts[1];

        $file = $this->facturaXml[$pIndex][$uIndex] ?? null;
        if (!$file) return;

        try {
            $this->xmlParseado[$pIndex][$uIndex] = $this->parsearCfdi($file->getRealPath());
        } catch (\Throwable $e) {
            $this->xmlParseado[$pIndex][$uIndex] = ['error' => $e->getMessage()];
        }

        $proveedorOrigen = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
        if (!$proveedorOrigen) return;

        $coincidentes = collect($this->propuestasAsignacion)
            ->filter(fn($p) => ($p['proveedor'] ?? '') === $proveedorOrigen)
            ->count();

        if ($coincidentes <= 1) return;

        foreach ($this->propuestasAsignacion as $pi => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $ui) {
                    if ($pi === $pIndex && $ui === $uIndex) continue;
                    $this->facturaXml[$pi][$ui]  = $file;
                    $this->xmlParseado[$pi][$ui] = $this->xmlParseado[$pIndex][$uIndex];
                }
            }
        }

        $this->dispatchBrowserEvent('swal:info', [
            'message' => "XML detectado y aplicado a todas las unidades de: {$proveedorOrigen}",
        ]);
    }

    public function updatedFacturaPdf($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        $pIndex = (int) $parts[0];
        $uIndex = (int) $parts[1];

        $file = $this->facturaPdf[$pIndex][$uIndex] ?? null;
        if (!$file) return;

        $proveedorOrigen = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
        if (!$proveedorOrigen) return;

        foreach ($this->propuestasAsignacion as $pi => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $ui) {
                    if ($pi === $pIndex && $ui === $uIndex) continue;
                    $this->facturaPdf[$pi][$ui] = $file;
                }
            }
        }
    }

    public function updatedFacturas($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        $pIndexOrigen = (int) $parts[0];
        $uIndexOrigen = (int) $parts[1];

        $facturaSubida = $this->facturas[$pIndexOrigen][$uIndexOrigen] ?? null;
        if (!$facturaSubida) return;

        $proveedorOrigen = $this->propuestasAsignacion[$pIndexOrigen]['proveedor'] ?? null;
        if (!$proveedorOrigen) return;

        $propuestasConMismoProveedor = collect($this->propuestasAsignacion)
            ->filter(fn($p) => ($p['proveedor'] ?? '') === $proveedorOrigen)
            ->count();

        if ($propuestasConMismoProveedor <= 1) return;

        foreach ($this->propuestasAsignacion as $pIndex => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $uIndex) {
                    $this->facturas[$pIndex][$uIndex] = $facturaSubida;
                }
            }
        }

        $this->dispatchBrowserEvent('swal:info', [
            'message' => "Factura aplicada a todas las unidades de: {$proveedorOrigen}",
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Parseo CFDI — Mes como número entero (1-12)
    // ══════════════════════════════════════════════════════════════════════════

    private function parsearCfdi(string $rutaArchivo): array
    {
        $contenido = file_get_contents($rutaArchivo);
        if ($contenido === false) throw new \Exception('No se pudo leer el archivo XML.');

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $errores = array_map(fn($e) => $e->message, libxml_get_errors());
            libxml_clear_errors();
            throw new \Exception('XML inválido: ' . implode(', ', $errores));
        }

        $xml->registerXPathNamespace('cfdi',  'http://www.sat.gob.mx/cfd/4');
        $xml->registerXPathNamespace('cfdi3', 'http://www.sat.gob.mx/cfd/3');
        $xml->registerXPathNamespace('tfd',   'http://www.sat.gob.mx/TimbreFiscalDigital');

        $attrs   = $xml->attributes();
        $version = (string) ($attrs['Version'] ?? $attrs['version'] ?? '3.3');
        $nsCfdi  = str_starts_with($version, '4') ? 'cfdi' : 'cfdi3';

        $fecha  = (string) ($attrs['Fecha']  ?? '');
        $total  = (string) ($attrs['Total']  ?? '0');
        $moneda = (string) ($attrs['Moneda'] ?? 'MXN');

        $emisorNombre = '';
        $emisorNodes  = $xml->xpath("//{$nsCfdi}:Emisor") ?: $xml->xpath('//cfdi:Emisor') ?: [];
        if (!empty($emisorNodes)) {
            $ea           = $emisorNodes[0]->attributes();
            $emisorNombre = (string) ($ea['Nombre'] ?? '');
        }

        $uuid        = '';
        $timbreNodes = $xml->xpath('//tfd:TimbreFiscalDigital') ?: [];
        if (!empty($timbreNodes)) {
            $ta   = $timbreNodes[0]->attributes();
            $uuid = (string) ($ta['UUID'] ?? '');
        }

        // Mes como número entero 1-12
        $mes  = null;
        $anio = null;
        if ($fecha) {
            try {
                $cf   = Carbon::parse($fecha);
                $mes  = (int) $cf->format('n'); // 1-12
                $anio = (int) $cf->format('Y');
            } catch (\Throwable) {}
        }

        $conceptoNodes = $xml->xpath("//{$nsCfdi}:Concepto")
            ?: $xml->xpath('//cfdi:Concepto')
            ?: $xml->xpath('//cfdi3:Concepto')
            ?: [];

        $catalogo = Insumos::query()
            ->whereNull('deleted_at')
            ->get(['ID', 'NombreInsumo'])
            ->map(fn($i) => [
                'id'     => (int) $i->ID,
                'nombre' => mb_strtolower(trim((string) $i->NombreInsumo)),
            ])
            ->toArray();

        $conceptos = [];
        foreach ($conceptoNodes as $concepto) {
            $ca          = $concepto->attributes();
            $descripcion = (string) ($ca['Descripcion'] ?? '');
            $valorUnit   = (string) ($ca['ValorUnitario'] ?? '0');
            $importe     = (string) ($ca['Importe'] ?? '0');
            $cantidad    = (string) ($ca['Cantidad'] ?? '1');

            $insumoId  = null;
            $descLower = mb_strtolower(trim($descripcion));
            foreach ($catalogo as $cat) {
                if ($cat['nombre'] === $descLower) { $insumoId = $cat['id']; break; }
                if ($cat['nombre'] !== '' && (str_contains($descLower, $cat['nombre']) || str_contains($cat['nombre'], $descLower))) {
                    $insumoId = $cat['id'];
                }
            }

            $conceptos[] = [
                'nombre'   => $descripcion,
                'costo'    => $valorUnit,
                'importe'  => $importe,
                'cantidad' => $cantidad,
                'insumoId' => $insumoId,
            ];
        }

        return [
            'version'   => $version,
            'uuid'      => $uuid,
            'emisor'    => $emisorNombre,
            'fecha'     => $fecha,
            'mes'       => $mes,    // número 1-12
            'anio'      => $anio,
            'total'     => $total,
            'moneda'    => $moneda,
            'conceptos' => $conceptos,
        ];
    }

    private const VALID_STAGES = ['supervisor', 'gerencia', 'administracion'];

    private const STAGE_PERMISSIONS = [
        'gerencia'       => 'aprobar-solicitudes-gerencia',
        'administracion' => 'aprobar-solicitudes-administracion',
    ];

    private ?string $serialColumn          = null;
    private array   $checklistTemplatesCache = [];

    public function mount(): void
    {
        $this->serialColumn = $this->detectSerialColumn();
    }

    public function updatingFiltroEstatus(): void { $this->resetPage(); }
    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingPerPage(): void       { $this->resetPage(); }

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
                $idx    = (int) $parts[6];

                if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx])) return;

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
        $user             = auth()->user();
        $empleadoActual   = $user ? Empleados::query()->where('Correo', $user->email)->first() : null;
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
        $solicitudesRaw = $query->get();

        $solicitudesProcesadas = $solicitudesRaw->map(function ($solicitud) use ($user, $empleadoActualId) {
            return $this->hydrateSolicitudRow($solicitud, $user, $empleadoActualId);
        });

        if ($this->filtroEstatus) {
            $solicitudesProcesadas = $solicitudesProcesadas->filter(function ($item) {
                return $item->estatusDisplay === $this->filtroEstatus;
            })->values();
        }

        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $items       = $solicitudesProcesadas->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();
        $paginator   = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $solicitudesProcesadas->count(),
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.tabla-solicitudes', ['todasSolicitudes' => $paginator]);
    }

    private function hydrateSolicitudRow($solicitud, $user, ?int $empleadoActualId)
    {
        $nombreEmpleado              = $solicitud->empleadoid->NombreEmpleado ?? '';
        $solicitud->nombreFormateado = $this->formatNombreEmpleado((string) $nombreEmpleado);

        [$estatusReal, $estaRechazada]    = $this->resolveEstatusReal($solicitud);
        [$estatusDisplay, $colorEstatus]  = $this->resolveEstatusDisplay($solicitud, $estatusReal);

        $solicitud->estatusReal    = $estatusReal;
        $solicitud->estatusDisplay = $estatusDisplay;
        $solicitud->colorEstatus   = $colorEstatus;
        $solicitud->recotizarPropuestasText = '';

        if ($estatusReal === 'Re-cotizar' && $solicitud->pasoGerencia && $solicitud->pasoGerencia->comment) {
            $comment = $solicitud->pasoGerencia->comment;
            if (str_starts_with($comment, 'RECOTIZAR|')) {
                $parts = explode('|', $comment, 3);
                $nums  = isset($parts[1]) ? array_filter(array_map('trim', explode(',', $parts[1]))) : [];
                $solicitud->recotizarPropuestasText = $nums ? ' (Prop. ' . implode(', ', $nums) . ')' : '';
            }
        }

        $todasFirmaron      = $this->allStepsApproved($solicitud);
        $supervisorAprobado = $solicitud->pasoSupervisor && $solicitud->pasoSupervisor->status === 'approved';
        $tieneSeleccionada  = $this->hasSelectedCotizacion($solicitud);
        $todosGanadores     = $solicitud->todosProductosTienenGanador();
        $estaCancelada      = ($estatusReal === 'Cancelada');

        $solicitud->puedeCotizar = (bool) (
            !$estaCancelada && $supervisorAprobado && $user && !$estaRechazada
            && $estatusDisplay !== 'Aprobada' && !$todosGanadores
        );
        $solicitud->puedeSubirFactura = (bool) (!$estaCancelada && $todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAsignar      = (bool) (!$estaCancelada && $todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAprobar      = false;
        $solicitud->nivelAprobacion   = '';

        [$facturasSubidas, $totalNecesarias]  = $this->contarFacturas($solicitud);
        $solicitud->facturasSubidas           = $facturasSubidas;
        $solicitud->totalFacturasNecesarias   = $totalNecesarias;

        $pasoSupervisor     = $solicitud->pasoSupervisor;
        $pasoGerencia       = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        if ($user && !$estaRechazada && !$estaCancelada) {
            if (
                $estatusReal === 'Pendiente Aprobación Supervisor'
                && $pasoSupervisor
                && (int) $pasoSupervisor->approver_empleado_id === (int) $empleadoActualId
            ) {
                $solicitud->puedeAprobar    = true;
                $solicitud->nivelAprobacion = 'supervisor';
            } elseif (
                $estatusReal === 'Pendiente Aprobación Gerencia'
                && $solicitud->GerenciaID
                && $user->can(self::STAGE_PERMISSIONS['gerencia'])
            ) {
                $solicitud->puedeAprobar    = true;
                $solicitud->nivelAprobacion = 'gerencia';
            } elseif (
                $estatusReal === 'Pendiente Aprobación Administración'
                && $user->can(self::STAGE_PERMISSIONS['administracion'])
            ) {
                $solicitud->puedeAprobar    = true;
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
        $pasoSupervisor     = $solicitud->pasoSupervisor;
        $pasoGerencia       = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        if (in_array($solicitud->Estatus, ['Cancelada', 'Cerrada'], true)) return ['Cancelada', false];

        if (
            ($pasoSupervisor     && $pasoSupervisor->status     === 'rejected') ||
            ($pasoGerencia       && $pasoGerencia->status       === 'rejected') ||
            ($pasoAdministracion && $pasoAdministracion->status === 'rejected')
        ) return ['Rechazada', true];

        if (in_array($solicitud->Estatus, ['Aprobado', 'Aprobada'], true)) return ['Aprobado', false];
        if ($solicitud->Estatus === 'Re-cotizar') return ['Re-cotizar', false];

        $estatusReal = $solicitud->Estatus ?? 'Pendiente';

        if (in_array($solicitud->Estatus, ['Pendiente', 'En revisión', null, ''], true) || empty($solicitud->Estatus)) {
            if ($pasoSupervisor && $pasoSupervisor->status === 'approved') {
                if ($pasoGerencia && $pasoGerencia->status === 'approved') {
                    if ($pasoAdministracion && $pasoAdministracion->status === 'approved') {
                        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);
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

        return [$estatusReal, false];
    }

    private function resolveEstatusDisplay($solicitud, string $estatusReal): array
    {
        if ($estatusReal === 'Cancelada')    return ['Cancelada',   'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-700'];
        if ($estatusReal === 'Rechazada')    return ['Rechazada',   'bg-red-50 text-red-800 border border-red-200'];
        if ($estatusReal === 'Aprobado')     return ['Aprobada',    'bg-emerald-50 text-emerald-800 border border-emerald-200'];
        if ($estatusReal === 'Cotizaciones Enviadas') return ['Cotizaciones Enviadas', 'bg-blue-50 text-blue-800 border border-blue-200'];
        if ($estatusReal === 'Re-cotizar')   return ['Re-cotizar',  'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700'];
        if ($estatusReal === 'Completada')   return ['En revisión', 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700'];
        if ($estatusReal === 'Pendiente Cotización TI') return ['Pendiente', 'bg-amber-50 text-amber-800 border border-amber-200'];

        if (in_array($estatusReal, ['Pendiente Aprobación Supervisor', 'Pendiente Aprobación Gerencia', 'Pendiente Aprobación Administración'], true)) {
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
        $ps = $solicitud->pasoSupervisor;
        $pg = $solicitud->pasoGerencia;
        $pa = $solicitud->pasoAdministracion;
        return ($ps && $ps->status === 'approved') && ($pg && $pg->status === 'approved') && ($pa && $pa->status === 'approved');
    }

    private function contarFacturas($solicitud): array
    {
        if (!$solicitud->cotizaciones || $solicitud->cotizaciones->isEmpty()) return [0, 0];
        $seleccionadas   = $solicitud->cotizaciones->where('Estatus', 'Seleccionada');
        if ($seleccionadas->isEmpty()) return [0, 0];

        $proveedoresUnicos = $seleccionadas->pluck('Proveedor')->filter()->unique();
        $totalNecesarias   = $proveedoresUnicos->count();
        if ($totalNecesarias === 0) return [0, 0];

        $cotizacionIds = $seleccionadas->pluck('CotizacionID')->filter()->unique()->toArray();
        if (empty($cotizacionIds)) return [0, $totalNecesarias];

        $activos = SolicitudActivo::query()
            ->whereIn('CotizacionID', $cotizacionIds)
            ->whereNotNull('FacturaPath')
            ->where('FacturaPath', '!=', '')
            ->select('CotizacionID')
            ->distinct()
            ->get();

        if ($activos->isEmpty()) return [0, $totalNecesarias];

        $cotizacionesConFactura = $activos->pluck('CotizacionID')->toArray();
        $proveedoresConFactura  = $seleccionadas
            ->whereIn('CotizacionID', $cotizacionesConFactura)
            ->pluck('Proveedor')->filter()->unique()->count();

        return [$proveedoresConFactura, $totalNecesarias];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Aprobación / Rechazo
    // ══════════════════════════════════════════════════════════════════════════

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
            $solicitud     = Solicitud::findOrFail($solicitudId);
            $usuarioActual = auth()->user();
            if (!$usuarioActual) throw new \Exception('Sesión inválida.');

            $usuarioEmpleado = Empleados::query()->where('Correo', $usuarioActual->email)->firstOrFail();

            $step = SolicitudPasos::query()
                ->where('solicitud_id', $solicitud->SolicitudID)
                ->where('stage', $nivel)
                ->lockForUpdate()
                ->firstOrFail();

            if ($step->status !== 'pending') throw new \Exception('Etapa ya resuelta.');

            $this->authorizeDecision($usuarioActual, $usuarioEmpleado, $solicitud, $step, $nivel);

            $step->update([
                'status'                 => $decision,
                'comment'                => $comentario,
                'decided_at'             => now(),
                'decided_by_empleado_id' => (int) $usuarioEmpleado->EmpleadoID,
            ]);

            if ($decision === 'rejected') $solicitud->update(['Estatus' => 'Rechazada']);
        });
    }

    private function assertValidStage(string $nivel): void
    {
        if (!in_array($nivel, self::VALID_STAGES, true)) throw new \Exception('Etapa inválida.');
    }

    private function authorizeDecision($user, Empleados $empleado, Solicitud $solicitud, SolicitudPasos $step, string $nivel): void
    {
        $approverId = (int) ($step->approver_empleado_id ?? 0);
        if ($approverId > 0 && $approverId !== (int) $empleado->getAttribute('EmpleadoID')) throw new \Exception('No tienes permiso para resolver esta etapa.');
        if ($approverId > 0) return;
        if ($nivel === 'supervisor') throw new \Exception('No tienes permiso para resolver esta etapa.');
        $perm = self::STAGE_PERMISSIONS[$nivel] ?? null;
        if ($perm && !$user->can($perm)) throw new \Exception('No tienes permiso para resolver esta etapa.');
        if ($nivel === 'gerencia' && empty($solicitud->GerenciaID)) throw new \Exception('Solicitud sin gerencia asignada.');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Modal Cancelación
    // ══════════════════════════════════════════════════════════════════════════

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
            $usuarioActual = auth()->user();
            if (!$usuarioActual) throw new \Exception('Sesión inválida.');

            DB::transaction(function () use ($usuarioActual) {
                $solicitud = Solicitud::findOrFail($this->solicitudCancelarId);
                $solicitud->update([
                    'Estatus'            => 'Cancelada',
                    'motivo_cancelacion' => trim($this->motivoCancelacion),
                    'fecha_cancelacion'  => now(),
                    'cancelado_por'      => $usuarioActual->id,
                ]);
            });

            $this->dispatchBrowserEvent('swal:success', [
                'message' => "Solicitud #{$this->solicitudCancelarId} cancelada correctamente.",
            ]);
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

    // ══════════════════════════════════════════════════════════════════════════
    // Modal Asignación
    // ══════════════════════════════════════════════════════════════════════════

    public function abrirModalAsignacion(int $solicitudId): void { $this->openAsignacion($solicitudId); }

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

            $activos       = SolicitudActivo::query()->where('SolicitudID', $solicitudId)->get();
            $activosPorKey = $activos->keyBy(fn($a) => (int) $a->CotizacionID . ':' . (int) $a->UnidadIndex);
            $activoIds     = $activos->pluck('SolicitudActivoID')->filter()->values()->all();

            $checklists = empty($activoIds)
                ? collect()
                : SolicitudActivoCheckList::query()
                    ->whereIn('SolicitudActivoID', $activoIds)
                    ->get()
                    ->groupBy('SolicitudActivoID');

            $empleadosMap = $this->loadEmpleadosDeptMap(
                $activos->pluck('EmpleadoID')->filter()->unique()->values()->all()
            );

            $agrupadas = $seleccionadas->groupBy(fn($c) => (int) ($c->NumeroPropuesta ?? 0));
            $out       = [];

            foreach ($agrupadas as $numeroPropuesta => $items) {
                $cot = $items->first();
                if (!$cot) continue;

                $qty      = max(1, (int) ($cot->Cantidad ?? 1));
                $unidades = [];

                for ($i = 1; $i <= $qty; $i++) {
                    $key    = (int) $cot->CotizacionID . ':' . $i;
                    $activo = $activosPorKey->get($key);

                    $empleadoId  = $activo ? (int) ($activo->EmpleadoID ?? 0) : 0;
                    $empleadoRow = $empleadoId && isset($empleadosMap[$empleadoId]) ? $empleadosMap[$empleadoId] : null;

                    $deptId = $activo && !empty($activo->DepartamentoID)
                        ? (int) $activo->DepartamentoID
                        : ($empleadoRow['departamento_id'] ?? null);

                    $template        = $this->checklistTemplateByDept($deptId);
                    $saved           = $activo ? ($checklists->get((int) $activo->SolicitudActivoID) ?? collect()) : collect();
                    $unidadChecklist = $this->applySavedChecklist($template, $saved);

                    $serialVal = '';
                    if ($activo && $this->serialColumn) {
                        $serialVal = (string) ($activo->{$this->serialColumn} ?? '');
                    }

                    // ── Cargar rutas guardadas desde tabla facturas ──────────
                    // ArchivoRuta = XML, PdfRuta = PDF
                    $xmlSavedPath = '';
                    $pdfSavedPath = '';
                    if ($activo) {
                        $facturaGuardada = Facturas::query()
                            ->where('SolicitudID', $solicitudId)
                            ->where(function ($q) {
                                $q->whereNotNull('ArchivoRuta')->where('ArchivoRuta', '!=', '')
                                  ->orWhereNotNull('PdfRuta')->where('PdfRuta', '!=', '');
                            })
                            ->latest()
                            ->first();

                        if ($facturaGuardada) {
                            $xmlSavedPath = (string) ($facturaGuardada->ArchivoRuta ?? '');
                            $pdfSavedPath = (string) ($facturaGuardada->PdfRuta     ?? '');
                        }
                    }

                    $unidades[] = [
                        'unidadIndex'         => $i,
                        'activoId'            => $activo ? (int) $activo->SolicitudActivoID : null,
                        'serial'              => $serialVal,
                        'factura_xml_path'    => $xmlSavedPath,   // ArchivoRuta
                        'factura_pdf_path'    => $pdfSavedPath,   // PdfRuta
                        'factura_path'        => $activo ? (string) ($activo->FacturaPath ?? '') : '',
                        'fecha_entrega'       => $activo && $activo->FechaEntrega ? $activo->FechaEntrega->format('Y-m-d') : null,
                        'empleado_id'         => $empleadoId ?: null,
                        'empleado_nombre'     => $empleadoRow['nombre'] ?? null,
                        'departamento_id'     => $deptId,
                        'departamento_nombre' => $empleadoRow['departamento_nombre'] ?? null,
                        'checklist_open'      => true,
                        'checklist'           => $unidadChecklist,
                        'requiere_config'     => $saved->isNotEmpty(),
                    ];
                }

                $out[] = [
                    'numeroPropuesta' => (int) $numeroPropuesta,
                    'cotizacionId'    => (int) $cot->CotizacionID,
                    'nombreEquipo'    => (string) ($cot->NombreEquipo ?? 'Sin nombre'),
                    'proveedor'       => (string) ($cot->Proveedor    ?? 'Sin proveedor'),
                    'precioUnitario'  => (string) ($cot->Precio       ?? '0.00'),
                    'itemsTotal'      => $qty,
                    'unidades'        => $unidades,
                ];
            }

            $this->propuestasAsignacion = $out;
            $this->usuarioSearch        = [];
            $this->usuarioOptions       = [];

            foreach ($this->propuestasAsignacion as $pIndex => $p) {
                foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                    $this->usuarioSearch[$pIndex][$uIndex]  = (string) ($u['empleado_nombre'] ?? '');
                    $this->usuarioOptions[$pIndex][$uIndex] = [];
                }
            }

            $this->modalAsignacionAbierto = true;
        } catch (\Throwable $e) {
            $this->resetAsignacionState();
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error abriendo asignación: ' . $e->getMessage()]);
        }
    }

    public function closeAsignacion(): void { $this->resetAsignacionState(); }

    private function handleUsuarioSearchUpdated(int $pIndex, int $uIndex, string $term): void
    {
        $term = trim($term);
        $this->usuarioOptions[$pIndex]          = $this->usuarioOptions[$pIndex] ?? [];
        $this->usuarioOptions[$pIndex][$uIndex] = [];

        if ($term === '') return;

        $rows = Empleados::query()
            ->where('Estado', true)
            ->where(function ($q) use ($term) {
                $q->where('NombreEmpleado', 'like', "%{$term}%")
                    ->orWhere('Correo', 'like', "%{$term}%");
                if (ctype_digit($term)) $q->orWhere('EmpleadoID', (int) $term);
            })
            ->limit(8)
            ->get(['EmpleadoID', 'NombreEmpleado', 'Correo'])
            ->map(fn($e) => [
                'id'     => (int) $e->EmpleadoID,
                'name'   => (string) $e->NombreEmpleado,
                'correo' => (string) $e->Correo,
            ])
            ->toArray();

        $this->usuarioOptions[$pIndex][$uIndex] = $rows;
    }

    public function seleccionarEmpleado(int $pIndex, int $uIndex, int $empleadoId): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex])) throw new \Exception('Ítem inválido para asignación.');

        $row = $this->getEmpleadoConDept($empleadoId);

        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_id']         = (int) $row['EmpleadoID'];
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_nombre']     = (string) $row['NombreEmpleado'];
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['departamento_id']     = $row['DepartamentoID'] ? (int) $row['DepartamentoID'] : null;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['departamento_nombre'] = $row['NombreDepartamento'] ?: null;

        $deptId = $row['DepartamentoID'] ? (int) $row['DepartamentoID'] : null;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'] = $this->checklistTemplateByDept($deptId);

        $this->lockUsuarioSearch($pIndex, $uIndex);
        $this->usuarioSearch[$pIndex][$uIndex]  = (string) $row['NombreEmpleado'];
        $this->usuarioOptions[$pIndex][$uIndex] = [];
    }

    public function guardarAsignacion(): void { $this->persistAsignacion(false, false); }

    private function persistAsignacion(bool $strict, bool $closeAfter): void
    {
        if (!$this->asignacionSolicitudId) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']);
            return;
        }

        try {
            $errors = $this->validateAsignacionPayload($strict);
            if (!empty($errors)) {
                $primerError = array_values($errors)[0] ?? 'Error de validación';
                $this->dispatchBrowserEvent('swal:error', ['message' => $primerError]);
                throw ValidationException::withMessages($errors);
            }

            DB::transaction(function () {
                \Log::info('[Asignacion] Iniciando transacción', [
                    'solicitudId'     => $this->asignacionSolicitudId,
                    'propuestas'      => count($this->propuestasAsignacion),
                    'xmlKeys'         => array_keys($this->facturaXml),
                    'pdfKeys'         => array_keys($this->facturaPdf),
                    'xmlParseadoKeys' => array_keys($this->xmlParseado),
                ]);

                $xmlPorProveedor     = [];
                $pdfPorProveedor     = [];
                $facturaPorProveedor = [];

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    $proveedor = $p['proveedor'] ?? null;
                    if (!$proveedor) continue;
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!isset($xmlPorProveedor[$proveedor])     && !empty($this->facturaXml[$pIndex][$uIndex]))  $xmlPorProveedor[$proveedor]     = $this->facturaXml[$pIndex][$uIndex];
                        if (!isset($pdfPorProveedor[$proveedor])     && !empty($this->facturaPdf[$pIndex][$uIndex]))  $pdfPorProveedor[$proveedor]     = $this->facturaPdf[$pIndex][$uIndex];
                        if (!isset($facturaPorProveedor[$proveedor]) && !empty($this->facturas[$pIndex][$uIndex]))    $facturaPorProveedor[$proveedor] = $this->facturas[$pIndex][$uIndex];
                    }
                }

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!$this->shouldPersistUnit($pIndex, $uIndex, $u)) continue;

                        $dataUpdate = [
                            'NumeroPropuesta' => (int) ($p['numeroPropuesta'] ?? 0),
                            'FechaEntrega'    => !empty($u['fecha_entrega']) ? $u['fecha_entrega'] : null,
                            'EmpleadoID'      => !empty($u['empleado_id']) ? (int) $u['empleado_id'] : null,
                            'DepartamentoID'  => !empty($u['departamento_id']) ? (int) $u['departamento_id'] : null,
                        ];
                        if ($this->serialColumn) $dataUpdate[$this->serialColumn] = (string) ($u['serial'] ?? '');

                        $activo = SolicitudActivo::updateOrCreate(
                            [
                                'SolicitudID'  => (int) $this->asignacionSolicitudId,
                                'CotizacionID' => (int) ($p['cotizacionId'] ?? 0),
                                'UnidadIndex'  => (int) ($u['unidadIndex'] ?? ($uIndex + 1)),
                            ],
                            $dataUpdate
                        );

                        $proveedor = $p['proveedor'] ?? null;
                        $baseDir   = "solicitudes/{$this->asignacionSolicitudId}/activos/{$activo->SolicitudActivoID}";

                        // ── Guardar XML → ArchivoRuta ────────────────────────
                        $rutaXml     = null;
                        $xmlAGuardar = ($proveedor && isset($xmlPorProveedor[$proveedor]))
                            ? $xmlPorProveedor[$proveedor]
                            : ($this->facturaXml[$pIndex][$uIndex] ?? null);

                        if ($xmlAGuardar) {
                            $ext  = strtolower((string) $xmlAGuardar->getClientOriginalExtension());
                            $mime = strtolower((string) $xmlAGuardar->getMimeType());
                            if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml', 'application/xml', 'text/plain'], true)) {
                                throw new \Exception('El archivo XML de factura no es válido.');
                            }
                            $rutaXml = $xmlAGuardar->storeAs($baseDir, 'factura.xml', 'public');
                        }

                        // ── Guardar PDF → PdfRuta ────────────────────────────
                        $rutaPdf     = null;
                        $pdfAGuardar = ($proveedor && isset($pdfPorProveedor[$proveedor]))
                            ? $pdfPorProveedor[$proveedor]
                            : ($this->facturaPdf[$pIndex][$uIndex] ?? null);

                        if ($pdfAGuardar) {
                            $ext  = strtolower((string) $pdfAGuardar->getClientOriginalExtension());
                            $mime = strtolower((string) $pdfAGuardar->getMimeType());
                            if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
                                throw new \Exception('El archivo PDF de factura no es válido.');
                            }
                            $rutaPdf = $pdfAGuardar->storeAs($baseDir, 'factura.pdf', 'public');
                        }

                        // ── Legacy (campo $facturas único) ───────────────────
                        if (!$rutaXml && !$rutaPdf) {
                            $facturaLegacy = ($proveedor && isset($facturaPorProveedor[$proveedor]))
                                ? $facturaPorProveedor[$proveedor]
                                : ($this->facturas[$pIndex][$uIndex] ?? null);

                            if ($facturaLegacy) {
                                $ext  = strtolower((string) $facturaLegacy->getClientOriginalExtension());
                                $mime = strtolower((string) $facturaLegacy->getMimeType());
                                if (!in_array($ext, ['pdf', 'xml'], true) || !in_array($mime, ['application/pdf', 'application/x-pdf', 'text/xml', 'application/xml'], true)) {
                                    throw new \Exception('La factura debe ser PDF o XML.');
                                }
                                $path = $facturaLegacy->storeAs($baseDir, $ext === 'xml' ? 'factura.xml' : 'factura.pdf', 'public');
                                if ($ext === 'xml') $rutaXml = $path;
                                else                $rutaPdf = $path;
                            }
                        }

                        if ($rutaXml || $rutaPdf) {
                            $activo->update(['FacturaPath' => $rutaXml ?? $rutaPdf]);
                        }

                        // ── Guardar conceptos en tabla facturas ──────────────
                        $parsed = $this->xmlParseado[$pIndex][$uIndex]
                            ?? ($proveedor ? $this->buscarXmlParseadoPorProveedor($pIndex, $proveedor) : null);

                        if ($parsed && empty($parsed['error']) && !empty($parsed['conceptos'])) {
                            $uuid = trim((string) ($parsed['uuid'] ?? ''));
                            \Log::info('[Asignacion] Guardando facturas', [
                                'solicitudId' => $this->asignacionSolicitudId,
                                'uuid'        => $uuid,
                                'conceptos'   => count($parsed['conceptos']),
                                'rutaXml'     => $rutaXml,
                                'rutaPdf'     => $rutaPdf,
                            ]);

                            foreach ($parsed['conceptos'] as $concepto) {
                            $nombreConcepto = trim((string) ($concepto['nombre'] ?? ''));
                            if ($nombreConcepto === '') continue;
                            $nombreConcepto = mb_substr($nombreConcepto, 0, 300);

                                try {
                                    $factura = Facturas::updateOrCreate(
                                        [
                                            'SolicitudID' => (int) $this->asignacionSolicitudId,
                                            'UUID'        => $uuid,
                                            'Nombre'      => $nombreConcepto,
                                        ],
                                        [
                                            'Importe'     => is_numeric($concepto['importe'] ?? null) ? (float) $concepto['importe'] : 0,
                                            'Costo'       => is_numeric($concepto['costo']   ?? null) ? (float) $concepto['costo']   : 0,
                                            'Mes'         => !empty($parsed['mes'])  ? (int) $parsed['mes']  : null,  // número 1-12
                                            'Anio'        => !empty($parsed['anio']) ? (int) $parsed['anio'] : null,
                                            'InsumoID'    => $concepto['insumoId'] ?? null,
                                            'Emisor'      => $parsed['emisor'] ?? '',
                                            'ArchivoRuta' => $rutaXml ?? '',   // ← XML
                                            'PdfRuta'     => $rutaPdf ?? '',   // ← PDF
                                        ]
                                    );
                                    \Log::info('[Asignacion] Factura guardada OK', [
                                        'FacturasID'         => $factura->getKey(),
                                        'nombre'             => $nombreConcepto,
                                        'wasRecentlyCreated' => $factura->wasRecentlyCreated,
                                    ]);
                                } catch (\Throwable $fe) {
                                    \Log::error('[Asignacion] ERROR al guardar factura', [
                                        'concepto' => $nombreConcepto,
                                        'error'    => $fe->getMessage(),
                                    ]);
                                    throw $fe;
                                }
                            }
                        }

                        // ── Checklist ────────────────────────────────────────
                        if (!empty($u['requiere_config'])) {
                            foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                                foreach (($u['checklist'][$catKey] ?? []) as $item) {
                                    $reqId = (int) ($item['req_id'] ?? 0);
                                    if (!$reqId) continue;
                                    $realizado = !empty($item['realizado']);
                                    SolicitudActivoCheckList::updateOrCreate(
                                        [
                                            'SolicitudActivoID'           => (int) $activo->SolicitudActivoID,
                                            'DepartamentoRequerimientoID' => $reqId,
                                        ],
                                        [
                                            'completado'  => (bool) $realizado,
                                            'responsable' => $realizado ? (string) ($item['responsable'] ?? '') : null,
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            });

            $this->dispatchBrowserEvent('swal:success', [
                'message' => $strict ? 'Asignación finalizada' : '✅ Avance guardado correctamente',
            ]);

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
                $basePath = "propuestasAsignacion.$pIndex.unidades.$uIndex";

                if ($strict) {
                    if (empty($u['serial']))        $errors["$basePath.serial"]        = 'El Serial es obligatorio.';
                    if (empty($u['fecha_entrega'])) $errors["$basePath.fecha_entrega"] = 'La fecha de entrega es obligatoria.';
                    if (empty($u['empleado_id']))   $errors["$basePath.empleado_id"]   = 'El usuario final es obligatorio.';
                }

                if (!empty($u['requiere_config'])) {
                    foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                        foreach (($u['checklist'][$catKey] ?? []) as $idx => $item) {
                            if (!empty($item['realizado']) && empty($item['responsable'])) {
                                $errors["$basePath.checklist.$catKey.$idx.responsable"] = 'Responsable obligatorio si la tarea está marcada.';
                            }
                        }
                    }
                }

                if (isset($this->facturaXml[$pIndex][$uIndex]) && $this->facturaXml[$pIndex][$uIndex]) {
                    try {
                        $ext = strtolower((string) $this->facturaXml[$pIndex][$uIndex]->getClientOriginalExtension());
                        if ($ext !== 'xml') $errors["facturaXml.$pIndex.$uIndex"] = 'El archivo debe ser XML.';
                    } catch (\Throwable) {}
                }

                if (isset($this->facturaPdf[$pIndex][$uIndex]) && $this->facturaPdf[$pIndex][$uIndex]) {
                    try {
                        $ext = strtolower((string) $this->facturaPdf[$pIndex][$uIndex]->getClientOriginalExtension());
                        if ($ext !== 'pdf') $errors["facturaPdf.$pIndex.$uIndex"] = 'El archivo debe ser PDF.';
                    } catch (\Throwable) {}
                }

                if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
                    $file   = $this->facturas[$pIndex][$uIndex];
                    $ext    = strtolower((string) $file->getClientOriginalExtension());
                    $mime   = strtolower((string) $file->getMimeType());
                    if (!in_array($ext, ['pdf', 'xml'], true) || !in_array($mime, ['application/pdf', 'application/x-pdf', 'text/xml', 'application/xml'], true)) {
                        $errors["facturas.$pIndex.$uIndex"] = 'La factura debe ser PDF o XML.';
                    }
                }
            }
        }

        return $errors;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Helpers checklist
    // ══════════════════════════════════════════════════════════════════════════

    private function checklistTemplateByDept(?int $departamentoId): array
    {
        if (!$departamentoId) return [];
        if (isset($this->checklistTemplatesCache[$departamentoId])) return $this->checklistTemplatesCache[$departamentoId];

        $reqs = DepartamentoRequerimientos::query()
            ->byDepartamentos($departamentoId)
            ->seleccionados()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get(['id', 'categoria', 'nombre']);

        $payload = [];
        foreach ($reqs as $r) {
            $catKey = (string) $r->categoria;
            if (!isset($payload[$catKey])) $payload[$catKey] = [];
            $payload[$catKey][] = ['req_id' => (int) $r->id, 'nombre' => (string) $r->nombre, 'realizado' => false, 'responsable' => ''];
        }

        $this->checklistTemplatesCache[$departamentoId] = $payload;
        return $payload;
    }

    private function applySavedChecklist(array $template, $checklistRows): array
    {
        $map = collect($checklistRows)->keyBy(fn($x) => (int) ($x->DepartamentoRequerimientoID ?? 0));
        foreach (array_keys($template) as $catKey) {
            foreach ($template[$catKey] as $idx => $item) {
                $reqId = (int) ($item['req_id'] ?? 0);
                if (!$reqId) continue;
                $row = $map->get($reqId);
                if ($row) {
                    $template[$catKey][$idx]['realizado']   = (bool) ($row->completado  ?? false);
                    $template[$catKey][$idx]['responsable'] = (string) ($row->responsable ?? '');
                }
            }
        }
        return $template;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Helpers generales
    // ══════════════════════════════════════════════════════════════════════════

    private function currentUserPrefix(): string
    {
        $user = auth()->user();
        if (!$user || empty($user->name)) return '';
        $empleado = Empleados::query()->whereRaw('LOWER(NombreEmpleado) = ?', [mb_strtolower(trim((string) $user->name))])->first();
        if (!$empleado || empty($empleado->Correo)) return '';
        return (string) Str::before(strtolower($empleado->Correo), '@');
    }

    private function resetAsignacionState(): void
    {
        $this->modalAsignacionAbierto  = false;
        $this->asignacionSolicitudId   = null;
        $this->propuestasAsignacion    = [];
        $this->facturas                = [];
        $this->facturaXml              = [];
        $this->facturaPdf              = [];
        $this->xmlParseado             = [];
        $this->usuarioSearch           = [];
        $this->usuarioOptions          = [];
        $this->usuarioSearchLock       = [];
        $this->checklistTemplatesCache = [];
    }

    private function detectSerialColumn(): ?string
    {
        if (!Schema::hasTable('solicitud_activos')) return null;
        if (Schema::hasColumn('solicitud_activos', 'serial'))      return 'serial';
        if (Schema::hasColumn('solicitud_activos', 'Serial'))      return 'Serial';
        if (Schema::hasColumn('solicitud_activos', 'NumeroSerie')) return 'NumeroSerie';
        if (Schema::hasColumn('solicitud_activos', 'NumSerie'))    return 'NumSerie';
        return null;
    }

    private function shouldPersistUnit(int $pIndex, int $uIndex, array $u): bool
    {
        if (!empty($u['serial']))          return true;
        if (!empty($u['fecha_entrega']))   return true;
        if (!empty($u['empleado_id']))     return true;
        if (!empty($u['departamento_id'])) return true;
        if (isset($this->facturas[$pIndex][$uIndex])    && $this->facturas[$pIndex][$uIndex])    return true;
        if (isset($this->facturaXml[$pIndex][$uIndex])  && $this->facturaXml[$pIndex][$uIndex])  return true;
        if (isset($this->facturaPdf[$pIndex][$uIndex])  && $this->facturaPdf[$pIndex][$uIndex])  return true;
        if (isset($this->xmlParseado[$pIndex][$uIndex]) && empty($this->xmlParseado[$pIndex][$uIndex]['error'])) return true;

        if (!empty($u['requiere_config'])) {
            foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                foreach (($u['checklist'][$catKey] ?? []) as $item) {
                    if (!empty($item['realizado']) || !empty($item['responsable'])) return true;
                }
            }
        }
        return false;
    }

    private function loadEmpleadosDeptMap(array $empleadoIds): array
    {
        if (empty($empleadoIds)) return [];
        $rows = Empleados::query()
            ->withTrashed()
            ->from('empleados as e')
            ->leftJoin('puestos as p',       'p.PuestoID',       '=', 'e.PuestoID')
            ->leftJoin('departamentos as d',  'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->whereIn('e.EmpleadoID', $empleadoIds)
            ->get(['e.EmpleadoID', 'e.NombreEmpleado', 'e.Correo', 'd.DepartamentoID', 'd.NombreDepartamento']);

        $map = [];
        foreach ($rows as $r) {
            $id       = (int) $r->EmpleadoID;
            $map[$id] = [
                'nombre'              => (string) ($r->NombreEmpleado    ?? ''),
                'correo'              => (string) ($r->Correo            ?? ''),
                'departamento_id'     => $r->DepartamentoID     ? (int) $r->DepartamentoID     : null,
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
            ->leftJoin('puestos as p',      'p.PuestoID',       '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->where('e.EmpleadoID', $empleadoId)
            ->first(['e.EmpleadoID', 'e.NombreEmpleado', 'e.Correo', 'd.DepartamentoID', 'd.NombreDepartamento']);

        if (!$row) throw new \Exception('Empleado no encontrado.');

        return [
            'EmpleadoID'         => (int) $row->EmpleadoID,
            'NombreEmpleado'     => (string) ($row->NombreEmpleado    ?? ''),
            'Correo'             => (string) ($row->Correo            ?? ''),
            'DepartamentoID'     => $row->DepartamentoID     ? (int) $row->DepartamentoID     : null,
            'NombreDepartamento' => $row->NombreDepartamento ? (string) $row->NombreDepartamento : null,
        ];
    }

    private function lockUsuarioSearch(int $pIndex, int $uIndex): void
    {
        $this->usuarioSearchLock[$pIndex]          = $this->usuarioSearchLock[$pIndex] ?? [];
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