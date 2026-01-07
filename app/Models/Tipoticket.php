<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


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
class Tipoticket extends Model
{
    use SoftDeletes, HasFactory;


    public $table = 'tipotickets';
    
    protected $primaryKey = 'TipoID';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'NombreTipo',
        'SubtipoID',
        'TiempoEstimadoMinutos'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'TipoID' => 'integer',
        'NombreTipo' => 'string',
        'SubtipoID' => 'integer',
        'TiempoEstimadoMinutos' => 'integer'
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
        return $this->belongsTo(Subtipos::class, 'SubtipoID', 'SubtipoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function empleados()
    {
        return $this->belongsToMany(\App\Models\Empleado::class, 'tickets');
    }
}
