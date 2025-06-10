<?php

namespace App\Http\Livewire\Reportes;

use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Services\ReporteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class Index extends Component
{
    public $modelo;
    public $titulo;
    public $columnas = [];
    public $columnasSeleccionadas = [];
    public $filtros = [];
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

    protected array $relacionesUniversales = [
        'empleados' => [
            'puestos' => ['puestos.PuestoID', '=', 'empleados.PuestoID'],
        ],
        'puestos' => [
            'departamentos' => ['departamentos.DepartamentoID', '=', 'puestos.DepartamentoID'],
        ],
        'obras' => [
            'empleados' => ['empleados.ObraID', '=', 'obras.ObraID']
        ],
        'departamentos' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'departamentos.GerenciaID'],
        ],
        'gerencia' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID'],
        ],
        'unidadesdenegocio' => [
            'gerencia' => ['gerencia.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID']
        ],
        'categorias' => [
            'tiposdecategorias' => ['tiposdecategorias.ID', '=', 'categorias.TipoID']
        ],
        'gerencias_usuarios' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'gerencias_usuarios.GerenciaID']
        ],
        'insumos' => [
            'categorias' => ['categorias.CategoriaID', '=', 'insumos.CategoriaID']
        ],
        'lineastelefonicas' => [
            'planes' => ['planes.PlanID', '=', 'lineastelefonicas.PlanID']
        ],
        'planes' => [
            'companiaslineastelefonicas' => ['companialineastelefonicas.ID', '=', 'planes.CompaniaID']
        ],
    ];

    public function generarReporte()
    {
        if (!$this->modeloClase) return;

        $query = $this->modeloClase::query();
        $tablaBase = $query->getModel()->getTable();

        $joinsHechos = [];
        $columnas = [];

        foreach ($this->columnasSeleccionadas as $columna) {
            if (strpos($columna, '.') !== false) {
                $partes = explode('.', $columna);
                $campoFinal = array_pop($partes);
                $tablaDestino = end($partes);


                $camino = $this->resolverRutaJoins($tablaBase, $tablaDestino);

                foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                    if (!in_array($tablaJoin, $joinsHechos)) {
                        $query->join($tablaJoin, $from, $op, $to);
                        $joinsHechos[] = $tablaJoin;
                    }
                }

                $columnas[] = $tablaDestino . '.' . $campoFinal;
            } else {
                $columnas[] = $tablaBase . '.' . $columna;
            }
        }

        $query->select($columnas);

        foreach ($this->filtros as $filtro) {
            if (!empty($filtro['columna']) && isset($filtro['valor'])) {
                $valor = $filtro['valor'];
                if ($filtro['operador'] === 'like') {
                    $valor = '%' . $valor . '%';
                }
                $query->where($filtro['columna'], $filtro['operador'] ?? '=', $valor);
            }
        }

        if ($this->grupo) {
            $query->groupBy($this->grupo);
        }

        if ($this->ordenColumna && in_array($this->ordenDireccion, ['asc', 'desc'])) {
            $query->orderBy($this->ordenColumna, $this->ordenDireccion);
        }

        if ($this->limite) {
            $query->limit($this->limite);
        }

        //dd(vsprintf(str_replace('?', "'%s'", $query->toSql()), $query->getBindings()));

        $this->saveSql(
            vsprintf(
                str_replace('?', "'%s'", $query->toSql()),
                $query->getBindings()
            )
        );

        $this->resetEstado();
    }

    public function saveSql($sql)
    {
        DB::table('query_forms')->insert([
            'title' => $this->titulo,
            'query_details' => json_encode($sql),
        ]);
    }

    protected function resolverRutaJoins($desde, $hasta)
    {
        $camino = [];
        $visitado = [];

        $encontrado = $this->buscarRuta($desde, $hasta, $camino, $visitado);

        if (!$encontrado) {
            throw new \Exception("No se pudo encontrar una ruta entre '$desde' y '$hasta'");
        }

        return $camino;
    }

    protected function buscarRuta($actual, $destino, &$camino, &$visitado)
    {
        if ($actual === $destino) {
            return true;
        }

        if (isset($visitado[$actual])) return false;

        $visitado[$actual] = true;

        if (!isset($this->relacionesUniversales[$actual])) return false;

        foreach ($this->relacionesUniversales[$actual] as $siguiente => $join) {
            $camino[] = [$siguiente, $join];
            if ($siguiente === $destino || $this->buscarRuta($siguiente, $destino, $camino, $visitado)) {
                return true;
            }
            array_pop($camino);
        }
        return false;
    }

    public function updatedRelacionActual()
    {
        $this->columnasRelacionActual = ReporteService::obtenerColumnasRelacion($this->modeloClase, $this->relacionActual);
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
        $this->filtros[] = [
            'columna' => '',
            'operador' => '=',
            'valor' => '',
        ];
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
            'relaciones' => $this->relaciones,
            'columnasRelacionActual' => $this->columnasRelacionActual,
            'relacionActual' => $this->relacionActual
        ]);
    }
}
