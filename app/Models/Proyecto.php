<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Proyecto
 * @package App\Models
 * @version August 15, 2025, 6:19 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $solicitudes
 * @property string $RazonSocial
 * @property string $Proyecto
 * @property string $NombreProyecto
 */
class Proyecto extends Model
{
    use SoftDeletes;


    public $table = 'proyectos';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'RazonSocial',
        'Proyecto',
        'NombreProyecto'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'ProyectoID' => 'integer',
        'RazonSocial' => 'string',
        'Proyecto' => 'string',
        'NombreProyecto' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'RazonSocial' => 'required|string|max:100',
        'Proyecto' => 'required|string|max:100',
        'NombreProyecto' => 'required|string|max:250'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function solicitudes()
    {
        return $this->hasMany(\App\Models\Solicitude::class, 'ProyectoID');
    }
}
