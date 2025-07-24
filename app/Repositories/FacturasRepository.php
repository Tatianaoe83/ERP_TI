<?php

namespace App\Repositories;

use App\Models\Facturas;
use App\Repositories\BaseRepository;

/**
 * Class FacturasRepository
 * @package App\Repositories
 * @version July 23, 2025, 5:19 pm UTC
*/

class FacturasRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'Imagen',
        'Descripcion',
        'Importe',
        'InsumoID'
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
        return Facturas::class;
    }
}
