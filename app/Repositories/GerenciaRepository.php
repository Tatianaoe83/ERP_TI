<?php

namespace App\Repositories;

use App\Models\Gerencia;
use App\Repositories\BaseRepository;

/**
 * Class GerenciaRepository
 * @package App\Repositories
 * @version January 22, 2025, 6:45 pm UTC
*/

class GerenciaRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombreGerencia',
        'UnidadNegocioID',
        'NombreGerente'
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
        return Gerencia::class;
    }
}
