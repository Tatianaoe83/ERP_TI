<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AutocompleteInput extends Component
{
    public $tabla, $columna, $valor, $sugerencias = [], $indice, $inputId;

    public function mount($tabla, $columna, $indice = null, $valor = null, $inputId = null)
    {
        $this->tabla = $tabla;
        $this->columna = $columna;
        $this->indice = $indice;
        $this->valor = $valor;
        $this->inputId = $inputId;
    }

    public function updatedValor($valor)
    {
        if (!$this->tabla || !$this->columna || !Schema::hasTable($this->tabla) || !Schema::hasColumn($this->tabla, $this->columna)) {
            $this->sugerencias = [];
            return;
        }

        $this->sugerencias = DB::table($this->tabla)
            ->select($this->columna)
            ->distinct()
            ->where($this->columna, 'like', "%{$valor}%")
            ->groupBy($this->columna)
            ->limit(5)
            ->pluck($this->columna)
            ->toArray();
    }

    public function seleccionar($valor)
    {
        $this->valor = $valor;
        $this->sugerencias = [];
        $this->emitUp('valorAutocompletado', $valor, $this->indice);
    }

    public function render()
    {
        return view('livewire.autocomplete-input');
    }
}
