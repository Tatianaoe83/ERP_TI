<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Helpers\JoinHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReporteHelper
{
    /**
     * Construye y retorna el Query Builder sin ejecutarlo.
     */
    public static function construirQuery(array $metadata, array $relacionesUniversales)
    {
        $tabla     = $metadata['tabla_principal'];
        $relaciones = is_array($metadata['tabla_relacion'] ?? [])
            ? $metadata['tabla_relacion']
            : [$metadata['tabla_relacion']];
        $columnas  = $metadata['columnas']  ?? ['*'];
        $filtros   = $metadata['filtros']   ?? [];
        $ordenCol  = $metadata['ordenColumna']  ?? null;
        $ordenDir  = $metadata['ordenDireccion'] ?? 'asc';
        $limite    = $metadata['limite']    ?? null;

        $query      = DB::table($tabla);
        $joinsHechos = [];

        // ── JOINs ──────────────────────────────────────────────────────────────
        foreach ($relaciones as $relacion) {
            $camino = JoinHelper::resolverRutaJoins($tabla, $relacion, $relacionesUniversales);
            foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                if (!in_array($tablaJoin, $joinsHechos)) {
                    $query->join($tablaJoin, $from, $op, $to);
                    $joinsHechos[] = $tablaJoin;
                }
            }
        }

        // ── Soft-deletes ───────────────────────────────────────────────────────
        if (Schema::hasColumn($tabla, 'deleted_at')) {
            $query->whereNull("$tabla.deleted_at");
        }
        foreach ($joinsHechos as $tablaJoin) {
            if (Schema::hasColumn($tablaJoin, 'deleted_at')) {
                $query->whereNull("$tablaJoin.deleted_at");
            }
        }

        // ── SELECT ─────────────────────────────────────────────────────────────
        // Alias automáticos para evitar columnas duplicadas en el subquery que
        // genera Yajra al hacer COUNT(*) con DISTINCT.
        $aliasSeen  = [];
        $columnasConAlias = [];

        foreach ($columnas as $col) {
            $col = trim($col);

            // Si ya viene con alias explícito ("tabla.Campo as alias"), lo dejamos
            if (stripos($col, ' as ') !== false) {
                $columnasConAlias[] = $col;
                $partes = preg_split('/\s+as\s+/i', $col);
                $alias  = strtolower(trim($partes[1]));
                $aliasSeen[$alias] = true;
                continue;
            }

            // Extraer el nombre corto de la columna
            $nombreCorto = str_contains($col, '.') ? last(explode('.', $col)) : $col;
            $key = strtolower($nombreCorto);

            if (isset($aliasSeen[$key])) {
                // Colisión: alias = tabla_Columna
                $tabla  = str_contains($col, '.') ? explode('.', $col)[0] : 'col';
                $alias  = $tabla . '_' . $nombreCorto;
                // Escapar tabla y columna por separado para que MySQL no interprete
                // el punto como parte del nombre: `gerencia`.`estado` as `gerencia_estado`
                if (str_contains($col, '.')) {
                    [$t, $c] = explode('.', $col, 2);
                    $expr = "`{$t}`.`{$c}`";
                } else {
                    $expr = "`{$col}`";
                }
                $columnasConAlias[] = DB::raw("{$expr} as `{$alias}`");
                $aliasSeen[strtolower($alias)] = true;
            } else {
                $columnasConAlias[] = $col;
                $aliasSeen[$key] = true;
            }
        }

        // DISTINCT elimina duplicados causados por JOINs encadenados
        $query->distinct()->select($columnasConAlias);

        // ── FILTROS ────────────────────────────────────────────────────────────
        $meses = [
            'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3,    'Abril'     => 4,
            'Mayo'  => 5, 'Junio'   => 6, 'Julio' => 7,    'Agosto'    => 8,
            'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12,
        ];

        foreach ($filtros as $filtro) {
            if (empty($filtro['columna']) || !isset($filtro['valor'])) {
                continue;
            }

            $columna  = $filtro['columna'];
            $operador = $filtro['operador'] ?? '=';
            $valor    = $filtro['valor'];

            if ($operador === 'between') {
                if (is_array($valor)) {
                    $inicio = $valor['inicio'] ?? null;
                    $fin    = $valor['fin']    ?? null;

                    if (!is_null($inicio) && !is_null($fin)) {
                        if ($columna === 'inventarioinsumo.MesDePago') {
                            $inicioNum = $meses[$inicio] ?? null;
                            $finNum    = $meses[$fin]    ?? null;
                            if ($inicioNum && $finNum) {
                                $query->whereBetween(
                                    DB::raw("FIELD(`MesDePago`, " . implode(',', array_map(fn($m) => "'$m'", array_keys($meses))) . ")"),
                                    [$inicioNum, $finNum]
                                );
                            }
                        } elseif (
                            (strtotime($inicio) && strtotime($fin)) ||
                            (is_numeric($inicio) && is_numeric($fin))
                        ) {
                            $query->whereBetween($columna, [$inicio, $fin]);
                        } else {
                            Log::warning("Filtro 'between' inválido: se ignoró", compact('columna', 'valor'));
                        }
                    }
                }
            } else {
                if ($operador === 'like') {
                    $valor = "%{$valor}%";
                }
                $query->where($columna, $operador, $valor);
            }
        }

        // ── ORDEN ──────────────────────────────────────────────────────────────
        if ($ordenCol) {
            $query->orderBy($ordenCol, $ordenDir);
        }

        // ── LÍMITE (sólo para previews, no para DataTables) ───────────────────
        if ($limite) {
            $query->limit($limite);
        }

        return $query;
    }

    /**
     * Compatibilidad hacia atrás: ejecuta la consulta completa
     */
    public static function ejecutarConsulta(array $metadata, array $relacionesUniversales)
    {
        $query = static::construirQuery($metadata, $relacionesUniversales);

        // Log de la SQL final (sólo en debug)
        if (config('app.debug')) {
            $sqlCompleto = vsprintf(
                str_replace('?', '%s', $query->toSql()),
                collect($query->getBindings())->map(fn($b) => is_numeric($b) ? $b : "'$b'")->toArray()
            );
            Log::debug('Query lista para ejecutar:', [$sqlCompleto]);
        }

        return $query->get();
    }
}