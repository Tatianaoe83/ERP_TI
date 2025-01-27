<?php

namespace App\Repositories;

use App\Models\Planes;
use App\Repositories\BaseRepository;

/**
 * Class PlanesRepository
 * @package App\Repositories
 * @version January 27, 2025, 6:42 pm UTC
*/

class PlanesRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'CompaniaID',
        'NombrePlan',
        'PrecioPlan'
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
        return Planes::class;
    }
}
