<?php

namespace App\DataTables\Concerns;

use Yajra\DataTables\Html\Builder;

trait HasIndexPageHtml
{
    protected function indexPageHtml(string $tableId, array $options = []): Builder
    {
        return $this->builder()
            ->setTableId($tableId)
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom($options['dom'] ?? "<'index-page__dt-toolbar'Bf>t<'index-page__dt-footer'ip>")
            ->orderBy($options['orderColumn'] ?? 1, $options['orderDir'] ?? 'asc')
            ->buttons($this->indexPageButtons())
            ->parameters($this->indexPageParameters($options['parameters'] ?? []));
    }

    protected function indexPageButtons(): array
    {
        return [
            [
                'extend' => 'colvis',
                'className' => 'index-page__colvis',
                'text' => '<i class="fas fa-columns"></i> Columnas',
            ],
        ];
    }

    protected function indexPageParameters(array $override = []): array
    {
        $defaults = [
            'processing' => true,
            'serverSide' => true,
            'responsive' => true,
            // Si el script de init corre dos veces (pushes duplicados, navegación
            // sin recarga, etc.) DataTables lanza "Cannot reinitialise". Con
            // retrieve devuelve la instancia existente en vez de tronar.
            'retrieve' => true,
            'pageLength' => 10,
            'searching' => true,
            'language' => [
                'sProcessing' => 'Procesando...',
                'sLengthMenu' => 'Mostrar _MENU_',
                'sZeroRecords' => 'No se encontraron resultados',
                'sEmptyTable' => 'Ningún dato disponible en esta tabla',
                'sInfo' => 'Mostrando _START_ a _END_ de _TOTAL_',
                'sInfoEmpty' => 'Mostrando 0 a 0 de 0',
                'sInfoFiltered' => '(filtrado de _MAX_ registros)',
                'sSearch' => '',
                'searchPlaceholder' => 'Buscar...',
                'oPaginate' => [
                    'sFirst' => 'Primero',
                    'sLast' => 'Último',
                    'sNext' => 'Siguiente',
                    'sPrevious' => 'Anterior',
                ],
                'buttons' => [
                    'colvis' => 'Columnas',
                    'colvisRestore' => 'Restaurar',
                ],
            ],
            'drawCallback' => 'function() {
                var api = this.api();
                if (window.IndexPage) {
                    window.IndexPage.updateCount(api);
                }
                if (typeof $ !== "undefined" && $("[data-toggle=tooltip]").tooltip) {
                    $("[data-toggle=tooltip]").tooltip();
                }
            }',
            'initComplete' => 'function() {
                if (window.IndexPage) {
                    window.IndexPage.init(this.api());
                }
            }',
        ];

        return array_replace_recursive($defaults, $override);
    }
}
