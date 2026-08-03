<?php

namespace App\Http\Controllers\Traits;

use App\Models\Cotizacion;

trait CotizacionTrait
{
    // Genera clave única por propuesta+producto
    private function claveProducto(Cotizacion $c): string
    {
        return 'np_' . (int)($c->NumeroPropuesta ?? 0) . '_prod_' . (int)($c->NumeroProducto ?? 1);
    }

    // Agrupa cotizaciones jerárquicamente: Propuesta -> Producto -> Cotizaciones
    private function agruparCotizacionesPorProducto($cotizaciones): array
    {
        $propuestas = [];
        foreach ($cotizaciones as $c) {
            $numPropuesta = (int)($c->NumeroPropuesta ?? 1);
            $numProducto  = (int)($c->NumeroProducto ?? 1);

            if (!isset($propuestas[$numPropuesta])) {
                $propuestas[$numPropuesta] = [
                    'numeroPropuesta' => $numPropuesta,
                    'productos'       => [],
                ];
            }

            $claveProducto = 'prod_' . $numProducto;
            if (!isset($propuestas[$numPropuesta]['productos'][$claveProducto])) {
                $nombre = trim($c->NombreEquipo ?? '');
                $nombre = preg_replace('/\|+$/', '', $nombre);
                $nombre = trim(preg_replace('/\s*\d+\s*$/', '', $nombre));

                $propuestas[$numPropuesta]['productos'][$claveProducto] = [
                    'numeroProducto' => $numProducto,
                    'descripcion'    => $nombre !== '' ? $nombre : ('Producto ' . $numProducto),
                    'cotizaciones'   => collect([]),
                ];
            }

            $propuestas[$numPropuesta]['productos'][$claveProducto]['cotizaciones']->push($c);
        }

        $result = [];
        foreach ($propuestas as $propuesta) {
            $propuesta['productos'] = array_values($propuesta['productos']);
            usort($propuesta['productos'], fn($a, $b) => $a['numeroProducto'] <=> $b['numeroProducto']);
            $result[] = $propuesta;
        }

        usort($result, fn($a, $b) => $a['numeroPropuesta'] <=> $b['numeroPropuesta']);

        return $result;
    }
}