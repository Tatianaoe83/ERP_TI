<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Un renglón por cada par equipo-licencia del personal de planta, con el
 * filtro nativo de Excel (el desplegable del encabezado de columna).
 *
 * inventarioequipo e inventarioinsumo sólo se relacionan con el empleado,
 * nunca entre sí: el sistema no sabe "esta licencia vive en ese equipo". Para
 * que Modalidad (Stock/Compartido/Propio) también se pueda filtrar junto con
 * las licencias, cada equipo del empleado se cruza con cada una de sus
 * licencias —si tiene 2 equipos y 3 licencias, salen 6 renglones—; es una
 * repetición a propósito, así está el sistema.
 *
 * Los conteos fijos viven aparte, en "Conteo por gerencia": esta hoja es
 * sólo para buscar y filtrar el detalle, sus filtros no alimentan nada más.
 */
class AuditoriaDetalleSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    private const FILA_TITULO    = 1;
    private const FILA_SUBTITULO = 2;
    private const FILA_CABECERA  = 4;

    private const COLUMNAS = 13;

    private const TINTA        = '0F172A';
    private const TENUE        = '64748B';
    private const BORDE        = 'E2E8F0';
    private const CEBRA        = 'F8FAFC';
    private const PAPEL        = 'FFFFFF';
    private const ACENTO       = '4F46E5';
    private const ACENTO_HONDO = '312E81';
    private const ACENTO_TENUE = 'EEF2FF';

    private const CABECERA = [
        'Gerencia', 'Obra', 'Departamento', 'Empleado',
        'Categoría de equipo', 'Marca', 'Modelo', 'No. de serie', 'Folio', 'Modalidad',
        'Licencia', 'Tiene licencia', 'Original',
    ];

    private int $filaUltima;

    /**
     * @param array $filas Un renglón por cada par equipo-licencia del
     *                      empleado (o uno suelto si le falta el otro lado):
     *                      [gerencia, obra, departamento, empleado,
     *                      categoríaEquipo, marca, modelo, numSerie, folio,
     *                      modalidad, licencia, tieneLicencia, original].
     *                      Vacío lo que no aplica.
     */
    public function __construct(
        private string $titulo,
        private string $subtitulo,
        private array $filas,
    ) {
        $this->filaUltima = self::FILA_CABECERA + max(count($filas), 1);
    }

    public function title(): string
    {
        return 'Detalle equipos y licencias';
    }

    public function array(): array
    {
        $hoja = array_fill(0, $this->filaUltima, array_fill(0, self::COLUMNAS, null));

        $hoja[self::FILA_TITULO - 1][0]    = $this->titulo;
        $hoja[self::FILA_SUBTITULO - 1][0] = $this->subtitulo;

        foreach (self::CABECERA as $i => $etiqueta) {
            $hoja[self::FILA_CABECERA - 1][$i] = $etiqueta;
        }

        $primeraFilaDatos = self::FILA_CABECERA + 1;

        if (empty($this->filas)) {
            $hoja[$primeraFilaDatos - 1][0] = 'Todavía no hay equipos ni licencias registradas';
        }

        foreach ($this->filas as $i => $fila) {
            $filaHoja = $primeraFilaDatos + $i;
            foreach ($fila as $col => $valor) {
                $hoja[$filaHoja - 1][$col] = $valor;
            }
        }

        return $hoja;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();
                $ultimaCol = Coordinate::stringFromColumnIndex(self::COLUMNAS);

                $hoja->mergeCells('A' . self::FILA_TITULO . ":{$ultimaCol}" . self::FILA_TITULO);
                $hoja->mergeCells('A' . self::FILA_SUBTITULO . ":{$ultimaCol}" . self::FILA_SUBTITULO);
                $hoja->getStyle('A' . self::FILA_TITULO . ":{$ultimaCol}" . self::FILA_SUBTITULO)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
                ]);
                $hoja->getStyle('A' . self::FILA_TITULO)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => self::ACENTO_HONDO]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $hoja->getStyle('A' . self::FILA_SUBTITULO)->applyFromArray([
                    'font'      => ['size' => 10, 'italic' => true, 'color' => ['rgb' => self::TENUE]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $hoja->getRowDimension(self::FILA_TITULO)->setRowHeight(32);
                $hoja->getRowDimension(self::FILA_SUBTITULO)->setRowHeight(20);
                $hoja->getRowDimension(3)->setRowHeight(8);

                $hoja->getStyle('A' . self::FILA_CABECERA . ":{$ultimaCol}" . self::FILA_CABECERA)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::PAPEL]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::PAPEL]]],
                ]);
                $hoja->getRowDimension(self::FILA_CABECERA)->setRowHeight(28);

                $primeraDatos = self::FILA_CABECERA + 1;

                $hoja->getStyle("A{$primeraDatos}:{$ultimaCol}{$this->filaUltima}")->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['rgb' => self::TINTA]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDE]]],
                ]);

                // Modalidad, Tiene licencia y Original son de lectura rápida: centrados.
                foreach (['J', 'L', 'M'] as $col) {
                    $hoja->getStyle("{$col}{$primeraDatos}:{$col}{$this->filaUltima}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                for ($fila = $primeraDatos; $fila <= $this->filaUltima; $fila++) {
                    if (($fila - $primeraDatos) % 2 === 1) {
                        $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CEBRA]],
                        ]);
                    }
                }

                $hoja->setAutoFilter("A" . self::FILA_CABECERA . ":{$ultimaCol}{$this->filaUltima}");
                $hoja->freezePane('A' . $primeraDatos);

                $anchos = [24, 20, 20, 26, 20, 16, 20, 16, 12, 16, 22, 14, 12];
                foreach ($anchos as $i => $ancho) {
                    $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($ancho);
                }

                $hoja->setSelectedCell('A' . $primeraDatos);
            },
        ];
    }
}
