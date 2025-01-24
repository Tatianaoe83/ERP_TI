<?php

namespace App\Repositories;

use App\Models\Departamentos;
use App\Repositories\BaseRepository;

/**
 * Class DepartamentosRepository
 * @package App\Repositories
 * @version January 24, 2025, 5:38 pm UTC
*/

class DepartamentosRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombreDepartamento',
        'GerenciaID'
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
        return Departamentos::class;
    }
}
