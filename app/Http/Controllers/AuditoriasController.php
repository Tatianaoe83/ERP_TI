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
     * Sólo se audita lo resguardado por personal de planta. Los equipos de personas
     * referenciadas o extraordinarias no entran a la revisión.
     */
    private const TIPO_PERSONA_AUDITABLE = 'FISICA';

    /**
     * Listado de corridas. La auditoría es un evento generado, así que lo primero
     * que se ve es el historial, no los datos en vivo.
     */
    public function index()
    {
        $equipos = $this->equiposAuditables();

        return view('auditorias.index', [
            'auditorias'        => Auditoria::orderByDesc('generada_en')->paginate(15),
            'ultima'            => Auditoria::orderByDesc('generada_en')->first(),
            'catalogoLicencias' => $this->catalogoLicencias(),
            'catalogoEquipos'   => $equipos,
            'gerencias'         => $equipos
                ->pluck('GerenciaEquipo')
                ->map(fn($g) => trim((string) $g) ?: 'Sin gerencia')
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
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
            'alcance'     => ['required', 'in:todos,seleccion'],
            'equipos'     => ['array'],
            'equipos.*'   => ['integer'],
            'licencias'   => ['required', 'array', 'min:1'],
            'licencias.*' => ['string', 'max:255'],
        ], [
            'alcance.required'   => 'Indica si la auditoría es general o por equipos.',
            'licencias.required' => 'Selecciona al menos una licencia para auditar.',
            'licencias.min'      => 'Selecciona al menos una licencia para auditar.',
        ]);

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

        $equipos = $this->equiposAuditables();

        if ($datos['alcance'] === 'seleccion') {
            // Se cruza contra los auditables: por POST podrían llegar equipos de otra
            // categoría o de personal no auditable.
            $ids = collect($datos['equipos'] ?? [])->map(fn($id) => (int) $id)->unique();
            $equipos = $equipos->whereIn('InventarioID', $ids->all())->values();
        }

        if ($equipos->isEmpty()) {
            return back()->withErrors([
                'equipos' => $datos['alcance'] === 'seleccion'
                    ? 'Selecciona al menos un equipo para auditar.'
                    : 'No hay equipos auditables en el inventario.',
            ]);
        }

        $usuario = auth()->user();
        $ahora = Carbon::now();

        $licenciasPorEmpleado = $this->licenciasPorEmpleado($seleccion->all());

        $auditoria = DB::transaction(function () use ($usuario, $ahora, $equipos, $licenciasPorEmpleado, $seleccion) {
            $auditoria = Auditoria::create([
                'Folio'                     => $this->siguienteFolio($ahora),
                'generada_por'              => $usuario?->id,
                'generada_por_nombre'       => $usuario?->name ?: $usuario?->username,
                'generada_en'               => $ahora,
                'licencias_auditadas'       => $seleccion->all(),
                'total_licencias_auditadas' => $seleccion->count(),
            ]);

            $filas = $equipos->map(function ($equipo) use ($auditoria, $licenciasPorEmpleado, $ahora) {
                $licencias = collect($licenciasPorEmpleado->get($equipo->EmpleadoID, collect()));

                return [
                    'auditoria_id'      => $auditoria->id,
                    'InventarioID'      => $equipo->InventarioID,
                    'CategoriaEquipo'   => $equipo->CategoriaEquipo,
                    'Marca'             => $equipo->Marca,
                    'Modelo'            => $equipo->Modelo,
                    'NumSerie'          => $equipo->NumSerie,
                    'Folio'             => $equipo->Folio,
                    'GerenciaEquipo'    => $equipo->GerenciaEquipo ?: 'Sin gerencia',
                    'NombreEmpleado'    => $equipo->NombreEmpleado ?: 'Sin asignar',
                    'tipoEquipo'        => (int) $equipo->tipoEquipo,
                    'grupo'             => $this->grupoDe($equipo->CategoriaEquipo),
                    'licencias'         => $licencias->pluck('NombreInsumo')->implode(' | '),
                    'licencias_piratas' => $licencias->where('LicenciaPirata', true)->count(),
                    'created_at'        => $ahora,
                    'updated_at'        => $ahora,
                ];
            });

            // Insert por lotes: una fila a la vez serían cientos de queries.
            foreach ($filas->chunk(300) as $lote) {
                AuditoriaEquipo::insert($lote->all());
            }

            $auditoria->update([
                'total_equipos' => $filas->count(),
                'total_laptops' => $filas->where('grupo', AuditoriaEquipo::GRUPO_LAPTOP)->count(),
                'total_pcs'     => $filas->where('grupo', AuditoriaEquipo::GRUPO_PC)->count(),
                'total_otros'   => $filas->where('grupo', AuditoriaEquipo::GRUPO_OTROS)->count(),
                'total_propios' => $filas->where('tipoEquipo', InventarioEquipo::TIPO_PROPIO)->count(),
                'total_piratas' => $filas->sum('licencias_piratas'),
            ]);

            return $auditoria;
        });

        return redirect()
            ->route('auditorias.show', $auditoria->id)
            ->with('success', "Auditoría {$auditoria->Folio} generada con {$auditoria->total_equipos} equipos.");
    }

    /**
     * Detalle de una corrida, armado sobre el snapshot congelado.
     */
    public function show($id)
    {
        $auditoria = Auditoria::findOrFail($id);
        $detalle = $auditoria->equipos()->orderBy('GerenciaEquipo')->orderBy('NombreEmpleado')->get();

        return view('auditorias.show', [
            'auditoria' => $auditoria,
            'piratas'   => $this->piratasPorEquipo($detalle),
            'general'   => $detalle->whereIn('grupo', [AuditoriaEquipo::GRUPO_LAPTOP, AuditoriaEquipo::GRUPO_PC])->values(),
            // Referencia contra la corrida inmediatamente anterior.
            'anterior'  => Auditoria::where('generada_en', '<', $auditoria->generada_en)
                ->orderByDesc('generada_en')
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
            ->where('empleados.tipo_persona', self::TIPO_PERSONA_AUDITABLE)
            ->whereIn('inventarioequipo.CategoriaEquipo', [self::CATEGORIA_LAPTOP, self::CATEGORIA_PC])
            ->select('inventarioequipo.*', 'empleados.NombreEmpleado')
            ->orderBy('inventarioequipo.GerenciaEquipo')
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
     * Qué licencia es pirata en cada equipo de la corrida.
     *
     * El detalle congelado guarda los nombres y el conteo, pero no cuál renglón era
     * pirata. Se resuelve por resguardante: del equipo se saca el empleado y de ahí
     * sus licencias marcadas. Compararlo sólo por nombre pintaba de rojo la licencia
     * en todos los equipos aunque su booleano fuera 0.
     *
     * @return array [InventarioID => [nombre en minúsculas, ...]]
     */
    private function piratasPorEquipo($detalle): array
    {
        $empleadoPorEquipo = InventarioEquipo::query()
            ->whereIn('InventarioID', $detalle->pluck('InventarioID')->unique()->all())
            ->pluck('EmpleadoID', 'InventarioID');

        $piratasPorEmpleado = InventarioInsumo::query()
            ->where('CateogoriaInsumo', 'LIKE', '%LICENCIA%')
            ->where('LicenciaPirata', true)
            ->whereIn('EmpleadoID', $empleadoPorEquipo->filter()->unique()->values()->all())
            ->get(['EmpleadoID', 'NombreInsumo'])
            ->groupBy('EmpleadoID')
            ->map(fn($filas) => $filas
                ->pluck('NombreInsumo')
                ->map(fn($n) => mb_strtolower(trim((string) $n)))
                ->unique()
                ->values()
                ->all());

        $mapa = [];

        foreach ($detalle as $fila) {
            // El conteo congelado manda: si en la corrida no había piratas, no se
            // pinta nada aunque hoy la licencia esté marcada.
            if (! $fila->licencias_piratas) {
                continue;
            }

            $empleado = $empleadoPorEquipo[$fila->InventarioID] ?? null;
            $mapa[$fila->InventarioID] = $empleado ? ($piratasPorEmpleado[$empleado] ?? []) : [];
        }

        return $mapa;
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
