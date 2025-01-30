<?php

namespace App\Repositories;

use App\Models\Inventario;
use App\Repositories\BaseRepository;

/**
 * Class InventarioRepository
 * @package App\Repositories
 * @version January 29, 2025, 4:51 pm UTC
*/

class InventarioRepository extends BaseRepository
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
        return Inventario::class;
    }
}
