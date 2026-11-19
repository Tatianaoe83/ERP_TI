<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Cortes
 * @package App\Models
 * @version February 5, 2026, 12:32 pm CST
 *
 * @property string $NombreInsumo
 * @property string $Mes
 * @property string $Año
 * @property number $Costo
 * @property number $CostoTotal
 * @property number $Margen
 * @property integer $GerenciaID
 */
class Cortes extends Model
{
    use SoftDeletes;


    public $table = 'cortes';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'NombreInsumo',
        'Mes',
        'Año',
        'Costo',
        'CostoTotal',
        'Margen',
        'GerenciaID',
        'PresupuestoInternoID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'CortesID' => 'integer',
        'NombreInsumo' => 'string',
        'Mes' => 'string',
        'Año' => 'date',
        'Costo' => 'decimal:2',
        'CostoTotal' => 'decimal:2',
        'Margen' => 'decimal:2',
        'GerenciaID' => 'integer',
        'PresupuestoInternoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreInsumo' => 'nullable|string|max:100',
        'Mes' => 'nullable|string|max:100',
        'Año' => 'nullable',
        'Costo' => 'nullable|numeric',
        'CostoTotal' => 'nullable|numeric',
        'Margen' => 'nullable|numeric',
        'GerenciaID' => 'nullable|integer',
        'created_at' => 'required',
        'updated_at' => 'required',
        'deleted_at' => 'nullable'
    ];
}
