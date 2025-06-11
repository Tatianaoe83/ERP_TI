<?php

namespace App\Repositories;

use App\Models\Reportes;
use App\Repositories\BaseRepository;

/**
 * Class ReportesRepository
 * @package App\Repositories
 * @version June 11, 2025, 5:05 pm UTC
*/

class ReportesRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'title',
        'query_details',
        'password'
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
        return Reportes::class;
    }
}
