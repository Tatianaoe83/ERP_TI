<?php

namespace App\Http\Livewire\Reportes;

use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Services\ReporteService;

class Index extends Component
{
    public $modelo;
    public $titulo;
    public $columnas = [];
    public $columnasSeleccionadas = [];
    public $filtros = [];
    public $resultados = [];
    public $grupo;
    public $ordenColumna;
    public $ordenDireccion = 'asc';
    public $limite;
    public $relaciones = [];
    public $relacionesSeleccionadas = [];
    public $relacionActual;
    public $columnasRelacionActual = [];
    public $tablasDisponibles = [];
    public $modeloClase;

    public function mount()
    {
        $this->tablasDisponibles = ReporteService::obtenerTablas();
    }

    public function updatedModelo()
    {
        if (!$this->modelo) return;

        $this->modeloClase = ReporteService::modeloDesdeTabla($this->modelo);

        if ($this->modeloClase) {
            $this->columnas = ReporteService::listarColumnas($this->modelo);
            $this->relaciones = ReporteService::relacionesTablas($this->modeloClase);
        } else {
            $this->resetEstado();
        }
    }

    public function updatedRelacionActual()
    {
        $this->columnasRelacionActual = ReporteService::obtenerColumnasRelacion($this->modeloClase, $this->relacionActual);
    }

    public function generarReporte()
    {
        if (!$this->modeloClase) return;

        $query = $this->modeloClase::query();

        $relaciones = collect($this->columnasSeleccionadas)
            ->filter(fn($col) => str_contains($col, '.'))
            ->map(fn($col) => explode('.', $col)[0])
            ->unique()
            ->values()
            ->toArray();

        $columnasPrincipales = collect($this->columnasSeleccionadas)
            ->filter(fn($col) => !str_contains($col, '.'))
            ->values()
            ->toArray();

        $columnasPrincipales = ReporteService::agregarClavesForaneas($relaciones, $columnasPrincipales, $this->modeloClase);

        if (!empty($columnasPrincipales)) {
            $query->select($columnasPrincipales);
        }

        if (!empty($relaciones)) {
            $query->with($relaciones);
        }

        foreach ($this->filtros as $filtro) {
            if (!empty($filtro['columna']) && isset($filtro['valor'])) {
                $query->where($filtro['columna'], $filtro['operador'] ?? '=', $filtro['valor']);
            }
        }

        if ($this->grupo) $query->groupBy($this->grupo);
        if ($this->ordenColumna && in_array($this->ordenDireccion, ['asc', 'desc'])) {
            $query->orderBy($this->ordenColumna, $this->ordenDireccion);
        }
        if ($this->limite) $query->limit($this->limite);

        $this->resultados = $query->get();
        $this->columnasVistaPrevia = $this->columnasSeleccionadas;

        $this->resetEstado();
    }

    private function resetEstado()
    {
        $this->modelo = '';
        $this->titulo = '';
        $this->columnas = [];
        $this->columnasSeleccionadas = [];
        $this->filtros = [];
        $this->grupo = null;
        $this->ordenColumna = null;
        $this->ordenDireccion = 'asc';
        $this->limite = null;
        $this->relaciones = [];
        $this->relacionesSeleccionadas = [];
        $this->relacionActual = '';
        $this->columnasRelacionActual = [];
    }

    public function agregarFiltro()
    {
        $this->filtros[] = ['columna' => '', 'operador' => '=', 'valor' => ''];
    }

    public function eliminarFiltro($index)
    {
        unset($this->filtros[$index]);
        $this->filtros = array_values($this->filtros);
    }

    public function render()
    {
        return view('livewire.reportes.index', [
            'tablasDisponibles' => $this->tablasDisponibles,
            'columnas' => $this->columnas,
            'resultados' => $this->resultados,
            'relaciones' => $this->relaciones,
            'columnasRelacionActual' => $this->columnasRelacionActual,
            'relacionActual' => $this->relacionActual
        ]);
    }
}
