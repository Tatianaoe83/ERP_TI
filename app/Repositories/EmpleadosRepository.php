<?php

namespace App\Repositories;

use App\Models\Empleados;
use App\Repositories\BaseRepository;

/**
 * Class EmpleadosRepository
 * @package App\Repositories
 * @version January 24, 2025, 9:39 pm UTC
*/

class EmpleadosRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'NombreEmpleado',
        'PuestoID',
        'ObraID',
        'NumTelefono',
        'Correo',
        'Estado'
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
        return Empleados::class;
    }
}
