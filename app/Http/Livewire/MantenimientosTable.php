<?php

namespace App\Http\Livewire;

use App\Models\Mantenimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class MantenimientosTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $estatus = 'pendiente';
    public string $search = '';
    public bool $modalReprogramarAbierto = false;
    public bool $modalDetalleAbierto = false;
    public ?int $mantenimientoSeleccionadoId = null;
    public string $fechaReprogramada = '';
    public string $comentario = '';
    public array $detalle = [];

    public function mount(): void
    {
        Carbon::setLocale('es');
    }

    public function updatedEstatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function limpiarBusqueda(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function verTodos(): void
    {
        $this->estatus = 'todos';
        $this->resetPage();
    }

    public function abrirReprogramar(int $mantenimientoId): void
    {
        $mantenimiento = Mantenimiento::findOrFail($mantenimientoId);

        $this->mantenimientoSeleccionadoId = $mantenimiento->id;
        $this->fechaReprogramada = optional($mantenimiento->FechaReprogramada)->format('Y-m-d') ?? '';
        $this->comentario = $mantenimiento->Comentario ?? '';
        $this->modalReprogramarAbierto = true;
    }

    public function cerrarReprogramar(): void
    {
        $this->modalReprogramarAbierto = false;
        $this->mantenimientoSeleccionadoId = null;
        $this->fechaReprogramada = '';
        $this->comentario = '';
    }

    public function guardarReprogramacion(): void
    {
        $this->validate([
            'fechaReprogramada' => ['required', 'date'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        $mantenimiento = Mantenimiento::findOrFail($this->mantenimientoSeleccionadoId);
        $mantenimiento->update([
            'Comentario' => $this->comentario,
            'FechaReprogramada' => $this->fechaReprogramada,
        ]);

        $this->cerrarReprogramar();
        $this->dispatchBrowserEvent('mantenimiento-seguimiento-guardado');
    }

    public function abrirDetalle(int $mantenimientoId): void
    {
        $mantenimiento = Mantenimiento::with([
            'empleado.puestos.departamentos.gerencia',
            'inventarioEquipo.empleados.puestos.departamentos.gerencia',
        ])->findOrFail($mantenimientoId);
        $usuarioRealizo = $mantenimiento->RealizadoPor
            ? DB::table('users')->where('id', $mantenimiento->RealizadoPor)->value('name')
            : null;

        $this->detalle = [
            'Empleado' => $mantenimiento->NombreEmpleado,
            'Gerencia' => $mantenimiento->NombreGerencia ?: '-',
            'Estatus' => $mantenimiento->EstatusMantenimiento,
            'Asignación' => $mantenimiento->RequierePersonaFisica ? $mantenimiento->MotivoAsignacionPersonaFisica . '. Debe asignarse a una persona tipo FISICA.' : 'Asignado a persona física',
            'Tipo' => $mantenimiento->TipoMantenimiento,
            'Folio equipo' => $mantenimiento->Folio ?: '-',
            'Fecha compra' => $mantenimiento->FechaDeCompra ? Carbon::parse($mantenimiento->FechaDeCompra)->format('d/m/Y') : '-',
            'Fecha mantenimiento' => $mantenimiento->FechaMantenimiento ? Carbon::parse($mantenimiento->FechaMantenimiento)->translatedFormat('l, d \\d\\e F \\d\\e Y') : '-',
            'Fecha reprogramada' => $mantenimiento->FechaReprogramada ? Carbon::parse($mantenimiento->FechaReprogramada)->format('d/m/Y') : '-',
            'Realizado por' => $usuarioRealizo ?: '-',
            'Fecha realizado' => $mantenimiento->FechaRealizado ? Carbon::parse($mantenimiento->FechaRealizado)->format('d/m/Y H:i') : '-',
            'Comentario' => $mantenimiento->Comentario ?: '-',
        ];

        $this->modalDetalleAbierto = true;
    }

    public function cerrarDetalle(): void
    {
        $this->modalDetalleAbierto = false;
        $this->detalle = [];
    }

    public function render()
    {
        $query = Mantenimiento::query()
            ->with([
                'empleado.puestos.departamentos.gerencia',
                'inventarioEquipo.empleados.puestos.departamentos.gerencia',
            ])
            ->leftJoin('inventarioequipo as ie', 'ie.InventarioID', '=', 'mantenimientos.InventarioID')
            ->leftJoin('empleados as e', 'e.EmpleadoID', '=', 'ie.EmpleadoID')
            ->leftJoin('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->leftJoin('gerencia as g', 'g.GerenciaID', '=', 'd.GerenciaID')
            ->select('mantenimientos.*')
            ->orderBy('mantenimientos.FechaMantenimiento')
            ->orderBy('g.NombreGerencia')
            ->orderBy('e.NombreEmpleado');

        if ($this->estatus === 'pendiente') {
            $query->where('mantenimientos.Estatus', 'Pendiente');
        } elseif ($this->estatus === 'realizado') {
            $query->where('mantenimientos.Estatus', 'Realizado');
        } elseif ($this->estatus === 'requiere_asignacion') {
            $query->where('mantenimientos.Estatus', '!=', 'Realizado')
                ->where(function ($estadoQuery) {
                    $estadoQuery->where('e.Estado', false)
                        ->orWhereNull('e.EmpleadoID')
                        ->orWhereRaw("UPPER(COALESCE(e.tipo_persona, '')) <> 'FISICA'");
                });
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($subquery) use ($search) {
                $subquery->where('e.NombreEmpleado', 'like', "%{$search}%")
                    ->orWhere('g.NombreGerencia', 'like', "%{$search}%")
                    ->orWhere('mantenimientos.Folio', 'like', "%{$search}%")
                    ->orWhere('mantenimientos.TipoMantenimiento', 'like', "%{$search}%")
                    ->orWhere('mantenimientos.Estatus', 'like', "%{$search}%")
                    ->orWhere('mantenimientos.Comentario', 'like', "%{$search}%");

                if (stripos('Baja', $search) !== false || stripos('persona fisica', $search) !== false || stripos('persona física', $search) !== false) {
                    $subquery->orWhere(function ($estadoQuery) {
                        $estadoQuery->where('e.Estado', false)
                            ->orWhereNull('e.EmpleadoID')
                            ->orWhereRaw("UPPER(COALESCE(e.tipo_persona, '')) <> 'FISICA'");
                    });
                }
            });
        }

        $mantenimientos = $query->paginate(15);

        return view('livewire.mantenimientos-table', [
            'mantenimientos' => $mantenimientos,
        ]);
    }
}
