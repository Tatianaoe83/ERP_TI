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
    public string $anio = '';
    public int $perPage = 15;
    public bool $modalReprogramarAbierto = false;
    public bool $modalDetalleAbierto = false;
    public ?int $mantenimientoSeleccionadoId = null;
    public string $fechaReprogramada = '';
    public string $comentario = '';
    public array $detalle = [];

    public function mount(): void
    {
        Carbon::setLocale('es');
        $this->anio = (string) now()->year;
    }

    public function updatedEstatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAnio(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (!in_array($this->perPage, [10, 15, 25, 50], true)) {
            $this->perPage = 15;
        }

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
            'Asignación' => $mantenimiento->NombreEmpleado ? 'Persona física activa' : '-',
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

        if ($this->anio !== 'todos') {
            $query->where('mantenimientos.AnioProgramacion', (int) $this->anio);
        }

        if ($this->estatus === 'pendiente') {
            $query->where('mantenimientos.Estatus', 'Pendiente')
                ->where('e.Estado', true)
                ->whereRaw("UPPER(COALESCE(e.tipo_persona, '')) = 'FISICA'");
        } elseif ($this->estatus === 'realizado') {
            $query->where('mantenimientos.Estatus', 'Realizado');
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
            });
        }

        $mantenimientos = $query->paginate($this->perPage);
        $aniosDisponibles = Mantenimiento::query()
            ->whereNotNull('AnioProgramacion')
            ->select('AnioProgramacion')
            ->distinct()
            ->orderByDesc('AnioProgramacion')
            ->pluck('AnioProgramacion');

        return view('livewire.mantenimientos-table', [
            'mantenimientos' => $mantenimientos,
            'aniosDisponibles' => $aniosDisponibles,
        ]);
    }
}
