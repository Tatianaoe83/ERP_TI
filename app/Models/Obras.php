<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Obras
 * @package App\Models
 * @version January 23, 2025, 10:22 pm UTC
 *
 * @property \App\Models\UnidadesDeNegocio $unidadnegocioid
 * @property string $NombreObra
 * @property string $Direccion
 * @property string $EncargadoDeObra
 * @property integer $UnidadNegocioID
 */
class Obras extends Model
{
    use SoftDeletes;


    public $table = 'obras';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'ObraID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreObra',
        'Direccion',
        'EncargadoDeObra',
        'UnidadNegocioID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'ObraID' => 'integer',
        'NombreObra' => 'string',
        'Direccion' => 'string',
        'EncargadoDeObra' => 'string',
        'UnidadNegocioID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreObra' => 'required|string|max:100',
        'Direccion' => 'required|string|max:150',
        'EncargadoDeObra' => 'required|string|max:150',
        'UnidadNegocioID' => 'required|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function unidadnegocioid()
    {
        return $this->belongsTo(\App\Models\UnidadesDeNegocio::class, 'UnidadNegocioID');
    }
}
