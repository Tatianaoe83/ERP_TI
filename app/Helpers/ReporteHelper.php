<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Helpers\JoinHelper;

class ReporteHelper
{
    public static function ejecutarConsulta(array $metadata, array $relacionesUniversales)
    {
        $tabla = $metadata['tabla_principal'];
        $relaciones = is_array($metadata['tabla_relacion'] ?? []) ? $metadata['tabla_relacion'] : [$metadata['tabla_relacion']];
        $columnas = $metadata['columnas'] ?? ['*'];
        $filtros = $metadata['filtros'] ?? [];
        $grupo = $metadata['grupo'] ?? null;
        $ordenCol = $metadata['ordenColumna'] ?? null;
        $ordenDir = $metadata['ordenDireccion'] ?? 'asc';
        $limite = $metadata['limite'] ?? null;

        $query = DB::table($tabla);
        $joinsHechos = [];

        foreach ($relaciones as $relacion) {
            $camino = JoinHelper::resolverRutaJoins($tabla, $relacion, $relacionesUniversales);
            foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                if (!in_array($tablaJoin, $joinsHechos)) {
                    $query->join($tablaJoin, $from, $op, $to);
                    $joinsHechos[] = $tablaJoin;
                }
            }
        }

        if ($grupo) {
            $columnas = array_map(function ($col) use ($grupo) {
                return $col === $grupo ? $col : DB::raw("MAX($col) as `" . str_replace('.', '_', $col) . "`");
            }, $columnas);
        }

        $query->select($columnas);

        foreach ($filtros as $filtro) {
            if (!empty($filtro['columna']) && isset($filtro['valor'])) {
                $valor = $filtro['valor'];
                if ($filtro['operador'] === 'like') {
                    $valor = "%$valor%";
                }
                $query->where($filtro['columna'], $filtro['operador'] ?? '=', $valor);
            }
        }

        if ($grupo) $query->groupBy($grupo);
        if ($ordenCol) $query->orderBy($ordenCol, $ordenDir);
        if ($limite) $query->limit($limite);

        return $query->get();
    }
}
