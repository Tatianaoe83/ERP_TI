<?php

namespace App\Exports;

use App\Helpers\PresupuestoAsignacion;
use App\Models\Auditoria;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * El libro de auditorías: tres hojas.
 *
 *   1. Auditorías                  — empleado → equipos → licencias, plegable.
 *   2. Conteo por gerencia         — dos conteos fijos: equipos/licencias por
 *                                    gerencia, y el catálogo de licencias del
 *                                    sistema. Números escritos directo, no
 *                                    dependen de ningún filtro.
 *   3. Detalle equipos y licencias — un renglón por cada par equipo-licencia,
 *                                    con AutoFilter nativo (Gerencia, Obra,
 *                                    Departamento, Empleado, categoría,
 *                                    Modalidad, Licencia).
 *
 * El detalle de la hoja 1 sale de la corrida más reciente de cada empleado, que
 * es la que describe el presente; el historial sigue viviendo en el módulo.
 */
class AuditoriasExport implements WithMultipleSheets
{
    private const CATEGORIA_LAPTOP = 'LAPTOP';
    private const CATEGORIA_PC     = 'PC ESCRITORIO';

    /** Sólo licencias: el resto de insumos no entra a la auditoría de software. */
    private const PATRON_LICENCIA = '%LICENCIA%';

    /**
     * Sólo se audita al personal de planta, igual que el módulo.
     *
     * Los referenciados son almacén, bajas y gerencias: no son personas que
     * resguarden algo de lo que responder, y contarlos junto al personal infla el
     * parque auditable con stock que nadie tiene encima. Los extraordinarios son
     * proyección de presupuesto, no equipo entregado.
     */
    private const TIPOS_PERSONA_AUDITABLES = ['FISICA'];

    private $corridas = null;

    public function sheets(): array
    {
        $sello = 'Generado el ' . Carbon::now()->format('d/m/Y H:i') . '.';

        [$filas, $rawEquipos, $rawLicencias] = $this->datosGerencia();

        return [
            new AuditoriaAgrupadaSheet(
                'Última auditoría de cada empleado. Usa los +/- del margen para abrir a quien vas a revisar. ' . $sello,
                $this->bloquesPorEmpleado(),
            ),

            new AuditoriaGerenciaSheet(
                'Auditoría equipos y licencias',
                'Equipos y licencias reales del personal de planta, por gerencia y catálogo del sistema. Cada tabla tiene sus propios filtros. ' . $sello,
                $rawEquipos,
                $rawLicencias,
            ),

            new AuditoriaDetalleSheet(
                'Detalle equipos y licencias',
                'Un renglón por cada equipo-licencia del personal de planta. Filtra por Gerencia, Obra, Departamento, '
                    . 'Empleado, categoría, Modalidad o Licencia. ' . $sello,
                $filas,
            ),
        ];
    }

    // ─────────────────────────── Hoja 1: agrupada ───────────────────────────

    /** Un bloque plegable por empleado, con sus dos sub-tablas ya armadas. */
    private function bloquesPorEmpleado(): array
    {
        return $this->corridas()->map(fn(Auditoria $corrida) => [
            'empleado'     => $this->empleado($corrida),
            'gerencia'     => $this->gerencia($corrida),
            'obra'         => $this->obra($corrida),
            'departamento' => $corrida->empleado?->puestos?->departamentos?->NombreDepartamento ?: 'Sin departamento',
            'folio'        => $corrida->Folio,
            'fecha'        => $corrida->created_at?->format('d/m/Y H:i') ?: '—',

            // La ficha se lee del inventario en vivo mientras el equipo siga ahí;
            // si ya se borró, queda la copia congelada al generar la corrida.
            'equipos' => $corrida->equipos
                ->sortBy(fn($f) => ($f->equipo?->CategoriaEquipo ?? $f->CategoriaEquipo ?? ''), SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn($fila) => [
                    $fila->equipo->CategoriaEquipo ?? $fila->CategoriaEquipo ?? '—',
                    $fila->equipo->Marca ?? $fila->Marca ?? '—',
                    $fila->equipo->Modelo ?? $fila->Modelo ?? '—',
                    $fila->equipo->NumSerie ?? $fila->NumSerie ?? 'S/N',
                    $fila->equipo->Folio ?? $fila->Folio ?? '—',
                    $fila->equipo
                        ? PresupuestoAsignacion::etiqueta($fila->equipo->tipoEquipo)
                        : 'Fuera de inventario',
                    $this->fecha($fila->equipo?->FechaAsignacion),
                    $fila->observaciones ?: '',
                ])
                ->values()
                ->all(),

            'licencias' => $corrida->licencias
                ->sortBy('NombreLicencia', SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn($fila) => [
                    $fila->NombreLicencia,
                    $fila->tiene_licencia ? 'Sí' : 'No',
                    $this->original($fila->tiene_licencia, $fila->original),
                    $fila->observaciones ?: '',
                ])
                ->values()
                ->all(),
        ])->all();
    }

