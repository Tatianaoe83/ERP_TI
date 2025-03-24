<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Categorias
 * @package App\Models
 * @version January 27, 2025, 6:41 pm UTC
 *
 * @property \App\Models\TiposDeCategoria $tipoid
 * @property \Illuminate\Database\Eloquent\Collection $equipos
 * @property \Illuminate\Database\Eloquent\Collection $insumos
 * @property integer $TipoID
 * @property string $Categoria
 */
class Categorias extends Model
{
    use HasFactory,SoftDeletes;


    public $table = 'categorias';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'ID';

    public $fillable = [
        'TipoID',
        'Categoria'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'ID' => 'integer',
        'TipoID' => 'integer',
        'Categoria' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'TipoID' => 'required|integer',
        'Categoria' => 'required|string|max:75'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function tipoid()
    {
        return $this->belongsTo(\App\Models\TiposDeCategorias::class, 'TipoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function equipos()
    {
        return $this->hasMany(\App\Models\Equipo::class, 'CategoriaID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function insumos()
    {
        return $this->hasMany(\App\Models\Insumo::class, 'CategoriaID');
    }
}
