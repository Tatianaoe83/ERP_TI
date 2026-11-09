<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMovimientoPaso extends Model
{
    protected $table = 'solicitud_movimiento_pasos';

    protected $dates = ['decided_at'];

    public $fillable = [
        'SolicitudMovimientoID',
        'step_order',
        'stage',
        'stage_label',
        'approver_empleado_id',
        'status',
        'decided_at',
        'decided_by_empleado_id',
        'comment',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudMovimiento::class, 'SolicitudMovimientoID', 'SolicitudMovimientoID');
    }

    public function approver()
    {
        return $this->belongsTo(Empleados::class, 'approver_empleado_id', 'EmpleadoID');
    }

    public function decidedBy()
    {
        return $this->belongsTo(Empleados::class, 'decided_by_empleado_id', 'EmpleadoID');
    }

    public function token()
    {
        return $this->hasOne(SolicitudMovimientoToken::class, 'paso_id', 'id')->latest('id');
    }
}
