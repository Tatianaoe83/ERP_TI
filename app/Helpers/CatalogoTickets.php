<?php

namespace App\Helpers;

use App\Models\Subtipos;
use App\Models\Tertipos;
use App\Models\Tipoticket;

/**
 * Catálogo de la cascada Categoría > Grupo > Subgrupo del modal de ticket.
 *
 * Son pocos registros (~95 en total), así que se manda entero al render y el modal
 * filtra en el cliente. Antes cada nivel se pedía por AJAX y esos fetch competían
 * con el x-model de Alpine: si las <option> llegaban tarde, el select se reseteaba
 * y dejaba la categoría vacía hasta recargar la página.
 *
 * Lo consumen el controlador de /tickets y el View Composer del modal global
 * (AppServiceProvider), para que ambos modales usen exactamente la misma fuente.
 */
class CatalogoTickets
{
    /**
     * @return array{tipos: \Illuminate\Support\Collection, subtipos: \Illuminate\Support\Collection, tertipos: \Illuminate\Support\Collection}
     */
    public static function todo(): array
    {
        return [
            'tipos'    => Tipoticket::select('TipoID', 'NombreTipo')
                ->orderBy('NombreTipo')->get(),

            'subtipos' => Subtipos::select('SubtipoID', 'NombreSubtipo', 'TipoID')
                ->orderBy('NombreSubtipo')->get(),

            'tertipos' => Tertipos::select('TertipoID', 'NombreTertipo', 'SubtipoID')
                ->orderBy('NombreTertipo')->get(),
        ];
    }
}
