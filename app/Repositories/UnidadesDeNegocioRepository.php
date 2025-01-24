<?php

namespace App\Repositories;

use App\Models\UnidadesDeNegocio;
use App\Repositories\BaseRepository;

/**
 * Class UnidadesDeNegocioRepository
 * @package App\Repositories
 * @version January 21, 2025, 11:06 pm UTC
*/

class UnidadesDeNegocioRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombreEmpresa',
        'RFC',
        'Direccion',
        'NumTelefono'
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
        return UnidadesDeNegocio::class;
    }
}
