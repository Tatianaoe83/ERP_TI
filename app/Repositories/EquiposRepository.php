<?php

namespace App\Repositories;

use App\Models\Equipos;
use App\Repositories\BaseRepository;

/**
 * Class EquiposRepository
 * @package App\Repositories
 * @version January 27, 2025, 5:35 pm UTC
*/

class EquiposRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'CategoriaID',
        'Marca',
        'Caracteristicas',
        'Modelo',
        'Precio'
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
        return Equipos::class;
    }
}
