<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Insumos
 * @package App\Models
 * @version January 27, 2025, 6:17 pm UTC
 *
 * @property \App\Models\Categoria $categoriaid
 * @property string $NombreInsumo
 * @property integer $CategoriaID
 * @property number $CostoMensual
 * @property number $CostoAnual
 * @property string $FrecuenciaDePago
 * @property string $Observaciones
 */
class Insumos extends Model
{
    use SoftDeletes;


    public $table = 'Insumos';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'ID';

    public $fillable = [
        'NombreInsumo',
        'CategoriaID',
        'CostoMensual',
        'CostoAnual',
        'FrecuenciaDePago',
        'Observaciones'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'ID' => 'integer',
        'NombreInsumo' => 'string',
        'CategoriaID' => 'integer',
        'CostoMensual' => 'decimal:2',
        'CostoAnual' => 'decimal:2',
        'FrecuenciaDePago' => 'string',
        'Observaciones' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreInsumo' => 'required|string|max:100',
        'CategoriaID' => 'required|integer',
        'CostoMensual' => 'required|numeric',
        'CostoAnual' => 'required|numeric',
        'FrecuenciaDePago' => 'required|string|max:50',
        'Observaciones' => 'nullable|string|max:255',
        'created_at' => 'required',
        'updated_at' => 'required',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function categoriaid()
    {
        return $this->belongsTo(\App\Models\Categoria::class, 'CategoriaID');
    }
}
