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
 * La auditoría vista como lo que es: un empleado, sus equipos y sus licencias.
 *
 * No es una tabla plana. Cada empleado abre una banda que se puede plegar y
 * desplegar con el esquema de Excel (los +/- del margen izquierdo), y debajo
 * cuelgan sus dos sub-tablas, ya desplegadas: el conteo en la banda es sólo un
 * resumen rápido, el detalle real —cada equipo y cada licencia en su propio
 * renglón— va abajo y a la vista, no escondido.
 *
 * Cada equipo va con su ficha completa —incluida la modalidad de tipoEquipo— y
 * cada licencia sólo con lo que la auditoría verifica: si la tiene y si es
 * original.
 */
class AuditoriaAgrupadaSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    private const FILA_TITULO    = 1;
    private const FILA_SUBTITULO = 2;
    private const FILA_CABECERA  = 4;
    private const FILA_BLOQUES   = 5;

    /** Ancho de la hoja: lo marca la sub-tabla de equipos, que es la más ancha. */
    private const COLUMNAS = 8;

    private const TINTA        = '0F172A';
    private const TENUE        = '64748B';
    private const BORDE        = 'E2E8F0';
    private const PAPEL        = 'FFFFFF';
    private const ACENTO       = '4F46E5';
    private const ACENTO_HONDO = '312E81';
    private const ACENTO_TENUE = 'EEF2FF';

    /** Franja alterna detrás de cada bloque de empleado: separa sin agrandar nada. */
    private const FRANJA = 'F8FAFC';

    private const CABECERA = [
        'Empleado', 'Gerencia', 'Obra', 'Departamento', 'Folio', 'Fecha', 'Equipos', 'Licencias',
    ];

    private const CABECERA_EQUIPOS = [
        'Categoría', 'Marca', 'Modelo', 'No. de serie', 'Folio', 'Modalidad', 'Fecha de asignación', 'Observaciones',
    ];

    private const CABECERA_LICENCIAS = [
        'Licencia', 'Cuenta con licencia', 'Licencia original', 'Observaciones',
    ];

    /** Renglones que abren un empleado; el esquema cuelga de ellos. */
    private array $bandas = [];

    /** Primer y último renglón de cada bloque de empleado, para la franja de fondo. */
    private array $bloques = [];

    /** Renglones de detalle que se pliegan bajo su banda. */
    private array $detalle = [];

    /** Renglones que son rótulo de sección o cabecera de sub-tabla. */
    private array $rotulos = [];
    private array $subcabeceras = [];

    /** Renglones de dato (no rótulo) dentro de una sub-tabla, alternados 1 de 2. */
    private array $zebra = [];

    /**
     * @param string $subtitulo Qué corte se exportó.
     * @param array  $empleados Cada uno:
     *                          ['empleado','gerencia','obra','departamento','folio','fecha',
     *                           'equipos' => [[…8 columnas…]],
     *                           'licencias' => [[…4 columnas…]]]
     */
    public function __construct(
        private string $subtitulo,
        private array $empleados,
    ) {}

    public function title(): string
    {
        return 'Auditorías';
    }

    public function array(): array
    {
        // El mapa de renglones se rearma cada vez: si la hoja se pidiera dos veces,
        // los índices del esquema quedarían duplicados y el plegado se rompería.
        $this->bandas = $this->detalle = $this->rotulos = $this->subcabeceras = $this->bloques = $this->zebra = [];

        $vacia = array_fill(0, self::COLUMNAS, null);

        $hoja = [
            $this->renglon(['Auditorías por empleado']),
            $this->renglon([$this->subtitulo]),
            $vacia,
            $this->renglon(self::CABECERA),
        ];

        if (empty($this->empleados)) {
            $hoja[] = $this->renglon(['Todavía no hay auditorías generadas']);

            return $hoja;
        }

        $fila = self::FILA_BLOQUES;

        foreach ($this->empleados as $empleado) {
            $inicioBloque = $fila;
            $this->bandas[] = $fila++;
            $hoja[] = $this->renglon([
                $empleado['empleado'],
                $empleado['gerencia'],
                $empleado['obra'],
                $empleado['departamento'],
                $empleado['folio'],
                $empleado['fecha'],
                count($empleado['equipos']),
                count($empleado['licencias']),
            ]);

            $fila = $this->bloque($hoja, $fila, 'EQUIPOS', self::CABECERA_EQUIPOS, $empleado['equipos'],
                'Sin equipos resguardados en esta corrida');

            $fila = $this->bloque($hoja, $fila, 'LICENCIAS', self::CABECERA_LICENCIAS, $empleado['licencias'],
                'Sin licencias revisadas en esta corrida');

            $this->bloques[] = [$inicioBloque, $fila - 1];

            // Un renglón de aire entre bloques; también se pliega con el empleado.
            $this->detalle[] = $fila++;
            $hoja[] = $vacia;
        }

        return $hoja;
    }

    /**
     * Agrega una sección plegable (rótulo, cabecera y renglones) y devuelve la
     * siguiente fila libre.
     */
    private function bloque(array &$hoja, int $fila, string $rotulo, array $cabecera, array $filas, string $vacio): int
    {
        $this->rotulos[] = $fila;
        $this->detalle[] = $fila++;
        $hoja[] = $this->renglon([$rotulo]);

        $this->subcabeceras[] = $fila;
        $this->detalle[] = $fila++;
        $hoja[] = $this->renglon($cabecera);

        foreach ($filas ?: [[$vacio]] as $indice => $renglon) {
            if ($indice % 2 === 1) {
                $this->zebra[] = $fila;
            }

            $this->detalle[] = $fila++;
            $hoja[] = $this->renglon($renglon);
        }

        return $fila;
    }

    private function renglon(array $valores): array
    {
        return array_slice(array_pad($valores, self::COLUMNAS, null), 0, self::COLUMNAS);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();
                $ultimaCol = Coordinate::stringFromColumnIndex(self::COLUMNAS);

                $hoja->mergeCells('A' . self::FILA_TITULO . ':' . $ultimaCol . self::FILA_TITULO);
                $hoja->mergeCells('A' . self::FILA_SUBTITULO . ':' . $ultimaCol . self::FILA_SUBTITULO);
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

                $hoja->getStyle('A' . self::FILA_CABECERA . ":{$ultimaCol}" . self::FILA_CABECERA)
                    ->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::PAPEL]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO]],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                            'indent'   => 1,
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::PAPEL]],
                        ],
                    ]);
                $hoja->getRowDimension(self::FILA_CABECERA)->setRowHeight(28);

                foreach ([38, 30, 26, 24, 18, 17, 10, 11] as $indice => $ancho) {
                    $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($indice + 1))->setWidth($ancho);
                }

                $hoja->freezePane('A' . self::FILA_BLOQUES);

                // El resumen del grupo va ARRIBA de su detalle: si no, Excel pone el
                // +/- en el renglón de abajo y el control queda lejos del empleado.
                $hoja->setShowSummaryBelow(false);

                // Cada empleado en su propia tarjeta: un marco alrededor de todo el
                // bloque para que se note dónde termina uno y empieza el siguiente,
                // aunque varios estén desplegados a la vez.
                foreach ($this->bloques as [$inicio, $fin]) {
                    $hoja->getStyle("A{$inicio}:{$ultimaCol}{$fin}")->applyFromArray([
                        'borders' => [
                            'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO_HONDO]],
                        ],
                    ]);
                }

                // Tarjeta par/impar: cada empleado alterna un fondo casi blanco,
                // así se distingue dónde acaba uno y empieza el siguiente aunque
                // varios estén desplegados a la vez y sin depender sólo del marco.
                foreach ($this->bloques as $indice => [$inicio, $fin]) {
                    if ($indice % 2 === 1) {
                        $hoja->getStyle("A{$inicio}:{$ultimaCol}{$fin}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::FRANJA]],
                        ]);
                    }
                }

                // Zebra dentro de cada sub-tabla: ayuda al ojo a seguir el renglón
                // en equipos/licencias. Va después de la tarjeta par/impar para
                // que gane sobre su fondo, y con un tinte propio para no
                // confundirse con él.
                foreach ($this->zebra as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
                    ]);
                }

                // Banda del empleado en dos zonas: quién es (nombre, gerencia, obra,
                // departamento) en el tono hondo, y su resultado (folio, fecha,
                // equipos, licencias) en el acento claro, para separar identidad de
                // cifra de un vistazo.
                foreach ($this->bandas as $fila) {
                    $hoja->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::PAPEL]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_HONDO]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                    ]);
                    $hoja->getStyle("E{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::PAPEL]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    // Equipos y licencias son la cifra que se busca primero: más
                    // grandes, como una tarjeta de indicador dentro de la banda.
                    $hoja->getStyle("G{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => self::PAPEL]],
                    ]);
                    // El borde izquierdo grueso es la pestaña de color de la tarjeta:
                    // se ve aun con la fila encogida por el zoom o la impresión.
                    $hoja->getStyle("A{$fila}")->applyFromArray([
                        'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO]]],
                    ]);
                    $hoja->getRowDimension($fila)->setRowHeight(28);
                }

                foreach ($this->detalle as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                        'font'      => ['size' => 10, 'color' => ['rgb' => self::TINTA]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 2],
                    ]);
                    // Mismo borde izquierdo que la banda: el acento corre sin cortes
                    // por toda la tarjeta del empleado, de arriba a abajo.
                    $hoja->getStyle("A{$fila}")->applyFromArray([
                        'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO]]],
                    ]);
                    // Nivel 1 pero visible: el detalle de cada equipo y licencia se ve
                    // de entrada, fila por fila; quien quiera lo pliega con el "-".
                    $hoja->getRowDimension($fila)->setOutlineLevel(1);
                }

                foreach ($this->rotulos as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => self::ACENTO_HONDO]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 2],
                    ]);
                    $hoja->getStyle("A{$fila}")->applyFromArray([
                        'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO]]],
                    ]);
                    $hoja->getRowDimension($fila)->setRowHeight(20);
                }

                foreach ($this->subcabeceras as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => self::TENUE]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BORDE]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 2],
                        'borders'   => [
                            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::TENUE]],
                        ],
                    ]);
                    $hoja->getStyle("A{$fila}")->applyFromArray([
                        'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO]]],
                    ]);
                    $hoja->getRowDimension($fila)->setRowHeight(17);
                }

                // Separación clara entre un empleado y el siguiente: el renglón de
                // aire respira un poco más y lleva un trazo grueso justo debajo,
                // así el corte se ve aunque varios bloques estén abiertos a la vez.
                foreach ($this->bandas as $fila) {
                    if ($fila <= self::FILA_BLOQUES) {
                        continue;
                    }

                    $filaAire = $fila - 1;
                    $hoja->getRowDimension($filaAire)->setRowHeight(14);
                    $hoja->getStyle("A{$filaAire}:{$ultimaCol}{$filaAire}")->applyFromArray([
                        'borders' => [
                            'bottom' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color'       => ['rgb' => self::ACENTO],
                            ],
                        ],
                    ]);
                }

                $hoja->setSelectedCell('A' . self::FILA_BLOQUES);
            },
        ];
    }
}
