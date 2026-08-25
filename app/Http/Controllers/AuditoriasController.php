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
     * Meses que una licencia auditada se considera al día. Pasado ese plazo, el
     * último resultado sigue siendo el conocido, pero ya no representa el presente.
     */
    private const MESES_VIGENCIA = 12;

    /** El index lo pide dos veces (semáforo del modal y cobertura); se calcula una. */
    private ?array $estadoLicencias = null;

    /**
     * Listado único de auditorías, agrupado por el par (empleado, equipo).
     *
     * Cada corrida se sigue guardando por separado —el historial es el producto del
     * módulo— pero la lista no muestra una fila por corrida: muestra una por par y
     * despliega su historial al abrirla. Con auditorías periódicas de 200+ empleados,
     * la lista plana se vuelve ilegible en el primer trimestre.
     *
     * Agrupar es lectura, nunca escritura: aquí no se actualiza ni se colapsa nada
     * en la base, sólo se presenta distinto.
     */
    public function index()
    {
        $equipos = $this->equiposAuditables();

        // Cuántas licencias tiene cada resguardante: una consulta agregada, no una
        // por empleado. Con cero no hay auditoría posible.
        $conLicencia = InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->whereIn('EmpleadoID', $equipos->pluck('EmpleadoID')->unique()->values()->all())
            ->selectRaw('EmpleadoID, COUNT(DISTINCT NombreInsumo) AS total')
            ->groupBy('EmpleadoID')
            ->pluck('total', 'EmpleadoID');

        return view('auditorias.index', [
            'grupos'              => $this->gruposDeAuditorias(),
            // Estado vigente por empleado: alimenta el semáforo del modal.
            'estadoLicencias'     => $this->estadoLicenciasPorEmpleado(),
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
     * Una corrida = una visita = un empleado y UN equipo. Si el empleado resguarda
     * dos máquinas se generan dos corridas, no una con las dos adentro: el equipo
     * es el contexto de la visita, no la llave del detalle.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'EmpleadoID'   => ['required', 'integer', 'exists:empleados,EmpleadoID'],
            'InventarioID' => ['required', 'integer'],
            'licencias'    => ['required', 'array', 'min:1'],
            'licencias.*'  => ['string', 'max:255'],
        ], [
            'EmpleadoID.required'   => 'Selecciona al empleado a auditar.',
            'EmpleadoID.exists'     => 'El empleado seleccionado ya no existe.',
            'InventarioID.required' => 'Selecciona el equipo que se está revisando.',
            'licencias.required'    => 'Selecciona al menos una licencia para auditar.',
            'licencias.min'         => 'Selecciona al menos una licencia para auditar.',
        ]);

        $empleadoAuditado = (int) $datos['EmpleadoID'];

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

        // El equipo se cruza contra los auditables del empleado elegido: por POST
        // podría llegar uno de otra categoría o de otro resguardante.
        $equipo = $this->equiposAuditables()
            ->where('EmpleadoID', $empleadoAuditado)
            ->firstWhere('InventarioID', (int) $datos['InventarioID']);

        if (! $equipo) {
            return back()->withErrors([
                'InventarioID' => 'Ese equipo no pertenece al empleado elegido o no es auditable.',
            ]);
        }

        // La modalidad se lee del equipo revisado, no de un filtro del modal: es un
        // hecho de la máquina, no del alcance con que se buscó.
        $tipoEquipo = $equipo->tipoEquipo === null ? null : (int) $equipo->tipoEquipo;

        $licenciasPorEmpleado = $this->licenciasPorEmpleado($seleccion->all());

        $licenciasDelEmpleado = collect($licenciasPorEmpleado->get($empleadoAuditado, collect()))
            ->unique('NombreInsumo')
            ->values();

        // Estado con que cerró la corrida anterior de este empleado, por licencia.
        // De ahí se arrastra el último resultado conocido en vez de arrancar todo
        // en blanco cada vez.
        $previas = $this->estadoAnteriorPorLicencia($empleadoAuditado);

        // Todas las licencias que el empleado resguarda hoy, sin filtrar por el alcance
        // elegido: una baja se mide contra el inventario completo, no contra lo que se
        // pidió revisar. Si no, dejar una licencia fuera del alcance la reportaría como
        // desaparecida cuando el empleado la sigue teniendo.
        $resguardadasHoy = InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->where('EmpleadoID', $empleadoAuditado)
            ->pluck('NombreInsumo')
            ->map(fn($nombre) => trim((string) $nombre))
            ->filter()
            ->unique();

        // Baja = la corrida pasada sí la encontró, entra en el alcance de hoy y ya no
        // está en el inventario. Las tres condiciones, no dos.
        $bajas = $previas
            ->filter(fn($p) => $p->tiene_licencia)
            ->filter(fn($p) => $seleccion->contains($p->NombreLicencia))
            ->reject(fn($p) => $resguardadasHoy->contains($p->NombreLicencia))
            ->values();

        // Nombres que la corrida reporta en negativo: las bajas de arriba más las que
        // el auditor pidió expresamente aunque el inventario no se las tenga asignadas
        // (el modal las muestra destildadas para que pueda marcarlas si encuentra una
        // instalada sin registrar). Comparten fila y llave, así que van en un solo set.
        $sinResguardo = $seleccion
            ->merge($bajas->pluck('NombreLicencia'))
            ->unique()
            ->reject(fn($nombre) => $licenciasDelEmpleado->contains('NombreInsumo', $nombre))
            ->values();

        // Una corrida sin licencias vivas ni bajas que reportar no audita nada. Pedir
        // sólo ajenas no cuenta: casi siempre significa que se eligió mal al empleado.
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

        $auditoria = DB::transaction(function () use ($usuario, $ahora, $licenciasDelEmpleado, $seleccion, $empleadoAuditado, $equipo, $tipoEquipo, $previas, $sinResguardo) {
            $auditoria = Auditoria::create([
                'Folio'                     => $this->siguienteFolio($ahora),
                'id_empleado'               => $usuario?->id,
                'generada_por_nombre'       => $usuario?->name ?: $usuario?->username,
                'EmpleadoID'                => $empleadoAuditado,
                'InventarioID'              => $equipo->InventarioID,
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

            // Sin resguardo: bajas de la corrida anterior y licencias que se pidieron
            // revisar sin tenerlas asignadas. Nacen en "no la tiene"; si el auditor
            // encuentra una instalada, la cambia desde el detalle.
            $filas = $filas->concat($sinResguardo->map(fn($nombre) => $base + [
                'NombreLicencia' => $nombre,
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
        $auditoria = Auditoria::with(['empleado.puestos.departamentos.gerencia', 'empleado.obras', 'equipo'])
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
            // El equipo revisado, leído del inventario: la corrida sólo guarda cuál fue.
            'equipo'    => $auditoria->equipo,
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
     * Último resultado conocido de cada par (empleado, licencia), sin importar en qué
     * corrida se capturó. Es la fuente del semáforo: lo que no aparece aquí nunca se
     * ha revisado, y lo que aparece con fecha vieja ya no representa al presente.
     *
     * Una sola consulta con ventana en vez de una corrida por empleado.
     *
     * @return \Illuminate\Support\Collection keyBy "EmpleadoID|NombreLicencia"
     */
    private function estadoVigente()
    {
        $ultimas = DB::table('auditorias_equipos as ae')
            ->join('auditorias as a', 'a.id', '=', 'ae.auditoria_id')
            ->whereNotNull('ae.NombreLicencia')
            ->whereNotNull('a.EmpleadoID')
            ->selectRaw(
                'a.EmpleadoID, a.Folio, a.created_at AS fecha, ae.NombreLicencia,'
                . ' ae.tiene_licencia, ae.original,'
                . ' ROW_NUMBER() OVER ('
                . '   PARTITION BY a.EmpleadoID, ae.NombreLicencia'
                . '   ORDER BY a.created_at DESC, a.id DESC'
                . ' ) AS rn'
            );

        return DB::query()
            ->fromSub($ultimas, 't')
            ->where('rn', 1)
            ->get()
            ->keyBy(fn($fila) => $fila->EmpleadoID . '|' . $fila->NombreLicencia);
    }

    /**
     * Licencias que hoy resguarda cada empleado, con el estado con que quedaron la
     * última vez que se auditaron. Es lo que el modal pinta antes de generar para
     * que la corrida se arme sobre lo que falta, no a ciegas.
     *
     * @return array [EmpleadoID => [NombreLicencia => ['estado','fecha','tiene','original']]]
     */
    private function estadoLicenciasPorEmpleado(): array
    {
        if ($this->estadoLicencias !== null) {
            return $this->estadoLicencias;
        }

        $vigente = $this->estadoVigente();
        $hoy = Carbon::now();

        return $this->estadoLicencias = $this->licenciasActualesPorEmpleado()
            ->map(function ($licencias, $empleadoID) use ($vigente, $hoy) {
                return $licencias
                    ->mapWithKeys(function ($nombre) use ($vigente, $hoy, $empleadoID) {
                        $previa = $vigente->get($empleadoID . '|' . $nombre);

                        if (! $previa) {
                            return [$nombre => ['estado' => 'nunca']];
                        }

                        $fecha = Carbon::parse($previa->fecha);

                        return [$nombre => [
                            'estado'   => $fecha->diffInMonths($hoy) >= self::MESES_VIGENCIA ? 'caducada' : 'alDia',
                            'fecha'    => $fecha->format('d/m/Y'),
                            'folio'    => $previa->Folio,
                            'tiene'    => (bool) $previa->tiene_licencia,
                            'original' => $previa->original === null ? null : (bool) $previa->original,
                        ]];
                    })
                    ->all();
            })
            ->all();
    }

    /**
     * Los grupos que se listan: un par (empleado, equipo) por fila, con su historial
     * completo de corridas adentro y el semáforo de licencias del empleado.
     *
     * Se pagina por GRUPO, no por corrida: abrir una fila nunca parte su historial
     * entre dos páginas.
     */
    private function gruposDeAuditorias(): LengthAwarePaginator
    {
        $agregados = Auditoria::query()
            ->selectRaw('EmpleadoID, InventarioID, COUNT(*) AS corridas, MAX(id) AS ultima_id, MAX(created_at) AS ultima_fecha')
            ->whereNotNull('EmpleadoID')
            ->groupBy('EmpleadoID', 'InventarioID')
            ->orderByDesc('ultima_fecha')
            ->get();

        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $porPagina = 15;
        $visibles = $agregados->forPage($pagina, $porPagina)->values();

        $paginador = new LengthAwarePaginator(
            collect(),
            $agregados->count(),
            $porPagina,
            $pagina,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if ($visibles->isEmpty()) {
            return $paginador;
        }

        $empleadoIDs = $visibles->pluck('EmpleadoID')->unique()->values()->all();
        $historial = $this->historialPorPar($empleadoIDs);
        $estados = $this->estadoLicenciasPorEmpleado();

        // Los datos del empleado y del equipo se leen del inventario en vivo, no de
        // la corrida: dos consultas para toda la página en vez de una por fila.
        $empleados = Empleados::with('puestos.departamentos.gerencia')
            ->whereIn('EmpleadoID', $empleadoIDs)
            ->get()
            ->keyBy('EmpleadoID');

        $equipos = InventarioEquipo::query()
            ->whereIn('InventarioID', $visibles->pluck('InventarioID')->filter()->unique()->values()->all())
            ->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Marca', 'Modelo', 'NumSerie', 'Folio', 'tipoEquipo')
            ->get()
            ->keyBy('InventarioID');

        $filas = $visibles->map(function ($grupo) use ($historial, $estados, $empleados, $equipos) {
            $empleado = $empleados->get($grupo->EmpleadoID);
            $corridas = $historial[$grupo->EmpleadoID . '|' . ((int) $grupo->InventarioID)] ?? collect();
            $porLicencia = collect($estados[$grupo->EmpleadoID] ?? []);

            return (object) [
                'clave'          => $grupo->EmpleadoID . '-' . ((int) $grupo->InventarioID),
                'EmpleadoID'     => (int) $grupo->EmpleadoID,
                'NombreEmpleado' => $empleado?->NombreEmpleado ?: 'Sin asignar',
                'tipo_persona'   => $empleado?->tipo_persona ?: '—',
                'gerencia'       => $empleado?->puestos?->departamentos?->gerencia?->NombreGerencia ?: 'Sin gerencia',
                'equipo'         => $grupo->InventarioID ? $equipos->get($grupo->InventarioID) : null,
                'corridas'       => $corridas,
                'total'          => (int) $grupo->corridas,
                'ultima'         => $corridas->last(),
                'licencias'      => $porLicencia->count(),
                'alDia'          => $porLicencia->where('estado', 'alDia')->count(),
                'caducadas'      => $porLicencia->where('estado', 'caducada')->count(),
                'nunca'          => $porLicencia->where('estado', 'nunca')->count(),
            ];
        });

        return $paginador->setCollection($filas);
    }

    /**
     * Corridas de esos empleados agrupadas por par (empleado, equipo), en orden
     * cronológico y con el delta de cada una contra la anterior.
     *
     * El delta se calcula contra la corrida previa del MISMO EMPLEADO, no del mismo
     * par: la licencia es del resguardante, así que cambiar de equipo entre visitas
     * no debe reiniciar la comparación. Por eso se recorre el historial completo del
     * empleado aunque la fila agrupe sólo un equipo.
     *
     * @return array [ "EmpleadoID|InventarioID" => Collection<Auditoria> ]
     */
    private function historialPorPar(array $empleadoIDs): array
    {
        $corridas = Auditoria::query()
            ->whereIn('EmpleadoID', $empleadoIDs)
            ->orderBy('EmpleadoID')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($corridas->isEmpty()) {
            return [];
        }

        $detalles = AuditoriaEquipo::query()
            ->whereIn('auditoria_id', $corridas->pluck('id')->all())
            ->get()
            ->groupBy('auditoria_id');

        // Una pasada: al ir en orden, la corrida anterior de cada empleado ya se vio.
        $anteriorDe = [];

        foreach ($corridas as $corrida) {
            $actual = ($detalles[$corrida->id] ?? collect())->keyBy('NombreLicencia');
            $previa = $anteriorDe[$corrida->EmpleadoID] ?? null;

            $corrida->cambios = $this->contarCambios($actual, $previa);
            $corrida->licencias = $actual->count();
            // Explícito, no deducido de los conteos: una corrida donde TODAS las
            // licencias son nuevas se ve igual que la primera, pero no lo es.
            $corrida->esPrimera = $previa === null;

            $anteriorDe[$corrida->EmpleadoID] = $actual;
        }

        return $corridas
            ->groupBy(fn($c) => $c->EmpleadoID . '|' . ((int) $c->InventarioID))
            ->all();
    }

    /**
     * Cuántas licencias entraron nuevas, se dieron de baja, cambiaron o siguen igual
     * respecto de la corrida anterior. Usa la misma regla que el detalle para que el
     * resumen del listado y el diff de la corrida nunca se contradigan.
     */
    private function contarCambios($actual, $previa): array
    {
        $hayAnterior = $previa !== null;
        $conteo = ['nueva' => 0, 'baja' => 0, 'cambio' => 0, 'igual' => 0];

        foreach ($actual as $nombre => $fila) {
            $marca = $this->marcaDeCambio($fila, $hayAnterior ? $previa->get($nombre) : null, $hayAnterior);
            $conteo[$marca]++;
        }

        return $conteo;
    }

    /**
     * Licencias que hoy tiene cada empleado en el inventario, normalizadas por nombre.
     *
     * @return \Illuminate\Support\Collection [EmpleadoID => Collection<string>]
     */
    private function licenciasActualesPorEmpleado()
    {
        return InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->whereNotNull('EmpleadoID')
            ->whereNotNull('NombreInsumo')
            ->where('NombreInsumo', '<>', '')
            ->get(['EmpleadoID', 'NombreInsumo'])
            ->groupBy('EmpleadoID')
            ->map(fn($filas) => $filas
                ->pluck('NombreInsumo')
                ->map(fn($nombre) => trim((string) $nombre))
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values());
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
