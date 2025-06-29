<?php

namespace App\Http\Livewire;

use App\Helpers\JoinHelper;
use App\Helpers\ReporteHelper;
use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Services\ReporteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

class Reporte extends Component
{
    public $modelo;
    public $titulo;
    public $columnas = [];
    public $columnasSeleccionadas = [];
    public $filtros = [];
    public $ordenColumna;
    public $ordenDireccion = 'asc';
    public $limite;
    public $relaciones = [];
    public $relacionesSeleccionadas = [];
    public $relacionActual = [];
    public $columnasRelacionActual = [];
    public $columnasPorRelacion = [];
    public $tablasDisponibles = [];
    public $modeloClase;

    public function mostrarPreview()
    {
        $metadata = [
            'tabla_principal'   => $this->modelo,
            'tabla_relacion'    => $this->relacionesSeleccionadas,
            'columnas'          => $this->columnasSeleccionadas,
            'filtros'           => $this->filtros,
            'ordenColumna'      => $this->ordenColumna,
            'ordenDireccion'    => $this->ordenDireccion,
            'limite'            => 10,
        ];

        try {
            $this->resultado = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
            $this->dispatchBrowserEvent('mostrarPreviewModal');
        } catch (\Exception $e) {
            $this->addError('preview', $e->getMessage());
        }
    }

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
        'categorias' => [
            'tiposdecategorias' => ['tiposdecategorias.ID', '=', 'categorias.TipoID'],
        ],
        'departamentos' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'departamentos.GerenciaID'],
        ],
        'empleados' => [
            'obras' => ['obras.ObraID', '=', 'empleados.ObraID'],
            'puestos' => ['puestos.PuestoID', '=', 'empleados.PuestoID'],
            'inventarioinsumo' => ['inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventarioequipo' => ['inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventariolineas' => ['inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID']
        ],
        'equipos' => [
            'categorias' => ['categorias.ID', '=', 'equipos.ID'],
        ],
        'gerencia' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID'],
        ],
        'gerencias_usuarios' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'gerencias_usuarios.GerencialID'],
            'users' => ['users.id', '=', 'gerencias_usuarios.user_id'],
        ],
        'insumos' => [
            'categorias' => ['categorias.CategoriaID', '=', 'insumos.CategoriaID'],
        ],
        'inventarioequipo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioequipo.EmpleadoID'],
        ],
        'inventarioinsumo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioinsumo.EmpleadoID'],
            'insumos' => ['insumos.InsumoID', '=', 'inventarioinsumo.InsumoID'],
        ],
        'inventariolineas' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventariolineas.EmpleadoID'],
            'lineastelefonicas' => ['lineastelefonicas.LineaID', '=', 'inventariolineas.LineaID'],
            'obras' => ['obras.ObraID', '=', 'inventariolineas.ObraID'],
        ],
        'lineastelefonicas' => [
            'obras' => ['obras.ObraID', '=', 'lineastelefonicas.ObraID'],
            'planes' => ['planes.ID', '=', 'lineastelefonicas.PlanID']
        ],
        'obras' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'obras.UnidadNegocioID'],
        ],
        'puestos' => [
            'departamentos' => ['departamentos.DepartamentoID', '=', 'puestos.DepartamentoID'],
        ],
        'planes' => [
            'companiaslineastelefonicas' => ['companiaslineastelefonicas.ID', '=', 'planes.CompaniaID'],
        ]
    ];

    public function generarReporte()
    {

        if (!$this->modeloClase) {
            $this->addError('modelo', 'La tabla seleccionada no es válida.');
            return;
        }

        $this->validate([
            'titulo' => 'required|string|max:255',
            'modelo' => 'required',
            'columnasSeleccionadas' => 'required|array|min:1',
        ]);

        $query = $this->modeloClase::query();
        $tablaBase = $query->getModel()->getTable();

        $joinsHechos = [];
        $columnas = [];

        foreach ($this->columnasSeleccionadas as $columna) {
            if (strpos($columna, '.') !== false) {
                $columnas[] = $columna;
                $partes = explode('.', $columna);
                $tablaDestino = $partes[0];
                foreach ($this->relacionesSeleccionadas as $relacion) {
                    $camino = JoinHelper::resolverRutaJoins($tablaBase, $relacion, $this->relacionesUniversales);

                    foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                        if (!in_array($tablaJoin, $joinsHechos)) {
                            $query->join($tablaJoin, $from, $op, $to);
                            $joinsHechos[] = $tablaJoin;
                        }
                    }
                }

                foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                    if (!in_array($tablaJoin, $joinsHechos)) {
                        $query->join($tablaJoin, $from, $op, $to);
                        $joinsHechos[] = $tablaJoin;
                    }
                }
            } else {
                $columnas[] = $tablaBase . '.' . $columna;
            }
        }

        $query->select($columnas);

        foreach ($this->filtros as $filtro) {
            if (empty($filtro['columna']) || !isset($filtro['valor'])) {
                continue;
            }

            $columna = $filtro['columna'];
            $operador = $filtro['operador'] ?? '=';
            $valor = $filtro['valor'];

            if ($operador === 'between') {
                if (is_array($valor)) {
                    $inicio = $valor['inicio'] ?? null;
                    $fin = $valor['fin'] ?? null;

                    if (!is_null($inicio) && !is_null($fin)) {
                        $query->whereBetween($columna, [$inicio, $fin]);
                    }
                }
            } elseif (!is_array($valor)) {
                if ($operador === 'like') {
                    $valor = '%' . $valor . '%';
                }

                $query->where($columna, $operador, $valor);
            }
        }

        if ($this->ordenColumna && in_array($this->ordenDireccion, ['asc', 'desc'])) {
            $query->orderBy($this->ordenColumna, $this->ordenDireccion);
        }

        if ($this->limite) {
            $query->limit($this->limite);
        }

       

        $this->saveSql($query->getBindings(), $columnas);

        return redirect()->route('reportes.index');
    }

    public function saveSql($sql, $columnasPrefijo)
    {
        $result = DB::table('query_forms')->insert([
            'title' => $this->titulo,
            'query_metadata' => json_encode([
                'tabla_principal' => $this->modelo,
                'tabla_relacion' => $this->relacionesSeleccionadas,
                'columnas' => $columnasPrefijo,
                'filtros' => $this->filtros,
                'ordenColumna' => $this->ordenColumna,
                'ordenDireccion' => $this->ordenDireccion,
                'limite' => $this->limite
            ])
        ], JSON_PRETTY_PRINT);
    }

    public function updatedFiltros($value, $key)
    {
        if (Str::endsWith($key, '.operador')) {
            $index = explode('.', $key)[0];

            if ($this->filtros[$index]['operador'] === 'between') {
                $this->filtros[$index]['valor'] = ['inicio' => '', 'fin' => ''];
            } else {
                $this->filtros[$index]['valor'] = '';
            }
        }
    }

    public function updatedRelacionActual($relacion)
    {
        if (in_array($relacion, $this->relacionesSeleccionadas)) {
            $this->relacionesSeleccionadas = array_filter(
                $this->relacionesSeleccionadas,
                fn($r) => $r !== $relacion
            );
            unset($this->columnasPorRelacion[$relacion]);
        } else {
            $this->relacionesSeleccionadas[] = $relacion;
            $this->columnasPorRelacion[$relacion] = ReporteService::obtenerColumnasRelacion($this->modeloClase, $relacion);
        }

        $this->relacionActual = '';
    }

    private function resetEstado()
    {
        $this->modelo = '';
        $this->titulo = '';
        $this->columnas = [];
        $this->columnasSeleccionadas = [];
        $this->filtros = [];
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

    public function agregarRelacion()
    {
        if ($this->relacionActual && !in_array($this->relacionActual, $this->relacionesSeleccionadas)) {
            $this->relacionesSeleccionadas[] = $this->relacionActual;

            $columnas = ReporteService::obtenerColumnasRelacion($this->modeloClase, $this->relacionActual);

            $this->columnasPorRelacion[$this->relacionActual] = $columnas;
        }

        $this->relacionActual;
    }

    public function render()
    {
        return view('livewire.reporte', [
            'tablasDisponibles' => $this->tablasDisponibles,
            'columnas' => $this->columnas,
            'relaciones' => $this->relaciones,
            'relacionActual' => $this->relacionActual,
            'columnasPorRelacion' => $this->columnasPorRelacion,
        ]);
    }
}
