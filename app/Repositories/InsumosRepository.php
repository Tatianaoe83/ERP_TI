<?php

namespace App\Repositories;

use App\Models\Insumos;
use App\Repositories\BaseRepository;

/**
 * Class InsumosRepository
 * @package App\Repositories
 * @version January 27, 2025, 6:17 pm UTC
*/

class InsumosRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombreInsumo',
        'CategoriaID',
        'CostoMensual',
        'CostoAnual',
        'FrecuenciaDePago',
        'Observaciones'
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
        return Insumos::class;
    }
}
