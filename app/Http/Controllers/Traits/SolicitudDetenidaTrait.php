<?php

namespace App\Http\Controllers\Traits;

use App\Models\Solicitud;
use Illuminate\View\View;

/**
 * Pantallas para una solicitud que ya no admite firmas.
 *
 * Cancelada/Cerrada: la detuvo TI desde el index.
 * Rechazada: la detuvo un aprobador desde su vista (supervisor, elección de
 * ganadores o administración), así que el motivo vive en el paso rechazado.
 */
trait SolicitudDetenidaTrait
{
    /** Estatus en los que la solicitud ya no admite firmas. */
    private static array $estatusDetenidos = ['Cancelada', 'Cerrada', 'Rechazada'];

    protected function estatusDetenidos(): array
    {
        return self::$estatusDetenidos;
    }

    protected function estaDetenida(?Solicitud $solicitud): bool
    {
        return $solicitud && in_array($solicitud->Estatus, self::$estatusDetenidos, true);
    }

    /** Vista de solicitud detenida, o null si la solicitud sigue viva. */
    protected function vistaSolicitudDetenida(?Solicitud $solicitud): ?View
    {
        if (!$this->estaDetenida($solicitud)) {
            return null;
        }

        if ($solicitud->Estatus !== 'Rechazada') {
            $fecha = $solicitud->fechaDetencion();

            return view('solicitudes.cancelada', [
                'motivo'           => $solicitud->motivoDetencion(),
                'canceladoPor'     => $solicitud->detenidoPorEtiqueta(),
                'fechaCancelacion' => $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y H:i') : null,
            ]);
        }

        $quien  = $solicitud->detenidoPorEtiqueta();
        $fecha  = $solicitud->fechaDetencion();
        $motivo = $solicitud->motivoDetencion();

        $razon = 'Esta solicitud fue rechazada';
        if ($quien) {
            $razon .= ' por ' . $quien;
        }
        if ($fecha) {
            $razon .= ' el ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y H:i');
        }
        $razon .= ' y ya no está activa';
        if ($motivo) {
            $razon .= '. Motivo: ' . $motivo;
        }

        return view('solicitudes.token-invalido', ['tokenInfo' => ['razon' => $razon]]);
    }
}
