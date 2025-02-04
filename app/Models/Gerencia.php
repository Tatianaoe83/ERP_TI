<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Gerencia
 * @package App\Models
 * @version January 22, 2025, 6:45 pm UTC
 *
 * @property \App\Models\UnidadesDeNegocio $unidadnegocioid
 * @property string $NombreGerencia
 * @property integer $UnidadNegocioID
 * @property string $NombreGerente
 */
class Gerencia extends Model
{
    use SoftDeletes;


    public $table = 'gerencia';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'GerenciaID';
    protected $keyType = 'int'; 

    public $fillable = [
        'NombreGerencia',
        'UnidadNegocioID',
        'NombreGerente'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'GerenciaID' => 'integer',
        'NombreGerencia' => 'string',
        'UnidadNegocioID' => 'integer',
        'NombreGerente' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreGerencia' => 'required|string|max:100',
        'UnidadNegocioID' => 'nullable|integer',
        'NombreGerente' => 'nullable|string|max:100'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function unidadnegocioid()
    {
        return $this->belongsTo(\App\Models\UnidadesDeNegocio::class, 'UnidadNegocioID');
    }
}
