<?php

namespace App\Models;

use Eloquent as Model;


/**
 * Class SolicitudPasos
 * @package App\Models
 * @version January 18, 2026, 1:46 am CST
 *
 * @property \App\Models\Empleado $approverEmpleado
 * @property \App\Models\Empleado $decidedByEmpleado
 * @property \App\Models\Solicitude $solicitud
 * @property integer $solicitud_id
 * @property boolean $step_order
 * @property string $stage
 * @property integer $approver_empleado_id
 * @property string $status
 * @property string|\Carbon\Carbon $decided_at
 * @property integer $decided_by_empleado_id
 * @property string $comment
 */
class SolicitudPasos extends Model
{

    public $table = 'solicitud_approval_steps';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'solicitud_id',
        'step_order',
        'stage',
        'approver_empleado_id',
        'status',
        'decided_at',
        'decided_by_empleado_id',
        'comment'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'solicitud_id' => 'integer',
        'step_order' => 'integer',
        'stage' => 'string',
        'approver_empleado_id' => 'integer',
        'status' => 'string',
        'decided_at' => 'datetime',
        'decided_by_empleado_id' => 'integer',
        'comment' => 'string'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function approverEmpleado()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'approver_empleado_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function decidedByEmpleado()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'decided_by_empleado_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function solicitud()
    {
        return $this->belongsTo(\App\Models\Solicitud::class, 'solicitud_id');
    }

    public function publicToken()
    {
        return $this->hasOne(SolicitudTokens::class, 'approval_step_id', 'id');
    }
}
