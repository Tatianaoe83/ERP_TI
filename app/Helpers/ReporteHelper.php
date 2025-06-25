<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Helpers\JoinHelper;
use Illuminate\Support\Facades\Log;

class ReporteHelper
{
    public static function ejecutarConsulta(array $metadata, array $relacionesUniversales)
    {
        $tabla = $metadata['tabla_principal'];
        $relaciones = is_array($metadata['tabla_relacion'] ?? []) ? $metadata['tabla_relacion'] : [$metadata['tabla_relacion']];
        $columnas = $metadata['columnas'] ?? ['*'];
        $filtros = $metadata['filtros'] ?? [];
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

        $query->select($columnas);

        foreach ($filtros as $filtro) {
            if (empty($filtro['columna']) || !isset($filtro['valor'])) {
                continue;
            }

            $columna = $filtro['columna'];
            $operador = $filtro['operador'] ?? '=';
            $valor = $filtro['valor'];

            if ($operador === 'between') {
                if (is_array($valor)) {
                    $inicio = $valor['inicio'] ?? null;
                    $fin = $valor['fin'] ?? null;

                    if (!is_null($inicio) && !is_null($fin)) {
                        $query->whereBetween($columna, [$inicio, $fin]);
                    }
                }
            } else {
                if ($operador === 'like') {
                    $valor = "%{$valor}%";
                }
                $query->where($columna, $operador, $valor);
            }
        }

        if ($ordenCol) {
            $query->orderBy($ordenCol, $ordenDir);
        }

        if ($limite) {
            $query->limit($limite);
        }

        Log::debug('Query generada:', [$query->toSql(), $query->getBindings()]);
        //dd($query->toSql());

        return $query->get();
    }
}
