<?php

namespace App\Http\Controllers;

use App\Exports\AuditoriasExport;
use App\Models\Auditoria;
use App\Models\AuditoriaEquipo;
use App\Models\AuditoriaLicencia;
use App\Models\Empleados;
use App\Models\InventarioEquipo;
use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AuditoriasController extends Controller
{
    /** Únicas categorías auditables: la auditoría es de equipo de cómputo. */
    private const CATEGORIA_LAPTOP = 'LAPTOP';
    private const CATEGORIA_PC     = 'PC ESCRITORIO';

    /**
     * Sólo se audita al personal de planta.
     *
     * Los extraordinarios quedan fuera porque su inventario es proyección de
     * presupuesto, no equipo resguardado. Los referenciados también: son gerencias y
     * control de almacén, no personas que resguarden un equipo del que responder.
     */
    private const TIPOS_PERSONA_AUDITABLES = ['FISICA'];

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
    public function index(Request $request)
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

        $grupos = $this->gruposDeAuditorias($equipos, $request);

        return view('auditorias.index', [
            'grupos'              => $grupos,
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
     * Genera una corrida nueva: congela lo que el empleado resguarda hoy.
     *
     * Una corrida = una visita al resguardante. Cubre TODOS sus equipos auditables y
     * las licencias elegidas: no se audita una máquina suelta, se audita a la persona
     * y todo lo que tiene bajo su nombre.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'EmpleadoID'  => ['required', 'integer', 'exists:empleados,EmpleadoID'],
            'licencias'   => ['array'],
            'licencias.*' => ['string', 'max:255'],
        ], [
            'EmpleadoID.required' => 'Selecciona al empleado a auditar.',
            'EmpleadoID.exists'   => 'El empleado seleccionado ya no existe.',
        ]);

        $empleadoAuditado = (int) $datos['EmpleadoID'];

        // Se normaliza contra el catálogo real para que nadie meta nombres inventados
        // por POST y la lista congelada quede con basura.
        $seleccion = collect($datos['licencias'] ?? [])
            ->map(fn($n) => trim((string) $n))
            ->intersect($this->catalogoLicencias())
            ->unique()
            ->values();

        // Todos los equipos auditables del empleado: la corrida no elige, los toma todos.
        $equipos = $this->equiposAuditables()
            ->where('EmpleadoID', $empleadoAuditado)
            ->values();

        $licenciasDelEmpleado = collect($this->licenciasPorEmpleado($seleccion->all())->get($empleadoAuditado, collect()))
            ->unique('NombreInsumo')
            ->values();

        // Estado con que cerró la corrida anterior de este empleado. De ahí se arrastra
        // el último resultado conocido en vez de arrancar todo en blanco cada vez.
        $anterior = $this->corridaAnterior($empleadoAuditado);
        $previasLic = $this->estadoAnteriorPorLicencia($empleadoAuditado);
        $previasEq = $anterior
            ? AuditoriaEquipo::where('auditoria_id', $anterior->id)->get()->keyBy('InventarioID')
            : collect();

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
        $bajas = $previasLic
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

        // Sin equipos y sin licencias no hay nada que congelar.
        if ($equipos->isEmpty() && $licenciasDelEmpleado->isEmpty() && $bajas->isEmpty()) {
            return back()->withErrors([
                'licencias' => 'Ese empleado no tiene equipos auditables ni licencias registradas, así que no hay nada que auditar.',
            ]);
        }

        $usuario = auth()->user();
        $ahora = Carbon::now();

        $auditoria = DB::transaction(function () use (
            $usuario, $ahora, $empleadoAuditado, $equipos,
            $licenciasDelEmpleado, $seleccion, $previasLic, $previasEq, $sinResguardo
        ) {
            $auditoria = Auditoria::create([
                'Folio'                     => $this->siguienteFolio($ahora),
                'id_empleado'               => $usuario?->id,
                'generada_por_nombre'       => $usuario?->name ?: $usuario?->username,
                'EmpleadoID'                => $empleadoAuditado,
                'licencias_auditadas'       => $seleccion->all(),
                'total_licencias_auditadas' => $seleccion->count(),
            ]);

            $base = [
                'auditoria_id' => $auditoria->id,
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ];

            // Arrastre: lo ya auditado conserva su último resultado conocido; lo que no
            // estaba en la corrida anterior nace sin revisar. Las observaciones NUNCA se
            // arrastran: son un hecho fechado de su corrida.
            //
            // La ficha (categoría, marca, modelo, serie, folio) se congela aquí además
            // de leerse en vivo del inventario: si el equipo alguna vez se borra del
            // inventario, esta copia es lo único que queda para saber cuál era.
            $filasEquipos = $equipos->map(fn($equipo) => $base + [
                'InventarioID'    => $equipo->InventarioID,
                'CategoriaEquipo' => $equipo->CategoriaEquipo,
                'Marca'           => $equipo->Marca,
                'Modelo'          => $equipo->Modelo,
                'NumSerie'        => $equipo->NumSerie,
                'Folio'           => $equipo->Folio,
                'observaciones'   => null,
            ]);

            foreach ($filasEquipos->chunk(300) as $lote) {
                AuditoriaEquipo::insert($lote->all());
            }

            // Una fila por licencia del resguardante. No se repite por equipo: la
            // licencia es de la persona, y duplicarla por máquina sólo garantiza que
            // el auditor capture el mismo dato dos veces con resultados distintos.
            $filasLicencias = $licenciasDelEmpleado->map(function ($licencia) use ($base, $previasLic) {
                $previa = $previasLic->get($licencia->NombreInsumo);

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
            $filasLicencias = $filasLicencias->concat($sinResguardo->map(fn($nombre) => $base + [
                'NombreLicencia' => $nombre,
                'tiene_licencia' => 0,
                'original'       => null,
                'observaciones'  => null,
            ]));

            foreach ($filasLicencias->chunk(300) as $lote) {
                AuditoriaLicencia::insert($lote->all());
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
    /**
     * Detalle de una corrida: una sola vista, en dos columnas.
     *
     * Derecha = ESTA auditoría, siempre fija, siempre editable: es la que dice la
     * URL. Izquierda = una corrida anterior del mismo empleado, elegible por
     * `?comparar=ID` (por defecto la inmediatamente anterior); es sólo lectura,
     * nunca se navega a su propia página desde aquí.
     */
    public function show($id, Request $request)
    {
        $auditoria = Auditoria::with(['empleado.puestos.departamentos.gerencia', 'empleado.obras'])
            ->findOrFail($id);

        // Todas las corridas previas del mismo empleado: universo del selector de
        // la izquierda, de la más reciente a la más vieja.
        $anteriores = Auditoria::where('EmpleadoID', $auditoria->EmpleadoID)
            ->where('created_at', '<', $auditoria->created_at)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        // Lo pedido por query manda si es válido (pertenece a este empleado y es
        // anterior a la corrida abierta); si no vino o no es válido, la más
        // reciente de las anteriores es el default.
        $comparaId = (int) $request->query('comparar', 0);
        $compara = $comparaId ? $anteriores->firstWhere('id', $comparaId) : null;
        $compara = $compara ?: $anteriores->first();

        // Snapshot de la corrida elegida, en una sola consulta por recurso: evita
        // una consulta por fila al calcular el "antes" de la derecha.
        $equiposCompara = $compara ? $this->equiposDeCorrida($compara)->keyBy('InventarioID') : collect();
        $licenciasCompara = $compara
            ? $compara->licencias()->orderBy('NombreLicencia')->get()->keyBy('NombreLicencia')
            : collect();

        // ── Columna derecha: ESTA auditoría, editable ───────────────────────
        $equiposDer = $this->equiposDeCorrida($auditoria)
            ->map(function ($fila) use ($equiposCompara, $compara) {
                $previa = $equiposCompara->get($fila->InventarioID);

                $fila->previa = $previa;
                $fila->marca = $this->marcaDeCambioEquipo($fila, $previa, (bool) $compara);

                return $fila;
            });

        $licenciasDer = $auditoria->licencias()->orderBy('NombreLicencia')->get()
            ->map(function ($fila) use ($licenciasCompara, $compara) {
                $previa = $licenciasCompara->get($fila->NombreLicencia);

                $fila->previa = $previa;
                $fila->marca = $this->marcaDeCambio($fila, $previa, (bool) $compara);

                return $fila;
            });

        // ── Columna izquierda: la corrida elegida, sólo lectura ─────────────
        // Se marca "baja" lo que ella tenía y ya no aparece en la de la derecha:
        // así el rojo vive junto al dato que desapareció, no como un hueco vacío
        // del otro lado.
        $idsEquiposDer = $equiposDer->pluck('InventarioID')->all();
        $nombresLicDerActivos = $licenciasDer->where('tiene_licencia', true)->pluck('NombreLicencia')->all();

        $equiposIzq = $equiposCompara->values()->map(function ($fila) use ($idsEquiposDer) {
            $fila->marca = in_array($fila->InventarioID, $idsEquiposDer, true) ? 'igual' : 'baja';

            return $fila;
        });

        $licenciasIzq = $licenciasCompara->values()->map(function ($fila) use ($nombresLicDerActivos) {
            $sigueActiva = $fila->tiene_licencia && in_array($fila->NombreLicencia, $nombresLicDerActivos, true);
            $fila->marca = $sigueActiva ? 'igual' : 'baja';

            return $fila;
        });

        return view('auditorias.show', [
            'auditoria'    => $auditoria,
            'anteriores'   => $anteriores,
            'compara'      => $compara,
            'equiposDer'   => $equiposDer,
            'licenciasDer' => $licenciasDer,
            'equiposIzq'   => $equiposIzq,
            'licenciasIzq' => $licenciasIzq,
        ]);
    }

    /** Equipos de una corrida, con la ficha del inventario en vivo ya cargada. */
    private function equiposDeCorrida(Auditoria $auditoria)
    {
        return $auditoria->equipos()->with('equipo')->get()
            ->sortBy(fn($f) => ($f->equipo?->CategoriaEquipo ?? '') . ' ' . ($f->equipo?->Marca ?? ''), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
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
        $registro = AuditoriaLicencia::findOrFail($fila);

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

    /**
     * Captura sobre un equipo de la corrida: sólo la nota del auditor.
     *
     * Del equipo no se verifica presencia: entra para dejar constancia de qué
     * resguardaba el empleado ese día, no para pasar lista máquina por máquina.
     */
    public function actualizarEquipo($fila, Request $request)
    {
        $registro = AuditoriaEquipo::findOrFail($fila);

        $datos = $request->validate([
            'campo' => ['required', 'in:observaciones'],
            'valor' => ['nullable', 'string', 'max:2000'],
        ]);

        $registro->observaciones = trim((string) ($datos['valor'] ?? '')) ?: null;
        $registro->save();

        return response()->json([
            'success'       => true,
            'observaciones' => $registro->observaciones,
        ]);
    }

    /**
     * Exporta el libro de auditorías: el detalle agrupado por empleado y las dos
     * hojas de conteo filtrables.
     *
     * El detalle sale de la corrida más reciente de cada quien, que es la que
     * describe el presente; los conteos miden el inventario completo, incluido lo
     * que todavía no se audita, porque ése es el trabajo pendiente.
     */
    public function exportar()
    {
        return Excel::download(
            new AuditoriasExport(),
            'auditorias-' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
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
     * Del equipo no se captura nada, así que el cambio es de pertenencia:
     *   nueva → el empleado no lo resguardaba en la auditoría anterior
     *   igual → ya lo tenía
     *
     * No hay "baja" porque la corrida sólo congela lo que resguarda hoy: una máquina
     * que dejó de tener simplemente no genera fila.
     */
    private function marcaDeCambioEquipo($fila, $previa, bool $hayAnterior): string
    {
        return (! $hayAnterior || ! $previa) ? 'nueva' : 'igual';
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

        return AuditoriaLicencia::where('auditoria_id', $anterior->id)
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
        $ultimas = DB::table('auditorias_licencias as ae')
            ->join('auditorias as a', 'a.id', '=', 'ae.auditoria_id')
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
     * Las filas del listado: un EMPLEADO por fila, y sólo los que ya tienen alguna
     * corrida. La lista crece conforme se generan auditorías, no arranca con los 200+
     * empleados del inventario esperando.
     *
     * Dentro de lo ya auditado se ordena por urgencia: lo caducado o sin revisar sube,
     * y lo más viejo encabeza su nivel.
     */
    private function gruposDeAuditorias($equipos, Request $request): LengthAwarePaginator
    {
        // El universo es el historial: una agregada por empleado, no una por fila.
        $agregados = Auditoria::query()
            ->selectRaw('EmpleadoID, COUNT(*) AS corridas, MAX(created_at) AS ultima_fecha')
            ->whereNotNull('EmpleadoID')
            ->groupBy('EmpleadoID')
            ->get();

        if ($agregados->isEmpty()) {
            return new LengthAwarePaginator(collect(), 0, 15, 1, [
                'path' => $request->url(), 'query' => $request->query(),
            ]);
        }

        $estados = $this->estadoLicenciasPorEmpleado();
        // Los equipos se leen del inventario en vivo: la fila muestra lo que el
        // empleado resguarda HOY, aunque su última corrida sea de hace meses.
        $porEmpleado = $equipos->groupBy('EmpleadoID');

        $empleados = Empleados::with('puestos.departamentos.gerencia')
            ->whereIn('EmpleadoID', $agregados->pluck('EmpleadoID')->all())
            ->get()
            ->keyBy('EmpleadoID');

        $filas = $agregados->map(function ($resumen) use ($estados, $porEmpleado, $empleados) {
            $empleadoID = $resumen->EmpleadoID;
            $empleado = $empleados->get($empleadoID);
            $susEquipos = collect($porEmpleado->get($empleadoID, collect()));
            $porLicencia = collect($estados[$empleadoID] ?? []);

            $caducadas = $porLicencia->where('estado', 'caducada')->count();
            $sinRevisar = $porLicencia->where('estado', 'nunca')->count();

            // Lo que no tiene nada que revisar se va al final: ya no aporta trabajo.
            if ($porLicencia->isEmpty() && $susEquipos->isEmpty()) {
                $estado = 'sinNada';
            } elseif ($caducadas > 0 || $sinRevisar > 0) {
                $estado = 'pendiente';
            } else {
                $estado = 'alDia';
            }

            $prioridad = ['pendiente' => 0, 'alDia' => 1, 'sinNada' => 2][$estado];

            return (object) [
                'clave'          => (string) $empleadoID,
                'EmpleadoID'     => (int) $empleadoID,
                'NombreEmpleado' => $empleado?->NombreEmpleado ?: 'Sin asignar',
                'tipo_persona'   => $empleado?->tipo_persona ?: '—',
                'gerencia'       => $empleado?->puestos?->departamentos?->gerencia?->NombreGerencia ?: 'Sin gerencia',
                'departamento'   => $empleado?->puestos?->departamentos?->NombreDepartamento ?: 'Sin departamento',
                'equipos'        => $susEquipos->values(),
                'estado'         => $estado,
                'prioridad'      => $prioridad,
                'total'          => (int) $resumen->corridas,
                'ultimaFecha'    => $resumen->ultima_fecha,
                'corridas'       => collect(),
                'ultima'         => null,
                'licencias'      => $porLicencia->count(),
                'alDia'          => $porLicencia->where('estado', 'alDia')->count(),
                'caducadas'      => $caducadas,
                'nunca'          => $sinRevisar,
            ];
        })->values();

        // Dentro de cada nivel de urgencia, primero lo más viejo: el que lleva más sin
        // revisarse encabeza.
        $filas = $filas
            ->sortBy([
                fn($a, $b) => $a->prioridad <=> $b->prioridad,
                fn($a, $b) => ($a->ultimaFecha ?? '') <=> ($b->ultimaFecha ?? ''),
                fn($a, $b) => strcasecmp($a->NombreEmpleado, $b->NombreEmpleado),
            ])
            ->values();

        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $porPagina = 15;
        $visibles = $filas->forPage($pagina, $porPagina)->values();

        // El historial sólo se arma para lo que se ve: es lo único que se despliega.
        $historial = $this->historialPorEmpleado($visibles->pluck('EmpleadoID')->all());

        $visibles->each(function ($fila) use ($historial) {
            $fila->corridas = $historial[$fila->EmpleadoID] ?? collect();
            $fila->ultima = $fila->corridas->last();
        });

        return new LengthAwarePaginator(
            $visibles,
            $filas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Corridas de esos empleados en orden cronológico, con el delta de cada una contra
     * la anterior del mismo empleado.
     *
     * @return array [EmpleadoID => Collection<Auditoria>]
     */
    private function historialPorEmpleado(array $empleadoIDs): array
    {
        if (empty($empleadoIDs)) {
            return [];
        }

        $corridas = Auditoria::query()
            ->whereIn('EmpleadoID', $empleadoIDs)
            ->orderBy('EmpleadoID')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($corridas->isEmpty()) {
            return [];
        }

        $ids = $corridas->pluck('id')->all();

        $licencias = AuditoriaLicencia::whereIn('auditoria_id', $ids)->get()->groupBy('auditoria_id');
        $equipos = AuditoriaEquipo::whereIn('auditoria_id', $ids)->get()->groupBy('auditoria_id');

        // Una pasada: al ir en orden, la corrida anterior de cada empleado ya se vio.
        $anteriorDe = [];

        foreach ($corridas as $corrida) {
            $lic = ($licencias[$corrida->id] ?? collect())->keyBy('NombreLicencia');
            $eq = ($equipos[$corrida->id] ?? collect())->keyBy('InventarioID');
            $previa = $anteriorDe[$corrida->EmpleadoID] ?? null;

            $corrida->cambios = $this->contarCambios($lic, $eq, $previa);
            $corrida->licencias = $lic->count();
            $corrida->equipos = $eq->count();
            // Explícito, no deducido de los conteos: una corrida donde TODO es nuevo
            // se ve igual que la primera, pero no lo es.
            $corrida->esPrimera = $previa === null;

            $anteriorDe[$corrida->EmpleadoID] = ['lic' => $lic, 'eq' => $eq];
        }

        return $corridas->groupBy('EmpleadoID')->all();
    }

    /**
     * Cuántos renglones entraron nuevos, se dieron de baja, cambiaron o siguen igual
     * respecto de la corrida anterior, sumando equipos y licencias.
     *
     * Usa las mismas reglas que el detalle para que el resumen del listado y el diff
     * de la corrida nunca se contradigan.
     */
    private function contarCambios($licencias, $equipos, $previa): array
    {
        $hayAnterior = $previa !== null;
        $conteo = ['nueva' => 0, 'baja' => 0, 'cambio' => 0, 'igual' => 0];

        foreach ($licencias as $nombre => $fila) {
            $marca = $this->marcaDeCambio($fila, $hayAnterior ? $previa['lic']->get($nombre) : null, $hayAnterior);
            $conteo[$marca]++;
        }

        foreach ($equipos as $inventarioID => $fila) {
            $marca = $this->marcaDeCambioEquipo($fila, $hayAnterior ? $previa['eq']->get($inventarioID) : null, $hayAnterior);
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
