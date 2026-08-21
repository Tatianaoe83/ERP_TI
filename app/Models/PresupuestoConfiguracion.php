<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PresupuestoConfiguracion extends Model
{
    public $table = 'presupuesto_configuracion';

    public $fillable = [
        'grupo',
        'valor',
    ];

    /** @var array<string, array<int, string>>|null */
    protected static $cacheValores = null;

    /** @var array<int, string>|null */
    protected static $cacheGruposGuardados = null;

    /**
     * Secciones del presupuesto que el usuario puede mapear a categorías de inventario.
     */
    public const GRUPOS = [
        'hardware' => [
            'label' => 'Equipo de cómputo / inversiones',
            'hint' => 'Categorías de equipo que entran al reporte de hardware, al bloque INVERSIONES del calendario y a los KPIs de cortes.',
            'origen' => 'equipo',
            'defaults' => ['LAPTOP', 'MONITOR', 'IMPRESORA', 'MODEM', 'TABLET', 'TABLETA', 'ANTENA', 'NO BREAK', 'PC ESCRITORIO'],
        ],
        'otros_insumos' => [
            'label' => 'Otros insumos',
            'hint' => 'Mantenimiento, reparaciones, servicios y hosting que salen en el bloque de otros insumos.',
            'origen' => 'insumo',
            'defaults' => ['MANTENIMIENTO', 'REPARACIONES', 'SERVICIO', 'HOSTING'],
        ],
        'licencias' => [
            'label' => 'Licencias',
            'hint' => 'Categorías de insumo que cuentan como licenciamiento (reportes, calendario y dashboard).',
            'origen' => 'insumo',
            'defaults' => ['LICENCIA'],
        ],
        'impresoras' => [
            'label' => 'Renta de impresora',
            'hint' => 'Categorías de insumo que suman en “Costo renta de impresoras”.',
            'origen' => 'insumo',
            'defaults' => ['RENTA DE IMPRESORA'],
        ],
        'internet' => [
            'label' => 'Internet fijo',
            'hint' => 'Categorías de insumo que suman en “Costo internet fijo”.',
            'origen' => 'insumo',
            'defaults' => ['INTERNET'],
        ],
        'insumos_mensuales' => [
            'label' => 'Insumos de pago mensual',
            'hint' => 'Categorías que en el calendario se prorratean los 12 meses (licencias recurrentes, hosting, internet, etc.).',
            'origen' => 'insumo',
            'defaults' => ['LICENCIA', 'HOSTING', 'STARLINK', 'INTERNET', 'TABLET'],
        ],
        'excluir_otros_anuales' => [
            'label' => 'Excluir de “otros anuales”',
            'hint' => 'Categorías que no deben mezclarse en el bloque de otros insumos anuales (porque ya van en hardware o licencias).',
            'origen' => 'insumo',
            'defaults' => ['LAPTOP', 'MONITOR', 'NO BREAK', 'LICENCIA', 'ACCESORIOS', 'BATERIA UPS', 'IMPRESORA'],
        ],
        'licencias_excluir_nombres' => [
            'label' => 'Nombres de licencia a omitir',
            'hint' => 'Nombres exactos de insumo que no deben listarse en el reporte de licencias (el costo de referencia PRO sigue usándose para cotizar HOME).',
            'origen' => 'nombre_insumo',
            'defaults' => ['WINDOWS 10 PRO', 'WINDOWS 11 PRO', 'ERP VSCONTROL TOTAL'],
        ],
        'lineas_voz' => [
            'label' => 'Líneas de voz',
            'hint' => 'Valores de tipo de línea que entran al presupuesto de telefonía.',
            'origen' => 'linea',
            'defaults' => ['Voz'],
        ],
        'lineas_datos' => [
            'label' => 'Líneas de datos',
            'hint' => 'Valores de tipo de línea que entran al presupuesto de datos.',
            'origen' => 'linea',
            'defaults' => ['Datos'],
        ],
        'lineas_gps' => [
            'label' => 'Líneas GPS',
            'hint' => 'Valores de tipo de línea que entran al presupuesto de GPS.',
            'origen' => 'linea',
            'defaults' => ['GPS'],
        ],
    ];

    public static function flushCache(): void
    {
        static::$cacheValores = null;
        static::$cacheGruposGuardados = null;
    }

    /**
     * @return array<int, string>
     */
    public static function valores(string $grupo): array
    {
        static::cargarCache();

        if (in_array($grupo, static::$cacheGruposGuardados ?? [], true)) {
            return static::$cacheValores[$grupo] ?? [];
        }

        return static::GRUPOS[$grupo]['defaults'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function valoresUpper(string $grupo): array
    {
        return array_values(array_unique(array_map(
            fn ($v) => mb_strtoupper(trim((string) $v), 'UTF-8'),
            static::valores($grupo)
        )));
    }

    public static function contiene($valor, string $grupo): bool
    {
        $needle = mb_strtoupper(trim((string) ($valor ?? '')), 'UTF-8');
        if ($needle === '') {
            return false;
        }

        return in_array($needle, static::valoresUpper($grupo), true);
    }

    public static function aplicarWhereIn($query, string $columna, string $grupo)
    {
        $valores = static::valoresUpper($grupo);
        if ($valores === []) {
            return $query->whereRaw('1 = 0');
        }

        $placeholders = implode(',', array_fill(0, count($valores), '?'));

        return $query->whereRaw('UPPER(TRIM(' . $columna . ')) IN (' . $placeholders . ')', $valores);
    }

    public static function aplicarWhereNotIn($query, string $columna, string $grupo)
    {
        $valores = static::valoresUpper($grupo);
        if ($valores === []) {
            return $query;
        }

        $placeholders = implode(',', array_fill(0, count($valores), '?'));

        return $query->whereRaw('UPPER(TRIM(' . $columna . ')) NOT IN (' . $placeholders . ')', $valores);
    }

    public static function sqlIn(string $columna, string $grupo): string
    {
        $valores = static::valoresUpper($grupo);
        if ($valores === []) {
            return '1 = 0';
        }

        $quoted = implode(',', array_map(
            fn ($v) => "'" . str_replace("'", "''", $v) . "'",
            $valores
        ));

        return 'UPPER(TRIM(' . $columna . ')) IN (' . $quoted . ')';
    }

    /**
     * @return array<int, string>
     */
    public static function opciones(string $origen): array
    {
        $deCatalogo = collect();
        $deInventario = collect();

        try {
            if ($origen === 'equipo') {
                $deCatalogo = Categorias::query()
                    ->whereHas('tiposdecategorias', function ($t) {
                        $t->whereRaw("UPPER(Categoria) LIKE '%EQUIPO%'");
                    })
                    ->pluck('Categoria');
                $deInventario = DB::table('inventarioequipo')->distinct()->pluck('CategoriaEquipo');
            } elseif ($origen === 'insumo') {
                $deCatalogo = Categorias::query()
                    ->whereHas('tiposdecategorias', function ($t) {
                        $t->whereRaw("UPPER(Categoria) LIKE '%INSUMO%'");
                    })
                    ->pluck('Categoria');
                $deInventario = DB::table('inventarioinsumo')->distinct()->pluck('CateogoriaInsumo');
            } elseif ($origen === 'linea') {
                $deInventario = DB::table('inventariolineas')->distinct()->pluck('TipoLinea');
            } elseif ($origen === 'nombre_insumo') {
                $cats = static::valoresUpper('licencias');
                $q = DB::table('inventarioinsumo')->select('NombreInsumo')->distinct();
                if ($cats !== []) {
                    $placeholders = implode(',', array_fill(0, count($cats), '?'));
                    $q->whereRaw('UPPER(TRIM(CateogoriaInsumo)) IN (' . $placeholders . ')', $cats);
                }
                $deInventario = $q->pluck('NombreInsumo');
            }
        } catch (\Throwable $e) {
            // catálogo o tablas aún no disponibles
        }

        return $deCatalogo
            ->merge($deInventario)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique(fn ($v) => mb_strtoupper($v, 'UTF-8'))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    protected static function cargarCache(): void
    {
        if (static::$cacheValores !== null) {
            return;
        }

        static::$cacheValores = [];
        static::$cacheGruposGuardados = [];

        try {
            if (! Schema::hasTable('presupuesto_configuracion')) {
                return;
            }

            $rows = static::query()->orderBy('id')->get(['grupo', 'valor']);
            static::$cacheGruposGuardados = $rows->pluck('grupo')->unique()->values()->all();
            static::$cacheValores = $rows
                ->groupBy('grupo')
                ->map(function ($grupoRows) {
                    return $grupoRows
                        ->pluck('valor')
                        ->map(fn ($v) => trim((string) $v))
                        ->filter()
                        ->values()
                        ->all();
                })
                ->all();
        } catch (\Throwable $e) {
            static::$cacheValores = [];
            static::$cacheGruposGuardados = [];
        }
    }
}
