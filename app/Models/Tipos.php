<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Tipos
 * @package App\Models
 * @version August 15, 2025, 5:52 pm UTC
 *
 * @property \App\Models\Subtipo $subtipoid
 * @property \Illuminate\Database\Eloquent\Collection $empleados
 * @property string $NombreTipo
 * @property integer $SubtipoID
 */
class Tipos extends Model
{
    use SoftDeletes;


    public $table = 'tipotickets';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'NombreTipo',
        'SubtipoID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'TipoID' => 'integer',
        'NombreTipo' => 'string',
        'SubtipoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreTipo' => 'required|string|max:200',
        'SubtipoID' => 'nullable|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function subtipoid()
    {
        return $this->belongsTo(\App\Models\Subtipo::class, 'SubtipoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function empleados()
    {
        return $this->belongsToMany(\App\Models\Empleado::class, 'tickets');
    }
}
