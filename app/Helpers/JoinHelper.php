<?php

namespace App\Helpers;

class JoinHelper
{
    public static function resolverRutaJoins($desde, $hasta, $relaciones)
    {
        $camino = [];
        $visitado = [];

        $encontrado = self::buscarRuta($desde, $hasta, $relaciones, $camino, $visitado);

        if (!$encontrado) {
            throw new \Exception("No se pudo encontrar una ruta entre '$desde' y '$hasta'");
        }

        return $camino;
    }

    protected static function buscarRuta($actual, $destino, $relaciones, &$camino, &$visitado)
    {
        if ($actual === $destino) return true;
        if (isset($visitado[$actual])) return false;

        $visitado[$actual] = true;

        if (!isset($relaciones[$actual])) return false;

        foreach ($relaciones[$actual] as $siguiente => $join) {
            $camino[] = [$siguiente, $join];
            if ($siguiente === $destino || self::buscarRuta($siguiente, $destino, $relaciones, $camino, $visitado)) {
                return true;
            }
            array_pop($camino);
        }

        return false;
    }
}
