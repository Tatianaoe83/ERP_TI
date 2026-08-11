<?php

namespace App\Http\Livewire\Concerns;

use App\Models\TicketMantenimiento;

/**
 * Atendidos y cancelados no dejan de acumularse, así que esas columnas del tablero se
 * muestran de a páginas en vez de volcar el histórico completo en el DOM.
 */
trait ColumnasFinalizadasPaginadas
{
    /** Página actual de cada columna paginada. */
    public array $paginas = ['atendido' => 1, 'cancelado' => 1];

    /** Total real por columna, para saber si queda otra página. */
    public array $totales = [];

    public function irAPagina($columna, $pagina): void
    {
        if (!in_array($columna, TicketMantenimiento::COLUMNAS_PAGINADAS, true)) {
            return;
        }

        $this->paginas[$columna] = max(1, min((int) $pagina, $this->ultimaPagina($columna)));
    }

    public function ultimaPagina($columna): int
    {
        $total = (int) ($this->totales[$columna] ?? 0);

        return max(1, (int) ceil($total / TicketMantenimiento::POR_PAGINA_FINALIZADOS));
    }

    /**
     * Consulta las columnas y deja los totales listos para la vista. Si un borrado dejó la
     * página actual fuera de rango, retrocede y vuelve a consultar para no pintar vacío.
     */
    protected function obtenerColumnas(): array
    {
        $datos = TicketMantenimiento::obtenerColumnas($this->paginas);
        $this->totales = $datos['totales'];

        $corregido = false;
        foreach (TicketMantenimiento::COLUMNAS_PAGINADAS as $columna) {
            $ultima = $this->ultimaPagina($columna);
            if ($this->paginas[$columna] > $ultima) {
                $this->paginas[$columna] = $ultima;
                $corregido = true;
            }
        }

        if ($corregido) {
            $datos = TicketMantenimiento::obtenerColumnas($this->paginas);
            $this->totales = $datos['totales'];
        }

        return $datos['grupos'];
    }
}
