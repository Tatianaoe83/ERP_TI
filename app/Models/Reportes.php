<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Reportes
 * @package App\Models
 * @version June 11, 2025, 5:05 pm UTC
 *
 * @property string $title
 * @property string $query_details
 * @property string $password
 */
class Reportes extends Model
{
    use SoftDeletes;


    public $table = 'query_forms';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'title',
        'query_details',
        'password'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'title' => 'string',
        'query_details' => 'string',
        'password' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'title' => 'nullable|string',
        'query_details' => 'nullable|string',
        'password' => 'nullable|string',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];
}
