<?php

namespace App\Helpers;

class PresupuestoAsignacion
{
    public const STOCK = 0;
    public const EXTRA = 1;
    public const COMPARTIDO = 2;

    public static function normalizar($valor): int
    {
        $v = (int) $valor;

        return in_array($v, [self::STOCK, self::EXTRA, self::COMPARTIDO], true)
            ? $v
            : self::STOCK;
    }

    public static function entraEnInventario($valor): bool
    {
        $v = self::normalizar($valor);

        return $v === self::STOCK || $v === self::COMPARTIDO;
    }

    public static function entraEnPresupuesto($valor): bool
    {
        $v = self::normalizar($valor);

        return $v === self::EXTRA || $v === self::COMPARTIDO;
    }

    public static function etiqueta($valor): string
    {
        return match (self::normalizar($valor)) {
            self::COMPARTIDO => 'Compartido',
            self::EXTRA => 'Extra',
            default => 'Stock',
        };
    }

    public static function chipHtml($valor): string
    {
        return match (self::normalizar($valor)) {
            self::COMPARTIDO => '<span class="inv-chip inv-chip-share">Compartido</span>',
            self::EXTRA => '<span class="inv-chip inv-chip-extra">Extra</span>',
            default => '<span class="inv-chip inv-chip-stock">Stock</span>',
        };
    }

    public static function aplicarWhere($query, string $modo)
    {
        if ($modo === 'presupuesto') {
            return $query->whereIn('Presupuestado', [self::EXTRA, self::COMPARTIDO]);
        }

        return $query->where(function ($q) {
            $q->where('Presupuestado', self::STOCK)
                ->orWhere('Presupuestado', self::COMPARTIDO)
                ->orWhereNull('Presupuestado');
        });
    }

    public static function sqlWhere(string $columna, string $modo): string
    {
        return $modo === 'presupuesto'
            ? " AND {$columna} IN (1, 2) "
            : " AND ({$columna} = 0 OR {$columna} = 2 OR {$columna} IS NULL) ";
    }
}
