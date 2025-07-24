<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Facturas
 * @package App\Models
 * @version July 23, 2025, 5:19 pm UTC
 *
 * @property \App\Models\Insumo $insumoid
 * @property string $Imagen
 * @property string $Descripcion
 * @property number $Importe
 * @property integer $InsumoID
 */
class Facturas extends Model
{
    use SoftDeletes;


    public $table = 'facturas';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'Imagen',
        'Descripcion',
        'Importe',
        'InsumoID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'FacturasID' => 'integer',
        'Imagen' => 'string',
        'Descripcion' => 'string',
        'Importe' => 'decimal:2',
        'InsumoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'Imagen' => 'required|string|max:100',
        'Descripcion' => 'nullable|string',
        'Importe' => 'nullable|numeric',
        'InsumoID' => 'nullable|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function insumoid()
    {
        return $this->belongsTo(\App\Models\Insumo::class, 'InsumoID');
    }
}
