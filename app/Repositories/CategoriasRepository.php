<?php

namespace App\Repositories;

use App\Models\Categorias;
use App\Repositories\BaseRepository;

/**
 * Class CategoriasRepository
 * @package App\Repositories
 * @version January 27, 2025, 6:41 pm UTC
*/

class CategoriasRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'TipoID',
        'Categoria'
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
        return Categorias::class;
    }
}
