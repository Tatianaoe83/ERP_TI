<?php

namespace App\Helpers;

/**
 * JoinHelper — BFS con penalización para rutas de tabla amplia.
 *
 * Mejoras respecto a la versión original:
 *  1. BFS (cola) en vez de DFS (recursión): garantiza la ruta MÁS CORTA,
 *     evitando que el algoritmo atraviese 'unidadesdenegocio' u 'obras'
 *     innecesariamente y genere producto cartesiano.
 *  2. Penalización configurable: las tablas "anchas" cuestan más saltos,
 *     así el BFS prefiere rutas directas.
 *  3. Bidireccional real: si A→B existe en las relaciones, también se
 *     puede recorrer B→A sin duplicar la definición en RelacionesUniversales.
 */
class JoinHelper
{
    /** Tablas cuyo cruce debe evitarse salvo que no haya otra ruta. */
    private const TABLAS_PESADAS = ['unidadesdenegocio', 'obras'];

    /**
     * Devuelve el camino de joins necesario para ir de $desde hasta $hasta.
     *
     * @return array  Array de [$tablaJoin, [$from, $op, $to]]
     * @throws \Exception si no existe ruta posible.
     */
    public static function resolverRutaJoins(string $desde, string $hasta, array $relaciones): array
    {
        if ($desde === $hasta) {
            return [];
        }

        // ── BFS con coste ─────────────────────────────────────────────────────
        // Cada nodo en la cola: [tabla_actual, coste_acumulado, camino_hasta_aquí]
        $cola     = [[$desde, 0, []]];
        $visitado = [];   // tabla => coste mínimo conocido

        while (!empty($cola)) {
            // Sacar el nodo de menor coste (mini-Dijkstra con array ordenado)
            usort($cola, fn($a, $b) => $a[1] <=> $b[1]);
            [$actual, $coste, $camino] = array_shift($cola);

            if (isset($visitado[$actual]) && $visitado[$actual] <= $coste) {
                continue;
            }
            $visitado[$actual] = $coste;

            if ($actual === $hasta) {
                return $camino;
            }

            // Vecinos directos definidos en las relaciones
            $vecinos = self::vecinosDesde($actual, $relaciones);

            foreach ($vecinos as $siguiente => $join) {
                if (isset($visitado[$siguiente])) {
                    continue;
                }

                $costeSalto  = in_array($siguiente, self::TABLAS_PESADAS) ? 10 : 1;
                $nuevoCamino = array_merge($camino, [[$siguiente, $join]]);
                $cola[]      = [$siguiente, $coste + $costeSalto, $nuevoCamino];
            }
        }

        throw new \Exception("No se encontró ruta de joins entre '{$desde}' y '{$hasta}'.");
    }

    /**
     * Devuelve todos los vecinos accesibles desde $tabla, incluyendo
     * las relaciones inversas (bidireccionalidad).
     */
    private static function vecinosDesde(string $tabla, array $relaciones): array
    {
        $vecinos = $relaciones[$tabla] ?? [];

        // Relaciones inversas: si B->A existe, permitir A->B
        foreach ($relaciones as $origen => $destinos) {
            if ($origen === $tabla) {
                continue;
            }
            foreach ($destinos as $destino => $join) {
                if ($destino === $tabla && !isset($vecinos[$origen])) {
                    // Invertir el join: from↔to permanecen igual porque son
                    // siempre "tabla_join.col = tabla_base.col"
                    $vecinos[$origen] = $join;
                }
            }
        }

        return $vecinos;
    }
}