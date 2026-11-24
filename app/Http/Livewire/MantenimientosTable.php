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
    public array $columnasOcultas = [];
    public string $fechaReprogramada = '';
    public string $comentario = '';

    // Se calcula una vez: solo cambia al generar programación, y eso recarga la página.
    public array $aniosDisponibles = [];

    public function mount(?array $anios = null): void
    {
        Carbon::setLocale('es');
        $this->anio = (string) now()->year;
        $this->aniosDisponibles = $anios ?? Mantenimiento::query()
            ->whereNotNull('AnioProgramacion')
            ->select('AnioProgramacion')
            ->distinct()
            ->orderByDesc('AnioProgramacion')
            ->pluck('AnioProgramacion')
            ->all();
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

    public function toggleColumna(string $columna): void
    {
        $permitidas = ['empleado', 'gerencia', 'folio', 'estatus', 'tipo', 'fecha'];
        if (!in_array($columna, $permitidas, true)) {
            return;
        }

        if (in_array($columna, $this->columnasOcultas, true)) {
            $this->columnasOcultas = array_values(array_diff($this->columnasOcultas, [$columna]));
            return;
        }

        $this->columnasOcultas[] = $columna;
    }

    public function limpiarBusqueda(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->search = '';
        $this->estatus = 'pendiente';
        $this->anio = (string) now()->year;
        $this->perPage = 15;
        $this->resetPage();
    }

    public function verTodos(): void
    {
        $this->estatus = 'todos';
        $this->resetPage();
    }

    // El modal de reprogramar se abre en el navegador; aquí solo se guarda.
    public function guardarReprogramacion(int $mantenimientoId): void
    {
        $mantenimiento = Mantenimiento::findOrFail($mantenimientoId);

        // Desde hoy (puede caer en otro año) y en día hábil, lunes a sábado.
        $this->validate([
            'fechaReprogramada' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($atributo, $valor, $falla) {
                    if (Carbon::parse($valor)->isSunday()) {
                        $falla('No se puede reprogramar en domingo.');
                    }
                },
            ],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ], [
            'fechaReprogramada.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
        ]);

        $mantenimiento->update([
            'Comentario' => $this->comentario,
            'FechaReprogramada' => $this->fechaReprogramada,
        ]);

        $this->fechaReprogramada = '';
        $this->comentario = '';
        $this->dispatchBrowserEvent('mantenimiento-seguimiento-guardado');
    }

    // Datos del modal de detalle; van en cada fila para abrirlo sin ir al servidor.
    private function detalleDe(Mantenimiento $mantenimiento, $usuarios): array
    {
        return [
            'Empleado' => $mantenimiento->NombreEmpleado,
            'Gerencia' => $mantenimiento->NombreGerencia ?: '-',
            'Estatus' => $mantenimiento->EstatusMantenimiento,
            'Asignación' => $mantenimiento->NombreEmpleado ? 'Persona física activa' : '-',
            'Tipo' => $mantenimiento->TipoMantenimiento,
            'Folio equipo' => $mantenimiento->Folio ?: '-',
            'Fecha compra' => $mantenimiento->FechaDeCompra ? Carbon::parse($mantenimiento->FechaDeCompra)->format('d/m/Y') : '-',
            'Fecha mantenimiento' => $mantenimiento->FechaMantenimiento ? Carbon::parse($mantenimiento->FechaMantenimiento)->translatedFormat('l, d \\d\\e F \\d\\e Y') : '-',
            'Fecha reprogramada' => $mantenimiento->FechaReprogramada ? Carbon::parse($mantenimiento->FechaReprogramada)->format('d/m/Y') : '-',
            'Realizado por' => $usuarios[$mantenimiento->RealizadoPor] ?? '-',
            'Fecha realizado' => $mantenimiento->FechaRealizado ? Carbon::parse($mantenimiento->FechaRealizado)->format('d/m/Y H:i') : '-',
            'Comentario' => $mantenimiento->Comentario ?: '-',
        ];
    }

    public function render()
    {
        $query = Mantenimiento::query()
            ->with('inventarioEquipo.empleados.puestos.departamentos.gerencia')
            ->leftJoin('inventarioequipo as ie', 'ie.InventarioID', '=', 'mantenimientos.InventarioID')
            ->leftJoin('empleados as e', 'e.EmpleadoID', '=', 'ie.EmpleadoID')
            ->leftJoin('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->leftJoin('gerencia as g', 'g.GerenciaID', '=', 'd.GerenciaID')
            ->select('mantenimientos.*')
            // Misma fecha que se muestra: la reprogramada si existe.
            ->orderByRaw('COALESCE(mantenimientos.FechaReprogramada, mantenimientos.FechaMantenimiento)')
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

        // El empleado directo solo se usa si el equipo no tiene empleado (Mantenimiento::empleadoAsignado):
        // se carga únicamente para esas filas.
        $mantenimientos->getCollection()
            ->filter(fn ($m) => !optional($m->inventarioEquipo)->empleados)
            ->load('empleado.puestos.departamentos.gerencia');

        $realizadoPor = $mantenimientos->getCollection()->pluck('RealizadoPor')->filter()->unique();
        $usuarios = $realizadoPor->isEmpty() ? [] : DB::table('users')->whereIn('id', $realizadoPor)->pluck('name', 'id')->all();
        foreach ($mantenimientos as $mantenimiento) {
            $mantenimiento->detalleModal = $this->detalleDe($mantenimiento, $usuarios);
        }

        return view('livewire.mantenimientos-table', [
            'mantenimientos' => $mantenimientos,
        ]);
    }
}
