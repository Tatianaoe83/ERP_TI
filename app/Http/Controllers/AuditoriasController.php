<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\AuditoriaEquipo;
use App\Models\Empleados;
use App\Models\InventarioEquipo;
use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuditoriasController extends Controller
{
    /** Únicas categorías auditables: la auditoría es de equipo de cómputo. */
    private const CATEGORIA_LAPTOP = 'LAPTOP';
    private const CATEGORIA_PC     = 'PC ESCRITORIO';

    /**
     * Se audita al personal de planta y al referenciado. Los extraordinarios no:
     * su inventario es proyección de presupuesto, no equipo resguardado.
     */
    private const TIPOS_PERSONA_AUDITABLES = ['FISICA', 'REFERENCIADO'];

    /**
     * Listado de corridas. La auditoría es un evento generado, así que lo primero
     * que se ve es el historial, no los datos en vivo.
     */
    public function index()
    {
        $equipos = $this->equiposAuditables();

        $auditorias = Auditoria::with('empleado.puestos.departamentos.gerencia')
            ->orderByDesc('created_at')
            ->paginate(15);

        // Cuántas licencias tiene cada resguardante: una consulta agregada, no una
        // por empleado. Con cero no hay auditoría posible.
        $conLicencia = InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->whereIn('EmpleadoID', $equipos->pluck('EmpleadoID')->unique()->values()->all())
            ->selectRaw('EmpleadoID, COUNT(DISTINCT NombreInsumo) AS total')
            ->groupBy('EmpleadoID')
            ->pluck('total', 'EmpleadoID');

        return view('auditorias.index', [
            'auditorias'          => $auditorias,
            // Categoría, marca, modelo, serie y tipo salen del inventario en vivo:
            // la corrida sólo guarda el EmpleadoID, y de ahí se llega a sus equipos.
            'equiposPorAuditoria' => $this->equiposPorAuditoria($auditorias->getCollection()),
            'ultima'              => Auditoria::with('empleado')->orderByDesc('created_at')->first(),
            'catalogoLicencias'   => $this->catalogoLicencias(),
            'catalogoEquipos'     => $equipos,
            // La corrida es por empleado: sólo se ofrecen los que tienen algo auditable.
            // Sin licencias no hay nada que revisar, así que se marcan para bloquearlos.
            'empleados'           => $equipos
                ->unique('EmpleadoID')
                ->map(fn($e) => (object) [
                    'EmpleadoID'     => $e->EmpleadoID,
                    'NombreEmpleado' => $e->NombreEmpleado ?: 'Sin asignar',
                    'tipo_persona'   => $e->tipo_persona,
                    'gerencia'       => trim((string) $e->NombreGerencia) ?: 'Sin gerencia',
                    'departamento'   => trim((string) $e->NombreDepartamento) ?: 'Sin departamento',
                    'licencias'      => $conLicencia->get($e->EmpleadoID, 0),
                ])
                ->sortBy('NombreEmpleado', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'gerencias'           => $this->opcionesDe($equipos, 'NombreGerencia', 'Sin gerencia'),
            'departamentos'       => $this->opcionesDe($equipos, 'NombreDepartamento', 'Sin departamento'),
            'tiposPersona'        => $this->opcionesDe($equipos, 'tipo_persona', 'Sin tipo'),
        ]);
    }

    /**
     * Genera una corrida nueva: congela el inventario actual en el detalle.
     *
     * Se elige el alcance en dos ejes: qué equipos entran (todos los auditables o una
     * selección) y qué licencias se revisan.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'EmpleadoID'  => ['required', 'integer', 'exists:empleados,EmpleadoID'],
            'tipoEquipo'  => ['nullable', 'integer', 'in:0,1,2,3'],
            'equipos'     => ['array'],
            'equipos.*'   => ['integer'],
            'licencias'   => ['required', 'array', 'min:1'],
            'licencias.*' => ['string', 'max:255'],
        ], [
            'EmpleadoID.required' => 'Selecciona al empleado a auditar.',
            'EmpleadoID.exists'   => 'El empleado seleccionado ya no existe.',
            'licencias.required'  => 'Selecciona al menos una licencia para auditar.',
            'licencias.min'       => 'Selecciona al menos una licencia para auditar.',
        ]);

        $empleadoAuditado = (int) $datos['EmpleadoID'];
        $tipoEquipo = $datos['tipoEquipo'] ?? null;

        // Se normaliza contra el catálogo real para que nadie meta nombres inventados
        // por POST y la lista congelada quede con basura.
        $seleccion = collect($datos['licencias'])
            ->map(fn($n) => trim((string) $n))
            ->intersect($this->catalogoLicencias())
            ->unique()
            ->values();

        if ($seleccion->isEmpty()) {
            return back()->withErrors(['licencias' => 'Las licencias seleccionadas ya no existen en el inventario.']);
        }

        // La corrida es de un solo empleado: todo lo demás se descarta aquí.
        $equipos = $this->equiposAuditables()
            ->where('EmpleadoID', $empleadoAuditado)
            ->values();

        if ($tipoEquipo !== null) {
            $equipos = $equipos->where('tipoEquipo', $tipoEquipo)->values();
        }

        // Sin lista explícita entran todos los del empleado; con lista, sólo esos.
        // Se cruza contra los auditables porque por POST podrían llegar equipos de
        // otra categoría o de otro resguardante.
        $ids = collect($datos['equipos'] ?? [])->map(fn($id) => (int) $id)->unique();

        if ($ids->isNotEmpty()) {
            $equipos = $equipos->whereIn('InventarioID', $ids->all())->values();
        }

        if ($equipos->isEmpty()) {
            return back()->withErrors([
                'equipos' => 'Ese empleado no tiene equipos auditables con el alcance elegido.',
            ]);
        }

        $licenciasPorEmpleado = $this->licenciasPorEmpleado($seleccion->all());

        $licenciasDelEmpleado = collect($licenciasPorEmpleado->get($empleadoAuditado, collect()))
            ->unique('NombreInsumo')
            ->values();

        // Estado con que cerró la corrida anterior de este empleado, por licencia.
        // De ahí se arrastra el último resultado conocido en vez de arrancar todo
        // en blanco cada vez.
        $previas = $this->estadoAnteriorPorLicencia($empleadoAuditado);

        // Licencias que la corrida pasada sí encontró y que hoy ya no están en el
        // inventario: entran como baja para dejar constancia de que desaparecieron.
        $bajas = $previas
            ->reject(fn($p) => $licenciasDelEmpleado->contains('NombreInsumo', $p->NombreLicencia))
            ->filter(fn($p) => $p->tiene_licencia)
            ->values();

        // Una corrida sin licencias vivas ni bajas que reportar no audita nada.
        if ($licenciasDelEmpleado->isEmpty() && $bajas->isEmpty()) {
            $tieneAlguna = InventarioInsumo::where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
                ->where('EmpleadoID', $empleadoAuditado)
                ->exists();

            return back()->withErrors([
                'licencias' => $tieneAlguna
                    ? 'Ese empleado no tiene ninguna de las licencias seleccionadas. Elige otras licencias o cambia de empleado.'
                    : 'Ese empleado no tiene ninguna licencia registrada en el inventario, así que no hay nada que auditar.',
            ]);
        }

        $usuario = auth()->user();
        $ahora = Carbon::now();

        $auditoria = DB::transaction(function () use ($usuario, $ahora, $licenciasDelEmpleado, $seleccion, $empleadoAuditado, $tipoEquipo, $previas, $bajas) {
            $auditoria = Auditoria::create([
                'Folio'                     => $this->siguienteFolio($ahora),
                'id_empleado'               => $usuario?->id,
                'generada_por_nombre'       => $usuario?->name ?: $usuario?->username,
                'EmpleadoID'                => $empleadoAuditado,
                'tipoEquipo'                => $tipoEquipo,
                'licencias_auditadas'       => $seleccion->all(),
                'total_licencias_auditadas' => $seleccion->count(),
            ]);

            // Una fila por licencia del empleado auditado. Los datos del equipo y del
            // empleado no se copian: salen del empleado por relación cuando se lee la
            // corrida. Las licencias son del resguardante, no del equipo, así que
            // repetirlas por cada equipo sólo duplicaba la misma información.
            $base = [
                'auditoria_id' => $auditoria->id,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ];

            // Arrastre: lo ya auditado conserva su último resultado conocido; lo que
            // no estaba en la corrida anterior nace sin revisar, a la fuerza. Las
            // observaciones NUNCA se arrastran: son un hecho fechado de su corrida.
            $filas = $licenciasDelEmpleado->map(function ($licencia) use ($base, $previas) {
                $previa = $previas->get($licencia->NombreInsumo);

                return $base + [
                    'NombreLicencia' => $licencia->NombreInsumo,
                    'tiene_licencia' => 1,
                    'original'       => $previa->original ?? null,
                    'observaciones'  => null,
                ];
            });

            // Bajas: estaban en la corrida anterior y ya no en el inventario.
            $filas = $filas->concat($bajas->map(fn($previa) => $base + [
                'NombreLicencia' => $previa->NombreLicencia,
                'tiene_licencia' => 0,
                'original'       => null,
                'observaciones'  => null,
            ]));

            foreach ($filas->chunk(300) as $lote) {
                AuditoriaEquipo::insert($lote->all());
            }

            return $auditoria;
        });

        return redirect()
            ->route('auditorias.show', $auditoria->id)
            ->with('success', "Auditoría {$auditoria->Folio} generada.");
    }

    /**
     * Detalle de una corrida, armado sobre el snapshot congelado.
     */
    public function show($id)
    {
        $auditoria = Auditoria::with(['empleado.puestos.departamentos.gerencia', 'empleado.obras'])
            ->findOrFail($id);
        // El detalle ya es sólo licencias: se ordena por nombre, y las que quedaron
        // sin nombre (corrida sin licencias) van al final.
        $detalle = $auditoria->equipos()
            ->orderByRaw('NombreLicencia IS NULL, NombreLicencia')
            ->get();

        // Corrida anterior del mismo empleado: la referencia del historial. El diff
        // se calcula al abrir, no se guarda, así corregir un dato viejo lo corrige.
        $anterior = Auditoria::where('EmpleadoID', $auditoria->EmpleadoID)
            ->where('created_at', '<', $auditoria->created_at)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $previas = $anterior
            ? AuditoriaEquipo::where('auditoria_id', $anterior->id)->get()->keyBy('NombreLicencia')
            : collect();

        $comparadas = $detalle->map(function ($fila) use ($previas, $anterior) {
            $previa = $previas->get($fila->NombreLicencia);

            $fila->previa = $previa;
            $fila->marca = $this->marcaDeCambio($fila, $previa, (bool) $anterior);

            return $fila;
        });

        return view('auditorias.show', [
            'auditoria' => $auditoria,
            'detalle'   => $comparadas,
            // Equipos del empleado leídos del inventario: la corrida no los copia.
            'equipos'   => $this->equiposPorAuditoria(collect([$auditoria]))[$auditoria->id] ?? collect(),
            'anterior'  => $anterior,
            'resumen'   => [
                'nueva'  => $comparadas->where('marca', 'nueva')->count(),
                'baja'   => $comparadas->where('marca', 'baja')->count(),
                'cambio' => $comparadas->where('marca', 'cambio')->count(),
                'igual'  => $comparadas->where('marca', 'igual')->count(),
            ],
        ]);
    }

    /**
     * Captura sobre la corrida ya generada: si el equipo tiene la licencia y si es
     * original. Son los dos únicos campos editables del detalle; el resto queda
     * congelado o se lee del empleado por relación.
     *
     * "original" es tri-estado, así que acepta null explícito para volver a
     * "sin revisar" sin tener que borrar la fila.
     */
    public function actualizarLicencia($fila, Request $request)
    {
        $registro = AuditoriaEquipo::findOrFail($fila);

        $datos = $request->validate([
            'campo' => ['required', 'in:tiene_licencia,original,observaciones'],
            'valor' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($datos['campo'] === 'observaciones') {
            $registro->observaciones = trim((string) ($datos['valor'] ?? '')) ?: null;
            $registro->save();

            return response()->json([
                'success'       => true,
                'observaciones' => $registro->observaciones,
            ]);
        }

        $campo = $datos['campo'];
        $valor = $datos['valor'];

        $registro->$campo = $valor === null ? null : (bool) (int) $valor;
        $registro->save();

        return response()->json([
            'success'        => true,
            'tiene_licencia' => (bool) $registro->tiene_licencia,
            'original'       => $registro->original === null ? null : (bool) $registro->original,
        ]);
    }

    public function destroy($id)
    {
        $auditoria = Auditoria::findOrFail($id);
        $folio = $auditoria->Folio;
        $auditoria->delete(); // el detalle cae por la FK en cascada

        return redirect()
            ->route('auditorias.index')
            ->with('success', "Auditoría {$folio} eliminada.");
    }

    /** Folio consecutivo por año: AUD-2026-0001. */
    private function siguienteFolio(Carbon $fecha): string
    {
        $prefijo = 'AUD-' . $fecha->year . '-';

        $ultimo = Auditoria::where('Folio', 'LIKE', $prefijo . '%')
            ->orderByDesc('Folio')
            ->value('Folio');

        $consecutivo = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relación de una licencia con la corrida anterior:
     *   nueva  → no existía antes
     *   baja   → la anterior la tenía y ésta ya no
     *   cambio → el estado auditado no coincide con el anterior
     *   igual  → mismo estado que la vez pasada
     *
     * Sin corrida anterior todo es "nueva": es la primera auditoría del empleado.
     */
    private function marcaDeCambio($fila, $previa, bool $hayAnterior): string
    {
        if (! $hayAnterior || ! $previa) {
            return 'nueva';
        }

        if ($previa->tiene_licencia && ! $fila->tiene_licencia) {
            return 'baja';
        }

        $mismoTiene = (bool) $previa->tiene_licencia === (bool) $fila->tiene_licencia;
        // Comparación laxa a propósito: null y "sin revisar" son el mismo estado.
        $mismoOrigen = $previa->original === $fila->original;

        return $mismoTiene && $mismoOrigen ? 'igual' : 'cambio';
    }

    /**
     * Corrida anterior de un empleado. Es la referencia contra la que se compara y
     * de la que se arrastra el último resultado conocido.
     */
    private function corridaAnterior(int $empleadoID, ?int $excepto = null): ?Auditoria
    {
        return Auditoria::where('EmpleadoID', $empleadoID)
            ->when($excepto, fn($q) => $q->where('id', '<>', $excepto))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Cómo cerró cada licencia en la corrida anterior del empleado, indexado por
     * NombreLicencia. Vacío si es su primera auditoría.
     *
     * @return \Illuminate\Support\Collection
     */
    private function estadoAnteriorPorLicencia(int $empleadoID, ?int $excepto = null)
    {
        $anterior = $this->corridaAnterior($empleadoID, $excepto);

        if (! $anterior) {
            return collect();
        }

        return AuditoriaEquipo::where('auditoria_id', $anterior->id)
            ->whereNotNull('NombreLicencia')
            ->get()
            ->keyBy('NombreLicencia');
    }

    /**
     * Equipos auditables del empleado de cada corrida, leídos del inventario en vivo:
     * el detalle sólo guarda licencias, así que categoría, marca, modelo y serie se
     * resuelven por EmpleadoID.
     *
     * Una sola consulta para toda la página; pedirlos por fila sería un N+1.
     *
     * @param \Illuminate\Support\Collection $auditorias
     * @return array [auditoria_id => Collection<equipo>]
     */
    private function equiposPorAuditoria($auditorias): array
    {
        $porEmpleado = $auditorias->pluck('EmpleadoID', 'id')->filter();

        if ($porEmpleado->isEmpty()) {
            return [];
        }

        $equipos = InventarioEquipo::query()
            ->whereIn('EmpleadoID', $porEmpleado->unique()->values()->all())
            ->whereIn('CategoriaEquipo', [self::CATEGORIA_LAPTOP, self::CATEGORIA_PC])
            ->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Marca', 'Modelo', 'NumSerie', 'Folio', 'tipoEquipo')
            ->orderBy('CategoriaEquipo')
            ->orderBy('Marca')
            ->get()
            ->groupBy('EmpleadoID');

        return $porEmpleado
            ->map(fn($empleadoID) => $equipos[$empleadoID] ?? collect())
            ->all();
    }

    /** Valores únicos y ordenados para los filtros del modal. */
    private function opcionesDe($equipos, string $campo, string $vacio)
    {
        return $equipos
            ->pluck($campo)
            ->map(fn($v) => trim((string) $v) ?: $vacio)
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Universo auditable: laptops y PC de escritorio resguardadas por personal físico.
     *
     * Se excluye todo lo demás — periféricos, telefonía, redes — y al personal
     * referenciado o extraordinario, que no entra a la revisión. El join trae al
     * resguardante de una vez para no disparar una consulta por fila.
     */
    private function equiposAuditables()
    {
        return InventarioEquipo::query()
            ->join('empleados', 'empleados.EmpleadoID', '=', 'inventarioequipo.EmpleadoID')
            ->leftJoin('puestos', 'puestos.PuestoID', '=', 'empleados.PuestoID')
            ->leftJoin('departamentos', 'departamentos.DepartamentoID', '=', 'puestos.DepartamentoID')
            ->leftJoin('gerencia', 'gerencia.GerenciaID', '=', 'departamentos.GerenciaID')
            ->whereIn('empleados.tipo_persona', self::TIPOS_PERSONA_AUDITABLES)
            ->whereIn('inventarioequipo.CategoriaEquipo', [self::CATEGORIA_LAPTOP, self::CATEGORIA_PC])
            ->select(
                'inventarioequipo.*',
                'empleados.NombreEmpleado',
                'empleados.tipo_persona',
                'departamentos.NombreDepartamento',
                'gerencia.NombreGerencia'
            )
            ->orderBy('gerencia.NombreGerencia')
            ->orderBy('empleados.NombreEmpleado')
            ->get();
    }

    /**
     * Licencias por empleado. Sólo la categoría LICENCIA: el resto de insumos
     * (internet, hosting, accesorios) no entra a la auditoría de software.
     *
     * @param array $seleccion NombreInsumo elegidos en la vista; sólo esos se congelan.
     */
    private function licenciasPorEmpleado(array $seleccion)
    {
        return InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->whereIn('NombreInsumo', $seleccion)
            ->get()
            ->groupBy('EmpleadoID');
    }

    /**
     * Nombres de licencia existentes. Es lo que se ofrece a elegir: se audita por
     * producto, no renglón por renglón.
     */
    private function catalogoLicencias()
    {
        return InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->whereNotNull('NombreInsumo')
            ->where('NombreInsumo', '<>', '')
            ->pluck('NombreInsumo')
            ->map(fn($nombre) => trim((string) $nombre))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

}
