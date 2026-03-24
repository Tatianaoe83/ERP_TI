<?php

namespace App\Http\Livewire;

use App\Helpers\JoinHelper;
use App\Helpers\ReporteHelper;
use App\Helpers\RelacionesUniversales;
use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Services\ReporteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

class Reporte extends Component
{
    public $modelo;
    public $titulo;
    public $columnas            = [];
    public $columnasSeleccionadas = [];
    public $filtros             = [];
    public $ordenColumna;
    public $ordenDireccion      = 'asc';
    public $limite;
    public $relaciones          = [];
    public $relacionesSeleccionadas = [];
    public $relacionActual      = [];
    public $columnasRelacionActual = [];
    public $columnasPorRelacion = [];
    public $tablasDisponibles   = [];
    public $modeloClase;
    public $filtroAutocompletarIndex = null;

    /** @var array Relaciones centralizadas — se inicializan en mount() */
    protected array $relacionesUniversales;

    protected $listeners = [
        'valorAutocompletado' => 'valorAutocompletado',
    ];

    // ── Lifecycle ──────────────────────────────────────────────────────────────
    // boot() se ejecuta SIEMPRE, en cada clic y recarga del componente
    public function boot()
    {
        $this->relacionesUniversales = RelacionesUniversales::get();
    }

    // mount() se ejecuta SOLO UNA VEZ al abrir la página
    public function mount()
    {
        $this->tablasDisponibles = ReporteService::obtenerTablas();
    }

    public function initModel()
    {
        $this->modelo = 'empleados';
        $this->updatedModelo();
    }

    // ── Watchers ───────────────────────────────────────────────────────────────

    public function updatedModelo()
    {
        if (!$this->modelo) return;

        $this->relacionesSeleccionadas = [];
        $this->relacionActual          = '';
        $this->columnasSeleccionadas   = [];
        $this->columnasPorRelacion     = [];
        $this->filtros                 = [];
        $this->ordenColumna            = '';
        $this->ordenDireccion          = 'asc';
        $this->limite                  = null;

        $this->modeloClase = ReporteService::modeloDesdeTabla($this->modelo);

        if ($this->modeloClase) {
            $this->columnas   = ReporteService::listarColumnas($this->modelo);
            $this->relaciones = ReporteService::relacionesTablas($this->modeloClase);
        } else {
            $this->resetEstado();
        }
    }

    public function updatedRelacionActual($relacion)
    {
        if (!$relacion) return;

        if (in_array($relacion, $this->relacionesSeleccionadas)) {
            // Si ya estaba seleccionada → quitarla y remover sus columnas
            if (isset($this->columnasPorRelacion[$relacion])) {
                $this->columnasSeleccionadas = array_values(
                    array_diff($this->columnasSeleccionadas, $this->columnasPorRelacion[$relacion])
                );
            }

            $this->relacionesSeleccionadas = array_values(
                array_filter($this->relacionesSeleccionadas, fn($r) => $r !== $relacion)
            );

            unset($this->columnasPorRelacion[$relacion]);
        } else {
            // Agregar relación y sus columnas disponibles
            $this->relacionesSeleccionadas[]        = $relacion;
            $this->columnasPorRelacion[$relacion]   = ReporteService::obtenerColumnasRelacion($this->modeloClase, $relacion);
        }

        $this->relacionActual = '';
    }

    public function updatedFiltros($value, $key)
    {
        if (Str::endsWith($key, '.operador')) {
            $index = explode('.', $key)[0];

            $this->filtros[$index]['valor'] = $this->filtros[$index]['operador'] === 'between'
                ? ['inicio' => '', 'fin' => '']
                : '';
        }
    }

    // ── Acciones ───────────────────────────────────────────────────────────────

    public function mostrarPreview()
    {
        $metadata = [
            'tabla_principal' => $this->modelo,
            'tabla_relacion'  => $this->relacionesSeleccionadas,
            'columnas'        => $this->columnasSeleccionadas,
            'filtros'         => $this->filtros,
            'ordenColumna'    => $this->ordenColumna,
            'ordenDireccion'  => $this->ordenDireccion,
            'limite'          => 10,
        ];

        try {
            $this->resultado = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
            $this->dispatchBrowserEvent('mostrarPreviewModal');
        } catch (\Exception $e) {
            $this->addError('preview', $e->getMessage());
        }
    }

