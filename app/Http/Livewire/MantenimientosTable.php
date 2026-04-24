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

    public function mount(): void
    {
        Carbon::setLocale('es');
    }

    public function updatedEstatus(): void
    {
        $this->resetPage();
    }

    public function verTodos(): void
    {
        $this->estatus = 'todos';
        $this->resetPage();
    }

    public function render()
    {
        $query = Mantenimiento::query()
            ->orderBy('FechaMantenimiento')
            ->orderBy('NombreGerencia')
            ->orderBy('NombreEmpleado');

        if ($this->estatus === 'pendiente') {
            $query->where('Estatus', 'Pendiente');
        } elseif ($this->estatus === 'realizado') {
            $query->where('Estatus', 'Realizado');
        }

        $mantenimientos = $query->paginate(15);

        $usuariosRealizacion = DB::table('users')
            ->whereIn('id', collect($mantenimientos->items())->pluck('RealizadoPor')->filter()->unique()->values())
            ->pluck('name', 'id');

        return view('livewire.mantenimientos-table', [
            'mantenimientos' => $mantenimientos,
            'usuariosRealizacion' => $usuariosRealizacion,
        ]);
    }
}
