<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReporteService
{
    public static function modeloDesdeTabla(string $tabla): ?string
    {
        foreach (self::listarModelos() as $modelo) {
            if ((new $modelo)->getTable() === $tabla) {
                return $modelo;
            }
        }
        return null;
    }

    public static function listarModelos(): array
    {
        return collect(glob(app_path("Models") . '/*.php'))
            ->map(fn($archivo) => "App\\Models\\" . basename($archivo, '.php'))
            ->filter(fn($clase) => class_exists($clase) && is_subclass_of($clase, Model::class))
            ->toArray();
    }

    public static function listarColumnas(string $tabla): array
    {
        return collect(Schema::getColumnListing($tabla))
            ->reject(fn($col) => Str::endsWith($col, ['ID', 'Id', '_id', '_at', 'created_at', 'updated_at', 'deleted_at']))
            ->values()
            ->toArray();
    }

    public static function relacionesTablas(string $modeloClase): array
    {
        if (!class_exists($modeloClase)) return [];

        $modelo = new $modeloClase;
        $relaciones = [];

        foreach (get_class_methods($modeloClase) as $method) {
            if (
                str_starts_with($method, 'get') ||
                str_starts_with($method, 'set') ||
                str_starts_with($method, 'scope') ||
                $method === '__construct'
            ) {
                continue;
            }

            try {
                $rel = $modelo->$method();
                if ($rel instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $relaciones[$method] = Str::headline(class_basename($rel->getRelated()));
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $relaciones;
    }

    public static function agregarClavesForaneas(array $relaciones, array $columnasPrincipales, string $modeloClase): array
    {
        $modelo = new $modeloClase;

        foreach ($relaciones as $relacion) {
            if (!method_exists($modelo, $relacion)) continue;

            try {
                $relacionObj = $modelo->$relacion();

                if (method_exists($relacionObj, 'getForeignKeyName')) {
                    $fk = $relacionObj->getForeignKeyName();
                    if (!in_array($fk, $columnasPrincipales)) {
                        $columnasPrincipales[] = $fk;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $columnasPrincipales;
    }

    public static function obtenerColumnasRelacion(string $modeloClase, string $relacion): array
    {
        try {
            $instancia = new $modeloClase;

            foreach (explode('.', $relacion) as $rel) {
                $relacionObj = $instancia->$rel();
                $instancia = $relacionObj->getRelated();
            }

            $tabla = $instancia->getTable();
            $columnas = Schema::getColumnListing($tabla);

            return collect($columnas)
                ->reject(fn($col) => Str::endsWith($col, ['ID', 'Id', '_id', '_at', 'created_at', 'updated_at', 'deleted_at']))
                ->map(fn($col) => $tabla . '.' . $col)
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }


    public static function obtenerTablas(): array
    {
        $nameDB = \DB::getDatabaseName();

        return collect(\DB::select("SHOW TABLES"))
            ->map(fn($obj) => $obj->{"Tables_in_{$nameDB}"})
            ->filter(fn($tabla) => self::modeloDesdeTabla($tabla))
            ->values()
            ->toArray();
    }
}
