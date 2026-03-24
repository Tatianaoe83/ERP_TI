<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReporteHelper
{
    const TABLAS_INVENTARIO = ['inventarioequipo', 'inventarioinsumo', 'inventariolineas'];

    public static function construirQuery(array $metadata, array $relacionesUniversales)
    {
        $tabla      = $metadata['tabla_principal'];
        $relaciones = is_array($metadata['tabla_relacion'] ?? [])
            ? $metadata['tabla_relacion']
            : [$metadata['tabla_relacion']];
        $columnas   = $metadata['columnas'] ?? ['*'];

        // Inferir tablas implícitas desde las columnas cualificadas
        foreach ($columnas as $col) {
            $col = trim($col);
            if (stripos($col, ' as ') !== false) {
                $col = preg_split('/\s+as\s+/i', $col)[0];
            }
            if (str_contains($col, '.')) {
                $tablaRequerida = str_replace('`', '', explode('.', trim($col))[0]);
                if (
                    $tablaRequerida !== $tabla
                    && !in_array($tablaRequerida, $relaciones)
                    && $tablaRequerida !== 'union_result'
                ) {
                    $relaciones[] = $tablaRequerida;
                }
            }
        }

        $metadata['tabla_relacion'] = array_values(array_unique($relaciones));

        $todasTablas       = array_merge([$tabla], $metadata['tabla_relacion']);
        $inventariosUsados = array_values(array_intersect($todasTablas, self::TABLAS_INVENTARIO));

        if (count($inventariosUsados) > 1) {
            return self::construirQueryUnion($metadata, $relacionesUniversales, $inventariosUsados);
        }

        return self::construirQuerySimple($metadata, $relacionesUniversales);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY SIMPLE
    // ──────────────────────────────────────────────────────────────────────────

    public static function construirQuerySimple(array $metadata, array $relacionesUniversales)
    {
        $tabla      = $metadata['tabla_principal'];
        $relaciones = is_array($metadata['tabla_relacion'] ?? [])
            ? $metadata['tabla_relacion']
            : [$metadata['tabla_relacion']];
        $columnas   = $metadata['columnas']      ?? ['*'];
        $filtros    = $metadata['filtros']        ?? [];
        $ordenCol   = $metadata['ordenColumna']   ?? null;
        $ordenDir   = $metadata['ordenDireccion'] ?? 'asc';
        $limite     = $metadata['limite']         ?? null;

        $query       = DB::table($tabla);
        $joinsHechos = [];

        foreach ($relaciones as $relacion) {
            try {
                $camino = JoinHelper::resolverRutaJoins($tabla, $relacion, $relacionesUniversales);
                foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                    if (!in_array($tablaJoin, $joinsHechos)) {
                        $query->leftJoin($tablaJoin, $from, $op, $to);
                        $joinsHechos[] = $tablaJoin;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("ReporteHelper: no se pudo resolver ruta para '{$relacion}': " . $e->getMessage());
            }
        }

        // ── Soft deletes ─────────────────────────────────────────────────────
        // FIX: el original usaba orWhereNotNull() en los joins, lo que hacía
        // el WHERE siempre verdadero (NULL OR NOT NULL = siempre true).
        // La lógica correcta: si la tabla joineada tiene deleted_at, excluimos
        // sus filas eliminadas (whereNull). Si un LEFT JOIN no encontró
        // coincidencia, deleted_at = NULL, lo cual también pasa el whereNull.
        if (Schema::hasColumn($tabla, 'deleted_at')) {
            $query->whereNull("{$tabla}.deleted_at");
        }

        foreach ($joinsHechos as $tablaJoin) {
            if (Schema::hasColumn($tablaJoin, 'deleted_at')) {
                $query->whereNull("{$tablaJoin}.deleted_at");
            }
        }

        // ── Filtro anti-filas-vacías para tablas de inventario ────────────────
        // Cuando el usuario agrega un inventario como relación, solo quiere
        // empleados que TENGAN registros en ese inventario. Sin este filtro,
        // el LEFT JOIN trae todos los empleados con columnas NULL (filas vacías).
        // Las tres tablas de inventario usan InventarioID como PK.
        foreach ($relaciones as $relacion) {
            if (in_array($relacion, self::TABLAS_INVENTARIO)) {
                $query->whereNotNull("{$relacion}.InventarioID");
            }
        }

        $columnasConAlias = self::resolverAliasColumnas($columnas);
        $query->distinct()->select($columnasConAlias);

        self::aplicarFiltros($query, $filtros);

        if ($ordenCol) {
            $query->orderBy($ordenCol, $ordenDir);
        }
        if ($limite) {
            $query->limit((int) $limite);
        }

        return $query;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY UNION (múltiples tablas de inventario)
    // ──────────────────────────────────────────────────────────────────────────

    protected static function construirQueryUnion(array $metadata, array $relacionesUniversales, array $inventariosUsados)
    {
        $tablaPrincipal  = $metadata['tabla_principal'];
        $todasRelaciones = is_array($metadata['tabla_relacion'] ?? [])
            ? $metadata['tabla_relacion']
            : [$metadata['tabla_relacion']];
        $columnasTotales = $metadata['columnas']      ?? [];
        $filtros         = $metadata['filtros']       ?? [];
        $ordenCol        = $metadata['ordenColumna']   ?? null;
        $ordenDir        = $metadata['ordenDireccion'] ?? 'asc';
        $limite          = $metadata['limite']         ?? null;

        $todasTablas = array_unique(array_merge([$tablaPrincipal], $todasRelaciones));

        // ── 1. DESCUBRIMIENTO DE DOMINIOS ────────────────────────────────────
        $tablaDominio = [];
        foreach ($todasTablas as $tabla) {
            if ($tabla === $tablaPrincipal) {
                continue;
            }
            if (in_array($tabla, self::TABLAS_INVENTARIO)) {
                $tablaDominio[$tabla] = $tabla;
                continue;
            }
            try {
                $camino = JoinHelper::resolverRutaJoins($tablaPrincipal, $tabla, $relacionesUniversales);
                foreach ($camino as $paso) {
                    if (in_array($paso[0], self::TABLAS_INVENTARIO)) {
                        $tablaDominio[$tabla] = $paso[0];
                        break;
                    }
                }
            } catch (\Exception $e) {
                // tabla sin dominio → se trata como base
            }
        }

        // ── 2. SEPARAR COLUMNAS Y RELACIONES POR DOMINIO ─────────────────────
        $columnasBase            = [];
        $columnasPorInventario   = array_fill_keys(self::TABLAS_INVENTARIO, []);
        $relacionesBase          = [];
        $relacionesPorInventario = array_fill_keys(self::TABLAS_INVENTARIO, []);

        foreach ($columnasTotales as $col) {
            $colLimpia   = str_replace('`', '', trim($col));
            $expr        = stripos($colLimpia, ' as ') !== false
                ? preg_split('/\s+as\s+/i', $colLimpia)[0]
                : $colLimpia;
            $tablaCol    = str_contains($expr, '.') ? explode('.', $expr)[0] : $tablaPrincipal;
            $invDominio  = $tablaDominio[$tablaCol] ?? null;

            if ($invDominio) {
                $columnasPorInventario[$invDominio][] = $col;
            } else {
                $columnasBase[] = $col;
            }
        }

        foreach ($todasRelaciones as $rel) {
            $invDominio = $tablaDominio[$rel] ?? null;
            if ($invDominio) {
                $relacionesPorInventario[$invDominio][] = $rel;
            } else {
                $relacionesBase[] = $rel;
            }
        }

        // ── 3. ORDEN Y ALIAS CANÓNICOS ────────────────────────────────────────
        $conteoNombres = [];
        foreach (self::TABLAS_INVENTARIO as $inv) {
            foreach ($columnasPorInventario[$inv] as $col) {
                $partes      = stripos($col, ' as ') !== false ? preg_split('/\s+as\s+/i', $col) : explode('.', $col);
                $nombreCorto = str_replace('`', '', trim(last($partes)));
                $conteoNombres[strtolower($nombreCorto)] = ($conteoNombres[strtolower($nombreCorto)] ?? 0) + 1;
            }
        }

        $ordenCanonico = [];
        $aliasVistos   = [];
        foreach (self::TABLAS_INVENTARIO as $inv) {
            foreach ($columnasPorInventario[$inv] as $col) {
                $partes      = stripos($col, ' as ') !== false ? preg_split('/\s+as\s+/i', $col) : explode('.', $col);
                $expr        = stripos($col, ' as ') !== false ? trim($partes[0]) : trim($col);
                $nombreCorto = str_replace('`', '', trim(last($partes)));
                $key         = strtolower($nombreCorto);

                $alias = ($conteoNombres[$key] > 1 || isset($aliasVistos[$key]))
                    ? $inv . '_' . $nombreCorto
                    : $nombreCorto;

                $aliasVistos[strtolower($alias)] = true;
                $ordenCanonico[] = ['alias' => $alias, 'expr' => $expr, 'inv' => $inv];
            }
        }

        $aliasBase    = [];
        $aliasVistos2 = [];
        foreach ($columnasBase as $col) {
            $partes      = stripos($col, ' as ') !== false ? preg_split('/\s+as\s+/i', $col) : explode('.', $col);
            $expr        = stripos($col, ' as ') !== false ? trim($partes[0]) : trim($col);
            $nombreCorto = str_replace('`', '', trim(last($partes)));
            $key         = strtolower($nombreCorto);

            $alias = isset($aliasVistos2[$key])
                ? str_replace('`', '', explode('.', $expr)[0]) . '_' . $nombreCorto
                : $nombreCorto;

            $aliasBase[]                         = ['expr' => $expr, 'alias' => $alias];
            $aliasVistos2[strtolower($alias)]    = true;
        }

        // ── 4. SUBQUERIES AISLADAS ────────────────────────────────────────────
        $subqueries = [];

        foreach ($inventariosUsados as $inventario) {
            if (empty($columnasPorInventario[$inventario])) {
                continue;
            }

            $selectParts = [];

            foreach ($aliasBase as $item) {
                $selectParts[] = DB::raw("{$item['expr']} as `{$item['alias']}`");
            }

            foreach ($ordenCanonico as $item) {
                $selectParts[] = $item['inv'] === $inventario
                    ? DB::raw("{$item['expr']} as `{$item['alias']}`")
                    : DB::raw("NULL as `{$item['alias']}`");
            }

            $subQuery    = DB::table($tablaPrincipal);
            $joinsHechos = [];

            // Joins base
            foreach ($relacionesBase as $relacion) {
                try {
                    $camino = JoinHelper::resolverRutaJoins($tablaPrincipal, $relacion, $relacionesUniversales);
                    foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                        if (!in_array($tablaJoin, $joinsHechos)) {
                            $subQuery->leftJoin($tablaJoin, $from, $op, $to);
                            $joinsHechos[] = $tablaJoin;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("UNION base join '{$relacion}': " . $e->getMessage());
                }
            }

            // Joins del inventario específico
            $relsInv = array_unique(array_merge([$inventario], $relacionesPorInventario[$inventario]));
            foreach ($relsInv as $relacion) {
                try {
                    $camino = JoinHelper::resolverRutaJoins($tablaPrincipal, $relacion, $relacionesUniversales);
                    foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                        if (!in_array($tablaJoin, $joinsHechos)) {
                            $subQuery->leftJoin($tablaJoin, $from, $op, $to);
                            $joinsHechos[] = $tablaJoin;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("UNION inv join '{$relacion}': " . $e->getMessage());
                }
            }

            // Soft deletes — FIX: solo whereNull, sin orWhereNotNull
            if (Schema::hasColumn($tablaPrincipal, 'deleted_at')) {
                $subQuery->whereNull("{$tablaPrincipal}.deleted_at");
            }
            foreach ($joinsHechos as $tablaJoin) {
                if (Schema::hasColumn($tablaJoin, 'deleted_at')) {
                    $subQuery->whereNull("{$tablaJoin}.deleted_at");
                }
            }

            self::aplicarFiltros($subQuery, $filtros);

            // Excluir filas donde este inventario no tiene datos
            $primerExpr = null;
            foreach ($ordenCanonico as $item) {
                if ($item['inv'] === $inventario) {
                    $primerExpr = $item['expr'];
                    break;
                }
            }
            if ($primerExpr) {
                $subQuery->whereNotNull(DB::raw($primerExpr));
            }

            $subQuery->select($selectParts);
            $subqueries[] = $subQuery;
        }

        if (empty($subqueries)) {
            return self::construirQuerySimple($metadata, $relacionesUniversales);
        }

        // ── 5. UNION FINAL — 1 fila por registro ────────────────────────────
        // Usamos UNION (sin ALL) para eliminar duplicados exactos entre
        // subqueries, devolviendo 1 fila por equipo/insumo. Esto hace el
        // reporte legible para soporte: una fila = un item, con los datos
        // del empleado repetidos por fila (igual que Excel o cualquier
        // reporte tabular estándar).

        $unionSqls = [];
        foreach ($subqueries as $sq) {
            $unionSqls[] = $sq->toSql();
        }

        $unionSql = implode(' UNION ', array_map(fn($s) => "({$s})", $unionSqls));

        $wrapped = DB::table(DB::raw("({$unionSql}) as `union_result`"));

        // mergeBindings preserva el orden de los '?' en el SQL compilado
        foreach ($subqueries as $sq) {
            $wrapped->mergeBindings($sq);
        }

        // Seleccionar todas las columnas sin agrupar — 1 fila por registro
        $selectCols = [];
        foreach ($aliasBase as $item) {
            $selectCols[] = DB::raw("`{$item['alias']}`");
        }
        foreach ($ordenCanonico as $item) {
            $selectCols[] = DB::raw("`{$item['alias']}`");
        }

        $wrapped->select($selectCols);

        if ($ordenCol) {
            $colCorta = str_replace('`', '', last(explode('.', $ordenCol)));
            $dir      = strtoupper($ordenDir) === 'DESC' ? 'DESC' : 'ASC';
            $wrapped->orderByRaw("`{$colCorta}` {$dir}");
        }
        if ($limite) {
            $wrapped->limit((int) $limite);
        }

        return $wrapped;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // EJECUCIÓN
    // ──────────────────────────────────────────────────────────────────────────

    public static function ejecutarConsulta(array $metadata, array $relacionesUniversales)
    {
        $query = static::construirQuery($metadata, $relacionesUniversales);

        if (config('app.debug')) {
            try {
                $sqlCompleto = vsprintf(
                    str_replace('?', '%s', $query->toSql()),
                    collect($query->getBindings())
                        ->map(fn($b) => is_numeric($b) ? $b : "'{$b}'")->toArray()
                );
                Log::debug('Query lista para ejecutar:', [$sqlCompleto]);
            } catch (\Exception $e) {
                Log::debug('Query UNION — SQL demasiado complejo para log simple: ' . $e->getMessage());
                Log::debug('RAW SQL:', [$query->toSql()]);
                Log::debug('BINDINGS:', [$query->getBindings()]);
            }
        }

        return $query->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    protected static function resolverAliasColumnas(array $columnas): array
    {
        $aliasSeen        = [];
        $columnasConAlias = [];

        foreach ($columnas as $col) {
            $col = trim($col);

            if (stripos($col, ' as ') !== false) {
                $columnasConAlias[] = $col;
                $partes             = preg_split('/\s+as\s+/i', $col);
                $aliasSeen[strtolower(trim($partes[1]))] = true;
                continue;
            }

            $nombreCorto = str_contains($col, '.') ? last(explode('.', $col)) : $col;
            $key         = strtolower($nombreCorto);

            if (isset($aliasSeen[$key])) {
                $tablaPrefix = str_contains($col, '.') ? explode('.', $col)[0] : 'col';
                $alias       = $tablaPrefix . '_' . $nombreCorto;
                $expr        = str_contains($col, '.')
                    ? '`' . implode('`.`', explode('.', $col, 2)) . '`'
                    : "`{$col}`";

                $columnasConAlias[]            = DB::raw("{$expr} as `{$alias}`");
                $aliasSeen[strtolower($alias)] = true;
            } else {
                $columnasConAlias[] = $col;
                $aliasSeen[$key]    = true;
            }
        }

        return $columnasConAlias;
    }

    protected static function aplicarFiltros($query, array $filtros): void
    {
        $meses = [
            'Enero'      => 1,  'Febrero'    => 2,  'Marzo'     => 3,  'Abril'     => 4,
            'Mayo'       => 5,  'Junio'      => 6,  'Julio'     => 7,  'Agosto'    => 8,
            'Septiembre' => 9,  'Octubre'    => 10, 'Noviembre' => 11, 'Diciembre' => 12,
        ];

        foreach ($filtros as $filtro) {
            if (empty($filtro['columna']) || !isset($filtro['valor'])) {
                continue;
            }

            $columna  = $filtro['columna'];
            $operador = $filtro['operador'] ?? '=';
            $valor    = $filtro['valor'];

            if ($operador === 'between') {
                if (!is_array($valor)) {
                    continue;
                }
                $inicio = $valor['inicio'] ?? null;
                $fin    = $valor['fin']    ?? null;

                if (is_null($inicio) || is_null($fin)) {
                    continue;
                }

                if ($columna === 'inventarioinsumo.MesDePago') {
                    $inicioNum = $meses[$inicio] ?? null;
                    $finNum    = $meses[$fin]    ?? null;
                    if ($inicioNum && $finNum) {
                        $ordenMeses = implode(',', array_map(fn($m) => "'{$m}'", array_keys($meses)));
                        $query->whereBetween(
                            DB::raw("FIELD(`MesDePago`, {$ordenMeses})"),
                            [$inicioNum, $finNum]
                        );
                    }
                } elseif (
                    (strtotime((string) $inicio) !== false && strtotime((string) $fin) !== false)
                    || (is_numeric($inicio) && is_numeric($fin))
                ) {
                    $query->whereBetween($columna, [$inicio, $fin]);
                }
            } else {
                if ($operador === 'like') {
                    $valor = "%{$valor}%";
                }
                $query->where($columna, $operador, $valor);
            }
        }
    }
}