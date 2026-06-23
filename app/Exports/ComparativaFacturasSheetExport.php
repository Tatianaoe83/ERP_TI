<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComparativaFacturasSheetExport implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    protected string $title;
    protected array $rows;
    protected array $headings;
    protected array $moneyColumns;
    protected array $options;

    public function __construct(string $title, array $rows, array $headings, array $moneyColumns = [], array $options = [])
    {
        $this->title = $title;
        $this->rows = $rows;
        $this->headings = $headings;
        $this->moneyColumns = $moneyColumns;
        $this->options = $options;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return array_merge(
            [
                [$this->options['title'] ?? $this->title],
                [$this->options['subtitle'] ?? ''],
                [''],
                $this->headings,
            ],
            $this->rows
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $headerRow = 4;
                $dataStartRow = 5;

                $sheet->freezePane("A{$dataStartRow}");
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);

                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->mergeCells("A2:{$highestColumn}2");

                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F4C63']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                $sheet->getStyle("A2:{$highestColumn}2")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F4C63']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                if ($highestRow >= $dataStartRow) {
                    $sheet->getStyle("A{$dataStartRow}:{$highestColumn}{$highestRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    $this->applyColumnHighlights($sheet, $highestRow, $dataStartRow);

                    for ($row = $dataStartRow; $row <= $highestRow; $row++) {
                        $label = (string)$sheet->getCell("A{$row}")->getValue();
                        if (stripos($label, 'TOTAL') === 0) {
                            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBF7D0']],
                            ]);
                        } elseif ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                            ]);
                        }
                    }

                    foreach ($this->moneyColumns as $column) {
                        $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$highestRow}")
                            ->getNumberFormat()
                            ->setFormatCode('$#,##0.00;-$#,##0.00;-');
                    }
                }
            },
        ];
    }

    private function applyColumnHighlights(Worksheet $sheet, int $highestRow, int $dataStartRow): void
    {
        foreach (($this->options['headerColors'] ?? []) as $column => $color) {
            $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$highestRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }
    }
}
