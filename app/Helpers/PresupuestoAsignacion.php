<?php

namespace App\Helpers;

class PresupuestoAsignacion
{
    public const STOCK = 0;
    public const EXTRA = 1;
    public const COMPARTIDO = 2;
    /** Sólo equipos: es del empleado, así que nunca entra al presupuesto. */
    public const PROPIO = 3;

    /** Columna que guarda la modalidad en cada tabla de inventario. */
    public const COLUMNA_EQUIPOS = 'tipoEquipo';
    public const COLUMNA_DEFAULT = 'Presupuestado';

    public static function normalizar($valor): int
    {
        $v = (int) $valor;

        return \in_array($v, [self::STOCK, self::EXTRA, self::COMPARTIDO, self::PROPIO], true)
            ? $v
            : self::STOCK;
    }

    public static function entraEnInventario($valor): bool
    {
        $v = self::normalizar($valor);

        return $v === self::STOCK || $v === self::COMPARTIDO || $v === self::PROPIO;
    }

    public static function entraEnPresupuesto($valor): bool
    {
        $v = self::normalizar($valor);

        return $v === self::EXTRA || $v === self::COMPARTIDO;
    }

    public static function etiqueta($valor): string
    {
        return match (self::normalizar($valor)) {
            self::PROPIO => 'Propio',
            self::COMPARTIDO => 'Compartido',
            self::EXTRA => 'Extra',
            default => 'Stock',
        };
    }

    public static function chipHtml($valor): string
    {
        return match (self::normalizar($valor)) {
            self::PROPIO => '<span class="inv-chip inv-chip-propio">Propio</span>',
            self::COMPARTIDO => '<span class="inv-chip inv-chip-share">Compartido</span>',
            self::EXTRA => '<span class="inv-chip inv-chip-extra">Extra</span>',
            default => '<span class="inv-chip inv-chip-stock">Stock</span>',
        };
    }

    /** Modalidades que alimentan los reportes de presupuesto. */
    public static function valoresPresupuesto(): array
    {
        return [self::EXTRA, self::COMPARTIDO];
    }

    /** Modalidades que se ven como inventario actual. */
    public static function valoresInventario(): array
    {
        return [self::STOCK, self::COMPARTIDO, self::PROPIO];
    }

    /**
     * Los equipos guardan la modalidad en un ENUM('0','1','2','3'): comparar contra
     * un entero haría que MySQL use el *índice* del ENUM (1 = '0', 2 = '1'...) y
     * devolvería justo las filas equivocadas. En cadena compara por valor, y en las
     * columnas TINYINT de insumos y líneas la cadena se convierte a número igual.
     */
    private static function comoTexto(array $valores): array
    {
        return array_map('strval', $valores);
    }

    public static function aplicarWhere($query, string $modo, string $columna = self::COLUMNA_DEFAULT)
    {
        if ($modo === 'presupuesto') {
            return $query->whereIn($columna, self::comoTexto(self::valoresPresupuesto()));
        }

        return $query->where(function ($q) use ($columna) {
            $q->whereIn($columna, self::comoTexto(self::valoresInventario()))
                ->orWhereNull($columna);
        });
    }

    public static function sqlWhere(string $columna, string $modo): string
    {
        $valores = $modo === 'presupuesto'
            ? self::valoresPresupuesto()
            : self::valoresInventario();

        $lista = "'" . implode("', '", self::comoTexto($valores)) . "'";

        return $modo === 'presupuesto'
            ? " AND {$columna} IN ({$lista}) "
            : " AND ({$columna} IN ({$lista}) OR {$columna} IS NULL) ";
    }
}
