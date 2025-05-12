<?php

namespace App\Http\Livewire\Reportes;

use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionClass;


class Index extends Component
{
    public $modelo;
    public $columnasSeleccionadas = [];
    public $filtros = [];
    public $resultados = [];
    public $relaciones;
    public $grupo;
    public $ordenColumna;
    public $ordenDireccion = 'asc';
    public $limite;
    public $relacionesDisponibles = [];
    public $relacionesSeleccionadas = [];
    public $columnasRelacionesSeleccionadas = [];
    public $columnasRelaciones = [];


    public function updatedModelo()
    {
        $this->columnas = Schema::getColumnListing((new $this->modelo)->getTable());
        $this->columnasSeleccionadas = $this->columnas;
        $this->relacionesDisponibles = $this->descubrirRelaciones($this->modelo);
    }

    private function descubrirRelaciones($modelo, $nivel = 0, $prefix = '')
    {
        if ($nivel > 2) return [];
    
        $relaciones = [];
        $instance = new $modelo;
        $reflection = new ReflectionClass($modelo);
    
        foreach ($reflection->getMethods() as $method) {
            if ($method->class !== get_class($instance)) continue;
            if (!empty($method->getParameters())) continue;
    
            try {
                $return = $method->invoke($instance);
                if ($return instanceof Relation) {
                    $relNombre = $method->getName();
                    $nombreCompleto = $prefix . $relNombre;
                    $relaciones[$nombreCompleto] = $nombreCompleto;
    
                    // Guardar columnas
                    $tabla = $return->getRelated()->getTable();
                    $this->columnasRelaciones[$nombreCompleto] = Schema::getColumnListing($tabla);
    
                    // Relaciones hijas
                    $relacionesHijas = $this->descubrirRelaciones(
                        get_class($return->getRelated()),
                        $nivel + 1,
                        $nombreCompleto . '.'
                    );
                    $relaciones = array_merge($relaciones, $relacionesHijas);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    
        return $relaciones;
    }


    public function generarReporte()
        {
            if (!$this->modelo || !class_exists($this->modelo)) return;

            $query = $this->modelo::query();

            // Relaciones
            if (!empty($this->relacionesSeleccionadas)) {
                $query->with($this->relacionesSeleccionadas);
            }

            // Filtros
            foreach ($this->filtros as $columna => $valor) {
                if (!empty($valor)) {
                    $query->where($columna, 'like', "%$valor%");
                }
            }

            // Agrupamiento
            if ($this->grupo) {
                $query->groupBy($this->grupo);
            }

            // Ordenamiento
            if ($this->ordenColumna) {
                $query->orderBy($this->ordenColumna, $this->ordenDireccion ?? 'asc');
            }

            // Límite
            if ($this->limite) {
                $query->limit($this->limite);
            }
            $this->resultados = $query->get()->map(function ($item) {
                $datos = collect($item->toArray())->only($this->columnasSeleccionadas);
            
                // Procesar relaciones y columnas seleccionadas
                foreach ($this->columnasRelacionesSeleccionadas as $rel => $cols) {
                    $relacion = data_get($item, $rel);
            
                    if (is_null($relacion)) {
                        $datos[$rel] = null;
                    } elseif ($relacion instanceof \Illuminate\Support\Collection || is_array($relacion)) {
                        $datos[$rel] = collect($relacion)->map(function ($hijo) use ($cols) {
                            return collect($hijo)->only($cols);
                        });
                    } else {
                        $datos[$rel] = collect($relacion)->only($cols);
                    }
                }
            
                return $datos;
            });
            
        }


    public function render()
    {
        $modelosDisponibles = [
            'Usuarios' => \App\Models\User::class,
            'Obras' => \App\Models\Obras::class,
            'Empleados' => \App\Models\Empleados::class,
            // Agrega tus modelos aquí
        ];

        $columnas = [];
        if ($this->modelo) {
            $table = (new $this->modelo)->getTable();
            $columnas = Schema::getColumnListing($table);
        }

        

        return view('livewire.reportes.index', compact('modelosDisponibles', 'columnas'));
    }
}