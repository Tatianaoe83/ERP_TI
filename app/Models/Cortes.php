<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Cortes
 * @package App\Models
 * @version July 23, 2025, 5:19 pm UTC
 *
 * @property \App\Models\Gerencium $gerenciaid
 * @property \App\Models\Insumo $insumoid
 * @property string $Mes
 * @property integer $GerenciaID
 * @property integer $InsumoID
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
        'Costo',
        'GerenciaID'
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
        'Costo' => 'float',
        'GerenciaID' => 'integer',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreInsumo' => 'required|string|100',
        'Mes' => 'required|string|max:100',
        'Costo' => 'required|float',
        'GerenciaID' => 'nullable|integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function gerenciaid()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function insumoid()
    {
        return $this->belongsTo(\App\Models\Insumo::class, 'InsumoID');
    }
}