    public function generarReporte()
    {
        if (!$this->modeloClase) {
            $this->addError('modelo', 'La tabla seleccionada no es válida.');
            return;
        }

        $this->validate([
            'titulo'                => 'required|string|max:255',
            'modelo'                => 'required',
            'columnasSeleccionadas' => 'required|array|min:1',
        ]);

        $query     = $this->modeloClase::query();
        $tablaBase = $query->getModel()->getTable();

        $joinsHechos = [];
        $columnas    = [];

        foreach ($this->columnasSeleccionadas as $columna) {
            if (strpos($columna, '.') !== false) {
                $columnas[] = $columna;

                foreach ($this->relacionesSeleccionadas as $relacion) {
                    try {
                        $camino = JoinHelper::resolverRutaJoins($tablaBase, $relacion, $this->relacionesUniversales);
                        foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                            if (!in_array($tablaJoin, $joinsHechos)) {
                                $query->join($tablaJoin, $from, $op, $to);
                                $joinsHechos[] = $tablaJoin;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("generarReporte: no se pudo resolver ruta para '{$relacion}': " . $e->getMessage());
                    }
                }
            } else {
                $columnas[] = $tablaBase . '.' . $columna;
            }
        }

        $query->select($columnas);

        foreach ($this->filtros as $filtro) {
            if (empty($filtro['columna']) || !isset($filtro['valor'])) continue;

            $columna  = $filtro['columna'];
            $operador = $filtro['operador'] ?? '=';
            $valor    = $filtro['valor'];

            if ($operador === 'between') {
                if (is_array($valor)) {
                    $inicio = $valor['inicio'] ?? null;
                    $fin    = $valor['fin']    ?? null;
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

    public function agregarFiltro()
    {
        $this->filtros[] = [
            'id'       => Str::uuid()->toString(),
            'columna'  => '',
            'operador' => '=',
            'valor'    => '',
        ];
    }

    public function eliminarFiltro($index)
    {
        unset($this->filtros[$index]);
    }

    public function agregarRelacion()
    {
        if ($this->relacionActual && !in_array($this->relacionActual, $this->relacionesSeleccionadas)) {
            $this->relacionesSeleccionadas[]      = $this->relacionActual;
            $this->columnasPorRelacion[$this->relacionActual] = ReporteService::obtenerColumnasRelacion(
                $this->modeloClase,
                $this->relacionActual
            );
        }
    }

    public function valorAutocompletado($value, $index)
    {
        if (isset($this->filtros[$index])) {
            $this->filtros[$index]['valor'] = $value;
        }
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.reporte', [
            'tablasDisponibles'  => $this->tablasDisponibles,
            'columnas'           => $this->columnas,
            'relaciones'         => $this->relaciones,
            'relacionActual'     => $this->relacionActual,
            'columnasPorRelacion' => $this->columnasPorRelacion,
        ]);
    }

    // ── Helpers privados ───────────────────────────────────────────────────────

    private function saveSql($sql, $columnasPrefijo)
    {
        DB::table('query_forms')->insert([
            'title'         => $this->titulo,
            'query_details' => json_encode([
                'tabla_principal' => $this->modelo,
                'tabla_relacion'  => $this->relacionesSeleccionadas,
                'columnas'        => $columnasPrefijo,
                'filtros'         => $this->filtros,
                'ordenColumna'    => $this->ordenColumna,
                'ordenDireccion'  => $this->ordenDireccion,
                'limite'          => $this->limite,
            ]),
        ]);
    }

    private function resetEstado()
    {
        $this->modelo                  = '';
        $this->titulo                  = '';
        $this->columnas                = [];
        $this->columnasSeleccionadas   = [];
        $this->filtros                 = [];
        $this->ordenColumna            = null;
        $this->ordenDireccion          = 'asc';
        $this->limite                  = null;
        $this->relaciones              = [];
        $this->relacionesSeleccionadas = [];
        $this->relacionActual          = '';
        $this->columnasRelacionActual  = [];
    }
}