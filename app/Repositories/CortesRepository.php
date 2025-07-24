<?php

namespace App\Repositories;

use App\Models\Cortes;
use App\Repositories\BaseRepository;

/**
 * Class CortesRepository
 * @package App\Repositories
 * @version July 23, 2025, 5:19 pm UTC
*/

class CortesRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'Mes',
        'GerenciaID',
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
        return Cortes::class;
    }
}
