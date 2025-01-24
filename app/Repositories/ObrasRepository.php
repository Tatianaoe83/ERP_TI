<?php

namespace App\Repositories;

use App\Models\Obras;
use App\Repositories\BaseRepository;

/**
 * Class ObrasRepository
 * @package App\Repositories
 * @version January 23, 2025, 10:22 pm UTC
*/

class ObrasRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombreObra',
        'Direccion',
        'EncargadoDeObra',
        'UnidadNegocioID'
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
        return Obras::class;
    }
}
