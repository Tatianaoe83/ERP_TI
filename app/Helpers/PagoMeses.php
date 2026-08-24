<?php

namespace App\Helpers;

class PagoMeses
{
    public const MESES = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];

    /**
     * Convierte el valor guardado (un mes, varios o vacío) en la lista canónica.
     * Si no hay meses y la frecuencia vieja era mensual, se asumen los 12.
     *
     * @return array<int, string>
     */
    public static function parse($valor, $frecuencia = null): array
    {
        $tokens = preg_split('/[,;|]+/', (string) ($valor ?? '')) ?: [];
        $meses = [];

        foreach ($tokens as $token) {
            $canon = self::canonico($token);
            if ($canon && ! in_array($canon, $meses, true)) {
                $meses[] = $canon;
            }
        }

        if ($meses !== []) {
            return self::ordenar($meses);
        }

        $freq = mb_strtoupper(trim((string) ($frecuencia ?? '')), 'UTF-8');
        if (strpos($freq, 'MENSUAL') !== false) {
            return self::MESES;
        }

        return [];
    }

    public static function encode($valor): string
    {
        if (is_array($valor)) {
            $meses = [];
            foreach ($valor as $item) {
                $canon = self::canonico($item);
                if ($canon && ! in_array($canon, $meses, true)) {
                    $meses[] = $canon;
                }
            }

            return implode(',', self::ordenar($meses));
        }

        return implode(',', self::parse($valor));
    }

    public static function aplica($valor, string $mes, $frecuencia = null): bool
    {
        $canon = self::canonico($mes);
        if (! $canon) {
            return false;
        }

        return in_array($canon, self::parse($valor, $frecuencia), true);
    }

    public static function etiqueta($valor, $frecuencia = null): string
    {
        $meses = self::parse($valor, $frecuencia);
        $n = count($meses);

        if ($n === 0) {
            return 'Sin meses';
        }
        if ($n === 12) {
            return 'Anual (12 meses)';
        }
        if ($n === 1) {
            return $meses[0];
        }

        return 'Parcial (' . $n . ' meses)';
    }

    public static function frecuenciaDerivada($valor, $frecuencia = null): string
    {
        $n = count(self::parse($valor, $frecuencia));
        if ($n >= 12) {
            return 'Mensual';
        }
        if ($n === 1) {
            return 'Pago único';
        }
        if ($n > 1) {
            return 'Parcial';
        }

        return 'Pago único';
    }

    public static function fromRequest($raw): string
    {
        if (is_array($raw)) {
            return self::encode($raw);
        }

        return self::encode((string) $raw);
    }

    public static function canonico($valor): ?string
    {
        $t = trim((string) ($valor ?? ''));
        if ($t === '' || strcasecmp($t, 'N/A') === 0) {
            return null;
        }

        $key = self::sinAcento(mb_strtoupper($t, 'UTF-8'));
        foreach (self::MESES as $mes) {
            if (self::sinAcento(mb_strtoupper($mes, 'UTF-8')) === $key) {
                return $mes;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $meses
     * @return array<int, string>
     */
    private static function ordenar(array $meses): array
    {
        $orden = array_flip(self::MESES);
        usort($meses, fn ($a, $b) => ($orden[$a] ?? 99) <=> ($orden[$b] ?? 99));

        return array_values($meses);
    }

    private static function sinAcento(string $texto): string
    {
        $map = ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U'];

        return strtr($texto, $map);
    }
}
