<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Departamentos
 * @package App\Models
 * @version January 24, 2025, 5:38 pm UTC
 *
 * @property string $NombreDepartamento
 * @property integer $GerenciaID
 */
class Departamentos extends Model
{
    use HasFactory, SoftDeletes;


    public $table = 'departamentos';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'DepartamentoID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreDepartamento',
        'GerenciaID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'DepartamentoID' => 'integer',
        'NombreDepartamento' => 'string',
        'GerenciaID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreDepartamento' => 'nullable|string|max:50',
        'GerenciaID' => 'nullable|integer'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/

    public function Gerencia()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaID');
    }
}