    // ─────────────────────── Hoja 2: conteo por gerencia ────────────────────

    /**
     * Todo lo que necesita la hoja 2: los renglones del detalle, el conteo
     * real por gerencia y el catálogo de licencias del sistema.
     *
     * inventarioequipo e inventarioinsumo sólo se relacionan con el empleado,
     * nunca entre sí, así que no hay forma de saber si una licencia "vive" en
     * un equipo en particular. Para que el filtro de Modalidad también
     * alcance a las licencias, cada equipo del empleado se cruza con cada una
     * de sus licencias en el detalle —si tiene 2 equipos y 3 licencias, salen
     * 6 renglones—. Es una repetición a propósito: así está el sistema. Los
     * conteos de arriba, en cambio, cuentan equipos y licencias reales, sin
     * esa repetición, y son números fijos: no dependen de la tabla de abajo.
     *
     * El resto del inventario —monitores, telefonía, redes, periféricos— no
     * entra: la auditoría es de equipo de cómputo y licencias. De las
     * modalidades de tipoEquipo van Stock, Compartido y Propio: el Extra es
     * proyección de presupuesto, equipo que todavía no existe.
     *
     * Licencias sale del mismo cruce que usaba la hoja vieja: universo del
     * inventario actual (lo nunca auditado también cuenta, es trabajo
     * pendiente) más lo que la última corrida revisó y ya no está asignado
     * (las bajas), para no esconder ese hallazgo.
     *
     * rawEquipos y rawLicencias son la materia prima de los filtros propios
     * de la hoja 2 (independientes entre sí, sin cascada): un renglón por
     * equipo real y uno por licencia real, sin el cruce que sí necesita el
     * detalle de la hoja 3.
     *
     * @return array [filas, rawEquipos, rawLicencias]
     */
    private function datosGerencia(): array
    {
        $empleados = DB::table('empleados as e')
            ->leftJoin('obras as o', 'o.ObraID', '=', 'e.ObraID')
            ->leftJoin('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->leftJoin('gerencia as g', 'g.GerenciaID', '=', 'd.GerenciaID')
            ->whereIn('e.tipo_persona', self::TIPOS_PERSONA_AUDITABLES)
            ->selectRaw(
                'e.EmpleadoID,'
                . " COALESCE(NULLIF(TRIM(g.NombreGerencia), ''), 'Sin gerencia') AS gerencia,"
                . " COALESCE(NULLIF(TRIM(o.NombreObra), ''), 'Sin obra') AS obra,"
                . " COALESCE(NULLIF(TRIM(d.NombreDepartamento), ''), 'Sin departamento') AS departamento,"
                . " COALESCE(NULLIF(TRIM(e.NombreEmpleado), ''), 'Sin asignar') AS empleado"
            )
            ->get()
            ->keyBy('EmpleadoID');

        if ($empleados->isEmpty()) {
            return [[], [], []];
        }

        $equiposConsulta = DB::table('inventarioequipo as ie')
            ->whereIn('ie.CategoriaEquipo', [self::CATEGORIA_LAPTOP, self::CATEGORIA_PC])
            ->whereIn('ie.EmpleadoID', $empleados->keys())
            ->select('ie.EmpleadoID', 'ie.CategoriaEquipo', 'ie.Marca', 'ie.Modelo', 'ie.NumSerie', 'ie.Folio', 'ie.tipoEquipo');

        // El helper compara el ENUM como cadena: contra un entero, MySQL usaría
        // el índice del ENUM y devolvería justo las filas equivocadas.
        $equiposPorEmpleado = PresupuestoAsignacion::aplicarWhere($equiposConsulta, 'inventario', 'ie.tipoEquipo')
            ->get()
            ->groupBy('EmpleadoID');

        $licenciasPorEmpleado = $this->licenciasPorEmpleado($empleados->keys()->all());

        $filas = [];
        $rawEquipos = [];
        $rawLicencias = [];

        foreach ($empleados as $empId => $emp) {
            $base = [$emp->gerencia, $emp->obra, $emp->departamento, $emp->empleado];

            $equipos = $equiposPorEmpleado->get($empId, collect())->sortBy('CategoriaEquipo')->values();
            $licencias = collect($licenciasPorEmpleado[$empId] ?? [])->sortBy('licencia')->values();

            if ($equipos->isEmpty() && $licencias->isEmpty()) {
                continue;
            }

            $modalidades = $equipos
                ->map(fn($f) => PresupuestoAsignacion::etiqueta($f->tipoEquipo))
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->implode(', ');

            foreach ($equipos as $equipo) {
                $rawEquipos[] = [$emp->gerencia, $emp->obra, PresupuestoAsignacion::etiqueta($equipo->tipoEquipo)];
            }

            foreach ($licencias as $licencia) {
                $rawLicencias[] = [$emp->gerencia, $emp->obra, $emp->empleado, $modalidades, $licencia['licencia']];
            }

            if ($equipos->isEmpty()) {
                // Tiene licencias pero ningún equipo de cómputo: no hay
                // modalidad que heredar, la columna se queda vacía.
                foreach ($licencias as $licencia) {
                    $filas[] = array_merge($base, [null, null, null, null, null, null], $this->columnasLicencia($licencia));
                }
                continue;
            }

            foreach ($equipos as $equipo) {
                $columnasEquipo = [
                    $equipo->CategoriaEquipo ?: '—',
                    $equipo->Marca ?: '—',
                    $equipo->Modelo ?: '—',
                    $equipo->NumSerie ?: 'S/N',
                    $equipo->Folio ?: '—',
                    PresupuestoAsignacion::etiqueta($equipo->tipoEquipo),
                ];

                if ($licencias->isEmpty()) {
                    $filas[] = array_merge($base, $columnasEquipo, [null, null, null]);
                    continue;
                }

                foreach ($licencias as $licencia) {
                    $filas[] = array_merge($base, $columnasEquipo, $this->columnasLicencia($licencia));
                }
            }
        }

        usort($filas, fn($a, $b) => strcasecmp($a[0], $b[0]) ?: strcasecmp($a[3], $b[3]) ?: strcasecmp($a[4], $b[4]) ?: strcasecmp((string) $a[10], (string) $b[10]));

        return [$filas, $rawEquipos, $rawLicencias];
    }

    /** [licencia, tieneLicencia, original] para el cruce equipo-licencia. */
    private function columnasLicencia(array $licencia): array
    {
        $tiene = $licencia['tiene'];

        return [
            $licencia['licencia'],
            $tiene === null ? 'Sin auditar' : ($tiene ? 'Sí' : 'No'),
            $tiene === null ? 'Sin auditar' : $this->original($tiene, $licencia['original']),
        ];
    }

    /**
     * Licencias por empleado: el inventario actual con su estado en la
     * corrida más reciente, más lo que la corrida revisó y ya se dio de baja.
     *
     * @return array [EmpleadoID => [['licencia','tiene','original'], …]]
     */
    private function licenciasPorEmpleado(array $empleadoIds): array
    {
        $auditadas = $this->licenciasAuditadas();

        $porEmpleado = [];

        DB::table('inventarioinsumo as ii')
            ->whereIn('ii.EmpleadoID', $empleadoIds)
            ->where('ii.CateogoriaInsumo', 'LIKE', self::PATRON_LICENCIA)
            ->whereNotNull('ii.NombreInsumo')
            ->where('ii.NombreInsumo', '<>', '')
            ->selectRaw('ii.EmpleadoID, TRIM(ii.NombreInsumo) AS licencia')
            ->distinct()
            ->get()
            ->each(function ($fila) use (&$auditadas, &$porEmpleado) {
                $clave = $fila->EmpleadoID . '|' . $fila->licencia;
                $estado = $auditadas[$clave] ?? null;
                unset($auditadas[$clave]);

                $porEmpleado[$fila->EmpleadoID][] = [
                    'licencia' => $fila->licencia,
                    'tiene'    => $estado['tiene'] ?? null,
                    'original' => $estado['original'] ?? null,
                ];
            });

        // Lo que quedó sin consumir vivió en la corrida pero ya no está asignado.
        foreach ($auditadas as $clave => $estado) {
            [$empleadoId] = explode('|', $clave, 2);
            $porEmpleado[$empleadoId][] = [
                'licencia' => $estado['licencia'],
                'tiene'    => $estado['tiene'],
                'original' => $estado['original'],
            ];
        }

        return $porEmpleado;
    }

    // ────────────────────────────── Apoyos ──────────────────────────────────

    /**
     * La corrida más reciente de cada empleado, con su detalle.
     *
     * Una ventana en vez de una consulta por empleado: con 200+ resguardantes la
     * diferencia es de dos consultas contra doscientas.
     */
    private function corridas()
    {
        if ($this->corridas !== null) {
            return $this->corridas;
        }

        $ultimas = DB::table('auditorias')
            ->whereNotNull('EmpleadoID')
            ->selectRaw(
                'id, ROW_NUMBER() OVER ('
                . '   PARTITION BY EmpleadoID ORDER BY created_at DESC, id DESC'
                . ' ) AS rn'
            );

        $ids = DB::query()->fromSub($ultimas, 't')->where('rn', 1)->pluck('id')->all();

        if (empty($ids)) {
            return $this->corridas = collect();
        }

        return $this->corridas = Auditoria::query()
            ->with(['empleado.puestos.departamentos.gerencia', 'empleado.obras', 'equipos.equipo', 'licencias'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy([
                fn($a, $b) => strcasecmp($this->gerencia($a), $this->gerencia($b)),
                fn($a, $b) => strcasecmp($this->empleado($a), $this->empleado($b)),
            ])
            ->values();
    }

    /**
     * Resultado de cada licencia en la última corrida de su empleado.
     *
     * @return array ["EmpleadoID|Licencia" => [tiene, original, gerencia, …]]
     */
    private function licenciasAuditadas(): array
    {
        $estado = [];

        foreach ($this->corridas() as $corrida) {
            foreach ($corrida->licencias as $fila) {
                $estado[$corrida->EmpleadoID . '|' . $fila->NombreLicencia] = [
                    'tiene'    => (bool) $fila->tiene_licencia,
                    'original' => $fila->original === null ? null : (bool) $fila->original,
                    'licencia' => $fila->NombreLicencia,
                    'empleado' => $this->empleado($corrida),
                    'gerencia' => $this->gerencia($corrida),
                    'obra'     => $this->obra($corrida),
                ];
            }
        }

        return $estado;
    }

    /**
     * "Original" es tri-estado y depende de si la licencia siquiera está: sin
     * licencia no aplica, y con licencia puede seguir sin revisarse. Aplanarlo a
     * "No" reportaría piratería inexistente.
     */
    private function original(bool $tiene, ?bool $original): string
    {
        if (! $tiene) {
            return 'No aplica';
        }

        return $original === null ? 'Sin revisar' : ($original ? 'Sí' : 'No');
    }

    private function fecha($valor): string
    {
        if (! $valor) {
            return '—';
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private function gerencia(Auditoria $corrida): string
    {
        return $corrida->empleado?->puestos?->departamentos?->gerencia?->NombreGerencia ?: 'Sin gerencia';
    }

    private function obra(Auditoria $corrida): string
    {
        return $corrida->empleado?->obras?->NombreObra ?: 'Sin obra';
    }

    private function empleado(Auditoria $corrida): string
    {
        return $corrida->empleado?->NombreEmpleado ?: 'Sin asignar';
    }
}
