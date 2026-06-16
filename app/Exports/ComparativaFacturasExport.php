<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ComparativaFacturasExport implements WithMultipleSheets
{
    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function sheets(): array
    {
        $sheets = [
            new ComparativaFacturasSheetExport(
                'Concentrado',
                $this->buildConcentradoRows(),
                ['Gerencia', 'Ahorro', 'No se consumió el insumo', 'Desviación', 'Presupuesto', 'Facturado', 'Total'],
                ['B', 'C', 'D', 'E', 'F', 'G'],
                [
                    'title' => 'Relación de inversiones PROSER TI',
                    'subtitle' => $this->buildSubtitle(),
                    'headerColors' => [
                        'B' => 'DCFCE7',
                        'C' => 'DCFCE7',
                        'D' => 'FEE2E2',
                        'E' => 'E0E7FF',
                        'F' => 'E0F2FE',
                        'G' => 'F8FAFC',
                    ],
                ]
            ),
        ];

        foreach (($this->payload['gerencias_detalle'] ?? []) as $gerencia) {
            $sheets[] = new ComparativaFacturasSheetExport(
                $this->sanitizeTitle((string)($gerencia['gerencia'] ?? 'Gerencia')),
                $this->buildGerenciaRows($gerencia),
                ['Mes', 'Insumo', 'Presupuesto autorizado', 'Usado / facturado', 'Ahorro', 'No consumido', 'Desviación', 'Saldo', 'Concepto'],
                ['C', 'D', 'E', 'F', 'G', 'H'],
                [
                    'title' => (string)($gerencia['gerencia'] ?? 'Gerencia'),
                    'subtitle' => 'Calendario mensual de presupuesto autorizado contra facturado · ' . $this->buildSubtitle(),
                    'headerColors' => [
                        'C' => 'E0E7FF',
                        'D' => 'E0F2FE',
                        'E' => 'DCFCE7',
                        'F' => 'DCFCE7',
                        'G' => 'FEE2E2',
                        'H' => 'F8FAFC',
                    ],
                ]
            );
        }

        return $sheets;
    }

    private function buildConcentradoRows(): array
    {
        $rows = [];
        foreach (($this->payload['concentrado_gerencias'] ?? []) as $row) {
            $rows[] = [
                $row['gerencia'] ?? '',
                (float)($row['ahorro'] ?? 0),
                (float)($row['no_consumido'] ?? 0),
                (float)($row['desviacion'] ?? 0),
                (float)($row['presupuesto'] ?? 0),
                (float)($row['facturado'] ?? 0),
                (float)($row['total'] ?? 0),
            ];
        }

        if (!empty($rows)) {
            $rows[] = [
                'TOTAL',
                array_sum(array_column($rows, 1)),
                array_sum(array_column($rows, 2)),
                array_sum(array_column($rows, 3)),
                array_sum(array_column($rows, 4)),
                array_sum(array_column($rows, 5)),
                array_sum(array_column($rows, 6)),
            ];
        }

        return $rows;
    }

    private function buildGerenciaRows(array $gerencia): array
    {
        $rows = [];
        foreach (($gerencia['meses'] ?? []) as $mes) {
            foreach (($mes['items'] ?? []) as $item) {
                $rows[] = [
                    $mes['mes_nombre'] ?? '',
                    $item['insumo'] ?? '',
                    (float)($item['presupuesto'] ?? 0),
                    (float)($item['facturado'] ?? 0),
                    (float)($item['ahorro'] ?? 0),
                    (float)($item['no_consumido'] ?? 0),
                    (float)($item['desviacion'] ?? 0),
                    (float)($item['saldo'] ?? 0),
                    $item['concepto'] ?? '',
                ];
            }

            $rows[] = [
                'TOTAL ' . ($mes['mes_nombre'] ?? ''),
                '',
                (float)($mes['presupuesto'] ?? 0),
                (float)($mes['facturado'] ?? 0),
                (float)($mes['ahorro'] ?? 0),
                (float)($mes['no_consumido'] ?? 0),
                (float)($mes['desviacion'] ?? 0),
                (float)($mes['total'] ?? 0),
                '',
            ];
        }

        if (!empty($rows)) {
            $dataRows = array_filter($rows, fn($row) => stripos((string)($row[0] ?? ''), 'TOTAL ') !== 0);
            $rows[] = ['', '', '', '', '', '', '', '', ''];
            $rows[] = [
                'TOTAL GERENCIA',
                '',
                array_sum(array_column($dataRows, 2)),
                array_sum(array_column($dataRows, 3)),
                array_sum(array_column($dataRows, 4)),
                array_sum(array_column($dataRows, 5)),
                array_sum(array_column($dataRows, 6)),
                array_sum(array_column($dataRows, 7)),
                '',
            ];
        }

        return $rows;
    }

    private function buildSubtitle(): string
    {
        $filters = $this->payload['meta']['filtros'] ?? [];
        $parts = [];

        if (!empty($filters['gerencia_id'])) {
            $parts[] = 'Gerencia filtrada';
        }
        if (!empty($filters['mes'])) {
            $parts[] = 'Mes ' . $filters['mes'];
        }
        if (!empty($filters['anio'])) {
            $parts[] = 'Año ' . $filters['anio'];
        }
        if (!empty($filters['insumo'])) {
            $parts[] = 'Insumo: ' . $filters['insumo'];
        }

        return $parts ? implode(' · ', $parts) : 'Sin filtros aplicados';
    }

    private function sanitizeTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]\\:]/', ' ', $title) ?: 'Gerencia';
        return mb_substr(trim($title), 0, 31) ?: 'Gerencia';
    }
}
