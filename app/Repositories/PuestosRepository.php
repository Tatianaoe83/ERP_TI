<?php

namespace App\Repositories;

use App\Models\Puestos;
use App\Repositories\BaseRepository;

/**
 * Class PuestosRepository
 * @package App\Repositories
 * @version January 24, 2025, 7:37 pm UTC
*/

class PuestosRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombrePuesto',
        'DepartamentoID'
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
        return Puestos::class;
    }
}
