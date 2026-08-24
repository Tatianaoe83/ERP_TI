<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\AuditoriaEquipo;
use App\Models\InventarioEquipo;
use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
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

        return view('auditorias.index', [
            'auditorias'        => Auditoria::with('empleado.puestos.departamentos.gerencia')
                ->orderByDesc('created_at')
                ->paginate(15),
            'ultima'            => Auditoria::with('empleado')->orderByDesc('created_at')->first(),
            'catalogoLicencias' => $this->catalogoLicencias(),
            'catalogoEquipos'   => $equipos,
            // La corrida es por empleado: sólo se ofrecen los que tienen algo auditable.
            'empleados'         => $equipos
                ->unique('EmpleadoID')
                ->map(fn($e) => (object) [
                    'EmpleadoID'     => $e->EmpleadoID,
                    'NombreEmpleado' => $e->NombreEmpleado ?: 'Sin asignar',
                    'tipo_persona'   => $e->tipo_persona,
                    'gerencia'       => trim((string) $e->NombreGerencia) ?: 'Sin gerencia',
                    'departamento'   => trim((string) $e->NombreDepartamento) ?: 'Sin departamento',
                ])
                ->sortBy('NombreEmpleado', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'gerencias'         => $this->opcionesDe($equipos, 'NombreGerencia', 'Sin gerencia'),
            'departamentos'     => $this->opcionesDe($equipos, 'NombreDepartamento', 'Sin departamento'),
            'tiposPersona'      => $this->opcionesDe($equipos, 'tipo_persona', 'Sin tipo'),
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

        $usuario = auth()->user();
        $ahora = Carbon::now();

        $licenciasPorEmpleado = $this->licenciasPorEmpleado($seleccion->all());

        $auditoria = DB::transaction(function () use ($usuario, $ahora, $equipos, $licenciasPorEmpleado, $seleccion, $empleadoAuditado, $tipoEquipo) {
            $auditoria = Auditoria::create([
                'Folio'                     => $this->siguienteFolio($ahora),
                'id_empleado'               => $usuario?->id,
                'generada_por_nombre'       => $usuario?->name ?: $usuario?->username,
                'EmpleadoID'                => $empleadoAuditado,
                'tipoEquipo'                => $tipoEquipo,
                'licencias_auditadas'       => $seleccion->all(),
                'total_licencias_auditadas' => $seleccion->count(),
            ]);

            // Una fila por licencia. El equipo sin ninguna deja una sola fila con
            // NombreLicencia nulo, para que no desaparezca del reporte.
            $filas = $equipos->flatMap(function ($equipo) use ($auditoria, $licenciasPorEmpleado, $ahora) {
                $base = [
                    'auditoria_id'    => $auditoria->id,
                    'InventarioID'    => $equipo->InventarioID,
                    'CategoriaEquipo' => $equipo->CategoriaEquipo,
                    'Marca'           => $equipo->Marca,
                    'Modelo'          => $equipo->Modelo,
                    'NumSerie'        => $equipo->NumSerie,
                    'Folio'           => $equipo->Folio,
                    'GerenciaEquipo'  => $equipo->GerenciaEquipo ?: 'Sin gerencia',
                    'NombreEmpleado'  => $equipo->NombreEmpleado ?: 'Sin asignar',
                    'tipoEquipo'      => (int) $equipo->tipoEquipo,
                    'grupo'           => $this->grupoDe($equipo->CategoriaEquipo),
                    'en_dominio'      => 0,
                    'created_at'      => $ahora,
                    'updated_at'      => $ahora,
                ];

                $licencias = collect($licenciasPorEmpleado->get($equipo->EmpleadoID, collect()))
                    ->unique('NombreInsumo')
                    ->values();

                if ($licencias->isEmpty()) {
                    return [$base + [
                        'NombreLicencia' => null,
                        'tiene_licencia' => 0,
                        'pirata'         => 0,
                    ]];
                }

                return $licencias->map(fn($licencia) => $base + [
                    'NombreLicencia' => $licencia->NombreInsumo,
                    'tiene_licencia' => 1,
                    'pirata'         => 0,
                ])->all();
            });

            // Insert por lotes: una fila a la vez serían cientos de queries.
            foreach ($filas->chunk(300) as $lote) {
                AuditoriaEquipo::insert($lote->all());
            }

            return $auditoria;
        });

        return redirect()
            ->route('auditorias.show', $auditoria->id)
            ->with('success', "Auditoría {$auditoria->Folio} generada con {$equipos->count()} equipos.");
    }

    /**
     * Detalle de una corrida, armado sobre el snapshot congelado.
     */
    public function show($id)
    {
        $auditoria = Auditoria::with('empleado.puestos.departamentos.gerencia')->findOrFail($id);
        $detalle = $auditoria->equipos()->orderBy('GerenciaEquipo')->orderBy('NombreEmpleado')->get();

        return view('auditorias.show', [
            'auditoria' => $auditoria,
            'detalle'   => $detalle,
            'general'   => $detalle->whereIn('grupo', [AuditoriaEquipo::GRUPO_LAPTOP, AuditoriaEquipo::GRUPO_PC])->values(),
            // Referencia contra la corrida inmediatamente anterior del mismo empleado.
            'anterior'  => Auditoria::where('created_at', '<', $auditoria->created_at)
                ->where('EmpleadoID', $auditoria->EmpleadoID)
                ->orderByDesc('created_at')
                ->first(),
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

    private function grupoDe($categoria): int
    {
        $categoria = trim((string) $categoria);

        if ($categoria === self::CATEGORIA_LAPTOP) {
            return AuditoriaEquipo::GRUPO_LAPTOP;
        }

        if ($categoria === self::CATEGORIA_PC) {
            return AuditoriaEquipo::GRUPO_PC;
        }

        return AuditoriaEquipo::GRUPO_OTROS;
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
