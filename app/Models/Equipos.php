<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;


/**
 * Class Equipos
 * @package App\Models
 * @version January 27, 2025, 5:35 pm UTC
 *
 * @property \App\Models\Categoria $categoriaid
 * @property integer $CategoriaID
 * @property string $Marca
 * @property string $Caracteristicas
 * @property string $Modelo
 * @property number $Precio
 */
class Equipos extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;


    public $table = 'equipos';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'ID';

    public $fillable = [
        'CategoriaID',
        'Marca',
        'Caracteristicas',
        'Modelo',
        'Precio'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'ID' => 'integer',
        'CategoriaID' => 'integer',
        'Marca' => 'string',
        'Caracteristicas' => 'string',
        'Modelo' => 'string',
        'Precio' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'CategoriaID' => 'required|integer',
        'Marca' => 'required|string|max:150',
        'Caracteristicas' => 'required|string|max:255',
        'Modelo' => 'required|string|max:100',
        'Precio' => 'required|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function categorias()
    {
        return $this->belongsTo(\App\Models\Categorias::class, 'CategoriaID');
    }
}
