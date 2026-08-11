<?php

namespace App\Http\Livewire\Concerns;

/**
 * Kanban, lista y tabla conviven en la misma página, así que montarlos los tres consultaba
 * el tablero completo tres veces por carga. Con esto arrancan apagados y solo consulta el
 * que el usuario tiene abierto; los demás se encienden cuando se cambia de vista.
 */
trait VistaLazy
{
    public bool $activo = false;

    /** Nombre de la vista que atiende el componente: 'kanban', 'lista' o 'tabla'. */
    abstract protected function nombreVista(): string;

    public function activarVista($vista): void
    {
        if ($vista === $this->nombreVista()) {
            $this->activo = true;
        }
    }
}
