<?php

namespace App\Repositories;

use App\Models\LineasTelefonicas;
use App\Repositories\BaseRepository;

/**
 * Class LineasTelefonicasRepository
 * @package App\Repositories
 * @version January 27, 2025, 4:33 pm UTC
*/

class LineasTelefonicasRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NumTelefonico',
        'PlanID',
        'CuentaPadre',
        'CuentaHija',
        'TipoLinea',
        'ObraID',
        'FechaFianza',
        'CostoFianza',
        'Activo',
        'Disponible',
        'MontoRenovacionFianza'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return LineasTelefonicas::class;
    }
}
