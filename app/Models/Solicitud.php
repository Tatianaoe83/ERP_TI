<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Solicitud
 * @package App\Models
 * @version August 15, 2025, 5:06 pm UTC
 *
 * @property \App\Models\Obra $obraid
 * @property \App\Models\Empleado $empleadoid
 * @property \App\Models\Proyecto $proyectoid
 * @property \App\Models\Gerencium $gerenciaid
 * @property \App\Models\Puesto $puestoid
 * @property string|\Carbon\Carbon $FechaSolicitud
 * @property string $Supervisor
 * @property string $Motivo
 * @property string $DescripcionMotivo
 * @property string $Requerimentos
 * @property number $Presupuesto
 * @property string $Estatus
 * @property integer $ObraID
 * @property integer $GerenciaID
 * @property integer $PuestoID
 * @property integer $EmpleadoID
 * @property integer $ProyectoID
 */
class Solicitud extends Model
{
    use SoftDeletes;


    public $table = 'solicitudes';
    protected $primaryKey = 'SolicitudID';


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'Motivo',
        'DescripcionMotivo',
        'Requerimientos',
        'Presupuesto',
        'Estatus',
        'ObraID',
        'GerenciaID',
        'PuestoID',
        'EmpleadoID',
        'Proyecto',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'SolicitudID' => 'integer',
        'Motivo' => 'string',
        'DescripcionMotivo' => 'string',
        'Requerimientos' => 'string',
        'Presupuesto' => 'float',
        'Estatus' => 'string',
        'ObraID' => 'integer',
        'GerenciaID' => 'integer',
        'PuestoID' => 'integer',
        'EmpleadoID' => 'integer',
        'Proyecto' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'Motivo' => 'nullable|string',
        'DescripcionMotivo' => 'required|string',
        'Requerimientos' => 'required|string',
        'Presupuesto' => 'nullable|numeric',
        'Estatus' => 'required|string',
        'ObraID' => 'required|integer',
        'GerenciaID' => 'required|integer',
        'PuestoID' => 'required|integer',
        'EmpleadoID' => 'required|integer',
        'Proyecto' => 'required|string',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function obraid()
    {
        return $this->belongsTo(\App\Models\Obras::class, 'ObraID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function empleadoid()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'EmpleadoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function gerenciaid()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function puestoid()
    {
        return $this->belongsTo(\App\Models\Puestos::class, 'PuestoID');
    }

    /**
     * Relación con cotizaciones
     */
    public function cotizaciones()
    {
        return $this->hasMany(\App\Models\Cotizacion::class, 'SolicitudID', 'SolicitudID');
    }
}
