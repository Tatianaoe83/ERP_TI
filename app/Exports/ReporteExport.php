<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Database\Query\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReporteExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    /** @var string[]  Claves cortas (sin prefijo tabla) en el orden deseado. */
    protected array $claves;

    public function __construct(
        protected Builder $query,
        protected array   $columnas
    ) {
        // Pre-calcular las claves cortas una sola vez para reutilizarlas
        // en map() sin repetir la lógica en cada fila.
        // FIX: el original hacía (array)$row que depende del orden interno
        // de las propiedades del stdClass, el cual puede no coincidir con
        // $columnas cuando hay alias o columnas de múltiples tablas.
        $this->claves = array_map(function (string $col): string {
            // Si tiene alias ("tabla.columna as alias" o "columna as alias")
            if (stripos($col, ' as ') !== false) {
                return trim(last(preg_split('/\s+as\s+/i', $col)));
            }
            // Si tiene prefijo de tabla
            if (str_contains($col, '.')) {
                return last(explode('.', $col));
            }
            return trim($col);
        }, $columnas);
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        // Usar las claves ya calculadas para que headings y map() sean
        // exactamente el mismo orden.
        return $this->claves;
    }

    /**
     * FIX: En lugar de iterar las propiedades del objeto en orden arbitrario,
     * extraemos los valores según $this->claves, que refleja el orden de
     * $this->columnas. Esto garantiza que cada celda del Excel corresponda
     * a la columna del encabezado correcto.
     */
    public function map($row): array
    {
        $rowArray    = (array) $row;
        $filaMapeada = [];

        foreach ($this->claves as $clave) {
            // Buscar la clave con y sin backticks por si el driver los dejó
            $valor = $rowArray[$clave]
                ?? $rowArray[str_replace('`', '', $clave)]
                ?? null;

            $filaMapeada[] = ($valor !== null && trim((string) $valor) !== '')
                ? $valor
                : 'N/A';
        }

        return $filaMapeada;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = count($this->claves);

                for ($col = 1; $col <= $colCount; $col++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))
                          ->setAutoSize(true);
                }

                $lastCol = Coordinate::stringFromColumnIndex($colCount);
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF191970'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}